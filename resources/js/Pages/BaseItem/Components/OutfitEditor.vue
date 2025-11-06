<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import OutfitBrowserDialog from './OutfitBrowserDialog.vue';

// Props to receive and update attributes object directly
const props = defineProps<{
    attributes: any; // The attributes object
}>();

const emit = defineEmits(['update:attributes']);

// Local state for outfit fields
const useOutfitTime = ref<number>(0);
const useOutfitSrc = ref<string>('');
const cooldownTime = ref<number | null>(null);
const description = ref<string>('');

// Browser dialog state
const showBrowserDialog = ref(false);

// Initialize values from existing attributes
const initializeFromAttributes = () => {
    const useOutfit = props.attributes?.useOutfit;
    if (useOutfit && typeof useOutfit === 'object') {
        useOutfitTime.value = useOutfit.time ?? 0;
        useOutfitSrc.value = useOutfit.src ?? '';
    } else {
        useOutfitTime.value = 0;
        useOutfitSrc.value = '';
    }

    // Initialize cooldownTime from attributes (stored as array [number])
    const cooldown = props.attributes?.cooldownTime;
    if (cooldown && Array.isArray(cooldown) && cooldown.length > 0) {
        cooldownTime.value = cooldown[0] ?? null;
    } else {
        cooldownTime.value = null;
    }

    // Initialize description
    description.value = props.attributes?.description ?? '';
};

// Initialize on mount
initializeFromAttributes();

// Watch for external changes to attributes
watch(() => props.attributes, () => {
    initializeFromAttributes();
}, { deep: true });

// Check if outfit is configured
const hasOutfit = computed(() => {
    return useOutfitSrc.value !== '' || useOutfitTime.value > 0;
});

// Validate outfit configuration
const isValid = computed(() => {
    // If outfit src is provided, it's valid
    if (useOutfitSrc.value && useOutfitSrc.value.trim() !== '') {
        return true;
    }
    // If no outfit src but no outfit data at all, it's also valid (empty state)
    if (!hasOutfit.value) {
        return true;
    }
    return false;
});

// Auto-save when outfit fields change
watch([useOutfitTime, useOutfitSrc, cooldownTime, description], () => {
    const updatedAttributes = { ...props.attributes };

    // Handle useOutfit
    if (useOutfitSrc.value && useOutfitSrc.value.trim() !== '') {
        updatedAttributes.useOutfit = {
            time: useOutfitTime.value,
            src: useOutfitSrc.value.trim()
        };
    } else {
        delete updatedAttributes.useOutfit;
    }

    // Handle cooldownTime (stored as array [number])
    if (typeof cooldownTime.value === 'number' && cooldownTime.value > 0) {
        updatedAttributes.cooldownTime = [cooldownTime.value];
    } else {
        // Don't delete cooldownTime if it exists elsewhere (e.g., from teleport)
        // Only delete if we're managing it here and it's null/0
        if (cooldownTime.value === null || cooldownTime.value === 0) {
            delete updatedAttributes.cooldownTime;
        }
    }

    // Handle description
    if (description.value && description.value.trim() !== '') {
        updatedAttributes.description = description.value.trim();
    } else {
        delete updatedAttributes.description;
    }

    emit('update:attributes', updatedAttributes);
});

// Remove outfit configuration
const removeOutfit = () => {
    const updatedAttributes = { ...props.attributes };
    delete updatedAttributes.useOutfit;
    delete updatedAttributes.cooldownTime;
    delete updatedAttributes.description;

    emit('update:attributes', updatedAttributes);

    // Clear local state
    useOutfitTime.value = 0;
    useOutfitSrc.value = '';
    cooldownTime.value = null;
    description.value = '';
};

// Preview the outfit image
const previewImageSrc = computed(() => {
    if (useOutfitSrc.value && useOutfitSrc.value.trim() !== '') {
        // Construct full URL using the same pattern as API response
        return `/s3/img/outfits/${useOutfitSrc.value.trim()}`;
    }
    return '';
});

// Open browser dialog
const openBrowser = () => {
    showBrowserDialog.value = true;
};

// Handle outfit selection from dialog
const handleOutfitSelected = (relativePath: string) => {
    useOutfitSrc.value = relativePath;
};
</script>

