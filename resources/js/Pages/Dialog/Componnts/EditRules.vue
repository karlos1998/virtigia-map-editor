<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue"
import { usePage } from "@inertiajs/vue3"
import { debounce } from "@/debounce"
import { DialogNodeOptionRule } from "@/types/DialogNodeOptionRule"
import { DropdownListType } from "@/Resources/DropdownList.type"
import { BaseItemResource } from "@/Resources/BaseItem.resource"
import { DialogNodeRulesResource } from "@/Resources/DialogNodeRules.resource"
import { route } from "ziggy-js"
import axios from "axios"
import { MultiSelectFilterEvent } from "primevue"
import { QuestResource, QuestStepResource } from "@/Resources/Quest.resource"
import TreeSelectAdapter from "@/Pages/Dialog/Componnts/TreeSelectAdapter.vue";
import { useQuestStepSelection } from "@/Pages/Dialog/Composables/useQuestStepSelection";

const rules = defineModel<DialogNodeRulesResource>("rules", {
    required: true,
    get(value) {
        return Array.isArray(value) ? {} : value
    },
    default: () => ({})
})

type RuleDropdownOption = DropdownListType<DialogNodeOptionRule, { canBeUsed: boolean }>

const staticAvailableRules = usePage<{
    availableRules: RuleDropdownOption
}>().props.availableRules

const availableRules = computed(() =>
    staticAvailableRules.filter(rule => !rules.value[rule.value])
)

const itemsDropdown = ref<BaseItemResource[]>([])

// Use the quest step selection composable
const { questNodes, loading, loadQuests, loadQuestStepById, onQuestNodeExpand } = useQuestStepSelection()

const searchItems = debounce(async (query: string, ids: number[]) => {
    const { data } = await axios.get<BaseItemResource[]>(
        route("base-items.search", { query, ids })
    )
    itemsDropdown.value = data
}, 500)

const itemsSearchChanged = ({ value }: MultiSelectFilterEvent) => {
    if (rules.value[DialogNodeOptionRule.items]) {
        searchItems(value, rules.value[DialogNodeOptionRule.items].value as number[])
    }
}

const newRule = ref<DialogNodeOptionRule>()

const submitNewRule = () => {

    if (!newRule.value) return

    let value: number | number[] | string | string[] = 0
    if (newRule.value === DialogNodeOptionRule.items) {
        value = []
    } else if (
        newRule.value === DialogNodeOptionRule.questStep ||
        newRule.value === DialogNodeOptionRule.questBeforeStep ||
        newRule.value === DialogNodeOptionRule.questAfterStep
    ) {
        value = []
    }

    const data = {
        value,
        consume: false,
        value2: null
    }

    if(Object.values(rules.value).length == 0) {
        //todo - ten if na pierwszy rzut oka jest bez sensu i starczyloby to co w else, ale czesto jak byl pusty obiekt i to na pewno {} a nie [] to i tak nie dodawlao elementu w else...
        rules.value = {
            [newRule.value]: data
        }
    } else {
        rules.value[newRule.value] = data
    }

    newRule.value = undefined
}

const canBeUsedOptions = [
    { value: true, label: "Zużyj" },
    { value: false, label: "Nie ingeruj" }
]

// Modal ilości przedmiotów
const showItemsAmountModal = ref(false)
const itemAmounts = ref<number[]>([])

const openItemsAmountModal = () => {
    const rule = rules.value[DialogNodeOptionRule.items]
    if (!rule || !Array.isArray(rule.value)) return

    itemAmounts.value = rule.value.map((_, i) => rule.value2?.[i] ?? 1)
    showItemsAmountModal.value = true
}

const saveItemAmounts = () => {
    if (rules.value[DialogNodeOptionRule.items]) {
        rules.value[DialogNodeOptionRule.items].value2 = [...itemAmounts.value]
    }
    showItemsAmountModal.value = false
}

watch(
    () => rules.value[DialogNodeOptionRule.items]?.value,
    (value: number[] | undefined) => {
        if (value) {
            searchItems("", value)
        }
    },
    { immediate: true }
)

onMounted(() => {
    console.log('on mounted')
    if (rules.value[DialogNodeOptionRule.items]) {
        searchItems("", rules.value[DialogNodeOptionRule.items].value as number[])
    }

    // Load quests for the TreeSelect
    loadQuests()

    // Check if quest steps are already selected and load their details
    if (rules.value[DialogNodeOptionRule.questStep]) {
        const value = rules.value[DialogNodeOptionRule.questStep].value

        // Handle both string (legacy) and array (new) formats
        if (typeof value === 'string' && value.startsWith('s-')) {
            // Legacy format: single string
            const stepId = parseInt(value.substring(2))
            if (!isNaN(stepId)) {
                loadQuestStepById(stepId)
            }
        } else if (Array.isArray(value)) {
            // New format: array of strings
            value.forEach(stepValue => {
                if (typeof stepValue === 'string' && stepValue.startsWith('s-')) {
                    const stepId = parseInt(stepValue.substring(2))
                    if (!isNaN(stepId)) {
                        loadQuestStepById(stepId)
                    }
                }
            })
        }
    }
})


