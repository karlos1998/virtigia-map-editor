<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {Link, router} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {ref} from "vue";

type BookRow = {
    id: number
    title: string
    content_preview?: string
}

type Data = { data: BookRow }

const createVisible = ref(false);
const createForm = ref({ title: "" });
const createProcessing = ref(false);

const createBook = () => {
    createProcessing.value = true;
    router.post(route('books.store'), createForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            createVisible.value = false;
            createForm.value = { title: "" };
        },
        onFinish: () => {
            createProcessing.value = false;
        }
    });
};
</script>

<template>
    <AppLayout>
        <div class="card">
            <Button label="Nowa książka" icon="pi pi-plus" @click="createVisible = true" />
        </div>

        <div class="card">
            <AdvanceTable prop-name="books">
                <template #header="{ globalFilterValue, globalFilterUpdated }">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Lista książek</h4>
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText :value="globalFilterValue" @update:model-value="globalFilterUpdated" placeholder="Szukaj po tytule" />
                        </IconField>
                    </div>
                </template>

                <AdvanceColumn field="id" header="ID" style="width: 10%" />
                <AdvanceColumn field="title" header="Tytuł" />
                <AdvanceColumn field="content_preview" header="Podgląd treści" />
                <Column header="Akcje" style="width: 14%">
                    <template #body="{ data }: Data">
                        <Link :href="route('books.show', { book: data.id })">
                            <Button icon="pi pi-eye" label="Edytuj" />
                        </Link>
                    </template>
                </Column>
            </AdvanceTable>
        </div>

        <Dialog v-model:visible="createVisible" modal header="Nowa książka" :style="{ width: '48rem' }">
            <div class="mb-3">
                <label class="font-semibold block mb-2">Tytuł</label>
                <InputText v-model="createForm.title" class="w-full" />
            </div>
            <div class="flex justify-end gap-2">
                <Button label="Anuluj" severity="secondary" @click="createVisible = false" />
                <Button :loading="createProcessing" label="Utwórz" @click="createBook" />
            </div>
        </Dialog>
    </AppLayout>
</template>
