<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue"
import { usePage } from "@inertiajs/vue3"
import { DialogNodeOptionRule } from "@/types/DialogNodeOptionRule"
import { DropdownListType } from "@/Resources/DropdownList.type"
import { BaseItemResource } from "@/Resources/BaseItem.resource"
import { DialogCounterResource } from "@/Resources/DialogCounter.resource"
import { DialogNodeRulesResource } from "@/Resources/DialogNodeRules.resource"
import { route } from "ziggy-js"
import axios from "axios"
import { useQuestStepSelection } from "@/Pages/Dialog/Composables/useQuestStepSelection";
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import BaseItemSearchSelect from "@/Components/BaseItemSearchSelect.vue";
import QuestRuleSelector from "@/Pages/Dialog/Componnts/QuestRuleSelector.vue";

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

const resolvedRuleItems = ref<BaseItemResource[]>([])

// Dialog counters
const dialogCounters = ref<DialogCounterResource[]>([])
const seasonalEvents = ref<Array<{ id: number; name: string; is_currently_active: boolean }>>([])

const weekdayOptions = [
    { value: 1, label: 'Poniedziałek' },
    { value: 2, label: 'Wtorek' },
    { value: 3, label: 'Środa' },
    { value: 4, label: 'Czwartek' },
    { value: 5, label: 'Piątek' },
    { value: 6, label: 'Sobota' },
    { value: 7, label: 'Niedziela' },
]

const plainNumberRules = new Set<string>([
    DialogNodeOptionRule.gold,
    DialogNodeOptionRule.level,
    DialogNodeOptionRule.levelBelow,
    DialogNodeOptionRule.dragonTears,
    DialogNodeOptionRule.activePlayersOnMap,
])

const questRuleDescriptions: Record<string, string> = {
    [DialogNodeOptionRule.questStep]: "Jeśli wybierzesz cały quest, gracz musi mieć ten quest rozpoczęty. Jeśli wybierzesz krok, aktualny krok gracza musi być dokładnie tym krokiem.",
    [DialogNodeOptionRule.questBeforeStep]: "Jeśli wybierzesz cały quest, gracz nie może mieć go rozpoczętego. Jeśli wybierzesz krok, gracz musi być w tym queście na wcześniejszym kroku.",
    [DialogNodeOptionRule.questAfterStep]: "Najbezpieczniej wybierz konkretny krok: reguła przejdzie, gdy gracz jest już na jednym z kolejnych kroków tego questa. Dla całego questa silnik traktuje ten warunek jak quest nierozpoczęty.",
}

const isPlainNumberRule = (rule: string | number): boolean => plainNumberRules.has(String(rule))

const getRuleLabel = (rule: string | number): string =>
    staticAvailableRules.find(availableRule => String(availableRule.value) === String(rule))?.label ?? String(rule)

const getQuestRuleDescription = (rule: string | number): string | null =>
    questRuleDescriptions[String(rule)] ?? null

const loadDialogCounters = async () => {
    const { data } = await axios.get<DialogCounterResource[]>(route("web-api.dialog-counters.index"))
    dialogCounters.value = data
}

const loadSeasonalEvents = async () => {
    const { data } = await axios.get<Array<{ id: number; name: string; is_currently_active: boolean }>>(route("web-api.seasonal-events.index"))
    seasonalEvents.value = data
}

// Use the quest step selection composable
const { questNodes, loading, loadQuests } = useQuestStepSelection()

const newRule = ref<DialogNodeOptionRule>()

