<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useToast } from 'primevue';
import { MapResource } from '@/Resources/Map.resource';

const props = defineProps<{
    map: MapResource;
    pvpTypeList: { value: number; label: string }[];
    respawnPoints: { id: number; map_id: number; map_name: string; x: number; y: number }[];
}>();

// Form variables
const mapName = ref('');
const selectedPvpType = ref(null);
const selectedRespawnPointId = ref(null);
const toast = useToast();

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
</script>

<template>
    <div class="card p-4 mb-4">
        <h2 class="text-xl font-bold mb-4">Informacje o mapie</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
    </div>
</template>
