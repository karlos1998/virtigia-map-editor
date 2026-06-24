<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { route } from 'ziggy-js';
import { useToast } from 'primevue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import InputText from 'primevue/inputtext';
import Select from 'primevue/dropdown';
import { DialogNodeAdditionalAction } from '@/types/DialogNodeAdditionalAction';
import { DropdownListType } from '@/Resources/DropdownList.type';
import { BaseItemResource } from '@/Resources/BaseItem.resource';
import { DialogCounterResource } from '@/Resources/DialogCounter.resource';
import { DialogNodeAdditionalActionsResource } from '@/Resources/DialogNodeAdditionalActions.resource';
import BaseItemSearchSelect from '@/Components/BaseItemSearchSelect.vue';
import OutfitBrowserDialog from '@/Pages/BaseItem/Components/OutfitBrowserDialog.vue';
import QuestRuleSelector from '@/Pages/Dialog/Componnts/QuestRuleSelector.vue';
import { useQuestStepSelection } from '../Composables/useQuestStepSelection';

const actions = defineModel<DialogNodeAdditionalActionsResource>('actions', {
    required: true,
    get(value) {
        return Array.isArray(value) ? {} : value ?? {};
    },
});

const toast = useToast();
const dialogNodeAdditionalActionsList = usePage<{
    dialogNodeAdditionalActionsList: DropdownListType<DialogNodeAdditionalAction>
}>().props.dialogNodeAdditionalActionsList;

const availableDialogNodeAdditionalActionsList = computed(() =>
    dialogNodeAdditionalActionsList.filter(action => !actions.value[action.value])
);

const newAdditionalAction = ref<DialogNodeAdditionalAction>();
const resolvedAddItems = ref<BaseItemResource[]>([]);
const selectedBlessingItem = ref<BaseItemResource | null>(null);
const showItemsAmountModal = ref(false);
const itemAmounts = ref<number[]>([]);
const showOutfitBrowserModal = ref(false);
const currentOutfitPreviewUrl = ref('');
const dialogCounters = ref<DialogCounterResource[]>([]);

const { questNodes, loading, loadQuests, loadQuestStepById } = useQuestStepSelection();

const loadDialogCounters = async (): Promise<void> => {
    const { data } = await axios.get<DialogCounterResource[]>(route('web-api.dialog-counters.index'));
    dialogCounters.value = data;
};

const addAdditionalAction = (): void => {
    if (!newAdditionalAction.value || actions.value[newAdditionalAction.value]) {
        return;
    }

    const action = newAdditionalAction.value;

    if (action === DialogNodeAdditionalAction.addItems) {
        actions.value[action] = { value: [], value2: [] } as any;
    } else if (action === DialogNodeAdditionalAction.blessing) {
        actions.value[action] = { value: null, scale: false } as any;
    } else if (action === DialogNodeAdditionalAction.setQuestStep) {
        actions.value[action] = { value: '' };
    } else if (action === DialogNodeAdditionalAction.setOutfit) {
        actions.value[action] = { value: '', duration: 0 };
    } else if (
        action === DialogNodeAdditionalAction.addDialogCounter ||
        action === DialogNodeAdditionalAction.resetDialogCounter
    ) {
        actions.value[action] = { value: null } as any;
    } else {
        actions.value[action] = { value: 1 };
    }

    newAdditionalAction.value = undefined;
};

const extractBlessingItemId = (action: any): number | null => {
    if (!action) {
        return null;
    }

    let value = typeof action === 'object' && 'value' in action ? action.value : action;

    if (value && typeof value === 'object' && 'value' in value) {
        if (typeof action.scale === 'undefined' && typeof value.scale === 'boolean') {
            action.scale = value.scale;
        }

        value = value.value;
    }

    const itemId = typeof value === 'number' ? value : parseInt(String(value));

    return Number.isInteger(itemId) && itemId > 0 ? itemId : null;
};

