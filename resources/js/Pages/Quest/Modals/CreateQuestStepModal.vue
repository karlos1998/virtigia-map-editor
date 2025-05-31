<script setup lang="ts">
import {useForm} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {useToast} from "primevue";

const props = defineProps<{
    questId: number;
}>();

const visible = defineModel<boolean>('visible');
const toast = useToast();

const form = useForm({
    name: '',
    description: '',
})

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
        </div>

        <div class="flex justify-end gap-2">
            <Button type="button" label="Anuluj" severity="secondary" @click="visible = false"></Button>
            <Button :loading="form.processing" type="button" label="Dodaj" @click="submit"></Button>
        </div>
    </Dialog>
</template>

<style scoped>
</style>
