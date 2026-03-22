<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import Chip from 'primevue/chip';
import Message from 'primevue/message';
import {useToast} from "primevue";
import {useForm} from "@inertiajs/vue3";
import {route} from "ziggy-js";

// Props to receive and update attributes object directly
const props = defineProps<{
    attributes: any; // The attributes object
    baseItem: any; // The base item object for image upload
}>();

const emit = defineEmits(['update:attributes']);
const toast = useToast();

// Local state for pet fields
const petSrc = ref<string>('');
const petActions = ref<string[]>([]);
const description = ref<string>('');
const newActionName = ref<string>('');

// Image upload form
const imageForm = useForm({
    image: null,
    name: null,
});

// Initialize values from existing attributes
const initializeFromAttributes = () => {
    petSrc.value = props.attributes?.petSrc ?? '';
    petActions.value = props.attributes?.petActions ?? [];
    description.value = props.attributes?.description ?? '';
};

// Initialize on mount
initializeFromAttributes();

// Watch for external changes to attributes
watch(() => props.attributes, () => {
    initializeFromAttributes();
}, { deep: true });

// Auto-save when pet fields change
watch([petSrc, petActions, description], () => {
    const updatedAttributes = { ...props.attributes };

    // Handle petSrc
    if (petSrc.value && petSrc.value.trim() !== '') {
        updatedAttributes.petSrc = petSrc.value.trim();
    } else {
        delete updatedAttributes.petSrc;
    }

    // Handle petActions
    if (petActions.value && petActions.value.length > 0) {
        updatedAttributes.petActions = [...petActions.value];
    } else {
        delete updatedAttributes.petActions;
    }

    // Handle description
    if (description.value && description.value.trim() !== '') {
        updatedAttributes.description = description.value.trim();
    } else {
        delete updatedAttributes.description;
    }

    emit('update:attributes', updatedAttributes);
}, { deep: true });

// Add new action
const addAction = () => {
    if (newActionName.value && newActionName.value.trim() !== '') {
        petActions.value.push(newActionName.value.trim());
        newActionName.value = '';
    }
};

// Remove action
const removeAction = (index: number) => {
    petActions.value.splice(index, 1);
};

// Preview the pet image
const previewImageSrc = computed(() => {
    if (petSrc.value && petSrc.value.trim() !== '') {
        return `/s3/img/pets/${petSrc.value.trim()}`;
    }
    return '';
});

// Handle file upload
const onFileSelect = (event: any) => {
    const file = event.files[0];
    const reader = new FileReader();

    reader.onload = async (e: any) => {
        imageForm.image = e.target.result;
        imageForm.name = file.name;

        // Automatically upload the file
        uploadImage();
    };

    reader.readAsDataURL(file);
};

// Upload image
const uploadImage = () => {
    imageForm.patch(route('base-items.pet-image.update', {baseItem: props.baseItem}), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page: any) => {
            toast.add({
                severity: 'success',
                summary: 'Sukces',
                detail: 'Grafika zwierzaka została przesłana i zapisana na S3',
                life: 3000
            });

            // The backend returns petSrc in the response
            // Update the local petSrc value
            const responseData = page?.props?.jetstream?.flash;

            // Clear the form
            imageForm.reset();

            // Reload to get the updated attributes
            window.location.reload();
        },
        onError: (errors: any) => {
            const errorMessage = errors.image || 'Nie udało się przesłać grafiki';
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: errorMessage,
                life: 5000
            });
        }
    });
};

// Check if pet is configured
const hasPetData = computed(() => {
    return petSrc.value !== '' || petActions.value.length > 0 || description.value !== '';
});

// Handle Enter key in action input
const handleActionKeyPress = (event: KeyboardEvent) => {
    if (event.key === 'Enter') {
        event.preventDefault();
        addAction();
    }
};

// Animation for pet walking preview
const currentFrame = ref(0);
let animationInterval: number | null = null;