const syncExistingActionState = async (): Promise<void> => {
    const blessingAction = actions.value[DialogNodeAdditionalAction.blessing] as any;
    const blessingItemId = extractBlessingItemId(blessingAction);

    if (blessingAction && blessingItemId) {
        const { data } = await axios.get<BaseItemResource[]>(route('base-items.search', {
            query: '',
            ids: [blessingItemId],
            category: 'blessings',
        }));

        selectedBlessingItem.value = data[0] ?? null;
        blessingAction.value = blessingItemId;
        blessingAction.scale = blessingAction.scale ?? false;
    } else {
        selectedBlessingItem.value = null;
    }

    const questStepAction = actions.value[DialogNodeAdditionalAction.setQuestStep];
    const questStepValue = questStepAction?.value;
    let stepId: number | null = null;

    if (typeof questStepValue === 'string' && questStepValue.startsWith('s-')) {
        stepId = parseInt(questStepValue.substring(2));
    } else if (typeof questStepValue === 'number') {
        stepId = questStepValue;
        questStepAction.value = `s-${stepId}`;
    }

    if (stepId !== null && !isNaN(stepId)) {
        await loadQuestStepById(stepId);
    }
};

onMounted(() => {
    void loadQuests({ withSteps: true });
    void loadDialogCounters();
    void syncExistingActionState();
});

watch(() => actions.value, () => {
    void syncExistingActionState();
});

watch(selectedBlessingItem, (newVal) => {
    const blessingAction = actions.value[DialogNodeAdditionalAction.blessing] as any;

    if (!blessingAction) {
        return;
    }

    blessingAction.value = newVal ? newVal.id : null;
    blessingAction.scale = blessingAction.scale ?? false;
});

const handleResolvedAddItems = (items: BaseItemResource[]): void => {
    resolvedAddItems.value = items;
};

const openItemsAmountModal = (): void => {
    const action = actions.value[DialogNodeAdditionalAction.addItems] as any;

    if (!action || !Array.isArray(action.value)) {
        return;
    }

    itemAmounts.value = action.value.map((_: any, index: number) => action.value2?.[index] ?? 1);
    showItemsAmountModal.value = true;
};

const saveItemAmounts = (): void => {
    const action = actions.value[DialogNodeAdditionalAction.addItems] as any;

    if (action) {
        action.value2 = [...itemAmounts.value];
    }

    showItemsAmountModal.value = false;
};

watch(
    () => {
        const action = actions.value[DialogNodeAdditionalAction.addItems] as any;

        if (!action) {
            return undefined;
        }

        if (Array.isArray(action)) {
            return action as number[];
        }

        return action.value as number[] | undefined;
    },
    (current: Array<number | { id?: number }> | undefined, previous: Array<number | { id?: number }> | undefined) => {
        if (!Array.isArray(current) || typeof previous === 'undefined') {
            return;
        }

        const action = actions.value[DialogNodeAdditionalAction.addItems] as any;

        if (!action) {
            return;
        }

        const toItemId = (item: number | { id?: number }): number | null => {
            if (typeof item === 'number') {
                return item;
            }

            if (item && typeof item === 'object' && typeof item.id === 'number') {
                return item.id;
            }

            return null;
        };

        const oldIds = Array.isArray(previous) ? previous.map(toItemId).filter((id): id is number => id !== null) : [];
        const oldValue2 = Array.isArray(action.value2) ? action.value2 : [];
        const newValue2: number[] = [];

        current.forEach((item, index) => {
            const itemId = toItemId(item);

            if (itemId === null) {
                newValue2[index] = 1;

                return;
            }

            const previousIndex = oldIds.indexOf(itemId);
            newValue2[index] = previousIndex !== -1 ? oldValue2[previousIndex] : 1;
        });

        action.value2 = newValue2;
    },
    { deep: true }
);

const currentOutfitSrc = computed({
    get: (): string => {
        const outfitAction = actions.value[DialogNodeAdditionalAction.setOutfit] as any;

        return outfitAction ? outfitAction.value || '' : '';
    },
    set: (value: string): void => {
        const outfitAction = actions.value[DialogNodeAdditionalAction.setOutfit] as any;

        if (outfitAction) {
            outfitAction.value = value;
        }
    },
});

