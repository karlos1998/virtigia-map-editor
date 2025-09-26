<script setup lang="ts">
import {ref, computed, onMounted, watch} from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useToast } from 'primevue';
import { MapResource } from '@/Resources/Map.resource';
import ReplaceMapImageModal from '@/Pages/Map/Modals/ReplaceMapImageModal.vue';

const props = defineProps<{
    map: MapResource;
    pvpTypeList: { value: number; label: string }[];
    respawnPoints: { id: number; map_id: number; map_name: string; x: number; y: number }[];
}>();

// Form variables
const mapName = ref('');
const selectedPvpType = ref<number | null>(null);
const selectedRespawnPointId = ref<number | null>(null);
const toast = useToast();
const showReplaceImageModal = ref(false);

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

const selectedBattleground = ref<string | null>(props.map.battleground ?? null);
const selectedBattlegroundObj = ref<{ file: string; src: string } | null>(null);
const selectedBattleground2 = ref<string | null>(props.map.battleground2 ?? null);
const selectedBattleground2Obj = ref<{ file: string; src: string } | null>(null);

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
    selectedBattleground.value = props.map.battleground ?? null;
    selectedBattlegroundObj.value = backgroundOptions.find(b => b.file === selectedBattleground.value) ?? null;
    selectedBattleground2.value = props.map.battleground2 ?? null;
    selectedBattleground2Obj.value = backgroundClassicOptions.find(b => b.file === selectedBattleground2.value) ?? null;
});

watch(selectedBattlegroundObj, (newVal) => {
    selectedBattleground.value = newVal ? newVal.file : null;
});
watch(selectedBattleground2Obj, (newVal) => {
    selectedBattleground2.value = newVal ? newVal.file : null;
});

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

// Update map battleground
const updateMapBattleground = () => {
    router.patch(route('maps.update.battleground', props.map.id), {
        battleground: selectedBattleground.value
    }, {
        onSuccess: () => {
            toast.add({severity: 'success', summary: 'Sukces', detail: 'Tło walki zostało zaktualizowane', life: 3000});
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: 'Nie udało się zaktualizować tła walki',
                life: 3000
            });
        }
    });
};
const updateMapBattleground2 = () => {
    router.patch(route('maps.update.battleground2', props.map.id), {
        battleground2: selectedBattleground2.value
    }, {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Sukces',
                detail: 'Grafika mapy 2 została zaktualizowana',
                life: 3000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: 'Nie udało się zaktualizować grafiki mapy 2',
                life: 3000
            });
        }
    });
};
</script>

<template>
    <div class="card p-4 mb-4">
        <h2 class="text-xl font-bold mb-4">Informacje o mapie</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Map Image -->
            <div>
                <h3 class="font-semibold mb-2">Grafika mapy</h3>
                <div class="flex items-center mb-2">
                    <Button label="Podmień grafikę" icon="pi pi-image" @click="showReplaceImageModal = true" class="w-full" />
                </div>

                <div>
                    <Dropdown v-model="selectedBattlegroundObj" :options="backgroundOptions" optionLabel="file"
                              class="w-full">
                        <template #option="{option}">
                            <div class="flex items-center gap-2">
                                <img :src="option.src" class="w-24 h-12 object-contain"/>
                                <div class="text-sm">{{ option.file }}</div>
                            </div>
                        </template>
                        <template #value="{value}">
                            <div class="flex items-center gap-2">
                                <img v-if="value" :src="value.src" class="w-28 h-14 object-contain"/>
                                <div class="text-sm">{{ value ? value.file : '-' }}</div>
                            </div>
                        </template>
                    </Dropdown>

                    <div class="flex items-center mt-2">
                        <div class="text-sm text-gray-600 mr-4"><strong>Wybrane tło:</strong>
                            {{ selectedBattleground ?? '-' }}
                        </div>
                        <Button label="Zapisz" @click="updateMapBattleground"/>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="font-semibold mb-2">Grafika mapy 2</h3>
                <div>
                    <Dropdown v-model="selectedBattleground2Obj" :options="backgroundClassicOptions" optionLabel="file"
                              class="w-full">
                        <template #option="{option}">
                            <div class="flex items-center gap-2">
                                <img :src="option.src" class="w-24 h-12 object-contain"/>
                                <div class="text-sm">{{ option.file }}</div>
                            </div>
                        </template>
                        <template #value="{value}">
                            <div class="flex items-center gap-2">
                                <img v-if="value" :src="value.src" class="w-28 h-14 object-contain"/>
                                <div class="text-sm">{{ value ? value.file : '-' }}</div>
                            </div>
                        </template>
                    </Dropdown>
                    <div class="flex items-center mt-2">
                        <div class="text-sm text-gray-600 mr-4"><strong>Wybrane tło:</strong>
                            {{ selectedBattleground2 ?? '-' }}
                        </div>
                        <Button label="Zapisz" @click="updateMapBattleground2"/>
                    </div>
                </div>
            </div>

            <!-- Map Name -->
            <div>
                <h3 class="font-semibold mb-2">Nazwa mapy</h3>
                <div class="flex items-center">
                    <InputText v-model="mapName" class="w-full mr-2" />
                    <Button label="Zapisz" @click="updateMapName" />
                </div>
            </div>

            <!-- PvP Type -->
            <div>
                <h3 class="font-semibold mb-2">Typ PvP</h3>
                <div class="flex items-center">
                    <Dropdown v-model="selectedPvpType" :options="pvpTypeList" optionLabel="label" optionValue="value" class="w-full mr-2" />
                    <Button label="Zapisz" @click="updateMapPvp" />
                </div>
            </div>

            <!-- Respawn Point -->
            <div>
                <h3 class="font-semibold mb-2">Punkt odrodzenia</h3>
                <div class="flex items-center">
                    <Dropdown v-model="selectedRespawnPointId" :options="formattedRespawnPoints" optionLabel="formatted" :optionValue="'id'" class="w-full mr-2" />
                    <Button label="Zapisz" @click="updateMapRespawnPoint" />
                </div>
            </div>
        </div>

        <!-- Replace Map Image Modal -->
        <ReplaceMapImageModal v-model:visible="showReplaceImageModal" :map="map" />
    </div>
</template>
