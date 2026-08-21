<script setup lang="ts">
import { computed, ref } from 'vue';
import AppLayout from '@/layout/AppLayout.vue';
import ItemHeader from '@/Components/ItemHeader.vue';
import CheckpointMapEditorModal from '@/Pages/MapTrack/Components/CheckpointMapEditorModal.vue';
import { DialogCounterResource, dialogCounterScopeLabels } from '@/Resources/DialogCounter.resource';
import { MapTrackCheckpointResource, MapTrackResource } from '@/Resources/MapTrack.resource';
import { router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useConfirm, useToast } from 'primevue';
import Select from 'primevue/select';
import InputSwitch from 'primevue/inputswitch';

const props = defineProps<{
    track: MapTrackResource;
    dialogCounters: DialogCounterResource[];
}>();

const confirm = useConfirm();
const toast = useToast();
const isCheckpointModalVisible = ref(false);
const selectedCheckpoint = ref<MapTrackCheckpointResource | null>(null);
const checkpoints = computed(() => props.track.checkpoints ?? []);

const form = useForm({
    name: props.track.name,
    dialog_counter_id: props.track.dialog_counter_id,
    enabled: props.track.enabled,
});

const saveTrack = () => form.patch(route('map-tracks.update', { mapTrack: props.track.id }), {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'success', summary: 'Trasa zapisana', life: 3000 }),
});

const addCheckpoint = () => {
    selectedCheckpoint.value = null;
    isCheckpointModalVisible.value = true;
};

const editCheckpoint = (checkpoint: MapTrackCheckpointResource) => {
    selectedCheckpoint.value = checkpoint;
    isCheckpointModalVisible.value = true;
};

const moveCheckpoint = (checkpoint: MapTrackCheckpointResource, direction: 'up' | 'down') => {
    router.patch(route('map-tracks.checkpoints.move', {
        mapTrack: props.track.id,
        checkpoint: checkpoint.id,
    }), { direction }, { preserveScroll: true });
};

const confirmDeleteCheckpoint = (checkpoint: MapTrackCheckpointResource) => {
    confirm.require({
        header: 'Usuwanie checkpointu',
        message: `Usunąć checkpoint ${checkpoint.sequence}${checkpoint.name ? ` „${checkpoint.name}”` : ''}?`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: { label: 'Anuluj', severity: 'secondary' },
        acceptProps: { label: 'Usuń', severity: 'danger' },
        accept: () => router.delete(route('map-tracks.checkpoints.destroy', {
            mapTrack: props.track.id,
            checkpoint: checkpoint.id,
        }), {
            preserveScroll: true,
            onSuccess: () => toast.add({ severity: 'success', summary: 'Checkpoint usunięty', life: 3000 }),
        }),
    });
};
</script>

