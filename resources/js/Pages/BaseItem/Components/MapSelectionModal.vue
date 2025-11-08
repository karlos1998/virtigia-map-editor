<script setup lang="ts">
import { inject, onMounted, Ref, ref } from 'vue';
import { debounce } from 'chart.js/helpers';
import axios from 'axios';
import { route } from 'ziggy-js';
import { MapResource } from '@/Resources/Map.resource';
import { DynamicDialogInstance } from 'primevue/dynamicdialogoptions';
import Button from 'primevue/button';
import AutoComplete from 'primevue/autocomplete';

// Dialog reference injected by PrimeVue
const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        teleportData?: {
            mapId: number;
            x: number;
            y: number;
            mapName?: string;
        }
    }
}> | null>('dialogRef');

const scale = ref(1);

// Selected map and dropdown options
const selectedMap = ref<MapResource | null>(null);
const dropdownMaps = ref<MapResource[]>([]);

// Tracker position for mouse hover
const trackerPosition = ref({ x: 0, y: 0 });

// Current teleport data
const teleportData = ref<{
    mapId: number | null;
    x: number | null;
    y: number | null;
    mapName?: string;
}>({
    mapId: null,
    x: null,
    y: null,
    mapName: ''
});

// Track if data has been changed
const changed = ref(false);

// Initialize data from dialog
onMounted(() => {
    if (!dialogRef.value) return;
    if (dialogRef.value.data?.teleportData) {
        teleportData.value = { ...dialogRef.value.data.teleportData };
        // Try to load the map if mapId is provided
        if (teleportData.value.mapId) {
            loadMapById(teleportData.value.mapId);
        }
    }
});

// Load map by ID
const loadMapById = async (mapId: number) => {
    try {
        const response = await axios.get(route('maps.data', { map: mapId }));
        selectedMap.value = response.data;
    } catch (error) {
        console.error('Failed to load map:', error);
    }
};

// Search maps with debounce
const searchOptions = debounce((event: any) => {
    axios.get(route('maps.search', { search: event[0].query }))
        .then(response => {
            dropdownMaps.value = response.data;
        });
}, 100);

// Handle mouse movement over map
const handleMouseMove = (event: MouseEvent) => {
    if (event.target !== event.currentTarget) {
        trackerPosition.value = { x: -32, y: -32 };
        return;
    }
    trackerPosition.value = {
        x: (event.offsetX / scale.value / 32) | 0,
        y: (event.offsetY / scale.value / 32) | 0
    };
};

// Handle click on map to select coordinates
const handleMapClick = () => {
    if (!selectedMap.value) return;

    teleportData.value = {
        x: trackerPosition.value.x,
        y: trackerPosition.value.y,
        mapId: selectedMap.value.id,
        mapName: selectedMap.value.name
    };
    changed.value = true;
};

// Reset to original data
const reset = () => {
    if (!dialogRef.value) return;
    if (dialogRef.value.data?.teleportData) {
        teleportData.value = { ...dialogRef.value.data.teleportData };
        if (teleportData.value.mapId) {
            loadMapById(teleportData.value.mapId);
        }
    } else {
        teleportData.value = {
            mapId: null,
            x: null,
            y: null,
            mapName: ''
        };
        selectedMap.value = null;
    }
    changed.value = false;
};

// Save and close dialog
const save = () => {
    if (!dialogRef.value) return;
    dialogRef.value.close({
        teleportData: teleportData.value
    });
};

// Cancel and close dialog
const cancel = () => {
    if (!dialogRef.value) return;
    dialogRef.value.close();
};
</script>

