<script setup lang="ts">
import {inject, onMounted, watch, Ref, ref} from "vue";
import {debounce} from "chart.js/helpers";
import axios from "axios";
import {route} from "ziggy-js";
import {DialogNodeTeleportationDataResource} from "../Resources/DialogNodeTeleportationData.resource";
import {MapResource} from "../Resources/Map.resource";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";

import type { DoorResource } from '@/Resources/Door.resource';
import type { NpcWithLocationResource } from '@/Resources/Npc.resource';
import CollisionRenderer from '@/Pages/Map/Components/CollisionRenderer.vue';
import DoorRenderer from '@/Pages/Map/Components/DoorRenderer.vue';
import NpcRenderer from '@/Pages/Map/Components/NpcRenderer.vue';

// const visible = defineModel<boolean>('visible')

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        teleportation?: DialogNodeTeleportationDataResource,
        option: {
            label: string,
            id: string
        },
        parent: string
    }
}> | null>('dialogRef');


const emit = defineEmits(['selected']);

onMounted(() => {
    teleportation.value = withTeleportationDefaults(dialogRef.value.data?.teleportation);
    previewMapId.value = teleportation.value?.mapId ?? null;
})

const scale = ref(1);

const teleportation = ref<DialogNodeTeleportationDataResource | null>(null);

const selectedMap = ref<MapResource | null>(null);
const dropdownMaps = ref<MapResource[]>([]);
const preview = ref<{ map: MapResource; doors: DoorResource[]; npcs: NpcWithLocationResource[] } | null>(null);
const previewError = ref(false);

const previewMapId = ref<number | null>(null);

watch(() => selectedMap.value?.id, (id) => {
    previewMapId.value = id ?? null;
});

watch(previewMapId, async (id, _, onCleanup) => {
    preview.value = null;
    previewError.value = false;
    if (!id) return;

    const controller = new AbortController();
    onCleanup(() => controller.abort());
    try {
        const { data } = await axios.get(route('maps.preview-data', { map: id }), { signal: controller.signal });
        if (!controller.signal.aborted) {
            preview.value = data;
            if (!selectedMap.value) selectedMap.value = data.map;
        }
    } catch {
        if (!controller.signal.aborted) previewError.value = true;
    }
});

const normalizeLevelOffset = (value?: number | null) => {
    const numberValue = Number(value ?? 0);
    return Number.isFinite(numberValue) ? Math.trunc(numberValue) : 0;
};

const withTeleportationDefaults = (value?: DialogNodeTeleportationDataResource | null): DialogNodeTeleportationDataResource | null => {
    if (!value) {
        return null;
    }

    return {
        ...value,
        npcLevelOffset: normalizeLevelOffset(value.npcLevelOffset),
        npcLootItemLevelOffset: normalizeLevelOffset(value.npcLootItemLevelOffset),
    };
};

const searchOptions = debounce((event) => {
    axios.get(route('maps.search', { search: event[0].query }))
        .then(response => {
            dropdownMaps.value = response.data;
        });
}, 100);

const handleMouseMove = (event: MouseEvent) => {
    setTrackerPosition(event);
};

const trackerPosition = ref({ x: 0, y: 0 });
const setTrackerPosition = (event: MouseEvent) => {
    if (event.target !== event.currentTarget) {
        trackerPosition.value = { x: -32, y: -32 };
        return;
    }
    trackerPosition.value = {
        x: (event.offsetX / scale.value / 32) | 0,
        y: (event.offsetY / scale.value / 32) | 0
    };
};

const handleClick = (event: MouseEvent) => {
    if (!selectedMap.value || preview.value?.map.id !== selectedMap.value.id) return;
    setTrackerPosition(event);
    teleportation.value = {
        x: trackerPosition.value.x,
        y: trackerPosition.value.y,
        mapId: selectedMap.value.id,
        mapName: selectedMap.value.name,
        createInstance: teleportation.value?.createInstance ?? false,
        includeNpcs: teleportation.value?.includeNpcs ?? false,
        scaleNpcsToPlayerLevel: teleportation.value?.scaleNpcsToPlayerLevel ?? false,
        npcLevelOffset: normalizeLevelOffset(teleportation.value?.npcLevelOffset),
        scaleNpcLootItemLevels: teleportation.value?.scaleNpcLootItemLevels ?? false,
        npcLootItemLevelOffset: normalizeLevelOffset(teleportation.value?.npcLootItemLevelOffset),
    }
    changed.value = true;
}

