<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {Ref, ref} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {useForm} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {useToast} from "primevue";
import axios from "axios";

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        option: {
            label: string,
            id: string
        },
        parent: string,
        dialog_id: number
    }
}> | null>('dialogRef');

const form = useForm({
    label: '',
})

const toast = useToast();

onMounted(() => {
    form.label = dialogRef.value.data?.option?.label ?? '';
})

const processing = ref(false);

const save = () => {
    //axios zeby form inertia nie przeladowywal strony

    processing.value = true;

    axios.patch(route('dialogs.nodes.options.update', {
        dialogNodeOption: dialogRef.value.data?.option?.id,
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data?.option?.node_id,
    }), {
        label: form.label,
    })
        .then(() => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Opcja dialogowa została edytowana', life: 3000 });
            dialogRef.value.close({
                label: form.label,
            });
        })
        .catch(() => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił problem podczas edycji opcji dialogowej', life: 3000 });
        })
        .finally(() => {
            processing.value = false;
        })
}

const remove = () => {

    processing.value = true;

    axios.delete(route('dialogs.nodes.options.destroy', {
        dialogNodeOption: dialogRef.value.data?.option?.id,
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data?.option?.node_id,
    }))
        .then(() => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Opcja dialogowa została usunięta', life: 3000 });
            dialogRef.value.close({
                remove: true,
            });
        })
        .catch(() => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił problem podczas usuwania opcji dialogowej', life: 3000 });
        })
        .finally(() => {
            processing.value = false;
        })

}
</script>

<template>
    <div class="flex flex-col gap-2">
        <Textarea v-model="form.label" rows="5" cols="50" />
        <div class="flex gap-1">
            <Button  :loading="processing"  fluid @click="remove" severity="danger">Remove</Button>
            <Button :loading="processing" fluid @click="save">Save</Button>
        </div>
    </div>
</template>

<style scoped>

</style>
