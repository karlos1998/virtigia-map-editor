<script setup lang="ts">
import { computed, ref, watch } from "vue"
import Button from "primevue/button"
import Checkbox from "primevue/checkbox"
import Dialog from "primevue/dialog"
import IconField from "primevue/iconfield"
import InputIcon from "primevue/inputicon"
import InputText from "primevue/inputtext"
import ProgressSpinner from "primevue/progressspinner"
import Tag from "primevue/tag"
import { QuestNode, QuestStepNode } from "@/Pages/Dialog/Composables/useQuestStepSelection"

type RecentQuest = {
    key: string
    label: string
}

const recentQuestStorageKey = "dialog-rule-selector-recent-quests"

const props = withDefaults(defineProps<{
    modelValue?: string | string[] | null
    quests: QuestNode[]
    loading?: boolean
    returnList?: boolean
    allowQuestSelection?: boolean
    contextLabel?: string | null
    contextDescription?: string | null
}>(), {
    loading: false,
    returnList: true,
    allowQuestSelection: true,
    contextLabel: null,
    contextDescription: null
})

const emit = defineEmits<{
    "update:modelValue": [value: string | string[] | null]
}>()

const visible = ref(false)
const search = ref("")
const activeQuestKey = ref<string | null>(null)
const recentQuests = ref<RecentQuest[]>([])

const selectedKeys = computed(() => {
    if (!props.modelValue) {
        return []
    }

    return Array.isArray(props.modelValue) ? props.modelValue : [props.modelValue]
})

const selectedKeySet = computed(() => new Set(selectedKeys.value))

const normalizedSearch = computed(() => normalizeText(search.value))

const questByKey = computed(() => {
    const items = new Map<string, QuestNode>()

    props.quests.forEach(quest => {
        items.set(quest.key, quest)
    })

    return items
})

const stepByKey = computed(() => {
    const items = new Map<string, QuestStepNode & { questLabel: string }>()

    props.quests.forEach(quest => {
        quest.children?.forEach(step => {
            items.set(step.key, {
                ...step,
                questLabel: quest.label
            })
        })
    })

    return items
})

const filteredQuests = computed(() => {
    if (!normalizedSearch.value) {
        return props.quests
    }

    return props.quests.filter(quest =>
        normalizeText(quest.label).includes(normalizedSearch.value)
    )
})

const availableRecentQuests = computed(() =>
    recentQuests.value
        .map(recentQuest => questByKey.value.get(recentQuest.key))
        .filter((quest): quest is QuestNode => Boolean(quest))
)

const visibleQuests = computed(() => {
    if (normalizedSearch.value) {
        return filteredQuests.value
    }

    const recentKeys = new Set(availableRecentQuests.value.map(quest => quest.key))

    return [
        ...availableRecentQuests.value,
        ...props.quests.filter(quest => !recentKeys.has(quest.key))
    ]
})

const activeQuest = computed(() => {
    if (activeQuestKey.value) {
        const foundQuest = questByKey.value.get(activeQuestKey.value)

        if (foundQuest) {
            return foundQuest
        }
    }

    return visibleQuests.value[0] ?? null
})

const selectedItems = computed(() => selectedKeys.value.map(key => {
    const quest = questByKey.value.get(key)

    if (quest) {
        return {
            key,
            label: quest.label,
            meta: "Quest"
        }
    }

    const step = stepByKey.value.get(key)

    if (step) {
        return {
            key,
            label: step.label,
            meta: step.questLabel
        }
    }

    return {
        key,
        label: key,
        meta: "Niezaładowane"
    }
}))

const selectedSummary = computed(() => {
    if (selectedItems.value.length === 0) {
        return props.allowQuestSelection ? "Wybierz quest lub kroki" : "Wybierz krok questa"
    }

    if (selectedItems.value.length === 1) {
        return selectedItems.value[0].label
    }

    return `${selectedItems.value.length} wybranych`
})

const activeQuestSelected = computed(() =>
    activeQuest.value ? selectedKeySet.value.has(activeQuest.value.key) : false
)

