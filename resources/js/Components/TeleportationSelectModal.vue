<script setup lang="ts">
import {inject, onMounted, Ref, ref} from "vue";
import {debounce} from "chart.js/helpers";
import axios from "axios";
import {route} from "ziggy-js";
import {DialogNodeTeleportationDataResource} from "../Resources/DialogNodeTeleportationData.resource";
import {MapResource} from "../Resources/Map.resource";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";

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
})

const scale = ref(1);

const teleportation = ref<DialogNodeTeleportationDataResource | null>(null);

const selectedMap = ref<MapResource | null>(null);
const dropdownMaps = ref<MapResource[]>([]);

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
    // const position = {
    //     x: trackerPosition.value.x,
    //     y: trackerPosition.value.y,
    // }
    // emit('selected', position);
    teleportation.value = {
        x: trackerPosition.value.x,
        y: trackerPosition.value.y,
        mapId: selectedMap.value.id,
        mapName: selectedMap.value.name,
        createInstance: teleportation.value?.createInstance ?? false,
        includeNpcs: teleportation.value?.includeNpcs ?? false,
        scaleNpcsToPlayerLevel: teleportation.value?.scaleNpcsToPlayerLevel ?? false,
        npcLevelOffset: normalizeLevelOffset(teleportation.value?.npcLevelOffset),
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
    }
    markChanged();
}

const markIncludeNpcsChanged = () => {
    if (!teleportation.value?.includeNpcs) {
        teleportation.value.scaleNpcsToPlayerLevel = false;
        teleportation.value.npcLevelOffset = 0;
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

const reset = () => {
    teleportation.value = withTeleportationDefaults(dialogRef.value.data?.teleportation);
    changed.value = false;
}

const save = () => {
    if (teleportation.value) {
        teleportation.value.npcLevelOffset = normalizeLevelOffset(teleportation.value.npcLevelOffset);
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
    </div>

    <div v-if="changed" class="flex justify-end gap-2">
            <Button type="button" label="Anuluj" severity="secondary" @click="cancel" />
            <Button type="button" label="Koryguj" severity="info" @click="reset" />
            <Button type="button" label="Zapisz" @click="save" />
    </div>
    <div v-else>
        <div class="font-bold text-lg flex flex-row gap-1 w-full">
            <AutoComplete v-model="selectedMap" :suggestions="dropdownMaps" optionLabel="name"
                      placeholder="Szukaj lokacji" class="w-full" @complete="searchOptions"
            >
                <template #option="slotProps">
                    <div class="flex align-items-center">
                        <div>{{ slotProps.option.name }}</div>
                    </div>
                </template>
            </AutoComplete>
        </div>
        <div
            v-if="selectedMap && selectedMap.id > 0"

        >
            <div
                class="map-container relative"
                @mousemove="handleMouseMove"
                :style="{
                    backgroundImage: `url(${selectedMap.src})`,
                    width: `${selectedMap.x * 32 * scale}px`,
                    height: `${selectedMap.y * 32 * scale}px`,
                    transformOrigin: 'top left',
                    transform: `translate(${selectedMap.x}px, ${selectedMap.y}px)`,
                }"
                @click.self="handleClick"
            >
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
.map-container {
    
}
</style>
