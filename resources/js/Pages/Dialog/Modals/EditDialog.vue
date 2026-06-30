<script setup lang="ts">
import { computed, inject, onMounted, reactive, Ref, ref, watch } from 'vue';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import { DynamicDialogInstance } from 'primevue/dynamicdialogoptions';
import { route } from 'ziggy-js';
import axios from 'axios';
import { useToast } from 'primevue';
import { DialogNodeAdditionalActionsResource } from '@/Resources/DialogNodeAdditionalActions.resource';
import AdditionalActionsEditor from '@/Pages/Dialog/Componnts/AdditionalActionsEditor.vue';
import { usePage } from '@inertiajs/vue3';
import type { DialogNodeActionDataResource, DialogNodeFocusResource } from '@/Resources/DialogNodeActionData.resource';
import type { NpcWithLocationsResource } from '@/Resources/Npc.resource';

type FocusMode = 'none' | 'npc' | 'coordinates' | 'reset';

type FocusNpcOption = {
    key: string;
    label: string;
    npcId: number;
    locationId: number;
    mapId: number;
    mapName: string;
    x: number;
    y: number;
};

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        content: string,
        dialog_id: number,
        node_id: number,
        action_data: DialogNodeActionDataResource | null,
        additional_actions: DialogNodeAdditionalActionsResource
    }
}> | null>('dialogRef');

const form = reactive<{
    content: string,
    action_data: DialogNodeActionDataResource | null,
    additional_actions: DialogNodeAdditionalActionsResource,
}>({
    content: '',
    action_data: null,
    additional_actions: {},
});

const toast = useToast();
const page = usePage<{ focusNpcs: NpcWithLocationsResource[] }>();
const processing = ref(false);
const copyProcessing = ref(false);
const focusMode = ref<FocusMode>('none');
const selectedFocusNpcKey = ref<string | null>(null);
const focusX = ref<number | null>(null);
const focusY = ref<number | null>(null);
const fallbackFocusNpcOption = ref<FocusNpcOption | null>(null);
const additionalActionsEditor = ref<{
    getPayload: () => DialogNodeAdditionalActionsResource | null;
} | null>(null);

const focusModeOptions: { label: string; value: FocusMode }[] = [
    { label: 'Bez zmiany', value: 'none' },
    { label: 'NPC', value: 'npc' },
    { label: 'Koordynaty', value: 'coordinates' },
    { label: 'Reset', value: 'reset' },
];

const toNullableNumber = (value: unknown): number | null => {
    if (typeof value === 'number' && Number.isFinite(value)) {
        return value;
    }

    if (typeof value === 'string' && value.trim() !== '') {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : null;
    }

    return null;
};

const makeNpcOption = (npc: NpcWithLocationsResource, location: NpcWithLocationsResource['locations'][number]): FocusNpcOption => ({
    key: `${npc.id}:${location.id}`,
    label: `#${npc.id} ${npc.name} - [${location.map_id}] ${location.map_name} (${location.x}, ${location.y})`,
    npcId: npc.id,
    locationId: location.id,
    mapId: location.map_id,
    mapName: location.map_name,
    x: location.x,
    y: location.y,
});

const focusNpcOptions = computed<FocusNpcOption[]>(() => {
    const options = (page.props.focusNpcs ?? [])
        .flatMap((npc) => (npc.locations ?? []).map((location) => makeNpcOption(npc, location)));

    const fallback = fallbackFocusNpcOption.value;
    if (fallback && !options.some((option) => option.key === fallback.key)) {
        options.unshift(fallback);
    }

    return options;
});

const selectedFocusNpc = computed<FocusNpcOption | null>(() => {
    if (!selectedFocusNpcKey.value) {
        return null;
    }

    return focusNpcOptions.value.find((option) => option.key === selectedFocusNpcKey.value) ?? null;
});

