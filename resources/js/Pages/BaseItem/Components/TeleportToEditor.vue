<template>
        <h3 class="text-xl font-bold mb-4">Edytor Teleportu</h3>

        <div class="mb-4">
            <p class="text-gray-600 mb-4">
                Skonfiguruj teleport dla tego przedmiotu. Teleport określa docelową mapę i pozycję, do której gracz
                zostanie przeniesiony.
            </p>
        </div>

        <!-- Current teleport status -->
        <div v-if="hasTeleport" class="mb-4 p-3 bg-green-50 border border-green-200 rounded">
            <div class="flex items-center justify-between mb-2">
        <span class="font-semibold text-green-700">
          <i class="pi pi-check-circle mr-2"></i>
          Teleport jest skonfigurowany
        </span>
                <Button
                    label="Usuń teleport"
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    size="small"
                    @click="removeTeleport"
                />
            </div>
            <div class="text-sm text-gray-700">
                <p>ID Mapy: <strong>{{ mapId || 'Nie ustawiono' }}</strong></p>
                <p>Pozycja: <strong>X: {{ xCoord || 0 }}, Y: {{ yCoord || 0 }}</strong></p>
                <p v-if="mapName">Nazwa mapy: <strong>{{ mapName }}</strong></p>
                <p v-if="cooldownTime">Czas odnowienia: <strong>{{ cooldownTime }} min</strong></p>
            </div>
        </div>

        <div v-else class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
            <p class="text-blue-700">
                <i class="pi pi-info-circle mr-2"></i>
                Ten przedmiot nie ma skonfigurowanego teleportu
            </p>
        </div>

    <!-- Teleport configuration -->
    <div class="mb-4">
        <Button
            :label="hasTeleport ? 'Zmień lokalizację teleportu' : 'Ustaw lokalizację teleportu'"
            icon="pi pi-map-marker"
            @click="openMapSelectionModal"
            class="w-full"
        />
        <small class="text-gray-500 block mt-2">Kliknij, aby wybrać mapę i koordynaty docelowe</small>
    </div>

    <!-- Cooldown time configuration -->
    <div class="field">
        <label for="cooldownTime" class="block font-semibold mb-2">
            Czas Odnowienia (opcjonalny)
        </label>
        <InputNumber
            v-model="cooldownTime"
            :min="0"
            placeholder="Czas w minutach"
            class="w-full"
            :useGrouping="false"
            suffix=" min"
        />
        <small class="text-gray-500">Czas w minutach przed ponownym użyciem teleportu</small>
        </div>

        <!-- Validation message -->
        <div v-if="!isValid && (mapId !== null || xCoord !== null || yCoord !== null)"
             class="mt-3 p-3 bg-red-50 border border-red-200 rounded">
            <p class="text-red-700 text-sm">
                <i class="pi pi-exclamation-triangle mr-2"></i>
                Wypełnij wymagane pola: ID Mapy, Pozycja X i Pozycja Y
            </p>
        </div>

        <!-- Auto-save info -->
        <div v-if="isValid" class="mt-3 p-3 bg-green-50 border border-green-200 rounded">
            <p class="text-green-700 text-sm">
                <i class="pi pi-check-circle mr-2"></i> Teleport jest poprawnie skonfigurowany. Pamiętaj aby zapisać
                zmiany głównym przyciskiem "Zapisz" na górze strony.
            </p>
        </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import { useDialog } from 'primevue/usedialog';
import { DynamicDialogCloseOptions } from 'primevue/dynamicdialogoptions';
import MapSelectionModal from './MapSelectionModal.vue';

// Props to receive and update attributes object directly
const props = defineProps<{
    attributes: any; // The attributes object
}>();

const emit = defineEmits(['update:attributes']);

// PrimeVue dialog service
const primeDialog = useDialog();

// Local state for teleport fields
// teleportTo structure: [mapId, x, y, mapName?]
const mapId = ref<number | null>(null);
const xCoord = ref<number | null>(null);
const yCoord = ref<number | null>(null);
const mapName = ref<string>('');
// cooldownTime is stored separately in attributes
const cooldownTime = ref<number | null>(null);

// Initialize values from existing teleportTo attribute
const initializeFromAttributes = () => {
    const teleportTo = props.attributes?.teleportTo;
    if (teleportTo && Array.isArray(teleportTo)) {
        mapId.value = teleportTo[0] ?? null;
        xCoord.value = teleportTo[1] ?? null;
        yCoord.value = teleportTo[2] ?? null;
        mapName.value = teleportTo[3] ?? '';
    }

    // Initialize cooldownTime from attributes (stored as array [number])
    const cooldown = props.attributes?.cooldownTime;
    if (cooldown && Array.isArray(cooldown) && cooldown.length > 0) {
        cooldownTime.value = cooldown[0] ?? null;
    } else {
        cooldownTime.value = null;
    }
};

