<script setup lang="ts">

import {useConfirm, useToast} from "primevue";
import {useForm} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";

const props = defineProps<{
    baseNpc: BaseNpcResource
}>()

const confirm = useConfirm();
const toast = useToast();

const form = useForm({});

const confirmDelete = (event) => {
    confirm.require({
        target: event.currentTarget,
        message: 'Na pewno potrzebujesz usunąć bazowy model NPC? Spowoduje to usunięcie jego wszystkich wystąpień na mapach. \nPrzemyśl, czo to faktycznie jest konieczne. Pojedyńcze wystąpienia możesz usunąć z konkretnej lokalizacji na mapie',
        icon: 'pi pi-info-circle',
        rejectProps: {
            label: 'Anuluj',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Usuń',
            severity: 'danger'
        },
        accept: () => {
            form.delete(route('base-npcs.destroy', props.baseNpc.id), {
                onSuccess: () => {
                    toast.add({ severity: 'info', summary: 'Usunięto', detail: 'Pomyślnie usunięto bazowego NPC', life: 7000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Usuwanie nie powiodło się', life: 3000 });
                }
            });
        },
    });
};

</script>
<template>

    <ConfirmPopup />

    <Button severity="danger" label="Usuń bazowego npc" class="w-full" @click="confirmDelete" />
</template>

<style scoped>

</style>