const isIntegerCoordinate = (value: number | null): value is number => (
    typeof value === 'number' && Number.isInteger(value)
);

const isEmptyObject = (value: Record<string, unknown>): boolean => Object.keys(value).length === 0;

const buildFocusPayload = (): DialogNodeFocusResource | false | null => {
    if (focusMode.value === 'none') {
        return null;
    }

    if (focusMode.value === 'reset') {
        return { type: 'reset' };
    }

    if (!isIntegerCoordinate(focusX.value) || !isIntegerCoordinate(focusY.value)) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Podaj całkowite koordynaty X i Y fokusu.', life: 6000 });
        return false;
    }

    if (focusMode.value === 'coordinates') {
        return {
            type: 'coordinates',
            x: focusX.value,
            y: focusY.value,
        };
    }

    const npc = selectedFocusNpc.value;
    if (!npc) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybierz NPC dla fokusu kamery.', life: 6000 });
        return false;
    }

    return {
        type: 'npc',
        npcId: npc.npcId,
        locationId: npc.locationId,
        mapId: npc.mapId,
        x: focusX.value,
        y: focusY.value,
    };
};

const buildActionDataPayload = (): DialogNodeActionDataResource | false | null => {
    const actionData = { ...(form.action_data ?? {}) } as DialogNodeActionDataResource;
    delete actionData.focus;

    const focusPayload = buildFocusPayload();
    if (focusPayload === false) {
        return false;
    }

    if (focusPayload) {
        actionData.focus = focusPayload;
    }

    return isEmptyObject(actionData as Record<string, unknown>) ? null : actionData;
};

const initialiseFocusForm = (focus?: DialogNodeFocusResource | null): void => {
    if (!focus?.type) {
        focusMode.value = 'none';
        selectedFocusNpcKey.value = null;
        focusX.value = null;
        focusY.value = null;
        return;
    }

    focusMode.value = focus.type === 'npc' || focus.type === 'coordinates' || focus.type === 'reset'
        ? focus.type
        : 'none';
    focusX.value = toNullableNumber(focus.x);
    focusY.value = toNullableNumber(focus.y);

    if (focus.type !== 'npc') {
        selectedFocusNpcKey.value = null;
        return;
    }

    const matchingOption = focusNpcOptions.value.find((option) => (
        option.npcId === focus.npcId
        && (focus.locationId == null || option.locationId === focus.locationId)
        && (focus.mapId == null || option.mapId === focus.mapId)
        && (focus.x == null || option.x === focus.x)
        && (focus.y == null || option.y === focus.y)
    ));

    if (matchingOption) {
        selectedFocusNpcKey.value = matchingOption.key;
        return;
    }

    if (focus.npcId != null && focus.x != null && focus.y != null) {
        const key = `${focus.npcId}:${focus.locationId ?? 'saved'}`;
        fallbackFocusNpcOption.value = {
            key,
            label: `#${focus.npcId} zapisany focus (${focus.x}, ${focus.y})`,
            npcId: focus.npcId,
            locationId: focus.locationId ?? 0,
            mapId: focus.mapId ?? 0,
            mapName: focus.mapId != null ? `Mapa #${focus.mapId}` : 'Nieznana mapa',
            x: focus.x,
            y: focus.y,
        };
        selectedFocusNpcKey.value = key;
    }
};

onMounted(() => {
    if (!dialogRef) {
        return;
    }

    form.content = dialogRef.value.data?.content ?? '';
    form.action_data = !Array.isArray(dialogRef.value.data?.action_data)
        ? dialogRef.value.data?.action_data ?? null
        : null;
    form.additional_actions = !Array.isArray(dialogRef.value.data?.additional_actions)
        ? dialogRef.value.data?.additional_actions ?? {}
        : {};

    initialiseFocusForm(form.action_data?.focus ?? null);
});

watch(selectedFocusNpc, (npc) => {
    if (!npc || focusMode.value !== 'npc') {
        return;
    }

    focusX.value = npc.x;
    focusY.value = npc.y;
});

