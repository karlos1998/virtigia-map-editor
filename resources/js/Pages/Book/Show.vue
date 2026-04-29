<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import ItemHeader from "@/Components/ItemHeader.vue";
import {route} from "ziggy-js";
import {useForm} from "@inertiajs/vue3";
import {useToast} from "primevue/usetoast";

const props = defineProps<{
    book: {
        id: number
        title: string
        content: string
    }
}>();

const toast = useToast();

const form = useForm({
    title: props.book.title,
    content: props.book.content,
});

const save = () => {
    form.patch(route('books.update', { book: props.book.id }), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Zapisano', detail: 'Książka została zaktualizowana', life: 3000 });
        }
    });
};
</script>

<template>
    <AppLayout>
        <ItemHeader :route-back="route('books.index')" route-back-label="Powrót do listy">
            <template #header>
                Książka #{{ book.id }}
            </template>
            <template #right-buttons>
                <Button label="Zapisz" icon="pi pi-save" @click="save" :loading="form.processing" />
            </template>
        </ItemHeader>

        <div class="card">
            <div class="mb-4">
                <label class="font-semibold block mb-2">Tytuł</label>
                <InputText v-model="form.title" class="w-full" />
            </div>
            <div>
                <label class="font-semibold block mb-2">Treść (BBCode, podział stron: [page])</label>
                <Textarea v-model="form.content" rows="20" class="w-full" autoResize />
            </div>
        </div>
    </AppLayout>
</template>
