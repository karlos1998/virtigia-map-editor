<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const props = defineProps<{
  modelValue: number | null;
  label?: string;
  placeholder?: string;
  error?: string;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: number | null): void;
}>();

// Local state for the input value
const inputSeconds = ref<number | null>(props.modelValue);

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  inputSeconds.value = newValue;
}, { immediate: true });

// Watch for changes to inputSeconds and emit update
watch(inputSeconds, (newValue) => {
  emit('update:modelValue', newValue);
});

// Format seconds to human-readable string
const formatTimeFromSeconds = (seconds: number | null): string => {
  if (seconds === null) return 'Domyślne dla silnika';
  if (seconds === 0) return '0 sekund';

  const days = Math.floor(seconds / (24 * 60 * 60));
  seconds %= (24 * 60 * 60);

  const hours = Math.floor(seconds / (60 * 60));
  seconds %= (60 * 60);

  const minutes = Math.floor(seconds / 60);
  seconds %= 60;

  const parts = [];

  if (days > 0) {
    parts.push(`${days} ${days === 1 ? 'dzień' : 'dni'}`);
  }

  if (hours > 0) {
    parts.push(`${hours} ${hours === 1 ? 'godzina' : hours >= 2 && hours <= 4 ? 'godziny' : 'godzin'}`);
  }

  if (minutes > 0) {
    parts.push(`${minutes} ${minutes === 1 ? 'minuta' : minutes >= 2 && minutes <= 4 ? 'minuty' : 'minut'}`);
  }

  if (seconds > 0) {
    parts.push(`${seconds} ${seconds === 1 ? 'sekunda' : seconds >= 2 && seconds <= 4 ? 'sekundy' : 'sekund'}`);
  }

  return parts.join(', ');
};

// Human-readable time display
const humanReadableTime = computed(() => {
  return formatTimeFromSeconds(inputSeconds.value);
});

// Reset value to null
const resetValue = () => {
  inputSeconds.value = null;
};
</script>

<template>
  <div class="time-input">
    <div v-if="label" class="mb-2 font-semibold">{{ label }}</div>

    <div class="flex flex-col gap-2">
      <div class="flex items-center gap-4">
        <div class="flex-1">
          <label class="block text-sm mb-1">Sekundy</label>
          <InputNumber
            v-model="inputSeconds"
            :min="0"
            class="w-full"
            :placeholder="placeholder || 'Wpisz liczbę sekund'"
            showButtons
            buttonLayout="horizontal"
            decrementButtonClass="p-button-secondary"
            incrementButtonClass="p-button-secondary"
          />
        </div>

        <div class="flex-1">
          <div class="text-sm mb-1">Czas w formacie czytelnym</div>
          <div class="p-2 border border-gray-300 rounded bg-gray-50 dark:bg-gray-800 dark:border-gray-700 min-h-[40px]">
            {{ humanReadableTime }}
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <Button
          type="button"
          label="Domyślne dla silnika"
          severity="secondary"
          size="small"
          @click="resetValue"
        />
      </div>
    </div>

    <small v-if="error" class="p-error">{{ error }}</small>
  </div>
</template>