const changed = ref(false);

const markChanged = () => {
    changed.value = true;
}

const markCreateInstanceChanged = () => {
    if (!teleportation.value?.createInstance) {
        teleportation.value.includeNpcs = false;
        teleportation.value.scaleNpcsToPlayerLevel = false;
        teleportation.value.npcLevelOffset = 0;
        teleportation.value.scaleNpcLootItemLevels = false;
        teleportation.value.npcLootItemLevelOffset = 0;
    }
    markChanged();
}

const markIncludeNpcsChanged = () => {
    if (!teleportation.value?.includeNpcs) {
        teleportation.value.scaleNpcsToPlayerLevel = false;
        teleportation.value.npcLevelOffset = 0;
        teleportation.value.scaleNpcLootItemLevels = false;
        teleportation.value.npcLootItemLevelOffset = 0;
    }
    markChanged();
}

const markScaleNpcsChanged = () => {
    if (!teleportation.value?.scaleNpcsToPlayerLevel) {
        teleportation.value.npcLevelOffset = 0;
    } else {
        teleportation.value.npcLevelOffset = normalizeLevelOffset(teleportation.value.npcLevelOffset);
    }
    markChanged();
}

const markScaleLootItemsChanged = () => {
    if (!teleportation.value?.scaleNpcLootItemLevels) {
        teleportation.value.npcLootItemLevelOffset = 0;
    } else {
        teleportation.value.npcLootItemLevelOffset = normalizeLevelOffset(teleportation.value.npcLootItemLevelOffset);
    }
    markChanged();
}

const reset = () => {
    teleportation.value = withTeleportationDefaults(dialogRef.value.data?.teleportation);
    changed.value = false;
}

const save = () => {
    if (teleportation.value) {
        teleportation.value.npcLevelOffset = normalizeLevelOffset(teleportation.value.npcLevelOffset);
        teleportation.value.npcLootItemLevelOffset = normalizeLevelOffset(teleportation.value.npcLootItemLevelOffset);
    }

    dialogRef.value.close({
        teleportation: teleportation.value,
    });
}

const cancel = () => {
    dialogRef.value.close()
}