<template>
    <div class="map-selection-modal">
        <!-- Current teleport info - Enhanced design -->
        <div v-if="teleportData.mapId && teleportData.x !== null && teleportData.y !== null"
             class="teleport-info-card selected">
            <div class="info-icon">
                <i class="pi pi-map-marker"></i>
            </div>
            <div class="info-content">
                <h4 class="info-title">Wybrana lokalizacja teleportu</h4>
                <div class="info-details">
                    <span class="detail-badge map-id">ID: {{ teleportData.mapId }}</span>
                    <span class="detail-badge map-name">{{ teleportData.mapName }}</span>
                    <span class="detail-badge coords">X: {{ teleportData.x }}, Y: {{ teleportData.y }}</span>
                </div>

                <!-- Action buttons when coordinates are selected -->
                <div v-if="changed" class="action-buttons">
                    <Button
                        type="button"
                        label="Anuluj"
                        severity="secondary"
                        outlined
                        icon="pi pi-times"
                        size="small"
                        @click="cancel"
                    />
                    <Button
                        type="button"
                        label="Zmień wybór"
                        severity="info"
                        outlined
                        icon="pi pi-refresh"
                        size="small"
                        @click="reset"
                    />
                    <Button
                        type="button"
                        label="Zapisz lokalizację"
                        icon="pi pi-check"
                        size="small"
                        @click="save"
                    />
                </div>
            </div>
        </div>
        <div v-else class="teleport-info-card empty">
            <div class="info-icon">
                <i class="pi pi-info-circle"></i>
            </div>
            <div class="info-content">
                <h4 class="info-title">Wybierz lokalizację</h4>
                <p class="info-description">Wyszukaj mapę, a następnie kliknij na niej, aby ustawić koordynaty
                    teleportu</p>
            </div>
        </div>

        <!-- Map search and selection -->
        <div v-if="!changed" class="map-selection-section">
            <div class="search-section">
                <label class="search-label">
                    <i class="pi pi-search"></i>
                    Wyszukaj mapę docelową
                </label>
                <AutoComplete
                    v-model="selectedMap"
                    :suggestions="dropdownMaps"
                    optionLabel="name"
                    placeholder="Zacznij pisać nazwę mapy..."
                    class="w-full p-0"
                    @complete="searchOptions"
                    fluid
                >
                    <template #option="slotProps">
                        <div class="autocomplete-option">
                            <span class="option-id">#{{ slotProps.option.id }}</span>
                            <span class="option-name">{{ slotProps.option.name }}</span>
                        </div>
                    </template>
                </AutoComplete>
                <small class="search-hint">
                    <i class="pi pi-info-circle"></i>
                    Wprowadź minimum 2 znaki, aby rozpocząć wyszukiwanie
                </small>
            </div>

            <!-- Map display -->
            <div v-if="selectedMap && selectedMap.id > 0" class="map-display-section">
                <div class="map-header">
                    <div class="map-info">
                        <h4 class="map-title">
                            <i class="pi pi-map"></i>
                            {{ selectedMap.name }}
                        </h4>
                        <span class="map-dimensions">{{ selectedMap.x }} × {{ selectedMap.y }} kratek</span>
                    </div>
                    <div class="map-instructions">
                        <i class="pi pi-hand-pointer"></i>
                        <span>Kliknij na mapie, aby wybrać punkt teleportacji</span>
                    </div>
                </div>

                <div class="map-wrapper">
                    <div
                        class="map-container"
                        @mousemove="handleMouseMove"
                        :style="{
                            backgroundImage: `url(${selectedMap.src})`,
                            width: `${selectedMap.x * 32 * scale}px`,
                            height: `${selectedMap.y * 32 * scale}px`,
                        }"
                        @click.self="handleMapClick"
                    >
                        <!-- Mouse tracker with enhanced styling -->
                        <div
                            class="mouse-tracker"
                            :style="{
                                width: `${32 * scale}px`,
                                height: `${32 * scale}px`,
                                top: `${trackerPosition.y * 32 * scale}px`,
                                left: `${trackerPosition.x * 32 * scale}px`,
                                display: trackerPosition.x >= 0 && trackerPosition.y >= 0 ? 'block' : 'none'
                            }"
                        >
                            <div class="tracker-crosshair"></div>
                            <div class="tracker-coords">
                                {{ trackerPosition.x }}, {{ trackerPosition.y }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state when no map selected -->
            <div v-else-if="!selectedMap" class="empty-state">
                <div class="empty-icon">
                    <i class="pi pi-map"></i>
                </div>
                <h4>Nie wybrano mapy</h4>
                <p>Użyj wyszukiwarki powyżej, aby znaleźć i wybrać mapę</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Main container */
.map-selection-modal {
    //min-height: 400px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Teleport info card - Enhanced */
.teleport-info-card {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    border-radius: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.teleport-info-card.selected {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 2px solid #3b82f6;
}

.teleport-info-card.empty {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border: 2px solid #e5e7eb;
}

.info-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 10px;
    flex-shrink: 0;
}

.teleport-info-card.selected .info-icon {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    font-size: 1.5rem;
}

.teleport-info-card.empty .info-icon {
    background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
    color: white;
    font-size: 1.5rem;
}

.info-content {
    flex: 1;
}

.info-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: #1e293b;
}

.info-description {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
    line-height: 1.5;
}

.info-details {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.detail-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.detail-badge.map-id {
    background: #dbeafe;
    color: #1e40af;
}

.detail-badge.map-name {
    background: #e0e7ff;
    color: #4338ca;
}

.detail-badge.coords {
    background: #dcfce7;
    color: #15803d;
}

/* Action buttons container */
.action-buttons {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    flex-wrap: wrap;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(59, 130, 246, 0.2);
}

/* Map selection section */
.map-selection-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Search section */
.search-section {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.search-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 1rem;
    color: #1e293b;
}

.search-label i {
    color: #3b82f6;
    font-size: 1.125rem;
}

.map-autocomplete {
    width: 50%;
}

.search-hint {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: #64748b;
    font-size: 0.8125rem;
}

.search-hint i {
    font-size: 0.875rem;
}

/* Autocomplete option styling */
.autocomplete-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
}

.option-id {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    padding: 0.25rem 0.5rem;
    background: #e0e7ff;
    color: #4338ca;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.8125rem;
}

.option-name {
    color: #1e293b;
    font-weight: 500;
}

/* Map display section */
.map-display-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.map-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.map-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.map-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
}

