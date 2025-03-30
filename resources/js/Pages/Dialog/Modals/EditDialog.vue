<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {computed, reactive, Ref, ref} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {route} from "ziggy-js";
import axios from "axios";
import {MultiSelectFilterEvent, useToast} from "primevue";
import {usePage} from "@inertiajs/vue3";
import {DropdownListType} from "../../../Resources/DropdownList.type";
import {DialogNodeAdditionalAction} from "../../../types/DialogNodeAdditionalAction";
import {BaseItemResource} from "../../../Resources/BaseItem.resource";
import {DialogNodeAdditionalActionsResource} from "../../../Resources/DialogNodeAdditionalActions.resource";
import {debounce} from "../../../debounce";
import {DialogNodeOptionRule} from "../../../types/DialogNodeOptionRule";

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        label: string,
        content: string,
        additional_actions: DialogNodeAdditionalActionsResource
    }
}> | null>('dialogRef');

const form = reactive<{
    content: '',
    additional_actions: DialogNodeAdditionalActionsResource,
}>({
    content: '',
    additional_actions: {},
})

onMounted(() => {
    form.content = dialogRef.value.data?.content ?? '';
    form.additional_actions = dialogRef.value.data?.additional_actions ?? {};

    if(form.additional_actions[DialogNodeAdditionalAction.addItems]) {
        searchItems('', form.additional_actions[DialogNodeAdditionalAction.addItems].value as number[])
    }
})

const toast = useToast();

const processing = ref(false);

const save = () => {
    processing.value = true;
    axios.patch(route('dialogs.nodes.update', {
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data.node_id,
    }), form)
        .then(() => {
            toast.add({ severity: 'success', summary: 'Operacja powiodła się', detail: 'Pomyślnie zmieniono treść dialogu npc', life: 6000 });
            dialogRef.value.close({
                content: form.content,
            });
        })
        .catch(({response}) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
        })
        .finally(() => {
            processing.value = false;
        })
}

const dialogNodeAdditionalActionsList = usePage<{
    dialogNodeAdditionalActionsList: DropdownListType
}>().props.dialogNodeAdditionalActionsList

const availableDialogNodeAdditionalActionsList = computed( () => dialogNodeAdditionalActionsList.filter(action => !form.additional_actions[action.value]))

const newAdditionalAction = ref<DialogNodeAdditionalAction>();

const addAdditionalAction = () => {
    if(!newAdditionalAction.value) return;

    if(form.additional_actions[newAdditionalAction.value]) return;

    let value;

    if(newAdditionalAction.value == DialogNodeAdditionalAction.addItems) {
        value = [];
    } else if(newAdditionalAction.value == DialogNodeAdditionalAction.addGold) {
        value = 1;
    }

    form.additional_actions[newAdditionalAction.value] = {
        value,
    }

    newAdditionalAction.value = undefined;
}



/// powtarzajacy sie kod

const itemsDropdown = ref<BaseItemResource[]>([]);

const searchItems = debounce(async (query: string, ids: number[]) => {
    const { data } = await axios.get<BaseItemResource[]>(route('base-items.search', { query, ids }));
    itemsDropdown.value = data;
}, 500);

const itemsSearchChanged = ({ value }: MultiSelectFilterEvent) => {
    searchItems(value, form.additional_actions[DialogNodeAdditionalAction.addItems].value as number[]);
};

</script>

<template>
    <div class="flex flex-col gap-2">
        <Textarea v-model="form.content" rows="5" cols="50" />

        {{ form.additional_actions }}

        <InputGroup v-for="(value, name) in form.additional_actions">

            <Button icon="pi pi-times" severity="danger" aria-label="Cancel"  @click="delete form.additional_actions[name]" />

            <InputGroupAddon style="min-width: 220px;">
                {{dialogNodeAdditionalActionsList.find(action => action.value == name).label}}
            </InputGroupAddon>

            <InputNumber
                v-if="typeof form.additional_actions[name].value == 'number' && (name == DialogNodeAdditionalAction.addGold)"
                v-model="form.additional_actions[name].value"
                :max="2000000000"
                :min="0"
            />

            <MultiSelect
                v-if="name == DialogNodeAdditionalAction.addItems"
                v-model="form.additional_actions[name].value"
                variant="filled"
                :optionLabel="(item: BaseItemResource) => `[${item.id}] ${item.name} (${item.in_use ? 'W użyciu' : 'Nieużywany'})`"
                option-value="id"
                filter
                placeholder="Szukaj przedmiotów"
                :maxSelectedLabels="3"
                class="w-full md:w-80"
                @filter="itemsSearchChanged"
                :options="itemsDropdown"
            />

        </InputGroup>

        <InputGroup>
            <Select
                class="flex-auto"
                name="category"
                v-model="newAdditionalAction"
                :options="availableDialogNodeAdditionalActionsList"
                option-value="value"
                option-label="label"
            />
            <Button
                severity="info"
                @click="addAdditionalAction"
            >
                Dodaj akcję
            </Button>
        </InputGroup>

        <Button :loading="processing" fluid @click="save">Zapisz</Button>
    </div>
</template>

<style scoped>

</style>