function normalizeText(value: string): string {
    return value
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .trim()
}

function isRecentQuest(quest: QuestNode): boolean {
    return availableRecentQuests.value.some(recentQuest => recentQuest.key === quest.key)
}

function loadRecentQuests(): void {
    if (typeof window === "undefined") {
        return
    }

    try {
        const value = window.localStorage.getItem(recentQuestStorageKey)
        recentQuests.value = value ? JSON.parse(value) : []
    } catch {
        recentQuests.value = []
    }
}

function rememberQuest(quest: QuestNode): void {
    recentQuests.value = [
        { key: quest.key, label: quest.label },
        ...recentQuests.value.filter(recentQuest => recentQuest.key !== quest.key)
    ].slice(0, 3)

    if (typeof window === "undefined") {
        return
    }

    window.localStorage.setItem(recentQuestStorageKey, JSON.stringify(recentQuests.value))
}

function openSelector(): void {
    loadRecentQuests()
    search.value = ""
    visible.value = true
    pickInitialQuest()
}

function pickInitialQuest(): void {
    const selectedQuestKey = selectedKeys.value
        .map(key => questByKey.value.get(key) ?? questByKey.value.get(`q-${stepByKey.value.get(key)?.questId}`))
        .find((quest): quest is QuestNode => Boolean(quest))

    activeQuestKey.value = selectedQuestKey?.key
        ?? availableRecentQuests.value[0]?.key
        ?? visibleQuests.value[0]?.key
        ?? null
}

function selectQuest(quest: QuestNode): void {
    activeQuestKey.value = quest.key
    rememberQuest(quest)
}

function emitSelection(keys: string[]): void {
    if (props.returnList) {
        emit("update:modelValue", keys)

        return
    }

    emit("update:modelValue", keys[0] ?? null)
}

function toggleKey(key: string): void {
    if (!props.returnList) {
        emit("update:modelValue", selectedKeySet.value.has(key) ? null : key)

        return
    }

    const keys = selectedKeySet.value.has(key)
        ? selectedKeys.value.filter(selectedKey => selectedKey !== key)
        : [...selectedKeys.value, key]

    emitSelection(keys)
}

function toggleQuest(quest: QuestNode): void {
    if (!props.allowQuestSelection) {
        return
    }

    rememberQuest(quest)
    toggleKey(quest.key)
}

function toggleStep(step: QuestStepNode): void {
    const quest = questByKey.value.get(`q-${step.questId}`)

    if (quest) {
        rememberQuest(quest)
    }

    toggleKey(step.key)
}

function removeSelection(key: string): void {
    emitSelection(selectedKeys.value.filter(selectedKey => selectedKey !== key))
}

watch(
    () => props.quests,
    () => {
        if (visible.value || !activeQuest.value) {
            pickInitialQuest()
        }
    },
    { deep: true }
)

watch(search, () => {
    if (visibleQuests.value.length > 0 && !visibleQuests.value.some(quest => quest.key === activeQuestKey.value)) {
        activeQuestKey.value = visibleQuests.value[0].key
    }
})
</script>