.map-title i {
    color: #3b82f6;
}

.map-dimensions {
    color: #64748b;
    font-size: 0.875rem;
}

.map-instructions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #3b82f6;
    font-weight: 500;
    font-size: 0.875rem;
}

.map-instructions i {
    font-size: 1rem;
}

/* Map wrapper */
.map-wrapper {
    overflow: auto;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    background: repeating-conic-gradient(#f1f5f9 0% 25%, #f8fafc 0% 50%) 50% / 20px 20px;
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06);
    max-height: 600px;
}

.map-container {
    position: relative;
    cursor: crosshair;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: top left;
}

/* Enhanced mouse tracker */
.mouse-tracker {
    position: absolute;
    pointer-events: none;
    background: rgba(59, 130, 246, 0.15);
    border: 2px solid #3b82f6;
    border-radius: 4px;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2),
    0 4px 12px rgba(59, 130, 246, 0.3);
    transition: all 0.1s ease;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2),
        0 4px 12px rgba(59, 130, 246, 0.3);
    }
    50% {
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3),
        0 6px 16px rgba(59, 130, 246, 0.4);
    }
}

.tracker-crosshair {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 16px;
    height: 16px;
}

.tracker-crosshair::before,
.tracker-crosshair::after {
    content: '';
    position: absolute;
    background: #3b82f6;
}

.tracker-crosshair::before {
    top: 50%;
    left: 0;
    width: 100%;
    height: 2px;
    transform: translateY(-50%);
}

.tracker-crosshair::after {
    left: 50%;
    top: 0;
    width: 2px;
    height: 100%;
    transform: translateX(-50%);
}

.tracker-coords {
    position: absolute;
    bottom: -24px;
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Empty state */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border-radius: 12px;
    border: 2px dashed #d1d5db;
}

.empty-icon {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    border-radius: 50%;
    margin-bottom: 1rem;
}

.empty-icon i {
    font-size: 2.5rem;
    color: #6b7280;
}

.empty-state h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
}

.empty-state p {
    margin: 0;
    color: #6b7280;
    font-size: 0.9375rem;
}

/* Responsive design */
@media (max-width: 768px) {
    .map-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .action-buttons {
        width: 100%;
    }

    .action-buttons button {
        flex: 1;
    }
}
</style>
