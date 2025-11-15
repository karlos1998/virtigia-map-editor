<script setup lang="ts">
// @ts-ignore - allow importing .vue layout without declaration
import AppLayout from "@/layout/AppLayout.vue";
import { useForm, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import {useToast, useConfirm} from "primevue";
import {ref} from 'vue';
import axios from 'axios';

interface CalendarItem {
    id: number;
    base_item?: {
        name: string;
    } | null;
    quantity: number;
}

interface CalendarDay {
    id: number;
    name?: string | null;
    day: number;
    month: number;
    year?: number | null;
    items: CalendarItem[];
}

const { days } = defineProps<{
    days: CalendarDay[];
}>();

const toast = useToast();
const confirm = useConfirm();

const dayForm = useForm({
    day: "",
    month: "",
    year: "",
    name: "",
});

const itemForm = useForm({
    calendarDayId: null as number | null,
    baseItemId: null as number | null,
    quantity: 1,
});

const showAddDayDialog = ref(false);
const selectedDate = ref<Date | null>(null);
const recurring = ref(false);
const dayName = ref('');

// add-item dialog state
const showAddItemDialog = ref(false);
const dialogDayId = ref<number | null>(null);
const selectedItem = ref<any | null>(null);
const filteredItems = ref<any[]>([]);
const dialogQuantity = ref<number>(1);

const createDay = () => {
    if (!selectedDate.value && !recurring.value) {
        toast.add({severity: 'warn', summary: 'Wybierz datę', detail: 'Proszę wybrać datę', life: 3000});
        return;
    }

    const date = selectedDate.value;

    // populate form fields (use null or numbers). useForm will send these values
    dayForm.day = date ? date.getDate() : null;
    dayForm.month = date ? date.getMonth() + 1 : null;
    dayForm.year = recurring.value ? null : (date ? date.getFullYear() : null);
    dayForm.name = dayName.value || null;

    // submit using form state
    dayForm.post(route("calendar-days.store"), {
        onSuccess: () => {
            toast.add({severity: 'success', summary: 'Dodano', detail: 'Dzień został dodany', life: 3000});
            dayForm.reset();
            selectedDate.value = null;
            recurring.value = false;
            dayName.value = '';
            showAddDayDialog.value = false;
        },
        onError: (errors: any) => {
            toast.add({severity: 'error', summary: 'Błąd', detail: errors.message || 'Błąd walidacji', life: 6000});
        }
    });
};

const searchItems = async (query: string) => {
    const {data} = await axios.get(route('base-items.search', {query}));
    return data;
};

const filterItems = async ({query}: { query: string }) => {
    filteredItems.value = await searchItems(query);
};

const openAddItemDialog = (dayId: number) => {
    dialogDayId.value = dayId;
    selectedItem.value = null;
    dialogQuantity.value = 1;
    showAddItemDialog.value = true;
};

const confirmAddItem = () => {
    if (!selectedItem.value) {
        toast.add({severity: 'warn', summary: 'Wybierz item', detail: 'Wybierz przedmiot', life: 3000});
        return;
    }

    itemForm.calendarDayId = dialogDayId.value;
    itemForm.baseItemId = selectedItem.value.id;
    itemForm.quantity = dialogQuantity.value || 1;

    itemForm.post(route('calendar-days.items.store'), {
        onSuccess: () => {
            toast.add({severity: 'success', summary: 'Dodano', detail: 'Item przypisany', life: 3000});
            selectedItem.value = null;
            filteredItems.value = [];
            showAddItemDialog.value = false;
        },
        onError: (errors: any) => {
            toast.add({severity: 'error', summary: 'Błąd', detail: errors.message || 'Błąd walidacji', life: 6000});
        }
    });
};

const removeItem = (id: number) => {
    confirm.require({
        message: 'Czy na pewno chcesz usunąć tę nagrodę?',
        header: 'Potwierdzenie usunięcia',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.delete(route("calendar-days.items.destroy", {id}), {
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Usunięto',
                        detail: 'Nagroda została usunięta',
                        life: 3000
                    });
                    router.reload();
                },
                onError: (errors: any) => {
                    toast.add({
                        severity: 'error',
                        summary: 'Błąd',
                        detail: errors.message || 'Nie udało się usunąć',
                        life: 6000
                    });
                }
            });
        }
    });
};

