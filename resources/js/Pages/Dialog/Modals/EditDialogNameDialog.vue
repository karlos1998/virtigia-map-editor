<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { DialogResource } from "@/Resources/Dialog.resource";
import { route } from "ziggy-js";
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';

const visible = defineModel<boolean>('visible');

const { dialog } = defineProps<{
    dialog: DialogResource
}>();

const form = useForm({
    name: dialog.name,
});

const cancel = () => {
    form.reset();
    visible.value = false;
};

const confirm = () => {
    form.patch(route('dialogs.update', { dialog: dialog.id }), {
        onSuccess: () => {
            visible.value = false;
        }
    });
};
</script>

<template>
    <Dialog v-model:visible="visible" modal header="Edytuj nazwę dialogu">
        <span class="text-surface-500 dark:text-surface-400 block mb-8">Edytuj nazwę dialogu</span>
        <div class="flex items-center gap-4 mb-4">
            <label for="name" class="font-semibold w-24">Nazwa</label>
            <InputText id="name" class="flex-auto" autocomplete="off" v-model="form.name" />
        </div>
        <Message severity="error" size="small" variant="simple">{{ form.errors.name }}</Message>

        <div class="flex justify-end gap-2">
            <Button type="button" label="Anuluj" severity="secondary" @click="cancel"></Button>
            <Button :loading="form.processing" type="button" label="Zapisz" @click="confirm"></Button>
        </div>
    </Dialog>
</template>

<style scoped>
</style>
