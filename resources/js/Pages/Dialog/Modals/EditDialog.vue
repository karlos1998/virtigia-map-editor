<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {computed, reactive, Ref, ref, watch} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {route} from "ziggy-js";
import axios from "axios";
import {MultiSelectFilterEvent, useToast} from "primevue";
import {usePage} from "@inertiajs/vue3";
import {DropdownListType} from "@/Resources/DropdownList.type";
import {DialogNodeAdditionalAction} from "@/types/DialogNodeAdditionalAction";
import {BaseItemResource} from "@/Resources/BaseItem.resource";
import {DialogNodeAdditionalActionsResource} from "@/Resources/DialogNodeAdditionalActions.resource";
import {debounce} from "@/debounce";
import TreeSelectAdapter from "../Componnts/TreeSelectAdapter.vue";
import { useQuestStepSelection } from "../Composables/useQuestStepSelection";

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
    if(!dialogRef) return;

    form.content = dialogRef.value.data?.content ?? '';
    form.additional_actions = !Array.isArray(dialogRef.value.data?.additional_actions) ? dialogRef.value.data?.additional_actions ?? {} : {};

    if(form.additional_actions[DialogNodeAdditionalAction.addItems]) {
        searchItems('', form.additional_actions[DialogNodeAdditionalAction.addItems].value as number[])
    }

    // Load quests for the TreeSelect
    loadQuests();

    // Check if a quest step is already selected and load its details
    if (form.additional_actions[DialogNodeAdditionalAction.setQuestStep]) {
        const questStepValue = form.additional_actions[DialogNodeAdditionalAction.setQuestStep].value;
        let stepId: number | null = null;

        if (typeof questStepValue === 'string' && questStepValue.startsWith('s-')) {
            // Extract step ID from the value (format: "s-{id}")
            stepId = parseInt(questStepValue.substring(2));
        } else if (typeof questStepValue === 'number') {
            // If the value is already a number, use it directly
            stepId = questStepValue;
            // Convert the numeric ID to the 's-{id}' format for the TreeSelectAdapter
            form.additional_actions[DialogNodeAdditionalAction.setQuestStep].value = `s-${stepId}`;
        }

        if (stepId !== null && !isNaN(stepId)) {
            loadQuestStepById(stepId);
        }
    }
})

const toast = useToast();

// Use the quest step selection composable
const { questNodes, loading, loadQuests, loadQuestStepById, onQuestNodeExpand } = useQuestStepSelection();

const processing = ref(false);

const save = () => {
    if(!dialogRef) return;

    // Validate setQuestStep action data
    if (form.additional_actions[DialogNodeAdditionalAction.setQuestStep]) {
        const questStepValue = form.additional_actions[DialogNodeAdditionalAction.setQuestStep].value;
        if (!questStepValue) {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybierz krok questa', life: 3000 });
            return;
        }

        // Make sure only quest steps (s-X) are selected, not quests (q-X)
        if (typeof questStepValue === 'string' && questStepValue.startsWith('q-')) {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybierz krok questa, nie cały quest', life: 3000 });
            return;
        }
    }

    // Create a deep copy of the form data
    const formData = JSON.parse(JSON.stringify(form));

    // Transform setQuestStep value from 's-1' to just the ID number
    if (formData.additional_actions[DialogNodeAdditionalAction.setQuestStep]) {
        const questStepValue = formData.additional_actions[DialogNodeAdditionalAction.setQuestStep].value;
        if (typeof questStepValue === 'string' && questStepValue.startsWith('s-')) {
            // Extract the ID from the string (e.g., 's-1' -> 1)
            const stepId = parseInt(questStepValue.substring(2));
            if (!isNaN(stepId)) {
                formData.additional_actions[DialogNodeAdditionalAction.setQuestStep].value = stepId;
            }
        }
    }

    processing.value = true;
    axios.patch(route('dialogs.nodes.update', {
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data.node_id,
    }), formData)
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
    dialogNodeAdditionalActionsList: DropdownListType<DialogNodeAdditionalAction>
}>().props.dialogNodeAdditionalActionsList

const availableDialogNodeAdditionalActionsList = computed( () => dialogNodeAdditionalActionsList.filter(action => !form.additional_actions[action.value]))

const newAdditionalAction = ref<DialogNodeAdditionalAction>();

const addAdditionalAction = () => {
    if(!newAdditionalAction.value) return;

    if(form.additional_actions[newAdditionalAction.value]) return;

    let value: number|number[]|string = 1;

    if(newAdditionalAction.value == DialogNodeAdditionalAction.addItems) {
        value = [];
    } else if(newAdditionalAction.value == DialogNodeAdditionalAction.addGold) {
        value = 1;
    } else if(newAdditionalAction.value == DialogNodeAdditionalAction.setQuestStep) {
        value = "";
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
    if(form.additional_actions[DialogNodeAdditionalAction.addItems]) {
        searchItems(value, form.additional_actions[DialogNodeAdditionalAction.addItems].value as number[]);
    }
};

</script>

<template>
    <div class="flex flex-col gap-2">
        <Textarea v-model="form.content" rows="5" cols="50" />

        <InputGroup v-for="(_, name) in form.additional_actions">

            <Button icon="pi pi-times" severity="danger" aria-label="Cancel"  @click="delete form.additional_actions[name]" />

            <InputGroupAddon style="min-width: 220px;">
                {{dialogNodeAdditionalActionsList.find(action => action.value == name)?.label}}
            </InputGroupAddon>

            <InputNumber
                v-if="form.additional_actions[name] && typeof form.additional_actions[name].value == 'number' && (name == DialogNodeAdditionalAction.addGold)"
                v-model="form.additional_actions[name].value"
                :max="2000000000"
                :min="0"
            />

            <MultiSelect
                v-if="form.additional_actions[name] && name == DialogNodeAdditionalAction.addItems"
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

            <!-- TreeSelectAdapter for setQuestStep action -->
            <TreeSelectAdapter
                v-if="form.additional_actions[name] && name == DialogNodeAdditionalAction.setQuestStep"
                v-model="form.additional_actions[name].value"
                :loading="loading"
                :options="questNodes"
                :onNodeExpand="onQuestNodeExpand"
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
