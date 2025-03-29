<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {computed, Ref, ref} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {useForm, usePage} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {MultiSelectFilterEvent, useToast} from "primevue";
import axios from "axios";
import {DialogOptionResource} from "../../../Resources/DialogOption.resource";
import {DropdownListType} from "../../../Resources/DropdownList.type";
import {DialogNodeOptionRule} from "../../../types/DialogNodeOptionRule";
import {BaseItemResource} from "../../../Resources/BaseItem.resource";

const dialogNodeOptionAdditionalActionsList = ref(usePage<{dialogNodeOptionAdditionalActionsList: DropdownListType}>().props.dialogNodeOptionAdditionalActionsList)
const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        option: DialogOptionResource,
        parent: string,
        dialog_id: number
    }
}> | null>('dialogRef');

const form = useForm<{
    label: string
    additional_action: any// todo
    rules: Record<DialogNodeOptionRule, { value: number| number[]; consume: boolean }>
}>({
    label: '',
    additional_action: null,
    rules: {} as Record<DialogNodeOptionRule, { value: number| number[]; consume: boolean }>,
})

const toast = useToast();

onMounted(() => {
    form.label = dialogRef.value.data?.option?.label ?? '';
    form.additional_action = dialogRef.value.data?.option?.additional_action ?? '';
    form.rules = dialogRef.value.data?.option?.rules ?? [];

    // const d = {};
    // for(const rule in form.rules) {
    //     d[rule] = rule;
    // }
    // selectedRules.value = d;
    // selectedRules.value = Object.keys(form.rules);

    searchItems('', form.rules[DialogNodeOptionRule.items].value as number[])
})

const processing = ref(false);

const save = () => {
    //axios zeby form inertia nie przeladowywal strony

    processing.value = true;

    const data = form.data();

    // data.rules = Object.values(selectedRules.value)
    //     .filter(key => form.rules[key])
    //     .reduce((acc, key) => {
    //         acc[key] = form.rules[key];
    //         return acc;
    //     }, {});

    axios.patch<DialogOptionResource>(route('dialogs.nodes.options.update', {
        dialogNodeOption: dialogRef.value.data?.option?.id,
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data?.option?.node_id,
    }), data)
        .then(({data}) => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Opcja dialogowa została edytowana', life: 3000 });
            dialogRef.value.close({
                dialogOption: data,
            });
        })
        .catch(() => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił problem podczas edycji opcji dialogowej', life: 3000 });
        })
        .finally(() => {
            processing.value = false;
        })
}

const remove = () => {

    processing.value = true;

    axios.delete(route('dialogs.nodes.options.destroy', {
        dialogNodeOption: dialogRef.value.data?.option?.id,
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data?.option?.node_id,
    }))
        .then(() => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Opcja dialogowa została usunięta', life: 3000 });
            dialogRef.value.close({
                remove: true,
            });
        })
        .catch(({response}) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
        })
        .finally(() => {
            processing.value = false;
        })

}

type RuleDropdownOption = DropdownListType<DialogNodeOptionRule, {canBeUsed: boolean}>;

const staticAvailableRules = usePage<{
    availableRules: RuleDropdownOption,
}>().props.availableRules;

const availableRules = computed(() => staticAvailableRules.filter(rule => !form.rules[rule.value]))
// const availableRules = computed(() => staticAvailableRules)

// const selectedRules = ref<DialogNodeOptionRule[]>([]);


//do helpera
const debounce = <T extends (...args: any[]) => void>(func: T, timeout: number = 300) => {
    let timer: ReturnType<typeof setTimeout> = 0;
    return (...args: Parameters<T>) => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            func(...args);
        }, timeout);
    };
};

const itemsDropdown = ref<BaseItemResource[]>([]);

const searchItems = debounce(async (query: string, ids: number[]) => {
    const { data } = await axios.get<BaseItemResource[]>(route('base-items.search', { query, ids }));
    itemsDropdown.value = data;
}, 500);

const itemsSearchChanged = ({ value }: MultiSelectFilterEvent) => {
    searchItems(value, form.rules[DialogNodeOptionRule.items].value as number[]);
};

const newRule = ref<DialogNodeOptionRule>();

const submitNewRule = () => {
    if(!newRule.value) return;

    let value: (number|number[]) = 0;

    if(newRule.value == DialogNodeOptionRule.items) {
        value = [];
    }

    form.rules[newRule.value] = {
        value,
        consume: false,
    }
}

const canBeUsedOptions = [
    {value: true, label: "Zużyj"},
    {value: false, label: "Nie ingeruj"},
]

</script>

<template>
    <div class="flex flex-col gap-2">

        <InputGroup>
            <Button icon="pi pi-times" severity="danger" aria-label="Cancel" @click="form.additional_action = null" />
            <Select v-model="form.additional_action" :options="dialogNodeOptionAdditionalActionsList" optionLabel="label" option-value="value" placeholder="Wybierz dodatkową akcje" class="w-full md:w-56" />
        </InputGroup>

        <Textarea v-model="form.label" rows="5" cols="50" maxlength="250" />

        <Message severity="error" size="small" variant="simple">{{ form.errors.label }}</Message>

        <div>
            <InputGroup v-for="(value, name) in form.rules">

                <Button icon="pi pi-times" severity="danger" aria-label="Cancel"  @click="delete form.rules[name]" />

                <InputGroupAddon style="min-width: 220px;">
                    {{staticAvailableRules.find(rule => rule.value == name).label}}
                </InputGroupAddon>

                <Select
                    v-if="staticAvailableRules.find(rule => rule.value == name).canBeUsed"
                    v-model="form.rules[name].consume"
                    optionLabel="label"
                    option-value="value"
                    class="w-full md:w-80"
                    :options="canBeUsedOptions"
                />

                <InputNumber
                    v-if="typeof form.rules[name].value == 'number' && (name == DialogNodeOptionRule.gold || name == DialogNodeOptionRule.level)"
                    v-model="form.rules[name].value"
                />

                <InputGroupAddon v-if="name == DialogNodeOptionRule.brotherhood">
                    <b>Wymaga bycia członkiem</b>
                </InputGroupAddon>

                <MultiSelect
                    v-if="name == DialogNodeOptionRule.items"
                    v-model="form.rules[name].value"
                    variant="filled"
                    optionLabel="name"
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
                <Select v-model="newRule" :options="availableRules" optionLabel="label" option-value="value" placeholder="Wybierz dodatkową regułe" class="w-full md:w-56" />
                <Button severity="info" label="Dodaj regułę" @click="submitNewRule" />
            </InputGroup>

        </div>

        <div class="flex gap-1">
            <Button  :loading="processing"  fluid @click="remove" severity="danger">Usuń</Button>
            <Button :loading="processing" fluid @click="save">Zapisz</Button>
        </div>
    </div>
</template>

<style scoped>

</style>
