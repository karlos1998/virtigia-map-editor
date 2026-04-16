<script setup lang="ts">
import { BaseItemResource } from "@/Resources/BaseItem.resource";
import { DoorResource } from "@/Resources/Door.resource";
import { HotelRoomResource } from "@/Resources/Hotel.resource";
import { MapResource } from "@/Resources/Map.resource";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "primevue/usetoast";
import { computed, ref, watch } from "vue";
import { route } from "ziggy-js";

const visible = defineModel<boolean>("visible", { default: false });

const props = defineProps<{
    hotelId: number
    room?: HotelRoomResource | null
    templateRoom?: HotelRoomResource | null
    rooms: HotelRoomResource[]
}>()

const emit = defineEmits<{
    closed: []
}>()

const toast = useToast()

const form = useForm({
    base_item_id: null as number | null,
    price: 0,
    door_id: null as number | null,
})

const selectedItem = ref<BaseItemResource | null>(null)
const selectedMap = ref<MapResource | string | null>(null)
const selectedDoorId = ref<number | null>(null)

const itemSuggestions = ref<BaseItemResource[]>([])
const mapSuggestions = ref<MapResource[]>([])
const doors = ref<DoorResource[]>([])
const isLoadingDoors = ref(false)

const occupiedRooms = computed(() => {
    return props.rooms.filter((hotelRoom) => hotelRoom.id !== props.room?.id)
})

const resetState = async (): Promise<void> => {
    const sourceRoom = props.room ?? props.templateRoom ?? null

    selectedItem.value = sourceRoom?.base_item ?? null
    selectedMap.value = sourceRoom?.door?.map ?? null
    selectedDoorId.value = props.room?.door_id ?? null

    form.base_item_id = selectedItem.value?.id ?? null
    form.price = sourceRoom?.price ?? 0
    form.door_id = selectedDoorId.value
    form.clearErrors()

    if (typeof selectedMap.value === "object" && selectedMap.value?.id) {
        await loadDoors(selectedMap.value.id)
    } else {
        doors.value = []
    }
}

watch(visible, async (isVisible) => {
    if (isVisible) {
        await resetState()
        return
    }

    emit("closed")
})

watch(selectedItem, (item) => {
    form.base_item_id = item?.id ?? null
})

watch(selectedDoorId, (doorId) => {
    form.door_id = doorId
})

const searchItems = async ({ query }: { query: string }): Promise<void> => {
    const { data } = await axios.get(route("base-items.search"), {
        params: {
            query,
            category: "keys",
        },
    })

    itemSuggestions.value = data
}

const searchMaps = async ({ query }: { query: string }): Promise<void> => {
    const { data } = await axios.get(route("maps.search"), {
        params: {
            search: query,
        },
    })

    mapSuggestions.value = data
}

const loadDoors = async (mapId: number): Promise<void> => {
    isLoadingDoors.value = true

    try {
        const { data } = await axios.get(route("doors.by-map", { map: mapId }))
        doors.value = data

        if (!doors.value.some((door) => door.id === selectedDoorId.value)) {
            selectedDoorId.value = null
        }
    } finally {
        isLoadingDoors.value = false
    }
}

const selectMap = async (map: MapResource | null): Promise<void> => {
    selectedMap.value = map
    selectedDoorId.value = null
    form.door_id = null

    if (!map?.id) {
        doors.value = []
        return
    }

    await loadDoors(map.id)
}

const clearMap = (): void => {
    selectedMap.value = null
    selectedDoorId.value = null
    form.door_id = null
    doors.value = []
}

const getRoomUsingDoor = (doorId: number): HotelRoomResource | undefined => {
    return occupiedRooms.value.find((hotelRoom) => hotelRoom.door_id === doorId)
}

const isDoorOccupied = (doorId: number): boolean => {
    return Boolean(getRoomUsingDoor(doorId))
}

const getDoorOccupancyTooltip = (doorId: number): string => {
    const occupiedRoom = getRoomUsingDoor(doorId)

    if (!occupiedRoom) {
        return ""
    }

    const roomName = occupiedRoom.door?.name ?? `Pokój #${occupiedRoom.id}`
    const keyName = occupiedRoom.base_item ? `[${occupiedRoom.base_item.id}] ${occupiedRoom.base_item.name}` : "brak klucza"

    return `Te drzwi są już użyte przez ${roomName}. Klucz: ${keyName}.`
}