// Initialize on mount
initializeFromAttributes();

// Watch for external changes to attributes
watch(() => props.attributes?.teleportTo, () => {
    initializeFromAttributes();
}, { deep: true });

watch(() => props.attributes?.cooldownTime, (newVal) => {
    if (newVal && Array.isArray(newVal) && newVal.length > 0) {
        cooldownTime.value = newVal[0] ?? null;
    } else {
        cooldownTime.value = null;
    }
});

// Auto-save when teleport fields change and are valid
watch([mapId, xCoord, yCoord, mapName, cooldownTime], () => {

    // Only auto-save if valid (all required fields are filled)
    if (isValid.value) {
        // Build the teleportTo array
        const teleportArray: any[] = [
            mapId.value,
            xCoord.value,
            yCoord.value
        ];

        // Add map name if provided
        if (mapName.value && mapName.value.trim() !== '') {
            teleportArray.push(mapName.value.trim());
        }

        // Update the attributes
        const updatedAttributes = {
            ...props.attributes,
            teleportTo: teleportArray
        };

        // Add or remove cooldownTime (stored as array [number])
        if (typeof cooldownTime.value === 'number' && cooldownTime.value > 0) {
            updatedAttributes.cooldownTime = [cooldownTime.value];
        } else {
            delete updatedAttributes.cooldownTime;
        }

        emit('update:attributes', updatedAttributes);
    } else if (mapId.value === null && xCoord.value === null && yCoord.value === null) {
        // If all fields are empty, remove teleportTo
        const updatedAttributes = { ...props.attributes };
        delete updatedAttributes.teleportTo;

        // Also handle cooldownTime when teleport is removed
        if (typeof cooldownTime.value === 'number' && cooldownTime.value > 0) {
            updatedAttributes.cooldownTime = [cooldownTime.value];
        } else {
            delete updatedAttributes.cooldownTime;
        }

        emit('update:attributes', updatedAttributes);
    } else {
        // Partial data - just update cooldownTime if changed
        const updatedAttributes = { ...props.attributes };

        if (typeof cooldownTime.value === 'number' && cooldownTime.value > 0) {
            updatedAttributes.cooldownTime = [cooldownTime.value];
        } else {
            delete updatedAttributes.cooldownTime;
        }

        emit('update:attributes', updatedAttributes);
    }
});

// Check if teleport is configured
const hasTeleport = computed(() => {
    return typeof mapId.value === 'number' &&
        typeof xCoord.value === 'number' &&
        typeof yCoord.value === 'number';
});

// Validate required fields - numbers (including 0) are valid
const isValid = computed(() => {
    return typeof mapId.value === 'number' &&
        typeof xCoord.value === 'number' &&
        typeof yCoord.value === 'number';
});

// Open map selection modal
const openMapSelectionModal = () => {
    primeDialog.open(MapSelectionModal, {
        props: {
            header: 'Wybierz lokalizację teleportu',
            modal: true,
            style: {
                width: '90vw',
                maxWidth: '1200px'
            },
            breakpoints: {
                '960px': '95vw',
                '640px': '100vw'
            }
        },
        data: {
            teleportData: hasTeleport.value ? {
                mapId: mapId.value,
                x: xCoord.value,
                y: yCoord.value,
                mapName: mapName.value
            } : undefined
        },
        onClose(closeOptions: DynamicDialogCloseOptions & {
            data?: {
                teleportData?: {
                    mapId: number;
                    x: number;
                    y: number;
                    mapName?: string;
                }
            }
        }) {
            if (closeOptions.data?.teleportData) {
                const { mapId: newMapId, x, y, mapName: newMapName } = closeOptions.data.teleportData;

                // Update local state
                mapId.value = newMapId;
                xCoord.value = x;
                yCoord.value = y;
                mapName.value = newMapName || '';
            }
        }
    });
};

// Remove teleport configuration
const removeTeleport = () => {
    // Remove teleportTo from attributes
    const updatedAttributes = { ...props.attributes };
    delete updatedAttributes.teleportTo;
    delete updatedAttributes.cooldownTime;

    emit('update:attributes', updatedAttributes);

    // Clear local state explicitly
    mapId.value = null;
    xCoord.value = null;
    yCoord.value = null;
    mapName.value = '';
    cooldownTime.value = null;
};
</script>

<style scoped>
.field {
    display: flex;
    flex-direction: column;
}
</style>
