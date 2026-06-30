<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { MapResource } from '@/Resources/Map.resource';
import { useConfirm } from 'primevue';
import InputSwitch from 'primevue/inputswitch';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';

type TileEditorLayer = 'cols' | 'water';
type TileEditorTool = 'brush' | 'rectangle' | 'preset';
type TileEditorCommand = 'undo' | 'redo' | 'fill-selection' | 'erase-selection' | 'clear-selection' | 'save-preset' | 'apply-preset';

type TileEditorSettings = {
    tool: TileEditorTool;
    brushSize: number;
    waterDepth: number;
    selectedPresetId: string | null;
};

type TileEditorPresetOption = {
    id: string;
    name: string;
    layer: TileEditorLayer;
    width: number;
    height: number;
    tileCount: number;
};

const props = withDefaults(defineProps<{
    map: MapResource;
    scale: number;
    naturalNpcSize: boolean;
    tileEditorPresets?: TileEditorPresetOption[];
    canUndoTileEdit?: boolean;
    canRedoTileEdit?: boolean;
    tileSelectionSummary?: string | null;
}>(), {
    tileEditorPresets: () => [],
    canUndoTileEdit: false,
    canRedoTileEdit: false,
    tileSelectionSummary: null,
});

const confirm = useConfirm();

const emit = defineEmits<{
    (e: 'zoomIn'): void;
    (e: 'zoomOut'): void;
    (e: 'editColsChanged', value: boolean): void;
    (e: 'editWaterChanged', value: boolean): void;
    (e: 'naturalNpcSizeChanged', value: boolean): void;
    (e: 'tileEditorSettingsChanged', settings: TileEditorSettings): void;
    (e: 'tileEditorCommand', command: TileEditorCommand): void;
}>();

// Use a single editMode ref instead of separate booleans
const editMode = ref<'none' | TileEditorLayer>('none');
const tileEditorTool = ref<TileEditorTool>('brush');
const brushSize = ref(1);
const waterDepth = ref(1);
const selectedPresetId = ref<string | null>(null);
const tileEditorTools = [
    { label: 'Pędzel', value: 'brush' },
    { label: 'Prostokąt', value: 'rectangle' },
    { label: 'Preset', value: 'preset' },
];

const activeLayer = computed<TileEditorLayer | null>(() => {
    return editMode.value === 'none' ? null : editMode.value;
});

const layerPresets = computed(() => {
    if (!activeLayer.value) {
        return [];
    }

    return props.tileEditorPresets.filter((preset) => preset.layer === activeLayer.value);
});

const hasActiveEditor = computed(() => editMode.value !== 'none');

const emitTileEditorSettings = () => {
    emit('tileEditorSettingsChanged', {
        tool: tileEditorTool.value,
        brushSize: Number(brushSize.value ?? 1),
        waterDepth: Number(waterDepth.value ?? 1),
        selectedPresetId: selectedPresetId.value,
    });
};

// Watch for changes to editMode and emit events
watch(editMode, (newMode, oldMode) => {
    // Update collision editing state
    const colsOn = newMode === 'cols';
    if (colsOn !== (oldMode === 'cols')) {
        emit('editColsChanged', colsOn);
    }

    // Update water editing state
    const waterOn = newMode === 'water';
    if (waterOn !== (oldMode === 'water')) {
        emit('editWaterChanged', waterOn);
    }

    if (newMode === 'none') {
        selectedPresetId.value = null;
    }

    emitTileEditorSettings();
});

watch([tileEditorTool, brushSize, waterDepth, selectedPresetId], () => {
    emitTileEditorSettings();
}, { immediate: true });

watch(layerPresets, (presets) => {
    if (selectedPresetId.value && !presets.some((preset) => preset.id === selectedPresetId.value)) {
        selectedPresetId.value = null;
    }
});

const runTileEditorCommand = (command: TileEditorCommand) => {
    emit('tileEditorCommand', command);
};

