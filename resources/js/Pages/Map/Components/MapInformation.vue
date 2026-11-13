<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useToast } from 'primevue';
import { MapResource } from '@/Resources/Map.resource';
import ReplaceMapImageModal from '@/Pages/Map/Modals/ReplaceMapImageModal.vue';
import InputSwitch from 'primevue/inputswitch';
import MapCitySettings from './MapCitySettings.vue';

const props = defineProps<{
    map: MapResource;
    pvpTypeList: { value: number; label: string }[];
    mapGroupList: { value: string; label: string }[];
    respawnPoints: { id: number; map_id: number; map_name: string; x: number; y: number }[];
}>();

// Form variables
const mapName = ref('');
const selectedPvpType = ref<number | null>(null);
const selectedMapGroup = ref<string>(props.map.map_group ?? 'no_group');
const selectedRespawnPointId = ref<number | null>(null);
const toast = useToast();
const showReplaceImageModal = ref(false);
const isTeleportLocked = ref(props.map.is_teleport_locked ?? false);
const isGroupingLocked = ref(props.map.is_grouping_locked ?? false);
const isInternal = ref(props.map.is_internal ?? false);
const isBackgroundSectionExpanded = ref(false);

// Static list of battleground images (place these files under public/img/Backgrounds)
const backgroundFiles = [
    'beach01.jpg',
    'bridge01.jpg',
    'castle01.jpg',
    'castle02.jpg',
    'castle03.jpg',
    'castletown01.jpg',
    'castletown02.jpg',
    'cave01.jpg',
    'cave02.jpg',
    'cave03.jpg',
    'cave04.jpg',
    'church01.jpg',
    'church02.jpg',
    'darkspace01.jpg',
    'desert01.jpg',
    'deserttown01.jpg',
    'deserttown02.jpg',
    'evilcastle01.jpg',
    'evilcastle02.jpg',
    'farmvillage01.jpg',
    'farmvillage02.jpg',
    'forest01.jpg',
    'foresttown01.jpg',
    'foresttown02.jpg',
    'fort01.jpg',
    'fort02.jpg',
    'grassland01.jpg',
    'heaven01.jpg',
    'heaven02.jpg',
    'innerbody01.jpg',
    'mine01.jpg',
    'minetown01.jpg',
    'minetown02.jpg',
    'mountain01.jpg',
    'porttown01.jpg',
    'porttown02.jpg',
    'posttown01.jpg',
    'posttown02.jpg',
    'ruins01.jpg',
    'sewer01.jpg',
    'ship01.jpg',
    'ship02.jpg',
    'shop01.jpg',
    'snowfield01.jpg',
    'snowtown01.jpg',
    'snowtown02.jpg',
    'swamp01.jpg',
    'tower01.jpg',
    'tower02.jpg',
    'woods01.jpg',
];

const backgroundOptions = backgroundFiles.map((file) => ({
    file,
    src: `/Backgrounds/${file}`,
}));

const backgroundClassicFiles = [
    '002N.jpg', '003N.jpg', '004N.jpg', '005N.jpg', '007N.jpg', '008N.jpg',
    '009N.jpg', '010N.jpg', '011N.jpg', '012N.jpg', '013N.jpg', '014N.jpg',
    '021N.jpg', '027N.jpg', '034N.jpg', '07nN.jpg', 'aa1N.jpg', 'aa2N.jpg',
    'cc1N.jpg', 'dd1N.jpg', 'dd2N.jpg', 'dd3N.jpg', 'dd4N.jpg', 'eeN.jpg',
    'fN.jpg', 'jN.jpg', 'kN.jpg',
];
const backgroundClassicOptions = backgroundClassicFiles.map((file) => ({
    file,
    src: `/BackgroundsClassic/${file}`,
}));

const backgroundModernOptions = Array.from({ length: 27 }, (_, index) => ({
    file: `m${index + 1}.jpg`,
    src: `/BackgroundsModern/m${index + 1}.jpg`,
}));