<template>
    <div>
        <h3 class="text-xl font-bold mb-4">Edytor Stroju (Outfit)</h3>

        <div class="mb-4">
            <p class="text-gray-600 mb-4">
                Skonfiguruj strój dla tego przedmiotu. Strój określa wygląd gracza po użyciu przedmiotu.
            </p>
        </div>

        <!-- Current outfit status -->
        <div v-if="hasOutfit" class="mb-4 p-3 bg-green-50 border border-green-200 rounded">
            <div class="flex items-center justify-between mb-2">
                <span class="font-semibold text-green-700">
                    <i class="pi pi-check-circle mr-2"></i>
                    Strój jest skonfigurowany
                </span>
                <Button
                    label="Usuń strój"
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    size="small"
                    @click="removeOutfit"
                />
            </div>
            <div class="text-sm text-gray-700">
                <p>Źródło grafiki: <strong>{{ useOutfitSrc || 'Nie ustawiono' }}</strong></p>
                <p>Czas trwania: <strong>{{ useOutfitTime }} min</strong></p>
                <p v-if="cooldownTime">Czas odnowienia: <strong>{{ cooldownTime }} min</strong></p>
                <p v-if="description">Opis: <strong>{{ description }}</strong></p>
            </div>
        </div>

        <div v-else class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
            <p class="text-blue-700">
                <i class="pi pi-info-circle mr-2"></i>
                Ten przedmiot nie ma skonfigurowanego stroju
            </p>
        </div>

        <!-- Outfit configuration -->
        <div class="space-y-4">
            <!-- Outfit source (image filename) -->
            <div class="field">
                <label for="outfitSrc" class="block font-semibold mb-2">
                    Źródło Grafiki (Plik GIF) <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                    <InputText
                        v-model="useOutfitSrc"
                        placeholder="np. grzyb.gif"
                        class="flex-1"
                    />
                    <Button
                        label="Przeglądaj"
                        icon="pi pi-folder-open"
                        @click="openBrowser"
                        severity="secondary"
                    />
                </div>
                <small class="text-gray-500">Nazwa pliku graficznego stroju (np. grzyb.gif)</small>
            </div>

            <!-- Preview image -->
            <div v-if="previewImageSrc" class="field">
                <label class="block font-semibold mb-2">Podgląd:</label>
                <img
                    :src="previewImageSrc"
                    alt="Podgląd stroju"
                    class="h-32 w-32 object-fill border border-gray-300 rounded"
                    @error="(e: Event) => ((e.target as HTMLImageElement).style.display = 'none')"
                />
            </div>

            <!-- Outfit time (duration) -->
            <div class="field">
                <label for="outfitTime" class="block font-semibold mb-2">
                    Czas Trwania (minuty)
                </label>
                <InputNumber
                    v-model="useOutfitTime"
                    :min="0"
                    placeholder="Czas w minutach"
                    class="w-full"
                    :useGrouping="false"
                    suffix=" min"
                />
                <small class="text-gray-500">Czas przez który strój jest aktywny (0 = permanentny)</small>
            </div>

            <!-- Cooldown time -->
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
                <small class="text-gray-500">Czas w minutach przed ponownym użyciem stroju</small>
            </div>

            <!-- Description -->
            <div class="field">
                <label for="description" class="block font-semibold mb-2">
                    Opis Stroju (opcjonalny)
                </label>
                <Textarea
                    v-model="description"
                    placeholder="Np. Strój Ar-Karlos"
                    class="w-full"
                    :rows="3"
                />
                <small class="text-gray-500">Opis wyświetlany w grze</small>
            </div>
        </div>

        <!-- Validation message -->
        <div v-if="!isValid" class="mt-3 p-3 bg-red-50 border border-red-200 rounded">
            <p class="text-red-700 text-sm">
                <i class="pi pi-exclamation-triangle mr-2"></i>
                Wypełnij wymagane pole: Źródło Grafiki
            </p>
        </div>

        <!-- Success message -->
        <div v-if="isValid && hasOutfit" class="mt-3 p-3 bg-green-50 border border-green-200 rounded">
            <p class="text-green-700 text-sm">
                <i class="pi pi-check-circle mr-2"></i>
                Strój jest poprawnie skonfigurowany. Pamiętaj aby zapisać zmiany głównym przyciskiem "Zapisz" na górze
                strony.
            </p>
        </div>

        <!-- Browser Dialog Component -->
        <OutfitBrowserDialog
            v-model:visible="showBrowserDialog"
            @select="handleOutfitSelected"
        />
    </div>
</template>

<style scoped>
.field {
    display: flex;
    flex-direction: column;
}
</style>