const currentOutfitDuration = computed({
    get: (): number => {
        const outfitAction = actions.value[DialogNodeAdditionalAction.setOutfit] as any;

        return outfitAction ? outfitAction.duration || 0 : 0;
    },
    set: (value: number): void => {
        const outfitAction = actions.value[DialogNodeAdditionalAction.setOutfit] as any;

        if (outfitAction) {
            outfitAction.duration = value;
        }
    },
});

watch(currentOutfitSrc, async (value) => {
    if (!value || value.trim() === '') {
        currentOutfitPreviewUrl.value = '';

        return;
    }

    const { data } = await axios.get<{ url: string }>(route('assets.sign-url', {
        path: `img/outfits/${value.trim()}`,
    }));

    currentOutfitPreviewUrl.value = data.url;
}, { immediate: true });

const handleOutfitSelected = (filePath: string): void => {
    currentOutfitSrc.value = filePath;
    showOutfitBrowserModal.value = false;
};

const validate = (): boolean => {
    const questStepAction = actions.value[DialogNodeAdditionalAction.setQuestStep];

    if (questStepAction) {
        const questStepValue = questStepAction.value;

        if (!questStepValue) {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybierz krok questa', life: 3000 });

            return false;
        }

        if (typeof questStepValue === 'string' && questStepValue.startsWith('q-')) {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybierz krok questa, nie cały quest', life: 3000 });

            return false;
        }
    }

    if (actions.value[DialogNodeAdditionalAction.blessing]) {
        if (!selectedBlessingItem.value) {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybierz błogosławieństwo', life: 3000 });

            return false;
        }

        if (selectedBlessingItem.value.category !== 'blessings') {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybrany przedmiot nie jest błogosławieństwem', life: 3000 });

            return false;
        }
    }

    const outfitAction = actions.value[DialogNodeAdditionalAction.setOutfit] as any;

    if (outfitAction && (!outfitAction.value || outfitAction.value.trim() === '')) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Podaj źródło grafiki stroju', life: 3000 });

        return false;
    }

    const addCounterAction = actions.value[DialogNodeAdditionalAction.addDialogCounter];

    if (addCounterAction && !addCounterAction.value) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybierz licznik dialogowy', life: 3000 });

        return false;
    }

    const resetCounterAction = actions.value[DialogNodeAdditionalAction.resetDialogCounter];

    if (resetCounterAction && !resetCounterAction.value) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybierz licznik dialogowy do wyczyszczenia', life: 3000 });

        return false;
    }

    return true;
};

const toItemId = (item: unknown): number | null => {
    if (typeof item === 'number') {
        return item;
    }

    if (item && typeof item === 'object' && 'id' in item) {
        const parsed = Number((item as { id: unknown }).id);

        return Number.isInteger(parsed) ? parsed : null;
    }

    return null;
};

const getPayload = (): DialogNodeAdditionalActionsResource | null => {
    if (!validate()) {
        return null;
    }

    const payload = JSON.parse(JSON.stringify(actions.value ?? {}));

    if (payload[DialogNodeAdditionalAction.setQuestStep]) {
        const questStepValue = payload[DialogNodeAdditionalAction.setQuestStep].value;

        if (typeof questStepValue === 'string' && questStepValue.startsWith('s-')) {
            const stepId = parseInt(questStepValue.substring(2));

            if (!isNaN(stepId)) {
                payload[DialogNodeAdditionalAction.setQuestStep].value = stepId;
            }
        }
    }

    if (payload[DialogNodeAdditionalAction.blessing]) {
        payload[DialogNodeAdditionalAction.blessing].value = selectedBlessingItem.value
            ? selectedBlessingItem.value.id
            : payload[DialogNodeAdditionalAction.blessing].value;
        payload[DialogNodeAdditionalAction.blessing].scale = Boolean(payload[DialogNodeAdditionalAction.blessing].scale);
    }

    if (payload[DialogNodeAdditionalAction.addItems] && Array.isArray(payload[DialogNodeAdditionalAction.addItems].value)) {
        payload[DialogNodeAdditionalAction.addItems].value = payload[DialogNodeAdditionalAction.addItems].value
            .map(toItemId)
            .filter((itemId: number | null): itemId is number => itemId !== null);
    }

    return payload;
};

