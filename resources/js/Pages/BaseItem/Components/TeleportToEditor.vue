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
            </div>
        </div>

        <div v-else class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
            <p class="text-blue-700">
                <i class="pi pi-info-circle mr-2"></i>
                Ten przedmiot nie ma skonfigurowanego teleportu
            </p>
        </div>

        <!-- Teleport configuration form -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="field">
                <label for="mapId" class="block font-semibold mb-2">
                    ID Mapy <span class="text-red-500">*</span>
                </label>
                <InputNumber
                    v-model="mapId"
                    :min="0"
                    placeholder="Wprowadź ID mapy docelowej"
                    class="w-full"
                    :useGrouping="false"
                />
                <small class="text-gray-500">Identyfikator mapy, do której ma prowadzić teleport</small>
            </div>

            <div class="field">
                <label for="mapName" class="block font-semibold mb-2">
                    Nazwa Mapy (opcjonalna)
                </label>
                <InputText
                    id="mapName"
                    v-model="mapName"
                    placeholder="Wprowadź nazwę mapy"
                    class="w-full"
                />
                <small class="text-gray-500">Czytelna nazwa mapy dla gracza</small>
            </div>

            <div class="field">
                <label for="xCoord" class="block font-semibold mb-2">
                    Pozycja X <span class="text-red-500">*</span>
                </label>
                <InputNumber
                    v-model="xCoord"
                    :min="0"
                    placeholder="Wprowadź współrzędną X"
                    class="w-full"
                    :useGrouping="false"
                />
                <small class="text-gray-500">Współrzędna X na mapie docelowej</small>
            </div>

            <div class="field">
                <label for="yCoord" class="block font-semibold mb-2">
                    Pozycja Y <span class="text-red-500">*</span>
                </label>
                <InputNumber
                    v-model="yCoord"
                    :min="0"
                    placeholder="Wprowadź współrzędną Y"
                    class="w-full"
                    :useGrouping="false"
                />
                <small class="text-gray-500">Współrzędna Y na mapie docelowej</small>
            </div>
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
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

// Props to receive and update attributes object directly
const props = defineProps<{
    attributes: any; // The attributes object
}>();

const emit = defineEmits(['update:attributes']);

// Local state for teleport fields
// teleportTo structure: [mapId, x, y, mapName?]
const mapId = ref<number | null>(null);
const xCoord = ref<number | null>(null);
const yCoord = ref<number | null>(null);
const mapName = ref<string>('');

// Initialize values from existing teleportTo attribute
const initializeFromAttributes = () => {
    const teleportTo = props.attributes?.teleportTo;
    if (teleportTo && Array.isArray(teleportTo)) {
        mapId.value = teleportTo[0] ?? null;
        xCoord.value = teleportTo[1] ?? null;
        yCoord.value = teleportTo[2] ?? null;
        mapName.value = teleportTo[3] ?? '';
    }
};

// Initialize on mount
initializeFromAttributes();

// Watch for external changes to attributes
watch(() => props.attributes?.teleportTo, () => {
    initializeFromAttributes();
}, { deep: true });

// Auto-save when teleport fields change and are valid
watch([mapId, xCoord, yCoord, mapName], () => {
    console.log('=== TELEPORT AUTO-SAVE TRIGGERED ===');
    console.log('mapId:', mapId.value);
    console.log('xCoord:', xCoord.value);
    console.log('yCoord:', yCoord.value);
    console.log('mapName:', mapName.value);
    console.log('isValid:', isValid.value);

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

        console.log('teleportArray:', teleportArray);
        console.log('props.attributes BEFORE:', JSON.parse(JSON.stringify(props.attributes || {})));

        // Update the attributes
        const updatedAttributes = {
            ...props.attributes,
            teleportTo: teleportArray
        };

        console.log('updatedAttributes:', JSON.parse(JSON.stringify(updatedAttributes)));
        console.log('=== TELEPORT AUTO-SAVE END, EMITTING ===');

        emit('update:attributes', updatedAttributes);
    } else if (mapId.value === null && xCoord.value === null && yCoord.value === null) {
        // If all fields are empty, remove teleportTo
        console.log('All fields empty, removing teleportTo');
        const updatedAttributes = { ...props.attributes };
        delete updatedAttributes.teleportTo;
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

// Remove teleport configuration
const removeTeleport = () => {
    // Remove teleportTo from attributes
    const updatedAttributes = { ...props.attributes };
    delete updatedAttributes.teleportTo;

    emit('update:attributes', updatedAttributes);

    // Reset local state
    initializeFromAttributes();
};
</script>

<style scoped>
.field {
    display: flex;
    flex-direction: column;
}
</style>
