<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {Ref, ref} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {useForm, usePage} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {useToast} from "primevue";
import axios from "axios";
import {DialogOptionResource} from "../../../Resources/DialogOption.resource";
import {DropdownListType} from "../../../Resources/DropdownList.type";

const dialogNodeOptionAdditionalActionsList = ref(usePage<{dialogNodeOptionAdditionalActionsList: DropdownListType}>().props.dialogNodeOptionAdditionalActionsList)
const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        option: DialogOptionResource,
        parent: string,
        dialog_id: number
    }
}> | null>('dialogRef');

const form = useForm({
    label: '',
    additional_action: null,
})

const toast = useToast();

onMounted(() => {
    form.label = dialogRef.value.data?.option?.label ?? '';
    form.additional_action = dialogRef.value.data?.option?.additional_action ?? '';
})

const processing = ref(false);

const save = () => {
    //axios zeby form inertia nie przeladowywal strony

    processing.value = true;

    axios.patch<DialogOptionResource>(route('dialogs.nodes.options.update', {
        dialogNodeOption: dialogRef.value.data?.option?.id,
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data?.option?.node_id,
    }), form.data())
        .then(({data}) => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Opcja dialogowa została edytowana', life: 3000 });
            dialogRef.value.close({
                dialogOption: data,
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
        .catch(({response}) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
        })
        .finally(() => {
            processing.value = false;
        })

}
</script>

<template>
    <div class="flex flex-col gap-2">
        <InputGroup>
            <Button icon="pi pi-times" severity="danger" aria-label="Cancel" @click="form.additional_action = null" />
            <Select v-model="form.additional_action" :options="dialogNodeOptionAdditionalActionsList" optionLabel="label" option-value="value" placeholder="Wybierz dodatkową akcje" class="w-full md:w-56" />
        </InputGroup>
        <Textarea v-model="form.label" rows="5" cols="50" />
        <div class="flex gap-1">
            <Button  :loading="processing"  fluid @click="remove" severity="danger">Usuń</Button>
            <Button :loading="processing" fluid @click="save">Zapisz</Button>
        </div>
    </div>
</template>

<style scoped>

</style>
