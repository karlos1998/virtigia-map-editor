<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import LootChestEditor from "@/Pages/BaseItem/Components/LootChestEditor.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { computed, ref } from "vue";
import { useConfirm, useToast } from "primevue";
import InputSwitch from "primevue/inputswitch";
import {
    MONTH_NAMES,
    formatCalendarDate,
    type RewardCalendarBaseItemPreview,
    type RewardCalendarDay,
    type RewardCalendarListItem,
    type RewardCalendarStyleOption,
    type RewardConfig,
} from "./types";

const props = defineProps<{
    calendar: RewardCalendarListItem;
    days: RewardCalendarDay[];
    baseItems: Record<number, RewardCalendarBaseItemPreview>;
    styles: RewardCalendarStyleOption[];
}>();

const confirm = useConfirm();
const toast = useToast();

const monthOptions = MONTH_NAMES.map((label, index) => ({ label, value: index + 1 }));

// ---------- Ustawienia kalendarza ----------
const calendarForm = useForm({
    name: props.calendar.name,
    is_recurring: props.calendar.is_recurring,
    year: (props.calendar.year ?? new Date().getFullYear()) as number | null,
    style: props.calendar.style,
    enabled: props.calendar.enabled,
});

const saveCalendar = () => {
    calendarForm
        .transform((data) => ({
            name: data.name,
            year: data.is_recurring ? null : data.year,
            style: data.style,
            enabled: data.enabled,
        }))
        .patch(route("reward-calendars.update", { rewardCalendar: props.calendar.id }), {
            preserveScroll: true,
            onSuccess: () => toast.add({ severity: "success", summary: "Kalendarz zapisany", life: 3000 }),
        });
};

// ---------- Edycja dnia ----------
type EditableTier = {
    uid: string;
    minOpenedDays: number;
    attributes: Record<string, any>;
};

const createUid = () => `tier-${Date.now()}-${Math.random().toString(16).slice(2)}`;

const dayDialogVisible = ref(false);
const editedDay = ref<RewardCalendarDay | null>(null);
const dayRewardsAttributes = ref<Record<string, any>>({});
const dayTiers = ref<EditableTier[]>([]);

const dayForm = useForm({
    month: 12,
    day: 1,
    type: "normal" as "normal" | "special",
});

const openNewDay = () => {
    const lastDay = props.days[props.days.length - 1];
    editedDay.value = null;
    dayForm.clearErrors();
    dayForm.month = lastDay?.month ?? 12;
    dayForm.day = lastDay ? Math.min(lastDay.day + 1, 31) : 1;
    dayForm.type = "normal";
    dayRewardsAttributes.value = {};
    dayTiers.value = [];
    dayDialogVisible.value = true;
};

const openEditDay = (day: RewardCalendarDay) => {
    editedDay.value = day;
    dayForm.clearErrors();
    dayForm.month = day.month;
    dayForm.day = day.day;
    dayForm.type = day.type;
    dayRewardsAttributes.value = day.rewards ? { lootChest: structuredClone(day.rewards) } : {};
    dayTiers.value = (day.tiers ?? []).map((tier) => ({
        uid: createUid(),
        minOpenedDays: tier.minOpenedDays,
        attributes: { lootChest: structuredClone(tier.rewards) },
    }));
    dayDialogVisible.value = true;
};

const addTier = () => {
    const highest = Math.max(-1, ...dayTiers.value.map((tier) => tier.minOpenedDays));
    dayTiers.value.push({ uid: createUid(), minOpenedDays: highest + 1, attributes: {} });
};

const removeTier = (uid: string) => {
    dayTiers.value = dayTiers.value.filter((tier) => tier.uid !== uid);
};

const sortedTiers = computed(() => [...dayTiers.value].sort((a, b) => a.minOpenedDays - b.minOpenedDays));