const deleteDay = (id: number) => {
    confirm.require({
        message: 'Czy na pewno chcesz usunąć dzień i wszystkie przypisane do niego nagrody?',
        header: 'Potwierdzenie usunięcia dnia',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.delete(route("calendar-days.destroy", {day: id}), {
                onSuccess: () => {
                    toast.add({severity: 'success', summary: 'Usunięto', detail: 'Dzień został usunięty', life: 3000});
                    router.reload();
                },
                onError: (errors: any) => {
                    toast.add({
                        severity: 'error',
                        summary: 'Błąd',
                        detail: errors.message || 'Nie udało się usunąć dnia',
                        life: 6000
                    });
                }
            });
        }
    });
};
</script>

<template>
    <AppLayout>
        <Toast/>
        <ConfirmDialog/>
        <div class="card">
            <h1 class="mb-4 text-2xl font-bold">
                Kalendarz nagród (globalny)
            </h1>

            <div class="mb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="mb-2 font-semibold">Dni</h3>
                        <p class="text-sm text-gray-500">Utwórz nowy dzień kalendarza lub edytuj istniejące.</p>
                    </div>

                    <div>
                        <Button label="Dodaj dzień" icon="pi pi-plus" class="p-button-primary"
                                @click.prevent="showAddDayDialog = true"/>
                    </div>
                </div>

                <Dialog v-model:visible="showAddDayDialog" header="Dodaj dzień" modal :style="{ width: '30rem' }">
                    <div class="grid gap-2">
                        <label class="font-medium">Data</label>
                        <Calendar v-model="selectedDate" dateFormat="dd/mm/yy" showIcon/>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" v-model="recurring" class="w-4 h-4"/>
                            <label class="text-sm">Powtarzalny (co roku) — brak roku</label>
                        </div>

                        <label class="font-medium">Nazwa (opcjonalnie)</label>
                        <input v-model="dayName" class="input" placeholder="np. Wigilia"/>

                        <div class="flex justify-end gap-2 mt-4">
                            <Button label="Anuluj" class="p-button-text" icon="pi pi-times"
                                    @click="showAddDayDialog = false"/>
                            <Button label="Zapisz" class="p-button-primary" icon="pi pi-check"
                                    @click.prevent="createDay"/>
                        </div>
                    </div>
                </Dialog>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div v-for="day in days" :key="day.id" class="w-full rounded-lg shadow p-4 bg-white">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-sm text-gray-500">Data</div>
                            <div class="text-lg font-bold">
                                {{ `${day.day}/${day.month}${day.year ? '/' + day.year : ''}` }}
                            </div>
                            <div class="text-sm text-gray-600">{{ day.name ?? '' }}</div>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-danger" @click.prevent="deleteDay(day.id)">Usuń</button>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="text-sm font-semibold mb-2">Nagrody</div>
                        <div v-if="!day.items || day.items.length === 0" class="text-sm text-gray-400">Brak nagród</div>
                        <div v-for="item in day.items" :key="item.id"
                             class="flex items-center justify-between py-2 border-t">
                            <div class="flex items-center gap-3">
                                <img v-tip.item="item.base_item" v-if="item.base_item?.src" :src="item.base_item.src"
                                     class="h-10 w-10 object-cover rounded"/>
                                <div>
                                    <div class="font-semibold">[{{ item.base_item?.id }}] {{
                                            item.base_item?.name
                                        }}
                                    </div>
                                    <div class="text-sm text-gray-500">x{{ item.quantity }}</div>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-danger" @click.prevent="removeItem(item.id)">Usuń</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <Button label="Dodaj item" icon="pi pi-plus" class="p-button-primary"
                                @click.prevent="openAddItemDialog(day.id)"/>
                    </div>
                    <Dialog v-model:visible="showAddItemDialog" header="Dodaj item" modal
                            :style="{ width: '30rem' }">
                        <div class="grid gap-2">
                            <label class="font-medium">Wyszukaj item</label>
                            <AutoComplete v-model="selectedItem" :suggestions="filteredItems"
                                          @complete="filterItems" field="name">
                                <template #option="slotProps">
                                    <div class="name-item flex items-center space-x-4">
                                        <img v-if="slotProps.option.src" :src="slotProps.option.src"
                                             class="h-12 w-12 object-cover rounded"/>
                                        <span class="font-semibold text-gray-800">[{{
                                                slotProps.option.id
                                            }}] {{ slotProps.option.name }}</span>
                                    </div>
                                </template>
                            </AutoComplete>
                            <label class="font-medium">Ilość</label>
                            <InputNumber v-model="dialogQuantity" mode="decimal"/>
                            <div class="flex justify-end gap-2 mt-4">
                                <Button label="Anuluj" class="p-button-text" icon="pi pi-times"
                                        @click="showAddItemDialog = false"/>
                                <Button label="Zapisz" class="p-button-primary" icon="pi pi-check"
                                        @click.prevent="confirmAddItem"/>
                            </div>
                        </div>
                    </Dialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
