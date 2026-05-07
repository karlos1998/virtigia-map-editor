<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import {useForm} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {ref} from "vue";

type SeasonalEvent = {
    id: number;
    name: string;
    slug: string;
    active: boolean | null;
    starts_at: string | null;
    ends_at: string | null;
    is_currently_active: boolean;
}

const props = defineProps<{ events: SeasonalEvent[] }>();

const createForm = useForm({
    name: "",
    slug: "",
    active: null as boolean | null,
    starts_at: null as string | null,
    ends_at: null as string | null,
});

const editForm = useForm({
    name: "",
    slug: "",
    active: null as boolean | null,
    starts_at: null as string | null,
    ends_at: null as string | null,
});

const selected = ref<SeasonalEvent | null>(null);
const showCreate = ref(false);
const showEdit = ref(false);

const createEvent = () => {
    createForm.post(route("seasonal-events.store"), {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
        },
    });
};

const openEdit = (event: SeasonalEvent) => {
    selected.value = event;
    editForm.name = event.name;
    editForm.slug = event.slug;
    editForm.active = event.active;
    editForm.starts_at = event.starts_at ? event.starts_at.slice(0, 16) : null;
    editForm.ends_at = event.ends_at ? event.ends_at.slice(0, 16) : null;
    showEdit.value = true;
};

const updateEvent = () => {
    if (!selected.value) return;
    editForm.patch(route("seasonal-events.update", {seasonalEvent: selected.value.id}), {
        preserveScroll: true,
        onSuccess: () => {
            showEdit.value = false;
            selected.value = null;
        },
    });
};

const removeEvent = (eventId: number) => {
    useForm({}).delete(route("seasonal-events.destroy", {seasonalEvent: eventId}), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <div class="card mb-4">
            <Button label="Dodaj wydarzenie" icon="pi pi-plus" @click="showCreate = true"/>
        </div>

        <div class="card">
            <DataTable :value="props.events" paginator :rows="20">
                <Column field="id" header="ID" style="width: 8%"/>
                <Column field="name" header="Nazwa"/>
                <Column field="slug" header="Slug"/>
                <Column header="Tryb aktywacji">
                    <template #body="{ data }">
                        <Tag v-if="data.active === true" severity="success" value="Ręcznie: aktywne"/>
                        <Tag v-else-if="data.active === false" severity="danger" value="Ręcznie: nieaktywne"/>
                        <Tag v-else severity="secondary" value="Zakres dat"/>
                    </template>
                </Column>
                <Column header="Status teraz">
                    <template #body="{ data }">
                        <Tag :severity="data.is_currently_active ? 'success' : 'secondary'" :value="data.is_currently_active ? 'Aktywne' : 'Nieaktywne'"/>
                    </template>
                </Column>
                <Column header="Akcje" style="width: 18%">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Button size="small" severity="secondary" label="Edytuj" icon="pi pi-pencil" @click="openEdit(data)"/>
                            <Button size="small" severity="danger" label="Usuń" icon="pi pi-trash" @click="removeEvent(data.id)"/>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showCreate" modal header="Nowe wydarzenie" :style="{ width: '560px' }">
            <div class="flex flex-col gap-3">
                <InputText v-model="createForm.name" placeholder="Nazwa (np. Wielkanoc)"/>
                <InputText v-model="createForm.slug" placeholder="Slug (opcjonalnie)"/>
                <Select v-model="createForm.active" :options="[
                    { label: 'Zakres dat', value: null },
                    { label: 'Aktywne (ręcznie)', value: true },
                    { label: 'Nieaktywne (ręcznie)', value: false },
                ]" optionLabel="label" optionValue="value"/>
                <InputText v-model="createForm.starts_at" type="datetime-local" placeholder="Początek"/>
                <InputText v-model="createForm.ends_at" type="datetime-local" placeholder="Koniec"/>
                <Button label="Utwórz" icon="pi pi-check" @click="createEvent"/>
            </div>
        </Dialog>

        <Dialog v-model:visible="showEdit" modal header="Edycja wydarzenia" :style="{ width: '560px' }">
            <div class="flex flex-col gap-3">
                <InputText v-model="editForm.name" placeholder="Nazwa"/>
                <InputText v-model="editForm.slug" placeholder="Slug (opcjonalnie)"/>
                <Select v-model="editForm.active" :options="[
                    { label: 'Zakres dat', value: null },
                    { label: 'Aktywne (ręcznie)', value: true },
                    { label: 'Nieaktywne (ręcznie)', value: false },
                ]" optionLabel="label" optionValue="value"/>
                <InputText v-model="editForm.starts_at" type="datetime-local" placeholder="Początek"/>
                <InputText v-model="editForm.ends_at" type="datetime-local" placeholder="Koniec"/>
                <Button label="Zapisz" icon="pi pi-check" @click="updateEvent"/>
            </div>
        </Dialog>
    </AppLayout>
</template>

