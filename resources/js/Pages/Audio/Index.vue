<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {Link, router} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {ref} from "vue";

type AudioRow = {
    id: number
    name: string
    path: string
}

type Data = { data: AudioRow }

const createVisible = ref(false);
const createForm = ref({ name: "", file: null as File | null });
const createProcessing = ref(false);

const createAudio = () => {
    if (!createForm.value.file) {
        return;
    }

    createProcessing.value = true;

    const formData = new FormData();
    formData.append('name', createForm.value.name);
    formData.append('file', createForm.value.file);

    router.post(route('audios.store'), formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            createVisible.value = false;
            createForm.value = { name: "", file: null };
        },
        onFinish: () => {
            createProcessing.value = false;
        }
    });
};

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    createForm.value.file = target.files?.[0] ?? null;
};
</script>

<template>
    <AppLayout>
        <div class="card">
            <Button label="Nowy plik audio" icon="pi pi-plus" @click="createVisible = true" />
        </div>

        <div class="card">
            <AdvanceTable prop-name="audios">
                <template #header="{ globalFilterValue, globalFilterUpdated }">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Lista plików audio</h4>
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText :value="globalFilterValue" @update:model-value="globalFilterUpdated" placeholder="Szukaj po nazwie" />
                        </IconField>
                    </div>
                </template>

                <AdvanceColumn field="id" header="ID" style="width: 10%" />
                <AdvanceColumn field="name" header="Nazwa" />
                <AdvanceColumn field="path" header="Ścieżka" />
                <Column header="Akcje" style="width: 14%">
                    <template #body="{ data }: Data">
                        <Link :href="route('audios.show', { audio: data.id })">
                            <Button icon="pi pi-eye" label="Edytuj" />
                        </Link>
                    </template>
                </Column>
            </AdvanceTable>
        </div>

        <Dialog v-model:visible="createVisible" modal header="Nowy plik audio" :style="{ width: '48rem' }">
            <div class="mb-3">
                <label class="font-semibold block mb-2">Nazwa</label>
                <InputText v-model="createForm.name" class="w-full" />
            </div>
            <div class="mb-3">
                <label class="font-semibold block mb-2">Plik audio</label>
                <input type="file" accept="audio/*" @change="onFileChange" />
            </div>
            <div class="flex justify-end gap-2">
                <Button label="Anuluj" severity="secondary" @click="createVisible = false" />
                <Button :loading="createProcessing" label="Utwórz" @click="createAudio" />
            </div>
        </Dialog>
    </AppLayout>
</template>