const submitNewRule = () => {

    if (!newRule.value) return

    let value: number | number[] | string | string[] | boolean | null = 0
    let value2: any = null
    if (newRule.value === DialogNodeOptionRule.items) {
        value = []
    } else if (
        newRule.value === DialogNodeOptionRule.questStep ||
        newRule.value === DialogNodeOptionRule.questBeforeStep ||
        newRule.value === DialogNodeOptionRule.questAfterStep
    ) {
        value = []
    } else if (newRule.value === DialogNodeOptionRule.messageContent) {
        value = ''
    } else if (newRule.value === DialogNodeOptionRule.dialogCounter) {
        value = null
        value2 = ['=', 0]
    } else if (newRule.value === DialogNodeOptionRule.seasonalEvent) {
        value = null
    } else if (
        newRule.value === DialogNodeOptionRule.timeAfter ||
        newRule.value === DialogNodeOptionRule.timeBefore
    ) {
        value = null
    } else if (newRule.value === DialogNodeOptionRule.weekday) {
        value = []
    } else if (newRule.value === DialogNodeOptionRule.hasActiveBlessing) {
        value = true
    }

    const data = {
        value,
        consume: false,
        value2,
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
const handleResolvedRuleItems = (items: BaseItemResource[]): void => {
    resolvedRuleItems.value = items
}

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

onMounted(() => {
    loadQuests({ withSteps: true })

    // Load dialog counters
    loadDialogCounters()
    loadSeasonalEvents()
})


watch(
    () => rules.value[DialogNodeOptionRule.items]?.value,
    (current: Array<number | { id?: number }> | undefined, previous: Array<number | { id?: number }> | undefined) => {
        if (!Array.isArray(current)) return

        const rule = rules.value[DialogNodeOptionRule.items]
        if (!rule) return

        const toItemId = (item: number | { id?: number }): number | null => {
            if (typeof item === 'number') {
                return item
            }

            if (item && typeof item === 'object' && typeof item.id === 'number') {
                return item.id
            }

            return null
        }

        const oldIds = Array.isArray(previous) ? previous.map(toItemId).filter((id): id is number => id !== null) : []
        const oldValue2 = Array.isArray(rule.value2) ? rule.value2 : []

        const newValue2: number[] = []

        current.forEach((item, idx) => {
            const id = toItemId(item)
            if (id === null) {
                newValue2[idx] = 1
                return
            }

            const prevIndex = oldIds.indexOf(id)
            newValue2[idx] = prevIndex !== -1 ? oldValue2[prevIndex] : 1
        })

        rule.value2 = newValue2
    },
    { deep: true }
)

</script>

<template>
    <div v-for="(_, name) in rules" :key="name" class="dialog-editor-row">
        <Button
            icon="pi pi-times"
            severity="danger"
            aria-label="Usuń regułę"
            class="dialog-editor-remove"
            @click="delete rules[name]"
        />

        <div class="dialog-editor-label">
            {{ staticAvailableRules.find(rule => rule.value === name)?.label }}
        </div>

        <div class="dialog-editor-controls">

        <Select
            v-if="staticAvailableRules.find(rule => rule.value === name)?.canBeUsed && rules[name]"
            v-model="rules[name].consume"
            optionLabel="label"
            optionValue="value"
            class="dialog-editor-control dialog-editor-control--compact"
            :options="canBeUsedOptions"
        />

        <InputNumber
            v-if="rules[name] && typeof rules[name].value === 'number' && isPlainNumberRule(name)"
            v-model="rules[name].value"
            :max="2000000000"
            :min="0"
            class="dialog-editor-control dialog-editor-control--compact"
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
            class="dialog-editor-control dialog-editor-control--compact"
        />

        <div v-if="name === DialogNodeOptionRule.brotherhood" class="dialog-editor-hint">
            <b>Wymaga bycia członkiem</b>
        </div>

        <div v-if="name === DialogNodeOptionRule.hasActiveBlessing" class="dialog-editor-hint">
            <b>Wymaga aktywnego błogosławieństwa</b>
        </div>

        <InputText
            v-if="rules[name] && (name === DialogNodeOptionRule.timeAfter || name === DialogNodeOptionRule.timeBefore)"
            v-model="rules[name].value"
            type="time"
            step="60"
            class="dialog-editor-control dialog-editor-control--compact"
        />

        <MultiSelect
            v-if="rules[name] && name === DialogNodeOptionRule.weekday"
            v-model="rules[name].value"
            :options="weekdayOptions"
            optionLabel="label"
            optionValue="value"
            display="chip"
            placeholder="Wybierz dni tygodnia"
            class="dialog-editor-control dialog-editor-control--wide"
        />

        <BaseItemSearchSelect
            v-if="rules[name] && name === DialogNodeOptionRule.items"
            v-model="rules[name].value"
            value-mode="id"
            multiple
            placeholder="Szukaj przedmiotów (nazwa lub #id)"
            class="dialog-editor-control dialog-editor-control--full"
            @resolved-items="handleResolvedRuleItems"
        />

        <div v-if="rules[name] && name === DialogNodeOptionRule.items" class="dialog-editor-button-line">
            <Button
                label="Ustaw ilości"
                icon="pi pi-pencil"
                severity="secondary"
                class="dialog-editor-action-button"
                @click="openItemsAmountModal"
            />
        </div>

        <QuestRuleSelector
            v-if="rules[name] && (name === DialogNodeOptionRule.questStep || name === DialogNodeOptionRule.questBeforeStep || name === DialogNodeOptionRule.questAfterStep)"
            v-model="rules[name].value"
            :loading="loading"
            :quests="questNodes"
            :returnList="true"
            :context-label="getRuleLabel(name)"
            :context-description="getQuestRuleDescription(name)"
            class="dialog-editor-control dialog-editor-control--full"
        />

        <InputText
            v-if="rules[name] && name === DialogNodeOptionRule.messageContent"
            v-model="rules[name].value"
            :maxlength="100"
            placeholder="Podaj treść odpowiedzi (max 100 znaków)"
            class="dialog-editor-control dialog-editor-control--wide"
        />

        <!-- Dialog Counter UI -->
        <template v-if="rules[name] && name === DialogNodeOptionRule.dialogCounter">
            <Select
                v-model="rules[name].value"
                :options="dialogCounters"
                optionLabel="name"
                optionValue="id"
                placeholder="Wybierz licznik"
                class="dialog-editor-control dialog-editor-control--medium"
            />
            <Select
                v-if="rules[name].value2"
                v-model="rules[name].value2[0]"
                :options="[{value: '>', label: '>'}, {value: '=', label: '='}, {value: '<', label: '<'}]"
                optionLabel="label"
                optionValue="value"
                class="dialog-editor-control dialog-editor-control--operator"
            />
            <InputNumber
                v-if="rules[name].value2"
                v-model="rules[name].value2[1]"
                :min="0"
                :max="2000000000"
                placeholder="Wartość"
                class="dialog-editor-control dialog-editor-control--compact"
            />
        </template>

        <Select
            v-if="rules[name] && name === DialogNodeOptionRule.seasonalEvent"
            v-model="rules[name].value"
            :options="seasonalEvents"
            optionLabel="name"
            optionValue="id"
            class="dialog-editor-control dialog-editor-control--wide"
            placeholder="Wybierz wydarzenie"
        >
            <template #option="{ option }">
                <div class="flex items-center justify-between gap-3 w-full">
                    <span>{{ option.name }}</span>
                    <Tag :severity="option.is_currently_active ? 'success' : 'secondary'" :value="option.is_currently_active ? 'Aktywne' : 'Nieaktywne'" />
                </div>
            </template>
        </Select>

        </div>
    </div>

    <div class="dialog-editor-add-row">
        <Select
            v-model="newRule"
            :options="availableRules"
            optionLabel="label"
            optionValue="value"
            placeholder="Wybierz dodatkową regułę"
            class="min-w-0 flex-auto"
        />
        <Button severity="info" label="Dodaj regułę" @click="submitNewRule" />
    </div>

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
                    {{ resolvedRuleItems.find(i => i.id === itemId)?.name ?? 'Nieznany' }}
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

<style scoped>
.dialog-editor-row {
    align-items: start;
    display: grid;
    gap: 0.5rem;
    grid-template-columns: 3rem minmax(0, 1fr);
    margin-bottom: 0.75rem;
}

.dialog-editor-remove {
    border-radius: 8px;
    height: 3rem;
    min-width: 3rem;
    width: 3rem;
}

.dialog-editor-label {
    align-items: center;
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    color: var(--text-color-secondary);
    display: flex;
    justify-content: flex-start;
    min-height: 3rem;
    padding: 0.625rem 0.875rem;
    text-align: left;
}

.dialog-editor-controls {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    grid-column: 2 / -1;
    min-width: 0;
}

.dialog-editor-control {
    flex: 1 1 18rem;
    max-width: 100%;
    min-width: 0;
}

.dialog-editor-control--operator {
    flex: 0 0 5rem;
}

.dialog-editor-control--compact {
    flex-basis: 12rem;
    max-width: 16rem;
}

.dialog-editor-control--medium {
    flex-basis: 16rem;
}

.dialog-editor-control--wide {
    flex-basis: 18rem;
}

.dialog-editor-control--full {
    flex-basis: 100%;
}

.dialog-editor-action-button {
    flex: 0 0 auto;
    white-space: nowrap;
}

.dialog-editor-button-line {
    display: flex;
    flex: 0 0 100%;
}

.dialog-editor-hint {
    align-items: center;
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    display: flex;
    min-height: 3rem;
    padding: 0.625rem 0.875rem;
}

.dialog-editor-add-row {
    display: flex;
    gap: 0.5rem;
    min-width: 0;
}

:deep(.dialog-editor-control .p-autocomplete),
:deep(.dialog-editor-control .p-dropdown),
:deep(.dialog-editor-control .p-select),
:deep(.dialog-editor-control .p-multiselect),
:deep(.dialog-editor-control .p-inputnumber),
:deep(.dialog-editor-control .p-inputtext) {
    max-width: 100%;
    width: 100%;
}

@media (max-width: 768px) {
    .dialog-editor-add-row {
        flex-direction: column;
    }
}
</style>
