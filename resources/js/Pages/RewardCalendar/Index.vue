<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { ref } from "vue";
import { useConfirm } from "primevue";
import InputSwitch from "primevue/inputswitch";
import type { RewardCalendarListItem, RewardCalendarStyleOption } from "./types";

const props = defineProps<{
    calendars: RewardCalendarListItem[];
    styles: RewardCalendarStyleOption[];
}>();

const confirm = useConfirm();
const showCreate = ref(false);

const createForm = useForm({
    name: "",
    is_recurring: true,
    year: new Date().getFullYear() as number | null,
    style: "blue",
    enabled: true,
});

const createCalendar = () => {
    createForm
        .transform((data) => ({
            name: data.name,
            year: data.is_recurring ? null : data.year,
            style: data.style,
            enabled: data.enabled,
        }))
        .post(route("reward-calendars.store"), {
            onSuccess: () => {
                showCreate.value = false;
                createForm.reset();
            },
        });
};

const removeCalendar = (calendar: RewardCalendarListItem) => {
    confirm.require({
        header: "Usuwanie kalendarza",
        message: `Usunąć kalendarz „${calendar.name}” razem ze wszystkimi dniami?`,
        icon: "pi pi-exclamation-triangle",
        rejectProps: { label: "Anuluj", severity: "secondary" },
        acceptProps: { label: "Usuń", severity: "danger" },
        accept: () => router.delete(route("reward-calendars.destroy", { rewardCalendar: calendar.id }), { preserveScroll: true }),
    });
};

const formatRange = (calendar: RewardCalendarListItem) => {
    if (!calendar.starts_on || !calendar.ends_on) {
        return "Brak dni";
    }
    const strip = (date: string) => (calendar.is_recurring ? date.slice(5) : date);
    return `${strip(calendar.starts_on)} → ${strip(calendar.ends_on)}`;
};
</script>

<template>
    <AppLayout>
        <div class="card mb-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold">Kalendarze nagród</h2>
                <p class="text-sm text-surface-500">
                    Kalendarz trwa od pierwszego do ostatniego przypisanego dnia. Gracz otwiera tylko dzisiejszy dzień (czas Europe/Warsaw).
                </p>
            </div>
            <Button label="Nowy kalendarz" icon="pi pi-plus" @click="showCreate = true" />
        </div>

        <div class="card">
            <DataTable :value="props.calendars" paginator :rows="20" data-key="id">
                <Column field="id" header="ID" style="width: 6%" />
                <Column field="name" header="Nazwa" />
                <Column header="Powtarzanie">
                    <template #body="{ data }">
                        <Tag v-if="data.is_recurring" severity="info" value="Co roku" />
                        <Tag v-else severity="secondary" :value="`Jednorazowo: ${data.year}`" />
                    </template>
                </Column>
                <Column header="Zakres">
                    <template #body="{ data }">{{ formatRange(data) }}</template>
                </Column>
                <Column header="Dni">
                    <template #body="{ data }">
                        {{ data.days_count }}
                        <span v-if="data.special_days_count" class="text-surface-500">(w tym specjalne: {{ data.special_days_count }})</span>
                    </template>
                </Column>
                <Column field="style_label" header="Styl" />
                <Column header="Status">
                    <template #body="{ data }">
                        <Tag v-if="!data.enabled" severity="danger" value="Wyłączony" />
                        <Tag v-else-if="data.is_active_now" severity="success" value="Trwa teraz" />
                        <Tag v-else severity="secondary" value="Nieaktywny" />
                    </template>
                </Column>
                <Column header="Akcje" style="width: 16%">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Link :href="route('reward-calendars.show', { rewardCalendar: data.id })">
                                <Button size="small" label="Edytuj" icon="pi pi-pencil" />
                            </Link>
                            <Button size="small" severity="danger" icon="pi pi-trash" aria-label="Usuń" @click="removeCalendar(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showCreate" modal header="Nowy kalendarz" :style="{ width: '520px' }">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Nazwa</label>
                    <InputText v-model="createForm.name" placeholder="np. Wigilia" />
                    <small v-if="createForm.errors.name" class="text-red-500">{{ createForm.errors.name }}</small>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Powtarzanie</label>
                    <SelectButton
                        v-model="createForm.is_recurring"
                        :options="[{ label: 'Co roku', value: true }, { label: 'Jednorazowo', value: false }]"
                        option-label="label"
                        option-value="value"
                        :allow-empty="false"
                    />
                </div>
                <div v-if="!createForm.is_recurring" class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Rok</label>
                    <InputNumber v-model="createForm.year" :use-grouping="false" :min="2000" :max="2100" />
                    <small v-if="createForm.errors.year" class="text-red-500">{{ createForm.errors.year }}</small>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Styl okienek</label>
                    <Select v-model="createForm.style" :options="props.styles" option-label="label" option-value="value" />
                </div>
                <div class="flex items-center gap-3">
                    <InputSwitch v-model="createForm.enabled" />
                    <span>Włączony</span>
                </div>
                <Button label="Utwórz" icon="pi pi-check" :loading="createForm.processing" @click="createCalendar" />
            </div>
        </Dialog>
    </AppLayout>
</template>