<template>
    <div class="flex w-full min-w-0 max-w-full flex-col gap-2">
        <Button
            type="button"
            icon="pi pi-search"
            :label="selectedSummary"
            severity="secondary"
            class="quest-rule-open-button w-full min-w-0 justify-start"
            @click="openSelector"
        />

        <div v-if="selectedItems.length > 0" class="flex min-w-0 flex-wrap gap-2">
            <Tag
                v-for="item in selectedItems"
                :key="item.key"
                severity="info"
                class="quest-rule-selection-tag min-w-0 max-w-full"
            >
                <span class="block min-w-0 truncate">
                    {{ item.label }}
                    <span v-if="item.meta !== 'Quest'" class="opacity-75"> · {{ item.meta }}</span>
                </span>
                <button
                    type="button"
                    class="ml-2 shrink-0 text-xs"
                    @click.stop="removeSelection(item.key)"
                >
                    ×
                </button>
            </Tag>
        </div>

        <Dialog
            v-model:visible="visible"
            modal
            header="Wybór questa"
            :style="{ width: '58rem', maxWidth: '95vw' }"
        >
            <div class="flex flex-col gap-3">
                <div
                    v-if="contextLabel || contextDescription"
                    class="rounded-md border border-surface-200 bg-surface-50 px-3 py-2"
                >
                    <div v-if="contextLabel" class="text-sm font-semibold text-surface-700">
                        {{ contextLabel }}
                    </div>
                    <div v-if="contextDescription" class="mt-1 text-sm leading-5 text-surface-600">
                        {{ contextDescription }}
                    </div>
                </div>

                <IconField class="w-full">
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText
                        v-model="search"
                        placeholder="Szukaj po nazwie questa"
                        class="w-full"
                        autofocus
                    />
                </IconField>

                <div v-if="loading" class="flex justify-center py-8">
                    <ProgressSpinner class="h-10 w-10" />
                </div>

                <div v-else class="grid min-h-[28rem] gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                    <div class="flex min-h-0 flex-col gap-2">
                        <div class="text-sm font-semibold text-surface-600">Questy</div>
                        <div class="max-h-[28rem] overflow-y-auto rounded-md border border-surface-200">
                            <button
                                v-for="quest in visibleQuests"
                                :key="quest.key"
                                type="button"
                                class="flex w-full items-center justify-between gap-3 border-b border-surface-100 px-3 py-2 text-left last:border-b-0 hover:bg-surface-50"
                                :class="{ 'bg-primary-50 text-primary-700': activeQuest?.key === quest.key }"
                                @click="selectQuest(quest)"
                            >
                                <span class="min-w-0">
                                    <span class="block truncate font-medium">{{ quest.label }}</span>
                                    <span class="text-xs text-surface-500">{{ quest.children?.length ?? 0 }} kroków</span>
                                </span>
                                <Tag v-if="isRecentQuest(quest)" value="Ostatni" severity="secondary" />
                            </button>

                            <div v-if="visibleQuests.length === 0" class="px-3 py-8 text-center text-sm text-surface-500">
                                Brak wyników
                            </div>
                        </div>
                    </div>

                    <div class="flex min-h-0 flex-col gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0 text-sm font-semibold text-surface-600">
                                <span class="block truncate">{{ activeQuest?.label ?? "Kroki" }}</span>
                            </div>
                            <Button
                                v-if="activeQuest && allowQuestSelection"
                                type="button"
                                size="small"
                                severity="secondary"
                                :label="activeQuestSelected ? 'Odznacz quest' : 'Zaznacz quest'"
                                @click="toggleQuest(activeQuest)"
                            />
                        </div>

                        <div class="max-h-[28rem] overflow-y-auto rounded-md border border-surface-200">
                            <label
                                v-for="step in activeQuest?.children ?? []"
                                :key="step.key"
                                class="flex cursor-pointer items-center gap-3 border-b border-surface-100 px-3 py-2 last:border-b-0 hover:bg-surface-50"
                            >
                                <Checkbox
                                    :modelValue="selectedKeySet.has(step.key)"
                                    binary
                                    @update:modelValue="toggleStep(step)"
                                />
                                <span class="min-w-0 truncate">{{ step.label }}</span>
                            </label>

                            <div v-if="activeQuest && (activeQuest.children?.length ?? 0) === 0" class="px-3 py-8 text-center text-sm text-surface-500">
                                Ten quest nie ma kroków
                            </div>

                            <div v-if="!activeQuest" class="px-3 py-8 text-center text-sm text-surface-500">
                                Brak questa
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Gotowe" icon="pi pi-check" @click="visible = false" />
                </div>
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
:deep(.quest-rule-open-button .p-button-label) {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

:deep(.quest-rule-selection-tag .p-tag-value) {
    display: flex;
    min-width: 0;
}
</style>
