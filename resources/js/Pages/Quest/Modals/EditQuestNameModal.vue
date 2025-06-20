<script setup lang="ts">
import {useForm} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {useToast} from "primevue";

const props = defineProps<{
    quest: {
        id: number;
        name: string;
    };
}>();

const visible = defineModel<boolean>('visible');
const toast = useToast();

const form = useForm({
    name: props.quest.name,
})

const submit = () => {
    form.patch(route('quests.update', { quest: props.quest.id }), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Nazwa questa została zmieniona', life: 3000 });
            visible.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił problem podczas zmiany nazwy questa', life: 6000 });
        },
        preserveScroll: true
    });
}
</script>

<template>
    <Dialog v-model:visible="visible" modal header="Zmiana nazwy questa" :style="{ width: '40rem' }">
        <div class="flex flex-col gap-4 mb-4">
            <div>
                <label for="name" class="font-semibold block mb-2">Nazwa</label>
                <InputText id="name" class="w-full" autocomplete="off" v-model="form.name" />
                <Message severity="error" size="small" variant="simple">{{ form.errors.name }}</Message>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <Button type="button" label="Anuluj" severity="secondary" @click="visible = false"></Button>
            <Button :loading="form.processing" type="button" label="Zapisz" @click="submit"></Button>
        </div>
    </Dialog>
</template>

<style scoped>
</style>
