<script setup lang="ts">
import {useForm} from "@inertiajs/vue3";
import {route} from "ziggy-js";

const visible = defineModel<boolean>('visible');

const form = useForm({
    name: '',
})

const submit = () => {
    form.post(route('dialogs.store'))
}
</script>
<template>
    <Dialog v-model:visible="visible" modal header="Tworzenie dialogu" :style="{ width: '25rem' }">
        <span class="text-surface-500 dark:text-surface-400 block mb-8">Podaj nazwę własną dialogu. Jest to tylko nazwa informacyjna czego dotyczy dialog, np 'Plan Astralny' która wskazuje, że trzeba podpiąć go do zakonników planu astralnego.</span>
        <div class="flex items-center gap-4 mb-4">
            <label for="name" class="font-semibold w-24">Nazwa</label>
            <InputText id="name" class="flex-auto" autocomplete="off" v-model="form.name" />
        </div>
        <Message severity="error" size="small" variant="simple">{{ form.errors.name }}</Message>

        <div class="flex justify-end gap-2">
            <Button type="button" label="Anuluj" severity="secondary" @click="visible = false"></Button>
            <Button :loading="form.processing" type="button" label="Utwórz" @click="submit"></Button>
        </div>
    </Dialog>
</template>
<style scoped>

</style>
