<script setup lang="ts">
import RoomDialog from "@/Pages/Hotel/Components/RoomDialog.vue";
import { HotelResource, HotelRoomResource } from "@/Resources/Hotel.resource";
import AppLayout from "@/layout/AppLayout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import { computed, ref } from "vue";
import { route } from "ziggy-js";

const props = defineProps<{
    hotel: HotelResource
}>()

const toast = useToast()

const hotelForm = useForm({
    name: props.hotel.name,
})

const isRoomDialogVisible = ref(false)
const editedRoom = ref<HotelRoomResource | null>(null)
const templateRoom = ref<HotelRoomResource | null>(null)

const rooms = computed<HotelRoomResource[]>(() => props.hotel.rooms ?? [])

const openCreateRoomDialog = (): void => {
    editedRoom.value = null
    templateRoom.value = null
    isRoomDialogVisible.value = true
}

const openEditRoomDialog = (room: HotelRoomResource): void => {
    editedRoom.value = room
    templateRoom.value = null
    isRoomDialogVisible.value = true
}

const openCloneRoomDialog = (room: HotelRoomResource): void => {
    editedRoom.value = null
    templateRoom.value = room
    isRoomDialogVisible.value = true
}

const saveHotel = (): void => {
    hotelForm.patch(route("hotels.update", props.hotel.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Zapisano nazwę hotelu",
                detail: "",
                life: 2500,
            })
        },
    })
}

const removeHotel = (): void => {
    if (!window.confirm(`Usunąć hotel "${props.hotel.name}"?`)) {
        return
    }

    router.delete(route("hotels.destroy", props.hotel.id))
}

const removeRoom = (room: HotelRoomResource): void => {
    const roomLabel = room.door?.name ?? `#${room.id}`

    if (!window.confirm(`Usunąć pokój "${roomLabel}" z hotelu?`)) {
        return
    }

    router.delete(route("hotels.rooms.destroy", {
        hotel: props.hotel.id,
        hotelRoom: room.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Pokój usunięty",
                detail: "",
                life: 2500,
            })
        },
    })
}
</script>

<template>
    <AppLayout>
        <RoomDialog
            v-model:visible="isRoomDialogVisible"
            :hotel-id="hotel.id"
            :room="editedRoom"
            :template-room="templateRoom"
            @closed="editedRoom = null; templateRoom = null"
        />

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link :href="route('hotels.index')" class="mb-2 inline-flex items-center gap-2 text-sm text-primary-500">
                    <i class="pi pi-arrow-left" />
                    Wróć do listy hoteli
                </Link>
                <h1 class="text-3xl font-bold">{{ hotel.name }}</h1>
                <p class="text-surface-500 dark:text-surface-400">
                    Zarządzaj pokojami i przypinaj klucze do konkretnych drzwi.
                </p>
            </div>

            <div class="flex gap-2">
                <Button label="Dodaj pokój" icon="pi pi-plus" @click="openCreateRoomDialog" />
                <Button label="Usuń hotel" icon="pi pi-trash" severity="danger" outlined @click="removeHotel" />
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[24rem_minmax(0,1fr)]">
            <div class="card h-fit">
                <div class="mb-4 text-lg font-semibold">Dane hotelu</div>

                <div class="flex flex-col gap-2">
                    <label for="hotel-name" class="font-semibold">Nazwa</label>
                    <InputText id="hotel-name" v-model="hotelForm.name" autocomplete="off" />
                    <Message v-if="hotelForm.errors.name" severity="error" size="small" variant="simple">
                        {{ hotelForm.errors.name }}
                    </Message>
                </div>

                <div class="mt-4 flex justify-end">
                    <Button label="Zapisz nazwę" icon="pi pi-save" :loading="hotelForm.processing" @click="saveHotel" />
                </div>
            </div>

            <div class="card">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-lg font-semibold">Pokoje</div>
                        <div class="text-sm text-surface-500 dark:text-surface-400">
                            {{ rooms.length }} pokoj{{ rooms.length === 1 ? "" : rooms.length < 5 ? "e" : "i" }} w tym hotelu
                        </div>
                    </div>
                </div>

                <div v-if="rooms.length" class="flex flex-col gap-3">
                    <div
                        v-for="room in rooms"
                        :key="room.id"
                        class="rounded-xl border border-surface-200 p-4 dark:border-surface-700"
                    >
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div class="flex flex-col gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Tag :value="`Pokój #${room.id}`" />
                                    <Tag v-if="room.door?.map" :value="room.door.map.name" severity="secondary" />
                                </div>

                                <div>
                                    <div class="text-xl font-semibold">
                                        {{ room.door?.name ?? 'Brak drzwi' }}
                                    </div>
                                    <div class="text-sm text-surface-500 dark:text-surface-400">
                                        Drzwi: ({{ room.door?.x }}, {{ room.door?.y }})
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="room.base_item?.src"
                                        :src="room.base_item.src"
                                        :alt="room.base_item.name"
                                        class="h-10 w-10 object-cover"
                                    />
                                    <div>
                                        <div class="font-semibold">
                                            {{ room.base_item ? `[${room.base_item.id}] ${room.base_item.name}` : 'Brak klucza' }}
                                        </div>
                                        <div class="text-sm text-surface-500 dark:text-surface-400">
                                            Klucz potrzebny do wejścia
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    label="Dodaj podobny"
                                    icon="pi pi-copy"
                                    severity="secondary"
                                    outlined
                                    @click="openCloneRoomDialog(room)"
                                />
                                <Button
                                    type="button"
                                    label="Edytuj"
                                    icon="pi pi-pencil"
                                    severity="info"
                                    outlined
                                    @click="openEditRoomDialog(room)"
                                />
                                <Button
                                    type="button"
                                    label="Usuń"
                                    icon="pi pi-trash"
                                    severity="danger"
                                    outlined
                                    @click="removeRoom(room)"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <Message v-else severity="info" :closable="false">
                    Ten hotel nie ma jeszcze żadnych pokoi. Dodaj pierwszy pokój i wybierz klucz oraz drzwi.
                </Message>
            </div>
        </div>
    </AppLayout>
</template>
