<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import {DialogCounterResource} from "@/Resources/DialogCounter.resource";
import {Link, useForm} from '@inertiajs/vue3';
import {route} from "ziggy-js";
import {useConfirm} from "primevue/useconfirm";
import {useToast} from "primevue";

type Data = {
    data: DialogCounterResource
}

const props = defineProps<{
    dialogCounters: DialogCounterResource[]
}>();

const confirm = useConfirm();
const toast = useToast();

const deleteForm = useForm({});

const confirmDelete = (counter: DialogCounterResource) => {
    confirm.require({
        message: `Czy na pewno chcesz usunąć licznik "${counter.name}"?`,
        header: 'Potwierdzenie usunięcia',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Anuluj',
            severity: 'secondary'
        },
        acceptProps: {
            label: 'Usuń',
            severity: 'danger'
        },
        accept: () => {
            deleteForm.delete(route('dialog-counters.destroy', {dialogCounter: counter.id}), {
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Sukces',
                        detail: 'Licznik został usunięty',
                        life: 3000
                    });
                },
                onError: () => {
                    toast.add({
                        severity: 'error',
                        summary: 'Błąd',
                        detail: 'Nie udało się usunąć licznika',
                        life: 3000
                    });
                }
            });
        }
    });
};
</script>

<template>
    <AppLayout>
        <div class="card">
            <div class="flex gap-2">
                <Link :href="route('dialog-counters.create')">
                    <Button label="Dodaj licznik" icon="pi pi-plus"/>
                </Link>
            </div>
        </div>

        <div class="card">
            <DataTable
                :value="dialogCounters"
                paginator
                :rows="10"
                :rowsPerPageOptions="[10, 20, 50]"
                stripedRows
                sortField="id"
                :sortOrder="1"
            >
                <template #header>
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Lista Liczników Dialogowych</h4>
                    </div>
                </template>

                <Column field="id" header="ID" style="width: 10%" sortable/>

                <Column field="name" header="Nazwa" style="width: 60%" sortable/>

                <Column header="Akcje" style="width: 30%">
                    <template #body="{data}">
                        <div class="flex gap-2">
                            <Link
                                :href="route('dialog-counters.edit', {dialogCounter: data.id})"
                            >
                                <Button
                                    icon="pi pi-pencil"
                                    severity="secondary"
                                    label="Edytuj"
                                    size="small"
                                />
                            </Link>
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                label="Usuń"
                                size="small"
                                @click="confirmDelete(data)"
                            />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-4 text-gray-500">
                        Brak liczników dialogowych
                    </div>
                </template>
            </DataTable>
        </div>

        <ConfirmDialog />
    </AppLayout>
</template>

<style scoped>
</style>