watch(
    () => rules.value[DialogNodeOptionRule.items]?.value,
    (current: number[] | undefined, previous: number[] | undefined) => {
        if (!Array.isArray(current)) return

        console.log('watch current', current)

        const rule = rules.value[DialogNodeOptionRule.items]
        if (!rule) return

        const oldIds = Array.isArray(previous) ? previous : []
        const oldValue2 = Array.isArray(rule.value2) ? rule.value2 : []

        const newValue2: number[] = []

        current.forEach((id, idx) => {
            const prevIndex = oldIds.indexOf(id)
            newValue2[idx] = prevIndex !== -1 ? oldValue2[prevIndex] : 1
        })

        console.log('newValue2', newValue2)

        rule.value2 = newValue2
    },
    { deep: true }
)

</script>

<template>
    <InputGroup v-for="(_, name) in rules" :key="name">
        <Button icon="pi pi-times" severity="danger" @click="delete rules[name]" />

        <InputGroupAddon style="min-width: 220px">
            {{ staticAvailableRules.find(rule => rule.value === name)?.label }}
        </InputGroupAddon>

        <Select
            v-if="staticAvailableRules.find(rule => rule.value === name)?.canBeUsed && rules[name]"
            v-model="rules[name].consume"
            optionLabel="label"
            optionValue="value"
            class="w-full md:w-80"
            :options="canBeUsedOptions"
        />

        <InputNumber
            v-if="rules[name] && typeof rules[name].value === 'number' && (name === DialogNodeOptionRule.gold || name === DialogNodeOptionRule.level || name === DialogNodeOptionRule.dragonTears)"
            v-model="rules[name].value"
            :max="2000000000"
            :min="0"
        />

        <InputNumber
            v-if="rules[name] && typeof rules[name].value === 'number' && name === DialogNodeOptionRule.percentageChance"
            v-model="rules[name].value"
            showButtons
            buttonLayout="horizontal"
            :step="5"
            :max="100"
            :min="0"
            suffix="%"
        />

        <InputGroupAddon v-if="name === DialogNodeOptionRule.brotherhood">
            <b>Wymaga bycia członkiem</b>
        </InputGroupAddon>

        <MultiSelect
            v-if="rules[name] && name === DialogNodeOptionRule.items"
            v-model="rules[name].value"
            variant="filled"
            :optionLabel="(item: BaseItemResource) => `[${item.id}] ${item.name} (${item.in_use ? 'W użyciu' : 'Nieużywany'})`"
            optionValue="id"
            filter
            placeholder="Szukaj przedmiotów"
            :maxSelectedLabels="3"
            class="w-full md:w-80"
            @filter="itemsSearchChanged"
            :options="itemsDropdown"
        />

        <Button
            v-if="rules[name] && name === DialogNodeOptionRule.items"
            label="Ustaw ilości"
            icon="pi pi-pencil"
            severity="secondary"
            @click="openItemsAmountModal"
        />

        <TreeSelectAdapter
            v-if="rules[name] && (name === DialogNodeOptionRule.questStep || name === DialogNodeOptionRule.questBeforeStep || name === DialogNodeOptionRule.questAfterStep)"
            v-model="rules[name].value"
            :loading="loading"
            :options="questNodes"
            :onNodeExpand="onQuestNodeExpand"
            :returnList="true"
        />

    </InputGroup>

    <InputGroup>
        <Select
            v-model="newRule"
            :options="availableRules"
            optionLabel="label"
            optionValue="value"
            placeholder="Wybierz dodatkową regułę"
            class="w-full md:w-56"
        />
        <Button severity="info" label="Dodaj regułę" @click="submitNewRule" />
    </InputGroup>

    <Dialog
        v-model:visible="showItemsAmountModal"
        modal
        header="Ilości przedmiotów"
        :style="{ width: '36rem', maxWidth: '95vw' }"
    >
        <div
            v-if="Array.isArray(rules[DialogNodeOptionRule.items]?.value)"
            class="space-y-4 max-h-[60vh] overflow-y-auto"
        >
            <div
                v-for="(itemId, idx) in rules[DialogNodeOptionRule.items]?.value"
                :key="itemId"
                class="border border-gray-200 rounded-xl p-4 shadow-sm"
            >
                <div class="text-sm text-gray-500 mb-1 font-medium">
                    [#{{ itemId }}]
                </div>
                <div class="text-base font-semibold mb-3">
                    {{ itemsDropdown.find(i => i.id === itemId)?.name ?? 'Nieznany' }}
                </div>
                <InputNumber
                    v-model="itemAmounts[idx]"
                    :min="1"
                    :max="1000"
                    showButtons
                    buttonLayout="horizontal"
                    suffix=" szt."
                    class="w-full"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Anuluj" severity="secondary" @click="showItemsAmountModal = false" />
                <Button label="Zapisz" icon="pi pi-check" severity="primary" @click="saveItemAmounts" />
            </div>
        </template>
    </Dialog>




</template>
