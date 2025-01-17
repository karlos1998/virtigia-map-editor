<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {Ref, ref} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {route} from "ziggy-js";
import axios from "axios";
import {useToast} from "primevue";

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        label: string,
        content: string,
    }
}> | null>('dialogRef');

const label = ref('');
const content = ref('');
onMounted(() => {
    label.value = dialogRef.value.data?.label ?? '';
    content.value = dialogRef.value.data?.content ?? '';
})

const toast = useToast();

const processing = ref(false);

const save = () => {
    processing.value = true;
    axios.patch(route('dialogs.nodes.update', {
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data.node_id,
    }), {
        content: content.value,
    })
        .then(() => {
            toast.add({ severity: 'success', summary: 'Operacja powiodła się', detail: 'Pomyślnie zmieniono treść dialogu npc', life: 6000 });
            dialogRef.value.close({
                label: label.value,
                content: content.value,
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
        <Textarea v-model="content" rows="5" cols="50" />
        <Button :loading="processing" fluid @click="save">Save</Button>
    </div>
</template>

<style scoped>

</style>