</script>
<template>
    <span v-if="teleportation" class="text-surface-500 dark:text-surface-400 block mb-8">Aktualna teleportacja: [{{teleportation.mapId}}] {{teleportation.mapName}} ({{teleportation.x}}, {{teleportation.y}})</span>
    <span v-else class="text-surface-500 dark:text-surface-400 block mb-8">Brak ustawionej teleportacji</span>

    <div v-if="teleportation" class="flex flex-column gap-3 mb-4">
        <div class="flex align-items-center gap-2">
            <Checkbox
                v-model="teleportation.createInstance"
                input-id="teleport-create-instance"
                :binary="true"
                @change="markCreateInstanceChanged"
            />
            <label for="teleport-create-instance">Twórz osobną instancję tej mapy</label>
        </div>

        <div class="flex align-items-center gap-2" :class="{ 'opacity-50': !teleportation.createInstance }">
            <Checkbox
                v-model="teleportation.includeNpcs"
                input-id="teleport-include-npcs"
                :binary="true"
                :disabled="!teleportation.createInstance"
                @change="markIncludeNpcsChanged"
            />
            <label for="teleport-include-npcs">Dodaj NPC z bazowej mapy do instancji</label>
        </div>

        <div class="flex align-items-center gap-2" :class="{ 'opacity-50': !teleportation.createInstance || !teleportation.includeNpcs }">
            <Checkbox
                v-model="teleportation.scaleNpcsToPlayerLevel"
                input-id="teleport-scale-npcs"
                :binary="true"
                :disabled="!teleportation.createInstance || !teleportation.includeNpcs"
                @change="markScaleNpcsChanged"
            />
            <label for="teleport-scale-npcs">Skaluj poziom mobów do poziomu gracza</label>
        </div>

        <div
            v-if="teleportation.createInstance && teleportation.includeNpcs && teleportation.scaleNpcsToPlayerLevel"
            class="flex flex-column gap-2"
        >
            <label for="teleport-npc-level-offset" class="font-semibold">Różnica poziomu NPC</label>
            <InputNumber
                id="teleport-npc-level-offset"
                v-model="teleportation.npcLevelOffset"
                :useGrouping="false"
                showButtons
                class="w-full"
                @update:modelValue="markChanged"
            />
            <small class="text-surface-500 dark:text-surface-400">0 oznacza poziom gracza, 10 oznacza gracz +10, -20 oznacza gracz -20.</small>
        </div>

        <div class="flex align-items-center gap-2" :class="{ 'opacity-50': !teleportation.createInstance || !teleportation.includeNpcs }">
            <Checkbox
                v-model="teleportation.scaleNpcLootItemLevels"
                input-id="teleport-scale-loot-items"
                :binary="true"
                :disabled="!teleportation.createInstance || !teleportation.includeNpcs"
                @change="markScaleLootItemsChanged"
            />
            <label for="teleport-scale-loot-items">Skaluj poziom przedmiotów z lootu NPC</label>
        </div>

        <div
            v-if="teleportation.createInstance && teleportation.includeNpcs && teleportation.scaleNpcLootItemLevels"
            class="flex flex-column gap-2"
        >
            <label for="teleport-loot-item-level-offset" class="font-semibold">Różnica poziomu przedmiotów z lootu</label>
            <InputNumber
                id="teleport-loot-item-level-offset"
                v-model="teleportation.npcLootItemLevelOffset"
                :useGrouping="false"
                showButtons
                class="w-full"
                @update:modelValue="markChanged"
            />
            <small class="text-surface-500 dark:text-surface-400">0 oznacza poziom gracza, -5 oznacza gracz -5, 10 oznacza gracz +10.</small>
        </div>
    </div>

    <div v-if="changed" class="flex justify-end gap-2">
            <Button type="button" label="Anuluj" severity="secondary" @click="cancel" />
            <Button type="button" label="Koryguj" severity="info" @click="reset" />
            <Button type="button" label="Zapisz" @click="save" />
    </div>
    <div v-else>
        <div class="font-bold text-lg flex flex-row gap-1 w-full">
            <AutoComplete forceSelection v-model="selectedMap" :suggestions="dropdownMaps" optionLabel="name"
                      placeholder="Szukaj lokacji" class="w-full" @complete="searchOptions"
            >
                <template #option="slotProps">
                    <div class="flex align-items-center">
                        <div>{{ slotProps.option.name }}</div>
                    </div>
                </template>
            </AutoComplete>
        </div>
        <p v-if="previewError" role="alert">Nie udało się załadować podglądu mapy.</p>
        <div
            v-if="selectedMap && selectedMap.id > 0 && !previewError"

        >
            <p v-if="preview?.map.id !== selectedMap.id" role="status">Ładowanie podglądu mapy…</p>
            <div
                v-else
                class="map-container relative"
                @mousemove="handleMouseMove"
                :style="{
                    backgroundImage: `url(${selectedMap.src})`,
                    width: `${selectedMap.x * 32 * scale}px`,
                    height: `${selectedMap.y * 32 * scale}px`,

                }"
                @click.self="handleClick"
            >
                <div class="map-overlays pointer-events-none" aria-hidden="true">
                    <CollisionRenderer :map="preview.map" :scale="scale" :edit-cols-on="false" />
                    <DoorRenderer :doors="preview.doors" :scale="scale" />
                    <NpcRenderer :npcs="preview.npcs" :scale="scale" :npc-scale="true"
                                 :natural-npc-size="false" :add-to-group-mode="false" :source-npc="null" />
                </div>
                <div
                    class="mouse-tracker absolute bg-yellow-500/70 pointer-events-none" :style="{
                    width: `${32 * scale}px`,
                    height: `${32 * scale}px`,
                    top: trackerPosition.y * 32 * scale,
                    left: trackerPosition.x * 32 * scale,
                }" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.map-overlays :deep(*) {
    pointer-events: none;
}
</style>