const copyDialog = (): void => {
    if (!dialogRef) {
        return;
    }

    copyProcessing.value = true;
    axios.post(route('dialogs.nodes.copy', {
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data.node_id,
    }))
        .then(({ data }) => {
            toast.add({ severity: 'success', summary: 'Operacja powiodła się', detail: 'Pomyślnie skopiowano dialog', life: 6000 });
            dialogRef.value.close({
                content: form.content,
                copied: true,
                newNode: data.node,
            });
        })
        .catch(({ response }) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
        })
        .finally(() => {
            copyProcessing.value = false;
        });
};

const save = (): void => {
    if (!dialogRef) {
        return;
    }

    const additionalActionsPayload = additionalActionsEditor.value?.getPayload();

    if (!additionalActionsPayload) {
        return;
    }

    const actionDataPayload = buildActionDataPayload();

    if (actionDataPayload === false) {
        return;
    }

    processing.value = true;
    axios.patch(route('dialogs.nodes.update', {
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data.node_id,
    }), {
        content: form.content,
        action_data: actionDataPayload,
        additional_actions: additionalActionsPayload,
    })
        .then(() => {
            toast.add({ severity: 'success', summary: 'Operacja powiodła się', detail: 'Pomyślnie zmieniono treść dialogu npc', life: 6000 });
            dialogRef.value.close({
                content: form.content,
                action_data: actionDataPayload,
                additional_actions: additionalActionsPayload,
            });
        })
        .catch(({ response }) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
        })
        .finally(() => {
            processing.value = false;
        });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <Textarea v-model="form.content" rows="5" cols="50" />

        <div class="flex flex-col gap-3 rounded-lg border border-surface-200 p-3 dark:border-surface-700">
            <h3 class="text-lg font-semibold m-0">Fokus kamery</h3>
            <SelectButton v-model="focusMode" :options="focusModeOptions" optionLabel="label" option-value="value" />

            <div v-if="focusMode === 'npc'" class="flex flex-col gap-3">
                <Message v-if="focusNpcOptions.length === 0" severity="warn" :closable="false">
                    Brak NPC na mapach powiązanych z tym dialogiem.
                </Message>
                <Select
                    v-model="selectedFocusNpcKey"
                    :options="focusNpcOptions"
                    optionLabel="label"
                    option-value="key"
                    filter
                    showClear
                    placeholder="Wybierz NPC"
                    class="w-full"
                />
                <div v-if="selectedFocusNpc" class="text-sm text-surface-500">
                    {{ selectedFocusNpc.mapName }}: {{ selectedFocusNpc.x }}, {{ selectedFocusNpc.y }}
                </div>
            </div>

            <div v-if="focusMode === 'coordinates'" class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">X</label>
                    <InputNumber v-model="focusX" :useGrouping="false" class="w-full" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Y</label>
                    <InputNumber v-model="focusY" :useGrouping="false" class="w-full" />
                </div>
            </div>

            <Message v-if="focusMode === 'reset'" severity="secondary" :closable="false">
                Po wejściu w ten node kamera wróci do bohatera.
            </Message>
            <Message v-if="focusMode === 'none'" severity="secondary" :closable="false">
                Ten node nie zmieni obecnego fokusu kamery.
            </Message>
        </div>

        <div class="flex flex-col gap-2">
            <h3 class="text-lg font-semibold m-0">Akcje dodatkowe</h3>
            <AdditionalActionsEditor ref="additionalActionsEditor" v-model:actions="form.additional_actions" />
        </div>

        <div class="flex gap-2">
            <Button :loading="processing" class="flex-1" @click="save">Zapisz</Button>
            <Button :loading="copyProcessing" severity="secondary" class="flex-1" @click="copyDialog">Kopiuj dialog</Button>
        </div>
    </div>
</template>