<template>
    <AppLayout>
        <CheckpointMapEditorModal
            v-model:visible="isCheckpointModalVisible"
            :track-id="track.id"
            :checkpoint="selectedCheckpoint"
            :checkpoints="checkpoints"
        />

        <ItemHeader :route-back="route('map-tracks.index')">
            <template #header>#{{ track.id }} — {{ track.name }}</template>
        </ItemHeader>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(320px,0.75fr)_minmax(600px,1.5fr)]">
            <div class="card h-fit">
                <div class="mb-5 flex items-center justify-between">
                    <h4 class="m-0">Ustawienia trasy</h4>
                    <Tag :value="form.enabled ? 'Aktywna' : 'Wyłączona'" :severity="form.enabled ? 'success' : 'secondary'" />
                </div>

                <form class="flex flex-col gap-5" @submit.prevent="saveTrack">
                    <div class="flex flex-col gap-2">
                        <label for="track-name" class="font-medium">Nazwa</label>
                        <InputText id="track-name" v-model="form.name" :invalid="Boolean(form.errors.name)" />
                        <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="track-counter" class="font-medium">Licznik ukończonych okrążeń</label>
                        <Select
                            id="track-counter"
                            v-model="form.dialog_counter_id"
                            :options="dialogCounters"
                            option-label="name"
                            option-value="id"
                            filter
                            :invalid="Boolean(form.errors.dialog_counter_id)"
                        >
                            <template #option="{ option }">
                                <div class="flex w-full items-center justify-between gap-4">
                                    <span>{{ option.name }}</span>
                                    <Tag :value="dialogCounterScopeLabels[option.scope]" severity="secondary" />
                                </div>
                            </template>
                        </Select>
                        <small v-if="form.errors.dialog_counter_id" class="text-red-500">{{ form.errors.dialog_counter_id }}</small>
                    </div>

                    <label class="flex items-center gap-3">
                        <InputSwitch v-model="form.enabled" />
                        <span class="font-medium">Trasa aktywna</span>
                    </label>

                    <Button type="submit" label="Zapisz ustawienia" icon="pi pi-save" :loading="form.processing" />
                </form>

                <Message severity="info" class="mt-5">
                    Po zaliczeniu ostatniego checkpointu silnik zwiększy wybrany licznik dialogowy i rozpocznie kolejne okrążenie.
                </Message>
            </div>

            <div class="card">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h4 class="m-0">Checkpointy</h4>
                        <p class="mb-0 mt-1 text-sm text-surface-500">Kolejność obowiązuje również wtedy, gdy checkpointy znajdują się na różnych mapach.</p>
                    </div>
                    <Button label="Dodaj checkpoint" icon="pi pi-plus" @click="addCheckpoint" />
                </div>

                <div v-if="checkpoints.length" class="flex flex-col gap-3">
                    <div
                        v-for="(checkpoint, index) in checkpoints"
                        :key="checkpoint.id"
                        class="flex flex-col gap-4 rounded-xl border border-surface-200 p-4 dark:border-surface-700 lg:flex-row lg:items-center"
                    >
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary text-xl font-bold text-primary-contrast">
                            {{ checkpoint.sequence }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h5 class="m-0">{{ checkpoint.name || `Checkpoint ${checkpoint.sequence}` }}</h5>
                                <Tag :value="`${checkpoint.tiles.length} kratek`" severity="info" />
                            </div>
                            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-surface-500">
                                <span><i class="pi pi-map mr-1" /> #{{ checkpoint.map.id }} — {{ checkpoint.map.name }}</span>
                                <span><i class="pi pi-arrows-h mr-1" /> {{ checkpoint.map.x }} × {{ checkpoint.map.y }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button
                                icon="pi pi-arrow-up"
                                severity="secondary"
                                outlined
                                :disabled="index === 0"
                                aria-label="Przesuń wyżej"
                                @click="moveCheckpoint(checkpoint, 'up')"
                            />
                            <Button
                                icon="pi pi-arrow-down"
                                severity="secondary"
                                outlined
                                :disabled="index === checkpoints.length - 1"
                                aria-label="Przesuń niżej"
                                @click="moveCheckpoint(checkpoint, 'down')"
                            />
                            <Button label="Edytuj" icon="pi pi-pencil" severity="secondary" @click="editCheckpoint(checkpoint)" />
                            <Button icon="pi pi-trash" severity="danger" outlined aria-label="Usuń" @click="confirmDeleteCheckpoint(checkpoint)" />
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border-2 border-dashed border-surface-300 px-6 py-14 text-center dark:border-surface-700">
                    <i class="pi pi-flag mb-4 text-5xl text-surface-400" />
                    <h4>Trasa nie ma jeszcze checkpointów</h4>
                    <p class="text-surface-500">Dodaj pierwszy checkpoint, wybierz mapę i narysuj bramkę na jej grafice.</p>
                    <Button label="Dodaj pierwszy checkpoint" icon="pi pi-plus" @click="addCheckpoint" />
                </div>
            </div>
        </div>

        <ConfirmDialog />
    </AppLayout>
</template>
