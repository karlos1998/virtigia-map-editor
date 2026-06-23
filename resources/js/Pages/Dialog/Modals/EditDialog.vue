<script setup lang="ts">
import { inject, onMounted, reactive, Ref, ref } from 'vue';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import { DynamicDialogInstance } from 'primevue/dynamicdialogoptions';
import { route } from 'ziggy-js';
import axios from 'axios';
import { useToast } from 'primevue';
import { DialogNodeAdditionalActionsResource } from '@/Resources/DialogNodeAdditionalActions.resource';
import AdditionalActionsEditor from '@/Pages/Dialog/Componnts/AdditionalActionsEditor.vue';

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        content: string,
        dialog_id: number,
        node_id: number,
        additional_actions: DialogNodeAdditionalActionsResource
    }
}> | null>('dialogRef');

const form = reactive<{
    content: string,
    additional_actions: DialogNodeAdditionalActionsResource,
}>({
    content: '',
    additional_actions: {},
});

const toast = useToast();
const processing = ref(false);
const copyProcessing = ref(false);
const additionalActionsEditor = ref<{
    getPayload: () => DialogNodeAdditionalActionsResource | null;
} | null>(null);

onMounted(() => {
    if (!dialogRef) {
        return;
    }

    form.content = dialogRef.value.data?.content ?? '';
    form.additional_actions = !Array.isArray(dialogRef.value.data?.additional_actions)
        ? dialogRef.value.data?.additional_actions ?? {}
        : {};
});

const copyDialog = (): void => {
    if (!dialogRef) {
        return;
    }

    copyProcessing.value = true;
    axios.post(route('dialogs.nodes.copy', {
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data.node_id,
    }))
        .then(({ data }) => {
            toast.add({ severity: 'success', summary: 'Operacja powiodła się', detail: 'Pomyślnie skopiowano dialog', life: 6000 });
            dialogRef.value.close({
                content: form.content,
                copied: true,
                newNode: data.node,
            });
        })
        .catch(({ response }) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
        })
        .finally(() => {
            copyProcessing.value = false;
        });
};

const save = (): void => {
    if (!dialogRef) {
        return;
    }

    const additionalActionsPayload = additionalActionsEditor.value?.getPayload();

    if (!additionalActionsPayload) {
        return;
    }

    processing.value = true;
    axios.patch(route('dialogs.nodes.update', {
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data.node_id,
    }), {
        content: form.content,
        additional_actions: additionalActionsPayload,
    })
        .then(() => {
            toast.add({ severity: 'success', summary: 'Operacja powiodła się', detail: 'Pomyślnie zmieniono treść dialogu npc', life: 6000 });
            dialogRef.value.close({
                content: form.content,
                additional_actions: additionalActionsPayload,
            });
        })
        .catch(({ response }) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
        })
        .finally(() => {
            processing.value = false;
        });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <Textarea v-model="form.content" rows="5" cols="50" />

        <div class="flex flex-col gap-2">
            <h3 class="text-lg font-semibold m-0">Akcje dodatkowe</h3>
            <AdditionalActionsEditor ref="additionalActionsEditor" v-model:actions="form.additional_actions" />
        </div>

        <div class="flex gap-2">
            <Button :loading="processing" class="flex-1" @click="save">Zapisz</Button>
            <Button :loading="copyProcessing" severity="secondary" class="flex-1" @click="copyDialog">Kopiuj dialog</Button>
        </div>
    </div>
</template>
