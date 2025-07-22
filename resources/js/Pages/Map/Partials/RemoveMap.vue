<script setup lang="ts">

import {useConfirm, useToast} from "primevue";
import {useForm} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {MapResource} from "@/Resources/Map.resource";

const props = defineProps<{
    map: MapResource
}>()

const confirm = useConfirm();
const toast = useToast();

const form = useForm({});

const confirmDelete = (event) => {
    confirm.require({
        target: event.currentTarget,
        message: 'Czy na pewno chcesz usunąć tę mapę? Ta operacja jest nieodwracalna.',
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
            form.delete(route('maps.destroy', props.map.id), {
                onSuccess: () => {
                    toast.add({ severity: 'info', summary: 'Usunięto', detail: 'Pomyślnie usunięto mapę', life: 7000 });
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

    <Button severity="danger" label="Usuń mapę" class="w-full" @click="confirmDelete" />
</template>

<style scoped>

</style>