defineExpose({
    getPayload,
});
</script>

<template>
    <div class="flex flex-col gap-3">
        <div v-for="name in Object.keys(actions)" :key="name" class="dialog-editor-row">
            <Button
                icon="pi pi-times"
                severity="danger"
                aria-label="Usuń akcję"
                class="dialog-editor-remove"
                @click="delete actions[name]"
            />

            <div class="dialog-editor-label">
                {{ dialogNodeAdditionalActionsList.find(action => action.value === name)?.label }}
            </div>

            <div class="dialog-editor-controls">

            <InputNumber
                v-if="actions[name] && typeof actions[name].value === 'number' && (name === DialogNodeAdditionalAction.addGold || name === DialogNodeAdditionalAction.addHonorPoints || name === DialogNodeAdditionalAction.addExp)"
                v-model="actions[name].value"
                :max="2000000000"
                :min="0"
                class="dialog-editor-control dialog-editor-control--compact"
            />

            <InputNumber
                v-if="actions[name] && typeof actions[name].value === 'number' && name === DialogNodeAdditionalAction.addExpPercent"
                v-model="actions[name].value"
                :step="0.01"
                :minFractionDigits="0"
                :maxFractionDigits="2"
                :max="100"
                :min="0"
                suffix="%"
                class="dialog-editor-control dialog-editor-control--compact"
            />

            <BaseItemSearchSelect
                v-if="actions[name] && name === DialogNodeAdditionalAction.addItems"
                v-model="actions[name].value"
                value-mode="id"
                multiple
                placeholder="Szukaj przedmiotów (nazwa lub #id)"
                class="dialog-editor-control dialog-editor-control--full"
                @resolved-items="handleResolvedAddItems"
            />

            <div v-if="actions[name] && name === DialogNodeAdditionalAction.addItems" class="dialog-editor-button-line">
                <Button
                    @click="openItemsAmountModal"
                    severity="secondary"
                    icon="pi pi-pencil"
                    label="Ustaw ilości"
                    class="dialog-editor-action-button"
                />
            </div>

            <BaseItemSearchSelect
                v-if="actions[name] && name === DialogNodeAdditionalAction.blessing"
                v-model="selectedBlessingItem"
                value-mode="object"
                category="blessings"
                placeholder="Szukaj błogosławieństwa (nazwa lub #id)"
                class="dialog-editor-control dialog-editor-control--wide"
            />

            <div v-if="actions[name] && name === DialogNodeAdditionalAction.blessing" class="flex items-center gap-2">
                <InputSwitch v-model="(actions[name] as any).scale" />
                <small>Skaluj poziom przedmiotu</small>
            </div>

            <QuestRuleSelector
                v-if="actions[name] && name === DialogNodeAdditionalAction.setQuestStep"
                v-model="actions[name].value"
                :loading="loading"
                :quests="questNodes"
                :returnList="false"
                :allowQuestSelection="false"
                context-label="Ustaw postęp questa"
                context-description="Po wybraniu tej opcji dialogowej gracz dostanie wskazany krok questa. W tym miejscu wybierasz konkretny krok, nie cały quest."
                class="dialog-editor-control dialog-editor-control--full"
            />

            <Select
                v-if="actions[name] && name === DialogNodeAdditionalAction.addDialogCounter"
                v-model="actions[name].value"
                :options="dialogCounters"
                optionLabel="name"
                optionValue="id"
                placeholder="Wybierz licznik dialogowy"
                class="dialog-editor-control dialog-editor-control--wide"
            />

            <Select
                v-if="actions[name] && name === DialogNodeAdditionalAction.resetDialogCounter"
                v-model="actions[name].value"
                :options="dialogCounters"
                optionLabel="name"
                optionValue="id"
                placeholder="Wybierz licznik do wyczyszczenia"
                class="dialog-editor-control dialog-editor-control--wide"
            />

            <div v-if="actions[name] && name === DialogNodeAdditionalAction.setOutfit" class="dialog-editor-control dialog-editor-control--wide space-y-3 rounded-lg border bg-gray-50 p-3">
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700">Źródło grafiki stroju</label>
                    <div class="flex gap-2">
                        <InputText
                            v-model="currentOutfitSrc"
                            placeholder="np. grzyb.gif"
                            class="flex-1"
                        />
                        <Button
                            @click="showOutfitBrowserModal = true"
                            severity="secondary"
                            icon="pi pi-folder-open"
                            label="Przeglądaj"
                            size="small"
                        />
                    </div>
                    <div v-if="currentOutfitSrc" class="mt-2">
                        <img
                            :src="currentOutfitPreviewUrl"
                            alt="Podgląd stroju"
                            class="h-16 w-16 object-contain border border-gray-300 rounded"
                            @error="(e: Event) => ((e.target as HTMLImageElement).style.display = 'none')"
                        />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700">Czas trwania (minuty)</label>
                    <InputNumber
                        v-model="currentOutfitDuration"
                        style="width:60px"
                        :min="0"
                        placeholder="Czas w minutach"
                    />
                    <small class="text-gray-500 text-xs mt-1 block">0 = permanentny strój</small>
                </div>
            </div>
            </div>
        </div>

        <div class="dialog-editor-add-row">
            <Select
                class="min-w-0 flex-auto"
                name="category"
                v-model="newAdditionalAction"
                :options="availableDialogNodeAdditionalActionsList"
                option-value="value"
                option-label="label"
            />
            <Button severity="info" @click="addAdditionalAction">
                Dodaj akcję
            </Button>
        </div>

        <Dialog
            v-model:visible="showItemsAmountModal"
            modal
            header="Ilości przedmiotów"
            :style="{ width: '36rem', maxWidth: '95vw' }"
        >
            <div
                v-if="Array.isArray(actions[DialogNodeAdditionalAction.addItems]?.value)"
                class="space-y-4 max-h-[60vh] overflow-y-auto"
            >
                <div
                    v-for="(itemId, index) in actions[DialogNodeAdditionalAction.addItems]?.value"
                    :key="itemId"
                    class="border border-gray-200 rounded-xl p-4 shadow-sm"
                >
                    <div class="text-sm text-gray-500 mb-1 font-medium">[#{{ itemId }}]</div>
                    <div class="text-base font-semibold mb-3">
                        {{ resolvedAddItems.find(item => item.id === itemId)?.name ?? 'Nieznany' }}
                    </div>
                    <InputNumber
                        v-model="itemAmounts[index]"
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

        <OutfitBrowserDialog
            v-model:visible="showOutfitBrowserModal"
            @select="handleOutfitSelected"
        />
    </div>
</template>

<style scoped>
.dialog-editor-row {
    align-items: start;
    display: grid;
    gap: 0.5rem;
    grid-template-columns: 3rem minmax(0, 1fr);
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

.dialog-editor-control--compact {
    flex-basis: 12rem;
    max-width: 16rem;
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

.dialog-editor-add-row {
    display: flex;
    gap: 0.5rem;
    min-width: 0;
}

:deep(.dialog-editor-control .p-autocomplete),
:deep(.dialog-editor-control .p-dropdown),
:deep(.dialog-editor-control .p-select),
:deep(.dialog-editor-control .p-inputnumber) {
    max-width: 100%;
    width: 100%;
}

@media (max-width: 768px) {
    .dialog-editor-add-row {
        flex-direction: column;
    }
}
</style>
