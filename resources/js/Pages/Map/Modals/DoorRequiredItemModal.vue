<script setup lang="ts">
import { ref, inject, Ref } from "vue";
import { DynamicDialogInstance } from "primevue/dynamicdialogoptions";
import { BaseItemResource } from "@/Resources/BaseItem.resource";
import { DoorResource } from "@/Resources/Door.resource";
import { useToast } from "primevue/usetoast";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import axios from "axios";

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        door: DoorResource
    }
}> | null>('dialogRef');
const emit = defineEmits(['close']);
const toast = useToast();

const selectedItem = ref<BaseItemResource | null>(null);
const filteredItems = ref<BaseItemResource[]>([]);

const search = async (query: string) => {
    const { data } = await axios.get(route('base-items.search', { query }));
    return data;
};

const filterItems = async ({ query }: { query: string }) => {
    filteredItems.value = await search(query);
};

const save = () => {
    router.patch(route('doors.required-item.update', { door: dialogRef?.value.data.door.id }), {
        base_item_id: selectedItem.value?.id || null,
    }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Zaktualizowano wymagany przedmiot', life: 4000 });
            dialogRef?.value.close();
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zaktualizować wymaganego przedmiotu', life: 6000 });
        }
    });
};

const clearItem = () => {
    selectedItem.value = null;
};
</script>

<template>
    <div class="p-4">
        <h2 class="text-xl mb-4">Ustaw wymagany przedmiot dla przejścia</h2>

        <div class="flex flex-col gap-4">
            <div class="field">
                <label for="requiredItem" class="block mb-2">Wymagany przedmiot</label>
                <AutoComplete
                    id="requiredItem"
                    class="w-full p-0"
                    v-model="selectedItem"
                    placeholder="Wyszukaj przedmiot"
                    :suggestions="filteredItems"
                    @complete="filterItems"
                    :option-label="(item: BaseItemResource | null) => item?.name || ''"
                    fluid
                >
                    <template #option="slotProps">
                        <div class="name-item flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <img
                                    class="h-12 w-12 object-cover"
                                    :src="slotProps.option.src"
                                    alt="Option Image"
                                    v-tip.item.top.show-id="slotProps.option"
                                />
                                <div class="text-center">
                                    <span class="font-semibold text-gray-800">
                                        [{{ slotProps.option.id }}] {{ slotProps.option.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </AutoComplete>
                <small class="block mt-1 text-gray-500">Pozostaw puste, jeśli nie ma wymaganego przedmiotu</small>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <Button label="Wyczyść" severity="secondary" outlined @click="clearItem" />
                <Button label="Anuluj" severity="secondary" outlined @click="emit('close')" />
                <Button label="Zapisz" @click="save" />
            </div>
        </div>
    </div>
</template>