const backgroundTypes = [
    { field: 'battleground', label: 'Tło 1 · Stary Classic', options: backgroundOptions },
    { field: 'battleground2', label: 'Tło 2 · Classic', options: backgroundClassicOptions },
    { field: 'battleground3', label: 'Tło 3 · Modern', options: backgroundModernOptions },
] as const;
type BackgroundField = typeof backgroundTypes[number]['field'];

const selectedBackgrounds = ref<Record<BackgroundField, string | null>>({
    battleground: props.map.battleground ?? null,
    battleground2: props.map.battleground2 ?? null,
    battleground3: props.map.battleground3 ?? null,
});

for (const { field } of backgroundTypes) {
    watch([() => props.map.id, () => props.map[field]], ([, file]) => {
        selectedBackgrounds.value[field] = file ?? null;
    });
}

const backgroundPickers = computed(() => backgroundTypes.map(background => {
    const selected = selectedBackgrounds.value[background.field];
    return { ...background, selected, preview: background.options.find(option => option.file === selected) };
}));

const backgroundSectionToggleLabel = computed(() => {
    return isBackgroundSectionExpanded.value ? 'Zwiń' : 'Rozwiń';
});

const backgroundSectionToggleIcon = computed(() => {
    return isBackgroundSectionExpanded.value ? 'pi pi-chevron-up' : 'pi pi-chevron-down';
});

// Format respawn points for dropdown
const formattedRespawnPoints = computed(() => {
    return props.respawnPoints.map(point => ({
        ...point,
        formatted: `${point.map_name} (${point.x}, ${point.y})`
    }));
});

// Initialize form values
onMounted(() => {
    mapName.value = props.map.name;
    selectedPvpType.value = props.map.pvp;
    if (props.map.respawn_point) {
        selectedRespawnPointId.value = props.map.respawn_point.id;
    }
});

const toggleBackgroundSection = () => {
    isBackgroundSectionExpanded.value = !isBackgroundSectionExpanded.value;
};

// Update map name
const updateMapName = () => {
    router.patch(route('maps.update.name', props.map.id), {
        name: mapName.value
    }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Sukces', detail: 'Nazwa mapy została zaktualizowana', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zaktualizować nazwy mapy', life: 3000 });
        }
    });
};

const updateMapGroup = () => {
    router.patch(route('maps.update.group', props.map.id), {
        map_group: selectedMapGroup.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Sukces', detail: 'Grupa mapy została zaktualizowana', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zaktualizować grupy mapy', life: 3000 });
        }
    });
};

// Update map PvP type
const updateMapPvp = () => {
    router.patch(route('maps.update.pvp', props.map.id), {
        pvp: selectedPvpType.value
    }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Sukces', detail: 'Typ PvP został zaktualizowany', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zaktualizować typu PvP', life: 3000 });
        }
    });
};

// Update map respawn point
const updateMapRespawnPoint = () => {
    router.patch(route('maps.update.respawn-point', props.map.id), {
        respawn_point_id: selectedRespawnPointId.value
    }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Sukces', detail: 'Punkt odrodzenia został zaktualizowany', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zaktualizować punktu odrodzenia', life: 3000 });
        }
    });
};

const updateMapBackground = (field: BackgroundField) => {
    router.patch(route(`maps.update.${field}`, props.map.id), {
        [field]: selectedBackgrounds.value[field],
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Sukces', detail: 'Tło walki zostało zaktualizowane', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zaktualizować tła walki', life: 3000 });
        },
    });
};

// Patch teleport_lock
const updateMapTeleportLocked = () => {
    router.patch(route('maps.update.teleport-locked', props.map.id), {
        is_teleport_locked: isTeleportLocked.value
    }, {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Sukces',
                detail: 'Opcja teleportowania została zaktualizowana',
                life: 3000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: 'Nie udało się zaktualizować opcji teleportowania',
                life: 3000
            });
        }
    });
};

const updateMapInternal = () => {
    router.patch(route('maps.update.internal', props.map.id), {
        is_internal: isInternal.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Sukces', detail: 'Zapisano typ mapy', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zapisać typu mapy', life: 3000 });
        }
    });
};