const saveDay = () => {
    const payload = dayForm.transform((data) => ({
        month: data.month,
        day: data.day,
        type: data.type,
        rewards: data.type === "normal" ? (dayRewardsAttributes.value.lootChest ?? { mode: "random", items: [] }) : null,
        tiers: data.type === "special"
            ? sortedTiers.value.map((tier) => ({
                minOpenedDays: tier.minOpenedDays,
                rewards: tier.attributes.lootChest ?? { mode: "random", items: [] },
            }))
            : null,
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dayDialogVisible.value = false;
            toast.add({ severity: "success", summary: "Dzień zapisany", life: 3000 });
        },
    };

    if (editedDay.value) {
        payload.patch(route("reward-calendars.days.update", {
            rewardCalendar: props.calendar.id,
            rewardCalendarDay: editedDay.value.id,
        }), options);
    } else {
        payload.post(route("reward-calendars.days.store", { rewardCalendar: props.calendar.id }), options);
    }
};

const removeDay = (day: RewardCalendarDay) => {
    confirm.require({
        header: "Usuwanie dnia",
        message: `Usunąć dzień ${formatCalendarDate(day.day, day.month)}?`,
        icon: "pi pi-exclamation-triangle",
        rejectProps: { label: "Anuluj", severity: "secondary" },
        acceptProps: { label: "Usuń", severity: "danger" },
        accept: () => router.delete(route("reward-calendars.days.destroy", {
            rewardCalendar: props.calendar.id,
            rewardCalendarDay: day.id,
        }), { preserveScroll: true }),
    });
};

// ---------- Podgląd nagród w tabeli ----------
const rewardItems = (config: RewardConfig | null | undefined) => (config?.items ?? []).map((item) => ({
    ...item,
    preview: props.baseItems[item.baseItemId] ?? null,
}));

const describeMode = (config: RewardConfig | null | undefined) => {
    if (!config || config.items.length === 0) {
        return "brak nagród";
    }
    if (config.mode === "guaranteed") {
        return "wszystkie";
    }
    return config.minRewards === config.maxRewards
        ? `losowo ${config.maxRewards}`
        : `losowo ${config.minRewards}–${config.maxRewards}`;
};

const normalDaysBefore = (day: RewardCalendarDay) => props.days.filter((other) => {
    if (other.type !== "normal") {
        return false;
    }
    return other.month < day.month || (other.month === day.month && other.day < day.day);
}).length;
</script>

<template>
    <AppLayout>
        <div class="card mb-4">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link :href="route('reward-calendars.index')">
                        <Button icon="pi pi-arrow-left" severity="secondary" text rounded aria-label="Wróć" />
                    </Link>
                    <h2 class="text-xl font-semibold">{{ props.calendar.name }}</h2>
                    <Tag v-if="!props.calendar.enabled" severity="danger" value="Wyłączony" />
                    <Tag v-else-if="props.calendar.is_active_now" severity="success" value="Trwa teraz" />
                </div>
                <Button label="Zapisz ustawienia" icon="pi pi-check" :loading="calendarForm.processing" @click="saveCalendar" />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Nazwa (widoczna na kafelkach)</label>
                    <InputText v-model="calendarForm.name" />
                    <small v-if="calendarForm.errors.name" class="text-red-500">{{ calendarForm.errors.name }}</small>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Powtarzanie</label>
                    <SelectButton
                        v-model="calendarForm.is_recurring"
                        :options="[{ label: 'Co roku', value: true }, { label: 'Jednorazowo', value: false }]"
                        option-label="label"
                        option-value="value"
                        :allow-empty="false"
                    />
                    <InputNumber
                        v-if="!calendarForm.is_recurring"
                        v-model="calendarForm.year"
                        :use-grouping="false"
                        :min="2000"
                        :max="2100"
                        placeholder="Rok"
                    />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium">Styl okienek w grze</label>
                    <Select v-model="calendarForm.style" :options="props.styles" option-label="label" option-value="value" />
                </div>
                <div class="flex items-center gap-3 pt-6">
                    <InputSwitch v-model="calendarForm.enabled" />
                    <span>Włączony</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">Dni kalendarza</h3>
                    <p class="text-sm text-surface-500">
                        Zakres: {{ props.calendar.starts_on ?? '—' }} → {{ props.calendar.ends_on ?? '—' }}.
                        Do progów dnia specjalnego liczą się otwarte zwykłe dni z tego samego roku.
                    </p>
                </div>
                <Button label="Dodaj dzień" icon="pi pi-plus" severity="success" @click="openNewDay" />
            </div>

            <div v-if="props.days.length === 0" class="rounded border border-dashed border-surface-300 p-6 text-center text-surface-500">
                Kalendarz nie ma jeszcze żadnych dni.
            </div>

            <DataTable v-else :value="props.days" data-key="id">
                <Column header="Data" style="width: 14%">
                    <template #body="{ data }">
                        <span class="font-medium">{{ formatCalendarDate(data.day, data.month) }}</span>
                    </template>
                </Column>
                <Column header="Typ" style="width: 10%">
                    <template #body="{ data }">
                        <Tag v-if="data.type === 'special'" severity="warn" value="Specjalny" />
                        <Tag v-else severity="secondary" value="Zwykły" />
                    </template>
                </Column>
                <Column header="Nagrody">
                    <template #body="{ data }">
                        <div v-if="data.type === 'normal'" class="flex flex-wrap items-center gap-2">
                            <span class="text-xs text-surface-500">{{ describeMode(data.rewards) }}</span>
                            <div v-for="item in rewardItems(data.rewards)" :key="item.baseItemId" class="flex items-center gap-1 rounded border border-surface-200 px-1">
                                <img v-if="item.preview" :src="item.preview.src" :alt="item.preview.name" class="h-6 w-6" />
                                <span class="text-xs">{{ item.preview?.name ?? `#${item.baseItemId}` }}</span>
                                <span class="text-xs text-surface-500">
                                    ×{{ item.minQuantity === item.maxQuantity ? item.minQuantity : `${item.minQuantity}–${item.maxQuantity}` }}
                                    <template v-if="data.rewards?.mode === 'random'">· {{ item.chancePercent }}%</template>
                                </span>
                            </div>
                        </div>
                        <div v-else class="flex flex-col gap-1">
                            <div v-for="tier in [...(data.tiers ?? [])].reverse()" :key="tier.minOpenedDays" class="flex flex-wrap items-center gap-2">
                                <Tag severity="contrast" :value="`≥ ${tier.minOpenedDays} dni`" />
                                <span class="text-xs text-surface-500">{{ describeMode(tier.rewards) }}</span>
                                <div v-for="item in rewardItems(tier.rewards)" :key="item.baseItemId" class="flex items-center gap-1 rounded border border-surface-200 px-1">
                                    <img v-if="item.preview" :src="item.preview.src" :alt="item.preview.name" class="h-6 w-6" />
                                    <span class="text-xs">{{ item.preview?.name ?? `#${item.baseItemId}` }}</span>
                                </div>
                            </div>
                            <small class="text-surface-500">Zwykłych dni przed tym dniem: {{ normalDaysBefore(data) }}</small>
                        </div>
                    </template>
                </Column>
                <Column header="Akcje" style="width: 10%">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Button size="small" icon="pi pi-pencil" aria-label="Edytuj" @click="openEditDay(data)" />
                            <Button size="small" severity="danger" icon="pi pi-trash" aria-label="Usuń" @click="removeDay(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog
            v-model:visible="dayDialogVisible"
            modal
            :header="editedDay ? `Dzień ${formatCalendarDate(editedDay.day, editedDay.month)}` : 'Nowy dzień'"
            :style="{ width: '1100px' }"
        >
            <div class="flex flex-col gap-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Miesiąc</label>
                        <Select v-model="dayForm.month" :options="monthOptions" option-label="label" option-value="value" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Dzień</label>
                        <InputNumber v-model="dayForm.day" :min="1" :max="31" show-buttons />
                        <small v-if="dayForm.errors.day" class="text-red-500">{{ dayForm.errors.day }}</small>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Typ dnia</label>
                        <SelectButton
                            v-model="dayForm.type"
                            :options="[{ label: 'Zwykły', value: 'normal' }, { label: 'Specjalny', value: 'special' }]"
                            option-label="label"
                            option-value="value"
                            :allow-empty="false"
                        />
                    </div>
                </div>

                <LootChestEditor v-if="dayForm.type === 'normal'" v-model:attributes="dayRewardsAttributes" />

                <template v-else>
                    <Message severity="info">
                        Gracz dostaje nagrodę z najwyższego progu, który spełnia (liczba otwartych zwykłych dni w tym kalendarzu w tym roku).
                        Próg „0” to nagroda pocieszenia.
                    </Message>
                    <div v-for="tier in sortedTiers" :key="tier.uid" class="rounded border border-surface-200 p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <label class="text-sm font-medium">Otwarto co najmniej</label>
                                <InputNumber v-model="tier.minOpenedDays" :min="0" :max="366" show-buttons />
                                <span class="text-sm">dni</span>
                            </div>
                            <Button icon="pi pi-trash" severity="danger" text rounded aria-label="Usuń próg" @click="removeTier(tier.uid)" />
                        </div>
                        <LootChestEditor v-model:attributes="tier.attributes" />
                    </div>
                    <Button label="Dodaj próg" icon="pi pi-plus" severity="secondary" @click="addTier" />
                </template>

                <div v-if="Object.keys(dayForm.errors).length" class="text-sm text-red-500">
                    <div v-for="(error, key) in dayForm.errors" :key="key">{{ error }}</div>
                </div>

                <div class="flex justify-end gap-2">
                    <Button label="Anuluj" severity="secondary" @click="dayDialogVisible = false" />
                    <Button label="Zapisz dzień" icon="pi pi-check" :loading="dayForm.processing" @click="saveDay" />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