// Calculate sprite dimensions
const spriteFrames = computed(() => {
    // Total width frames = 4 (walking) + number of actions
    return 4 + petActions.value.length;
});

// Start animation
const startAnimation = () => {
    if (animationInterval) {
        clearInterval(animationInterval);
    }

    currentFrame.value = 0;
    animationInterval = window.setInterval(() => {
        currentFrame.value = (currentFrame.value + 1) % 4; // Loop through 4 walking frames
    }, 250); // Change frame every 250ms (4 frames per second)
};

// Stop animation
const stopAnimation = () => {
    if (animationInterval) {
        clearInterval(animationInterval);
        animationInterval = null;
    }
};

// Animated sprite style
const animatedSpriteStyle = computed(() => {
    if (!previewImageSrc.value) return {};

    const totalFrames = spriteFrames.value;
    const spriteWidth = totalFrames * 32; // Each frame is 32px wide
    const spriteHeight = 4 * 32; // 4 directions, each 32px tall

    // Calculate background position to show the current frame from the top row
    const xOffset = currentFrame.value * 32; // Pixel offset for current frame

    return {
        backgroundImage: `url(${previewImageSrc.value})`,
        backgroundPosition: `-${xOffset}px 0px`, // Top row, change frame
        backgroundSize: `${spriteWidth}px ${spriteHeight}px`,
        width: '32px',
        height: '32px',
        imageRendering: 'pixelated' as const,
    };
});

// Start/stop animation when preview source changes
watch(previewImageSrc, (newSrc) => {
    if (newSrc) {
        startAnimation();
    } else {
        stopAnimation();
    }
});

// Cleanup on unmount
onUnmounted(() => {
    stopAnimation();
});

// Start animation if there's already a preview on mount
onMounted(() => {
    if (previewImageSrc.value) {
        startAnimation();
    }
});
</script>