const updateMapGroupingLocked = () => {
    router.patch(route('maps.update.grouping-locked', props.map.id), {
        is_grouping_locked: isGroupingLocked.value
    }, {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Sukces',
                detail: 'Opcja grupowania została zaktualizowana',
                life: 3000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: 'Nie udało się zaktualizować opcji grupowania',
                life: 3000
            });
        }
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="card p-4 mb-0">
            <h2 class="text-xl font-bold mb-4">Informacje o mapie</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <MapCitySettings :map="map" class="mb-4" />
                    <h3 class="font-semibold mb-2">Grafika mapy</h3>
                    <Button label="Podmień grafikę" icon="pi pi-image" @click="showReplaceImageModal = true" class="w-full" />
                </div>

                <div>
                    <h3 class="font-semibold mb-2">Nazwa mapy</h3>
                    <div class="flex items-center gap-2">
                        <InputText v-model="mapName" class="w-full" />
                        <Button label="Zapisz" @click="updateMapName" />
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold mb-2">Typ PvP</h3>
                    <div class="flex items-center gap-2">
                        <Dropdown v-model="selectedPvpType" :options="pvpTypeList" optionLabel="label" optionValue="value" class="w-full" />
                        <Button label="Zapisz" @click="updateMapPvp" />
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold mb-2">Grupa mapy</h3>
                    <div class="flex items-center gap-2">
                        <Dropdown v-model="selectedMapGroup" :options="mapGroupList" optionLabel="label" optionValue="value" class="w-full" />
                        <Button label="Zapisz" @click="updateMapGroup" />
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold mb-2">Punkt odrodzenia</h3>
                    <div class="flex items-center gap-2">
                        <Dropdown v-model="selectedRespawnPointId" :options="formattedRespawnPoints" optionLabel="formatted" :optionValue="'id'" class="w-full" />
                        <Button label="Zapisz" @click="updateMapRespawnPoint" />
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold mb-2">Blokada teleportacji</h3>
                    <div class="flex items-center gap-2">
                        <InputSwitch v-model="isTeleportLocked" />
                        <Button label="Zapisz" @click="updateMapTeleportLocked"/>
                    </div>
                    <div v-if="isTeleportLocked" class="mt-2 text-red-600 text-sm">
                        Z tej mapy nie można się teleportować.
                    </div>
                    <div v-else class="mt-2 text-green-600 text-sm">
                        Teleportacja dozwolona.
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold mb-2">Blokada grupowania</h3>
                    <div class="flex items-center gap-2">
                        <InputSwitch v-model="isGroupingLocked" />
                        <Button label="Zapisz" @click="updateMapGroupingLocked"/>
                    </div>
                    <div v-if="isGroupingLocked" class="mt-2 text-red-600 text-sm">
                        Gracze nie mogą grupować się w tej lokacji.
                    </div>
                    <div v-else class="mt-2 text-green-600 text-sm">
                        Grupowanie dozwolone.
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold mb-2">Mapa wewnętrzna</h3>
                    <div class="flex items-center gap-2">
                        <InputSwitch v-model="isInternal" />
                        <Button label="Zapisz" @click="updateMapInternal"/>
                    </div>
                    <div v-if="isInternal" class="mt-2 text-orange-600 text-sm">
                        Mapa administracyjna/testowa — pomijana w solucjach questów i zwojach szukających herosa.
                    </div>
                    <div v-else class="mt-2 text-green-600 text-sm">
                        Zwykła mapa dla graczy.
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-4 mb-4">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 class="text-xl font-bold">Tła mapy</h2>
                    <div class="text-sm text-surface-500 dark:text-surface-400">
                        Wybierz tło okna walki dla każdej wersji klienta.
                    </div>
                </div>

                <div class="grid min-w-0 flex-1 grid-cols-1 gap-3 md:grid-cols-3 xl:max-w-4xl">
                    <div v-for="background in backgroundPickers" :key="background.field" class="flex min-w-0 items-center gap-3 rounded-md border border-surface-200 bg-surface-50 p-2 dark:border-surface-700 dark:bg-surface-900/40">
                        <img
                            v-if="background.preview"
                            :src="background.preview.src"
                            :alt="background.preview.file"
                            class="h-14 w-24 shrink-0 rounded object-contain"
                        />
                        <div v-else class="flex h-14 w-24 shrink-0 items-center justify-center rounded bg-surface-100 text-xs text-surface-500 dark:bg-surface-800 dark:text-surface-400">
                            Brak
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">{{ background.label }}</div>
                            <div class="truncate text-sm font-semibold">{{ background.selected ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <Button
                    :label="backgroundSectionToggleLabel"
                    :icon="backgroundSectionToggleIcon"
                    severity="secondary"
                    outlined
                    @click="toggleBackgroundSection"
                />
            </div>

            <div v-if="isBackgroundSectionExpanded" class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2 2xl:grid-cols-3">
                <section v-for="picker in backgroundPickers" :key="picker.field" class="min-w-0">
                    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-semibold">{{ picker.label }}</h3>
                            <div class="text-sm text-surface-500 dark:text-surface-400">
                                Wybrane: <strong>{{ picker.selected ?? '-' }}</strong>
                            </div>
                        </div>
                        <Button label="Zapisz tło" icon="pi pi-save" :disabled="!picker.selected" @click="updateMapBackground(picker.field)" />
                    </div>

                    <div class="mb-4 overflow-hidden rounded-md border border-surface-200 bg-surface-50 dark:border-surface-700 dark:bg-surface-900/40">
                        <img
                            v-if="picker.preview"
                            :src="picker.preview.src"
                            :alt="picker.preview.file"
                            class="h-56 w-full object-contain md:h-72"
                        />
                        <div v-else class="flex h-56 items-center justify-center text-surface-500 dark:text-surface-400 md:h-72">
                            Brak wybranego tła
                        </div>
                    </div>

                    <div class="background-picker-grid">
                        <button
                            v-for="background in picker.options"
                            :key="background.file"
                            type="button"
                            class="background-picker-option"
                            :class="{ 'is-selected': picker.selected === background.file }"
                            :aria-pressed="picker.selected === background.file"
                            @click="selectedBackgrounds[picker.field] = background.file"
                        >
                            <img :src="background.src" alt="" loading="lazy" class="background-picker-image" />
                            <span class="background-picker-label">{{ background.file }}</span>
                        </button>
                    </div>
                </section>
            </div>
        </div>

        <ReplaceMapImageModal v-model:visible="showReplaceImageModal" :map="map" />
    </div>
</template>

<style scoped>
.background-picker-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 0.75rem;
    max-height: 34rem;
    overflow: auto;
    padding-right: 0.25rem;
}

.background-picker-option {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 0.5rem;
    border: 2px solid rgb(226 232 240);
    border-radius: 8px;
    background: rgb(248 250 252);
    padding: 0.5rem;
    text-align: left;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
}

.background-picker-option:hover {
    border-color: rgb(56 189 248);
    box-shadow: 0 8px 20px rgb(15 23 42 / 12%);
    transform: translateY(-1px);
}

.background-picker-option.is-selected {
    border-color: rgb(14 165 233);
    box-shadow: 0 0 0 3px rgb(14 165 233 / 20%);
}

.background-picker-image {
    width: 100%;
    height: 8.5rem;
    border-radius: 6px;
    background: rgb(226 232 240);
    object-fit: contain;
}

.background-picker-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.875rem;
    font-weight: 600;
    color: rgb(30 41 59);
}

:global(.app-dark) .background-picker-option {
    border-color: rgb(51 65 85);
    background: rgb(15 23 42 / 45%);
}

:global(.app-dark) .background-picker-option:hover {
    border-color: rgb(56 189 248);
}

:global(.app-dark) .background-picker-image {
    background: rgb(30 41 59);
}

:global(.app-dark) .background-picker-label {
    color: rgb(241 245 249);
}
</style>
