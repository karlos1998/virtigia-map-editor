<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {computed, reactive, Ref, ref, watch} from "vue";

import {inject, onMounted} from "vue";
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
import InputSwitch from 'primevue/inputswitch';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import OutfitBrowserDialog from '../../BaseItem/Components/OutfitBrowserDialog.vue'; // Dodaj import OutfitBrowserDialog

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

onMounted(async () => {
    if(!dialogRef) return;

    form.content = dialogRef.value.data?.content ?? '';
    form.additional_actions = !Array.isArray(dialogRef.value.data?.additional_actions) ? dialogRef.value.data?.additional_actions ?? {} : {};

    if(form.additional_actions[DialogNodeAdditionalAction.addItems]) {
        searchItems('', form.additional_actions[DialogNodeAdditionalAction.addItems].value as number[])
    }

    if (form.additional_actions[DialogNodeAdditionalAction.blessing]) {
        const current = form.additional_actions[DialogNodeAdditionalAction.blessing];
        const rawVal = (current && typeof current === 'object' && 'value' in current) ? current.value : current;
        const id = typeof rawVal === 'number' ? rawVal : parseInt(String(rawVal));
        if (!isNaN(id)) {
            const {data} = await axios.get<BaseItemResource[]>(route('base-items.search', {
                query: '',
                ids: [id],
                category: 'blessings'
            }));
            const item = data[0];
            selectedBlessingItem.value = item ?? null;
            // ensure form stores numeric id (not string) and preserves scale
            if (form.additional_actions[DialogNodeAdditionalAction.blessing]) {
                const current2 = form.additional_actions[DialogNodeAdditionalAction.blessing];
                if (current2 && typeof current2 === 'object') {
                    current2.value = id;
                } else {
                    form.additional_actions[DialogNodeAdditionalAction.blessing] = {value: id, scale: false} as any;
                }
            }
        }
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
const copyProcessing = ref(false);

const copyDialog = () => {
    if(!dialogRef) return;

    copyProcessing.value = true;
    axios.post(route('dialogs.nodes.copy', {
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data.node_id,
    }))
        .then(({data}) => {
            toast.add({ severity: 'success', summary: 'Operacja powiodła się', detail: 'Pomyślnie skopiowano dialog', life: 6000 });
            dialogRef.value.close({
                content: form.content,
                copied: true,
                newNode: data.node
            });
        })
        .catch(({response}) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
        })
        .finally(() => {
            copyProcessing.value = false;
        });
};

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

    // Validate blessing action: ensure selected blessing item exists and has correct category
    if (form.additional_actions[DialogNodeAdditionalAction.blessing]) {
        if (!selectedBlessingItem.value) {
            toast.add({severity: 'error', summary: 'Błąd', detail: 'Wybierz błogosławieństwo', life: 3000});
            return;
        }

        if (selectedBlessingItem.value.category !== 'blessings') {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: 'Wybrany przedmiot nie jest błogosławieństwem',
                life: 3000
            });
            return;
        }
    }

    // Validate setOutfit action: ensure src is provided
    if (form.additional_actions[DialogNodeAdditionalAction.setOutfit]) {
        const outfitAction = form.additional_actions[DialogNodeAdditionalAction.setOutfit] as any;
        if (!outfitAction.value || outfitAction.value.trim() === '') {
            toast.add({severity: 'error', summary: 'Błąd', detail: 'Podaj źródło grafiki stroju', life: 3000});
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

    // Assign blessing id from selectedBlessingItem into payload
    if (formData.additional_actions[DialogNodeAdditionalAction.blessing]) {
        formData.additional_actions[DialogNodeAdditionalAction.blessing].value = selectedBlessingItem.value ? selectedBlessingItem.value.id : null;
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
    } else if (newAdditionalAction.value == DialogNodeAdditionalAction.addExp) {
        value = 1;
    } else if (newAdditionalAction.value == DialogNodeAdditionalAction.blessing) {
        value = {value: 0, scale: false};
    } else if(newAdditionalAction.value == DialogNodeAdditionalAction.setQuestStep) {
        value = "";
    } else if (newAdditionalAction.value == DialogNodeAdditionalAction.setOutfit) {
        value = "";
        // duration will be set separately
    }

    form.additional_actions[newAdditionalAction.value] = {
        value,
    }

    // Initialize duration for setOutfit
    if (newAdditionalAction.value == DialogNodeAdditionalAction.setOutfit) {
        (form.additional_actions[newAdditionalAction.value] as any).duration = 0;
    }

    newAdditionalAction.value = undefined;
};

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

// Blessing (single item) search
const blessingDropdown = ref<BaseItemResource[]>([]);
const selectedBlessingItem = ref<BaseItemResource | null>(null);

const searchBlessings = debounce(async (query: string, id?: number | null) => {
    const ids = id ? [id] : [];
    const {data} = await axios.get<BaseItemResource[]>(route('base-items.search', {query, ids, category: 'blessings'}));
    blessingDropdown.value = data;

    return data[0];
}, 500);

const blessingSearchChanged = async ({query}: { query: string }) => {
    await searchBlessings(query);
};

watch(selectedBlessingItem, (newVal) => {
    if (!form.additional_actions[DialogNodeAdditionalAction.blessing]) return;
    const current = form.additional_actions[DialogNodeAdditionalAction.blessing];
    const id = newVal ? newVal.id : null;
    if (current && typeof current === 'object') {
        current.value = id;
    } else {
        form.additional_actions[DialogNodeAdditionalAction.blessing] = {value: id, scale: false} as any;
    }
});

// Modal ilości przedmiotów (analogicznie do EditRules.vue)
const showItemsAmountModal = ref(false)
const itemAmounts = ref<number[]>([])

const openItemsAmountModal = () => {
    const action = form.additional_actions[DialogNodeAdditionalAction.addItems]
    if (!action || !Array.isArray(action.value)) return

    itemAmounts.value = action.value.map((_: any, i: number) => action.value2?.[i] ?? 1)
    showItemsAmountModal.value = true
}

const saveItemAmounts = () => {
    const action = form.additional_actions[DialogNodeAdditionalAction.addItems]
    if (action) {
        (action as any).value2 = [...itemAmounts.value]
    }
    showItemsAmountModal.value = false
}

watch(
    () => {
        const action = form.additional_actions[DialogNodeAdditionalAction.addItems]
        if (!action) return undefined
        // if action itself is an array (legacy), return it
        if (Array.isArray(action)) return action as number[]
        // otherwise assume object with .value
        return (action as any).value as number[] | undefined
    },
    (current: number[] | undefined, previous: number[] | undefined) => {
        if (!Array.isArray(current)) return
        // don't run on initial population where we don't have a previous value
        if (typeof previous === 'undefined') return

        const action = form.additional_actions[DialogNodeAdditionalAction.addItems]
        if (!action) return

        const oldIds = Array.isArray(previous) ? previous : []
        const oldValue2 = Array.isArray((action as any).value2) ? (action as any).value2 : []

        const newValue2: number[] = []

        for (let idx = 0; idx < current.length; idx++) {
            const id = current[idx]
            const prevIndex = oldIds.indexOf(id)
            newValue2[idx] = prevIndex !== -1 ? oldValue2[prevIndex] : 1
        }

        (action as any).value2 = newValue2
    },
    {deep: true}
)

// Outfit browser dialog
const showOutfitBrowserModal = ref(false)

const openOutfitBrowserModal = () => {
    showOutfitBrowserModal.value = true
}

const handleOutfitSelected = (filePath: string) => {
    currentOutfitSrc.value = filePath;
    showOutfitBrowserModal.value = false
}

// Computed properties for outfit values to ensure reactivity
const currentOutfitSrc = computed({
    get: () => {
        const outfitAction = form.additional_actions[DialogNodeAdditionalAction.setOutfit];
        return outfitAction ? (outfitAction.value as string) || '' : '';
    },
    set: (value: string) => {
        const outfitAction = form.additional_actions[DialogNodeAdditionalAction.setOutfit];
        if (outfitAction) {
            outfitAction.value = value;
        }
    }
});

const currentOutfitDuration = computed({
    get: () => {
        const outfitAction = form.additional_actions[DialogNodeAdditionalAction.setOutfit];
        return outfitAction ? (outfitAction as any).duration || 0 : 0;
    },
    set: (value: number) => {
        const outfitAction = form.additional_actions[DialogNodeAdditionalAction.setOutfit];
        if (outfitAction) {
            (outfitAction as any).duration = value;
        }
    }
});
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
                v-if="form.additional_actions[name] && typeof form.additional_actions[name].value == 'number' && (name == DialogNodeAdditionalAction.addGold || name == DialogNodeAdditionalAction.addExp)"
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
            <Button
                v-if="form.additional_actions[name] && name == DialogNodeAdditionalAction.addItems"
                @click="openItemsAmountModal"
                severity="secondary"
                icon="pi pi-pencil"
            >
                Ustaw ilości
            </Button>

            <Dialog
                v-model:visible="showItemsAmountModal"
                modal
                header="Ilości przedmiotów"
                :style="{ width: '36rem', maxWidth: '95vw' }"
            >
                <div
                    v-if="Array.isArray(form.additional_actions[DialogNodeAdditionalAction.addItems]?.value)"
                    class="space-y-4 max-h-[60vh] overflow-y-auto"
                >
                    <div
                        v-for="(itemId, idx) in form.additional_actions[DialogNodeAdditionalAction.addItems]?.value"
                        :key="itemId"
                        class="border border-gray-200 rounded-xl p-4 shadow-sm"
                    >
                        <div class="text-sm text-gray-500 mb-1 font-medium">[#{{ itemId }}]</div>
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
                        <Button label="Anuluj" severity="secondary" @click="showItemsAmountModal = false"/>
                        <Button label="Zapisz" icon="pi pi-check" severity="primary" @click="saveItemAmounts"/>
                    </div>
                </template>
            </Dialog>

            <AutoComplete
                v-if="form.additional_actions[name] && name == DialogNodeAdditionalAction.blessing"
                v-model="selectedBlessingItem"
                :suggestions="blessingDropdown"
                @complete="blessingSearchChanged"
                :option-label="(item: BaseItemResource | null) => item?.name || ''"
                class="w-full p-0"
                placeholder="Szukaj błogosławieństwa"
            >
                <template #option="slotProps">
                    <div class="name-item flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <img
                                class="h-12 w-12 object-cover"
                                :src="slotProps.option.src"
                                alt="Option Image"
                                v-tip.item.top.show-id="slotProps.option"
                            />
                            <div class="text-center">
                                <span class="font-semibold text-gray-800">
                                    [{{ slotProps.option.id }}] {{ slotProps.option.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </AutoComplete>

            <div v-if="form.additional_actions[name] && name == DialogNodeAdditionalAction.blessing"
                 class="flex items-center gap-2">
                <InputSwitch v-model="(form.additional_actions[name] as any).scale"/>
                <small>Skaluj poziom przedmiotu</small>
            </div>

            <!-- TreeSelectAdapter for setQuestStep action -->
            <TreeSelectAdapter
                v-if="form.additional_actions[name] && name == DialogNodeAdditionalAction.setQuestStep"
                v-model="form.additional_actions[name].value"
                :loading="loading"
                :options="questNodes"
                :onNodeExpand="onQuestNodeExpand"
            />

            <!-- Input for setOutfit action -->
            <div v-if="form.additional_actions[name] && name == DialogNodeAdditionalAction.setOutfit"
                 class="space-y-3 p-3 bg-gray-50 rounded-lg border">
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700">Źródło grafiki stroju</label>
                    <div class="flex gap-2">
                        <InputText
                            v-model="currentOutfitSrc"
                            placeholder="np. grzyb.gif"
                            class="flex-1"
                        />
                        <Button
                            @click="openOutfitBrowserModal"
                            severity="secondary"
                            icon="pi pi-folder-open"
                            label="Przeglądaj"
                            size="small"
                        />
                    </div>
                    <!-- Preview image -->
                    <div v-if="currentOutfitSrc" class="mt-2">
                        <img
                            :src="`/s3/img/outfits/${currentOutfitSrc}`"
                            alt="Podgląd stroju"
                            class="h-16 w-16 object-contain border border-gray-300 rounded"
                            @error="(e: Event) => ((e.target as HTMLImageElement).style.display = 'none')"
                        />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700">Czas trwania (minuty)</label>
                    <InputNumber
                        style="width:60px"
                        v-model="currentOutfitDuration"
                        :min="0"
                        placeholder="Czas w minutach"
                    />
                    <small class="text-gray-500 text-xs mt-1 block">0 = permanentny strój</small>
                </div>
            </div>

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

        <div class="flex gap-2">
            <Button :loading="processing" class="flex-1" @click="save">Zapisz</Button>
            <Button :loading="copyProcessing" severity="secondary" class="flex-1" @click="copyDialog">Kopiuj dialog</Button>
        </div>
        <OutfitBrowserDialog
            v-model:visible="showOutfitBrowserModal"
            @select="handleOutfitSelected"
        />
    </div>
</template>

<style scoped>

</style>
