<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import { MapTrackResource } from '@/Resources/MapTrack.resource';
import { Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useConfirm, useToast } from 'primevue';

defineProps<{
    tracks: MapTrackResource[];
}>();

const confirm = useConfirm();
const toast = useToast();
const deleteForm = useForm({});

const confirmDelete = (track: MapTrackResource) => {
    confirm.require({
        header: 'Usuwanie trasy',
        message: `Usunąć trasę „${track.name}” wraz ze wszystkimi checkpointami?`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: { label: 'Anuluj', severity: 'secondary' },
        acceptProps: { label: 'Usuń', severity: 'danger' },
        accept: () => deleteForm.delete(route('map-tracks.destroy', { mapTrack: track.id }), {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Trasa usunięta', life: 3000 }),
        }),
    });
};
</script>

<template>
    <AppLayout>
        <div class="card">
            <Link :href="route('map-tracks.create')">
                <Button label="Dodaj trasę" icon="pi pi-plus" />
            </Link>
        </div>

        <div class="card">
            <DataTable :value="tracks" paginator :rows="10" striped-rows sort-field="name" :sort-order="1">
                <template #header>
                    <div class="flex items-center justify-between">
                        <h4 class="m-0">Trasy i checkpointy</h4>
                    </div>
                </template>

                <Column field="id" header="ID" sortable style="width: 8%" />
                <Column field="name" header="Nazwa" sortable />
                <Column header="Licznik okrążeń">
                    <template #body="{ data }">
                        {{ data.dialog_counter?.name }}
                    </template>
                </Column>
                <Column field="checkpoints_count" header="Checkpointy" sortable style="width: 12%" />
                <Column header="Status" style="width: 10%">
                    <template #body="{ data }">
                        <Tag :value="data.enabled ? 'Aktywna' : 'Wyłączona'" :severity="data.enabled ? 'success' : 'secondary'" />
                    </template>
                </Column>
                <Column header="Akcje" style="width: 22%">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Link :href="route('map-tracks.edit', { mapTrack: data.id })">
                                <Button label="Edytuj" icon="pi pi-pencil" size="small" severity="secondary" />
                            </Link>
                            <Button label="Usuń" icon="pi pi-trash" size="small" severity="danger" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="py-6 text-center text-surface-500">Nie utworzono jeszcze żadnej trasy.</div>
                </template>
            </DataTable>
        </div>

        <ConfirmDialog />
    </AppLayout>
</template>