const submit = (): void => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: props.room ? "Pokój zaktualizowany" : "Pokój dodany",
                detail: "",
                life: 2500,
            })
            visible.value = false
        },
        onError: () => {
            toast.add({
                severity: "error",
                summary: "Nie udało się zapisać pokoju",
                detail: Object.values(form.errors)[0] ?? "",
                life: 4000,
            })
        },
    }

    if (props.room) {
        form.patch(route("hotels.rooms.update", {
            hotel: props.hotelId,
            hotelRoom: props.room.id,
        }), options)

        return
    }

    form.post(route("hotels.rooms.store", { hotel: props.hotelId }), options)
}
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :style="{ width: '55rem', maxWidth: '95vw' }"
        :header="room ? 'Edycja pokoju' : templateRoom ? 'Dodaj pokój na bazie istniejącego' : 'Dodaj pokój'"
    >
        <div class="flex flex-col gap-5">
            <div class="rounded-xl border border-surface-200 bg-surface-50 p-4 dark:border-surface-700 dark:bg-surface-900">
                <div class="mb-2 font-semibold">Klucz / base item</div>

                <AutoComplete
                    v-model="selectedItem"
                    :suggestions="itemSuggestions"
                    :option-label="(item: BaseItemResource | null) => item ? `[${item.id}] ${item.name}` : ''"
                    placeholder="Wyszukaj klucz po nazwie"
                    class="w-full"
                    fluid
                    @complete="searchItems"
                >
                    <template #option="{ option }">
                        <div class="flex items-center gap-3">
                            <img
                                :src="option.src"
                                class="h-10 w-10 object-cover"
                                :alt="option.name"
                                v-tip.item.top.show-id="option"
                            />
                            <span>[{{ option.id }}] {{ option.name }}</span>
                        </div>
                    </template>
                    <template #value="{ value }">
                        <div v-if="value" class="flex items-center gap-3">
                            <img
                                :src="value.src"
                                class="h-10 w-10 object-cover"
                                :alt="value.name"
                                v-tip.item.top.show-id="value"
                            />
                            <span>[{{ value.id }}] {{ value.name }}</span>
                        </div>
                    </template>
                </AutoComplete>

                <div
                    v-if="selectedItem"
                    class="mt-4 flex items-center gap-3 rounded-lg border border-surface-200 bg-white p-3 dark:border-surface-700 dark:bg-surface-950"
                >
                    <img
                        :src="selectedItem.src"
                        class="h-12 w-12 object-cover"
                        :alt="selectedItem.name"
                        v-tip.item.top.show-id="selectedItem"
                    />
                    <div>
                        <div class="text-xs uppercase tracking-wide text-surface-500 dark:text-surface-400">Wybrany klucz</div>
                        <div class="font-semibold">[{{ selectedItem.id }}] {{ selectedItem.name }}</div>
                    </div>
                </div>

                <Message v-if="form.errors.base_item_id" severity="error" size="small" variant="simple">
                    {{ form.errors.base_item_id }}
                </Message>
            </div>

            <div class="rounded-xl border border-surface-200 bg-surface-50 p-4 dark:border-surface-700 dark:bg-surface-900">
                <div class="mb-2 font-semibold">Cena pokoju</div>

                <InputNumber
                    v-model="form.price"
                    :min="0"
                    :step="1"
                    :useGrouping="false"
                    fluid
                />

                <Message v-if="form.errors.price" severity="error" size="small" variant="simple">
                    {{ form.errors.price }}
                </Message>
            </div>

            <div class="rounded-xl border border-surface-200 bg-surface-50 p-4 dark:border-surface-700 dark:bg-surface-900">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <div class="font-semibold">Mapa z drzwiami</div>
                        <div class="text-sm text-surface-500 dark:text-surface-400">
                            Wyszukaj mapę, a potem wybierz jedne drzwi z listy.
                        </div>
                    </div>

                    <Button
                        v-if="typeof selectedMap === 'object' && selectedMap?.id"
                        type="button"
                        label="Zmień mapę"
                        severity="secondary"
                        outlined
                        icon="pi pi-refresh"
                        @click="clearMap"
                    />
                </div>

                <div v-if="typeof selectedMap !== 'object' || !selectedMap?.id" class="flex flex-col gap-2">
                    <AutoComplete
                        v-model="selectedMap"
                        :suggestions="mapSuggestions"
                        optionLabel="name"
                        placeholder="Szukaj mapy po nazwie"
                        class="w-full"
                        fluid
                        @complete="searchMaps"
                        @item-select="({ value }) => selectMap(value)"
                    >
                        <template #option="{ option }">
                            <div class="flex items-center gap-3">
                                <Tag :value="`#${option.id}`" />
                                <span>{{ option.name }}</span>
                            </div>
                        </template>
                    </AutoComplete>
                </div>

                <div v-else class="flex flex-col gap-4">
                    <div class="rounded-lg border border-primary-200 bg-primary-50 p-3 dark:border-primary-800 dark:bg-primary-950/40">
                        <div class="text-xs uppercase tracking-wide text-primary-500">Wybrana mapa</div>
                        <div class="text-lg font-semibold">{{ typeof selectedMap === 'object' ? selectedMap.name : '' }}</div>
                    </div>

                    <div v-if="isLoadingDoors" class="py-6 text-center text-surface-500 dark:text-surface-400">
                        Ładowanie drzwi...
                    </div>

                    <div v-else-if="doors.length" class="grid gap-3 md:grid-cols-2">
                        <button
                            v-for="door in doors"
                            :key="door.id"
                            type="button"
                            class="rounded-xl border p-4 text-left transition"
                            :class="selectedDoorId === door.id
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/40'
                                : isDoorOccupied(door.id)
                                    ? 'border-surface-300 bg-surface-100 hover:border-surface-400 dark:border-surface-600 dark:bg-surface-800'
                                    : 'border-surface-200 bg-white hover:border-primary-300 dark:border-surface-700 dark:bg-surface-900'"
                            @click="selectedDoorId = door.id"
                        >
                            <div class="mb-2 flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold">{{ door.name }}</span>
                                    <i
                                        v-if="isDoorOccupied(door.id)"
                                        class="pi pi-exclamation-triangle text-amber-500"
                                        v-tooltip.top="getDoorOccupancyTooltip(door.id)"
                                    />
                                </div>
                                <div class="flex items-center gap-2">
                                    <Tag
                                        v-if="isDoorOccupied(door.id)"
                                        value="Zajęte"
                                        severity="secondary"
                                    />
                                    <Tag :value="`#${door.id}`" severity="contrast" />
                                </div>
                            </div>
                            <div class="text-sm text-surface-500 dark:text-surface-400">
                                Drzwi na mapie: ({{ door.x }}, {{ door.y }})
                            </div>
                            <div class="text-sm text-surface-500 dark:text-surface-400">
                                Prowadzą do: {{ door.target_map?.name ?? door.name }}
                            </div>
                            <div
                                v-if="isDoorOccupied(door.id)"
                                class="mt-2 text-sm text-surface-600 dark:text-surface-300"
                            >
                                Już przypisane do innego pokoju w tym hotelu.
                            </div>
                        </button>
                    </div>

                    <Message v-else severity="warn" :closable="false">
                        Ta mapa nie ma żadnych drzwi.
                    </Message>
                </div>

                <Message v-if="form.errors.door_id" severity="error" size="small" variant="simple">
                    {{ form.errors.door_id }}
                </Message>
            </div>

            <div class="flex justify-end gap-2">
                <Button type="button" label="Anuluj" severity="secondary" outlined @click="visible = false" />
                <Button
                    type="button"
                    label="Zapisz"
                    icon="pi pi-check"
                    :loading="form.processing"
                    :disabled="!form.base_item_id || form.price === null || form.price < 0 || !form.door_id"
                    @click="submit"
                />
            </div>
        </div>
    </Dialog>
</template>