const saveCols = () => {
    router.patch(route('maps.update.col', {
        map: props.map.id,
    }), {
        col: props.map.col
    });
};

const saveWater = () => {
    router.patch(route('maps.update.water', {
        map: props.map.id,
    }), {
        water: props.map.water
    });
};

const confirmClearCollisions = (event) => {
    confirm.require({
        target: event.currentTarget,
        message: 'Czy na pewno chcesz wyczyścić kolizje? Ta operacja jest nieodwracalna.',
        icon: 'pi pi-info-circle',
        rejectProps: {
            label: 'Anuluj',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Wyczyść',
            severity: 'danger'
        },
        accept: () => {
            router.patch(route('maps.clear.collisions', {
                map: props.map.id,
            }));
        },
    });
};

const confirmClearWater = (event) => {
    confirm.require({
        target: event.currentTarget,
        message: 'Czy na pewno chcesz wyczyścić wodę? Ta operacja jest nieodwracalna.',
        icon: 'pi pi-info-circle',
        rejectProps: {
            label: 'Anuluj',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Wyczyść',
            severity: 'danger'
        },
        accept: () => {
            router.patch(route('maps.clear.water', {
                map: props.map.id,
            }));
        },
    });
};
</script>

<template>
    <div class="card mt-4 flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-2">
                <Button icon="pi pi-search-plus" aria-label="Powiększ" @click="emit('zoomIn')" />
                <Button icon="pi pi-search-minus" aria-label="Pomniejsz" @click="emit('zoomOut')" />
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <div class="flex items-center gap-2">
                    <InputSwitch
                        input-id="naturalNpcSize"
                        :model-value="props.naturalNpcSize"
                        @update:model-value="(value) => emit('naturalNpcSizeChanged', Boolean(value))"
                    />
                    <label for="naturalNpcSize">Naturalny rozmiar NPC</label>
                </div>

                <span class="font-medium">Tryb edycji</span>
                <div class="flex items-center gap-2">
                    <RadioButton id="editModeNone" v-model="editMode" name="editMode" value="none" />
                    <label for="editModeNone">Brak</label>
                </div>
                <div class="flex items-center gap-2">
                    <RadioButton id="editModeCols" v-model="editMode" name="editMode" value="cols" />
                    <label for="editModeCols">Kolizje</label>
                </div>
                <div class="flex items-center gap-2">
                    <RadioButton id="editModeWater" v-model="editMode" name="editMode" value="water" />
                    <label for="editModeWater">Woda</label>
                </div>
            </div>
        </div>

        <div
            v-if="hasActiveEditor"
            class="flex flex-wrap items-end gap-4 rounded-md border border-surface-200 bg-surface-50 p-3 dark:border-surface-700 dark:bg-surface-900"
        >
            <div class="flex min-w-64 shrink-0 flex-col gap-2">
                <span class="text-sm font-medium text-surface-600 dark:text-surface-300">Narzędzie</span>
                <div class="flex flex-wrap gap-3">
                    <div v-for="tool in tileEditorTools" :key="tool.value" class="flex items-center gap-2">
                        <RadioButton
                            :id="`tile-tool-${tool.value}`"
                            v-model="tileEditorTool"
                            name="tileEditorTool"
                            :value="tool.value"
                        />
                        <label :for="`tile-tool-${tool.value}`">{{ tool.label }}</label>
                    </div>
                </div>
            </div>

            <div v-if="tileEditorTool === 'brush'" class="flex min-w-36 shrink-0 flex-col gap-2">
                <label for="brushSize" class="text-sm font-medium text-surface-600 dark:text-surface-300">Rozmiar</label>
                <InputNumber
                    input-id="brushSize"
                    v-model="brushSize"
                    :min="1"
                    :max="12"
                    show-buttons
                    class="w-36"
                    input-class="w-full min-w-0"
                />
            </div>

            <div v-if="editMode === 'water' && tileEditorTool !== 'preset'" class="flex min-w-36 shrink-0 flex-col gap-2">
                <label for="waterDepth" class="text-sm font-medium text-surface-600 dark:text-surface-300">Głębokość</label>
                <InputNumber
                    input-id="waterDepth"
                    v-model="waterDepth"
                    :min="1"
                    :max="9"
                    show-buttons
                    class="w-36"
                    input-class="w-full min-w-0"
                />
            </div>

            <div v-if="tileEditorTool === 'preset'" class="flex min-w-64 shrink-0 flex-col gap-2">
                <label for="tilePreset" class="text-sm font-medium text-surface-600 dark:text-surface-300">Preset</label>
                <Dropdown
                    input-id="tilePreset"
                    v-model="selectedPresetId"
                    :options="layerPresets"
                    option-label="name"
                    option-value="id"
                    placeholder="Wybierz preset"
                    class="w-full"
                >
                    <template #option="{ option }">
                        <div class="flex w-full items-center justify-between gap-3">
                            <span>{{ option.name }}</span>
                            <span class="text-xs text-surface-500">{{ option.width }}x{{ option.height }}</span>
                        </div>
                    </template>
                </Dropdown>
            </div>

            <div class="flex min-w-fit shrink-0 flex-wrap items-center gap-2">
                <Button
                    icon="pi pi-undo"
                    severity="secondary"
                    outlined
                    :disabled="!props.canUndoTileEdit"
                    aria-label="Cofnij"
                    class="w-12 shrink-0"
                    @click="runTileEditorCommand('undo')"
                />
                <Button
                    icon="pi pi-refresh"
                    severity="secondary"
                    outlined
                    :disabled="!props.canRedoTileEdit"
                    aria-label="Ponów"
                    class="w-12 shrink-0"
                    @click="runTileEditorCommand('redo')"
                />
                <Button
                    v-if="tileEditorTool === 'rectangle'"
                    label="Wypełnij"
                    icon="pi pi-stop"
                    class="shrink-0"
                    @click="runTileEditorCommand('fill-selection')"
                />
                <Button
                    v-if="tileEditorTool === 'rectangle'"
                    label="Wyczyść"
                    icon="pi pi-eraser"
                    severity="secondary"
                    outlined
                    class="shrink-0"
                    @click="runTileEditorCommand('erase-selection')"
                />
                <Button
                    v-if="tileEditorTool === 'rectangle'"
                    label="Zapisz preset"
                    icon="pi pi-bookmark"
                    severity="secondary"
                    outlined
                    class="shrink-0"
                    @click="runTileEditorCommand('save-preset')"
                />
                <Button
                    v-if="tileEditorTool === 'rectangle'"
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    aria-label="Wyczyść zaznaczenie"
                    class="w-12 shrink-0"
                    @click="runTileEditorCommand('clear-selection')"
                />
                <Button
                    v-if="tileEditorTool === 'preset'"
                    label="Nałóż"
                    icon="pi pi-clone"
                    :disabled="!selectedPresetId"
                    class="shrink-0"
                    @click="runTileEditorCommand('apply-preset')"
                />
            </div>

            <span
                v-if="props.tileSelectionSummary"
                class="rounded bg-surface-100 px-2 py-1 text-sm text-surface-600 dark:bg-surface-800 dark:text-surface-300"
            >
                Zaznaczenie: {{ props.tileSelectionSummary }}
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button v-if="editMode === 'cols'" @click="saveCols" label="Zapisz kolizje" />
            <Button v-if="editMode === 'water'" @click="saveWater" label="Zapisz wodę" />
            <Button @click="confirmClearCollisions" label="Wyczyść kolizje" severity="danger" outlined />
            <Button @click="confirmClearWater" label="Wyczyść wodę" severity="danger" outlined />
        </div>
    </div>
</template>
