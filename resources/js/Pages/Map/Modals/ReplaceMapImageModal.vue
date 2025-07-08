<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useToast } from 'primevue';

const props = defineProps<{
  map: {
    id: number;
    x: number;
    y: number;
  };
}>();

const visible = defineModel<boolean>('visible');
const toast = useToast();
const src = ref(null);

const form = useForm({
  img: null as any,
  fileName: '',
});

function onFileSelect(event) {
  const file = event.files[0];
  const reader = new FileReader();

  form.fileName = file.name;

  reader.onload = async (e) => {
    src.value = e.target.result;

    const img = new Image();
    img.onload = () => {
      form.img = img;
    };
    img.src = e.target.result as string;
  };

  reader.readAsDataURL(file);
}

const isValidImage = computed(() => {
  if (!form.img) return false;

  // Check if dimensions are divisible by 32
  if (form.img.width % 32 !== 0 || form.img.height % 32 !== 0) {
    return false;
  }

  // Check if dimensions match the map's dimensions
  const mapWidth = props.map.x * 32;
  const mapHeight = props.map.y * 32;

  return form.img.width === mapWidth && form.img.height === mapHeight;
});

const dimensionsMessage = computed(() => {
  if (!form.img) return '';

  const mapWidth = props.map.x * 32;
  const mapHeight = props.map.y * 32;

  if (form.img.width % 32 !== 0 || form.img.height % 32 !== 0) {
    return 'Wymiary obrazu muszą być podzielne przez 32.';
  }

  if (form.img.width !== mapWidth || form.img.height !== mapHeight) {
    return `Wymiary obrazu (${form.img.width}x${form.img.height}) nie pasują do wymiarów mapy (${mapWidth}x${mapHeight}).`;
  }

  return '';
});

const submit = () => {
  if (!isValidImage.value) {
    toast.add({ severity: 'error', summary: 'Błąd', detail: dimensionsMessage.value, life: 3000 });
    return;
  }

  form
    .transform(({ img, fileName }) => {
      return {
        img: img.src,
        fileName,
      };
    })
    .post(route('maps.update.image', props.map.id), {
      onSuccess: () => {
        toast.add({ severity: 'success', summary: 'Sukces', detail: 'Grafika mapy została zaktualizowana', life: 3000 });
        visible.value = false;
        form.reset();
        src.value = null;
      },
      onError: (errors) => {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zaktualizować grafiki mapy', life: 3000 });
      }
    });
};

// Reset form when modal is closed
watch(visible, (newValue) => {
  if (!newValue) {
    form.reset();
    src.value = null;
  }
});
</script>

<template>
  <Dialog
    v-model:visible="visible"
    modal
    header="Podmień grafikę mapy"
    :style="{ width: '50vw' }"
    :breakpoints="{ '960px': '75vw', '641px': '90vw' }"
  >
    <div class="card p-fluid">
      <div class="field mb-4">
        <h3 class="font-semibold mb-2">Wybierz nową grafikę</h3>
        <p class="text-sm text-gray-600 mb-2">
          Grafika musi mieć wymiary podzielne przez 32 i pasować do wymiarów obecnej mapy ({{ map.x * 32 }}x{{ map.y * 32 }} pikseli).
        </p>
        <FileUpload
          mode="basic"
          @select="onFileSelect"
          customUpload
          auto
          accept="image/*"
          :maxFileSize="10000000"
          class="p-button-outlined"
        />
      </div>

      <div v-if="src" class="field mb-4">
        <h3 class="font-semibold mb-2">Podgląd</h3>
        <img :src="src" alt="Map Preview" class="rounded-xl w-full max-h-96 object-contain" />

        <Message v-if="dimensionsMessage" severity="error" class="mt-2">{{ dimensionsMessage }}</Message>
        <Message v-else-if="form.img" severity="success" class="mt-2">
          Wymiary obrazu są prawidłowe ({{ form.img.width }}x{{ form.img.height }}).
        </Message>
      </div>

      <div v-if="form.errors.img" class="field mb-4">
        <Message severity="error">{{ form.errors.img }}</Message>
      </div>

      <div v-if="form.errors.fileName" class="field mb-4">
        <Message severity="error">{{ form.errors.fileName }}</Message>
      </div>

      <div class="flex justify-end">
        <Button
          label="Anuluj"
          icon="pi pi-times"
          class="p-button-text mr-2"
          @click="visible = false"
        />
        <Button
          label="Podmień grafikę"
          icon="pi pi-check"
          :disabled="!isValidImage || form.processing"
          :loading="form.processing"
          @click="submit"
        />
      </div>
    </div>
  </Dialog>
</template>
