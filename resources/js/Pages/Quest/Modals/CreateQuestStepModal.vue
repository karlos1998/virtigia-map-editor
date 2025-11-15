<script setup lang="ts">
import {useForm} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {useToast} from "primevue";
import Checkbox from 'primevue/checkbox';
import Dropdown from 'primevue/dropdown';
import {computed} from 'vue';

const props = defineProps<{
    questId: number;
    steps: any[];
}>();

const visible = defineModel<boolean>('visible');
const toast = useToast();

const form = useForm({
    name: '',
    description: '',
    auto_advance_next_day: false,
    auto_advance_to_step_id: null,
})

const stepOptions = computed(() => {
    const mapped = props.steps.map(s => ({label: s.name, value: s.id}));
    return [{label: 'Wyzeruj quest', value: null}, ...mapped];
});

const submit = () => {
    form.post(route('quests.steps.store', { quest: props.questId }), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Krok został dodany', life: 3000 });
            visible.value = false;
            form.reset();
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił problem podczas dodawania kroku', life: 6000 });
        },
        preserveScroll: true
    });
}
</script>

<template>
    <Dialog v-model:visible="visible" modal header="Dodawanie kroku questa" :style="{ width: '40rem' }">
        <div class="flex flex-col gap-4 mb-4">
            <div>
                <label for="name" class="font-semibold block mb-2">Nazwa</label>
                <InputText id="name" class="w-full" autocomplete="off" v-model="form.name" />
                <Message severity="error" size="small" variant="simple">{{ form.errors.name }}</Message>
            </div>

            <div>
                <label for="description" class="font-semibold block mb-2">Opis</label>
                <Textarea id="description" class="w-full" rows="10" v-model="form.description" />
                <Message severity="error" size="small" variant="simple">{{ form.errors.description }}</Message>
            </div>

            <div>
                <div class="flex items-center mb-2">
                    <Checkbox v-model="form.auto_advance_next_day" :binary="true" inputId="autoAdvanceNextDay"/>
                    <label for="autoAdvanceNextDay" class="ml-2 font-semibold">Automatycznie przejść następnego dnia do
                        innego kroku</label>
                </div>

                <div class="mt-2">
                    <label class="font-semibold block mb-2">Krok docelowy (opcjonalne)</label>
                    <Dropdown v-model="form.auto_advance_to_step_id" :options="stepOptions" optionLabel="label"
                              optionValue="value" class="w-full" placeholder="Wybierz krok"/>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <Button type="button" label="Anuluj" severity="secondary" @click="visible = false"></Button>
            <Button :loading="form.processing" type="button" label="Dodaj" @click="submit"></Button>
        </div>
    </Dialog>
</template>

<style scoped>
</style>
