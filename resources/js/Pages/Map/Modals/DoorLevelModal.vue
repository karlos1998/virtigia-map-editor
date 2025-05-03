<script setup lang="ts">
import {ref, onMounted, inject, Ref} from 'vue';
import { DoorResource } from '@/Resources/Door.resource';
import { useToast } from 'primevue/usetoast';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";

// const props = defineProps<{
//     door: DoorResource;
// }>();
const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        door: DoorResource
    }
}> | null>('dialogRef');

const emit = defineEmits(['close']);

const minLevel = ref<number | null>(null);
const maxLevel = ref<number | null>(null);
const toast = useToast();

onMounted(() => {
    if(!dialogRef?.value.data) return;
    const door = dialogRef.value.data.door;
    console.log('door', door)
    minLevel.value = door.min_lvl;
    maxLevel.value = door.max_lvl;
});

const save = () => {
    if(!dialogRef?.value.data) return;

    router.patch(route('doors.level.update', { door: dialogRef.value.data.door.id }), {
        min_lvl: minLevel.value,
        max_lvl: maxLevel.value,
    }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Zaktualizowano poziomy przejścia', life: 4000 });
            dialogRef.value.close({

            });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zaktualizować poziomów przejścia', life: 6000 });
        }
    });
};
</script>

<template>
    <div class="p-4">
        <h2 class="text-xl mb-4">Ustaw wymagany poziom dla przejścia</h2>

        <div class="flex flex-col gap-4">
            <div class="field">
                <label for="minLevel" class="block mb-2">Minimalny poziom</label>
                <InputNumber id="minLevel" v-model="minLevel" :min="0" placeholder="Brak limitu" />
                <small class="block mt-1 text-gray-500">Pozostaw puste, jeśli nie ma minimalnego poziomu</small>
            </div>

            <div class="field">
                <label for="maxLevel" class="block mb-2">Maksymalny poziom</label>
                <InputNumber id="maxLevel" v-model="maxLevel" :min="0" placeholder="Brak limitu" />
                <small class="block mt-1 text-gray-500">Pozostaw puste, jeśli nie ma maksymalnego poziomu</small>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <Button label="Anuluj" severity="secondary" outlined @click="emit('close')" />
                <Button label="Zapisz" @click="save" />
            </div>
        </div>
    </div>
</template>
