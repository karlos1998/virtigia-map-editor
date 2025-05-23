<script setup lang="ts">
import {DialogNodeOptionRule} from "@/types/DialogNodeOptionRule";
import {DropdownListType} from "@/Resources/DropdownList.type";
import {usePage} from "@inertiajs/vue3";
import {computed, onMounted, ref} from "vue";
import {BaseItemResource} from "@/Resources/BaseItem.resource";
import axios from "axios";
import {route} from "ziggy-js";
import {MultiSelectFilterEvent} from "primevue";
import {DialogNodeRulesResource} from "@/Resources/DialogNodeRules.resource";
import {debounce} from "@/debounce";

const rules = defineModel<DialogNodeRulesResource>('rules', {
    required: true,
    get(value) {
        return Array.isArray(value) ? {} : value;
    },
    default: () => ({})
});


type RuleDropdownOption = DropdownListType<DialogNodeOptionRule, {canBeUsed: boolean}>;

const staticAvailableRules = usePage<{
    availableRules: RuleDropdownOption,
}>().props.availableRules;

const availableRules = computed(() => staticAvailableRules.filter(rule => !rules.value[rule.value]))

const itemsDropdown = ref<BaseItemResource[]>([]);

const searchItems = debounce(async (query: string, ids: number[]) => {
    const { data } = await axios.get<BaseItemResource[]>(route('base-items.search', { query, ids }));
    itemsDropdown.value = data;
}, 500);

const itemsSearchChanged = ({ value }: MultiSelectFilterEvent) => {
    if(rules.value[DialogNodeOptionRule.items]) {
        searchItems(value, rules.value[DialogNodeOptionRule.items].value as number[]);
    }
};

const newRule = ref<DialogNodeOptionRule>();

const submitNewRule = () => {
    if(!newRule.value) return;

    let value: (number|number[]) = 0;

    if(newRule.value == DialogNodeOptionRule.items) {
        value = [];
    }

    const rulesTmp = rules.value;

    rulesTmp[newRule.value] = {
        value,
        consume: false,
    }

    rules.value = rulesTmp;

    console.log('after submit new rule', rules.value)
}

const canBeUsedOptions = [
    {value: true, label: "Zużyj"},
    {value: false, label: "Nie ingeruj"},
]

onMounted(() => {

    if(rules.value[DialogNodeOptionRule.items]) {
        searchItems('', rules.value[DialogNodeOptionRule.items].value as number[])
    }
})


</script>

<template>
    <InputGroup v-for="(_, name) in rules">

        <Button icon="pi pi-times" severity="danger" aria-label="Cancel"  @click="delete rules[name]" />

        <InputGroupAddon style="min-width: 220px;">
            {{staticAvailableRules.find(rule => rule.value == name)?.label}}
        </InputGroupAddon>

        <Select
            v-if="staticAvailableRules.find(rule => rule.value == name)?.canBeUsed && rules[name]"
            v-model="rules[name].consume"
            optionLabel="label"
            option-value="value"
            class="w-full md:w-80"
            :options="canBeUsedOptions"
        />

        <InputNumber
            v-if="rules[name] && typeof rules[name].value == 'number' && (name == DialogNodeOptionRule.gold || name == DialogNodeOptionRule.level)"
            v-model="rules[name].value"
            :max="2000000000"
            :min="0"
        />

        <InputNumber
            v-if="rules[name] && typeof rules[name].value == 'number' && (name == DialogNodeOptionRule.percentageChance)"
            v-model="rules[name].value"
            showButtons
            buttonLayout="horizontal"
            :step="5"
            :max="100"
            :min="0"
            suffix=" %"
        />

        <InputGroupAddon v-if="name == DialogNodeOptionRule.brotherhood">
            <b>Wymaga bycia członkiem</b>
        </InputGroupAddon>

        <MultiSelect
            v-if="rules[name] && name == DialogNodeOptionRule.items"
            v-model="rules[name].value"
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
        <Select v-model="newRule" :options="availableRules" optionLabel="label" option-value="value" placeholder="Wybierz dodatkową regułe" class="w-full md:w-56" />
        <Button severity="info" label="Dodaj regułę" @click="submitNewRule" />
    </InputGroup>
</template>