<template>
    <div>
        <h3 class="text-xl font-bold mb-4">Edytor Zwierzaka (Pet)</h3>

        <div class="mb-4">
            <p class="text-gray-600 mb-4">
                Skonfiguruj zwierzaka dla tego przedmiotu. Zwierzak określa towarzysza gracza.
            </p>
        </div>

        <!-- Current pet status -->
        <div v-if="hasPetData" class="mb-4 p-3 bg-green-50 border border-green-200 rounded">
            <div class="flex items-center justify-between mb-2">
                <span class="font-semibold text-green-700">
                    <i class="pi pi-check-circle mr-2"></i>
                    Zwierzak jest skonfigurowany
                </span>
            </div>
            <div class="text-sm text-gray-700">
                <p v-if="petSrc">Źródło grafiki: <strong>{{ petSrc }}</strong></p>
                <p v-if="petActions.length > 0">Akcje: <strong>{{ petActions.join(', ') }}</strong></p>
                <p v-if="description">Opis: <strong>{{ description }}</strong></p>
            </div>
        </div>

        <div v-else class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
            <p class="text-blue-700">
                <i class="pi pi-info-circle mr-2"></i>
                Ten przedmiot nie ma skonfigurowanego zwierzaka
            </p>
        </div>

        <!-- Pet configuration -->
        <div class="space-y-4">
            <!-- Pet source (image filename) -->
            <div class="field">
                <label for="petSrc" class="block font-semibold mb-2">
                    Źródło Grafiki (Plik GIF)
                </label>
                <InputText
                    v-model="petSrc"
                    placeholder="np. kot01-1.gif"
                    class="w-full mb-2"
                />
                <small class="text-gray-500 block mb-2">Nazwa pliku graficznego zwierzaka (np. kot01-1.gif)</small>

                <!-- Image upload -->
                <div class="mt-2">
                    <FileUpload
                        mode="basic"
                        @select="onFileSelect"
                        customUpload
                        auto
                        severity="secondary"
                        class="p-button-outlined"
                        accept="image/*"
                        :maxFileSize="5000000"
                    >
                        <template #uploadicon>
                            <i class="pi pi-upload mr-2"></i>
                        </template>
                    </FileUpload>
                </div>
            </div>

            <!-- Preview image -->
            <div v-if="previewImageSrc" class="field">
                <label class="block font-semibold mb-2">Podgląd:</label>
                <div class="flex gap-4 items-start">
                    <!-- Full sprite preview -->
                    <div class="border border-gray-300 rounded p-2">
                        <div class="mb-1 text-xs text-gray-600">Pełna grafika:</div>
                        <img
                            :src="previewImageSrc"
                            alt="Podgląd zwierzaka"
                            class="h-32 w-auto object-fill"
                            style="image-rendering: pixelated;"
                        />
                    </div>

                    <!-- Animated walking preview -->
                    <div class="border border-gray-300 rounded p-2">
                        <div class="mb-1 text-xs text-gray-600">Animacja chodzenia:</div>
                        <div
                            :style="animatedSpriteStyle"
                            class="border border-gray-200"
                        ></div>
                        <div class="mt-1 text-xs text-gray-500">
                            Klatka {{ currentFrame + 1 }}/4
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-gray-500">URL: {{ previewImageSrc }}</small>
                </div>
                <div class="mt-1">
                    <small class="text-gray-500">
                        Klatek szerokości: {{ spriteFrames }} (4 chodzenie + {{ petActions.length }} akcji)
                    </small>
                </div>
            </div>

            <!-- Pet Actions -->
            <div class="field">
                <label for="petActions" class="block font-semibold mb-2">
                    Akcje Zwierzaka
                </label>

                <!-- Warning message about actions -->
                <Message severity="warn" class="mb-3">
                    <strong>Uwaga!</strong> Podanie odpowiedniej ilości akcji jest kluczowe do określenia szerokości grafiki.
                    Przykładowo - grafika kota ma 5 klatek szerokości. 4 klatki od lewej to zwykłe klatki podczas chodzenia,
                    a piątą jest grafika animacji "Miaucz". Jeśli byś jej nie podał, kod podzieliłby szerokość całej grafiki
                    na 4 warstwy zamiast 5 i źle dopasował obrazek.
                </Message>

                <!-- Display existing actions -->
                <div v-if="petActions.length > 0" class="mb-3 flex flex-wrap gap-2">
                    <Chip
                        v-for="(action, index) in petActions"
                        :key="index"
                        :label="action"
                        removable
                        @remove="removeAction(index)"
                    />
                </div>

                <!-- Add new action -->
                <div class="flex gap-2">
                    <InputText
                        v-model="newActionName"
                        placeholder="Np. Miaucz"
                        class="flex-1"
                        @keypress="handleActionKeyPress"
                    />
                    <Button
                        label="Dodaj"
                        icon="pi pi-plus"
                        @click="addAction"
                        :disabled="!newActionName || newActionName.trim() === ''"
                    />
                </div>
                <small class="text-gray-500">Dodaj akcje, które zwierzak może wykonywać</small>
            </div>

            <!-- Description -->
            <div class="field">
                <label for="description" class="block font-semibold mb-2">
                    Opis Zwierzaka
                </label>
                <Textarea
                    v-model="description"
                    placeholder="Np. Testowy zwierzak do celów demonstracyjnych"
                    class="w-full"
                    :rows="3"
                />
                <small class="text-gray-500">Opis wyświetlany w grze</small>
            </div>
        </div>

        <!-- Success message -->
        <div v-if="hasPetData" class="mt-3 p-3 bg-green-50 border border-green-200 rounded">
            <p class="text-green-700 text-sm">
                <i class="pi pi-check-circle mr-2"></i>
                Zwierzak jest poprawnie skonfigurowany. Pamiętaj aby zapisać zmiany głównym przyciskiem "Zapisz" na górze
                strony.
            </p>
        </div>
    </div>
</template>

<style scoped>
.field {
    display: flex;
    flex-direction: column;
}
</style>
