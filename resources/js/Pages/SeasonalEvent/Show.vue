<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import ItemHeader from "@/Components/ItemHeader.vue";
import {route} from "ziggy-js";
import {Link} from "@inertiajs/vue3";

type EventData = {
    id: number;
    name: string;
    slug: string;
    active: boolean | null;
    starts_at: string | null;
    ends_at: string | null;
    is_currently_active: boolean;
}

type BaseNpcRef = {
    id: number;
    name: string;
    lvl: number;
    rank: string | null;
    locations_count: number;
    npcs_preview: { id: number }[];
}

type DialogNpcRef = {
    id: number;
    base_npc_id: number | null;
    base_npc_name: string | null;
}

type DialogOptionRef = {
    option_id: number;
    option_label: string;
    node_id: number | null;
    node_type: string | null;
    dialog_id: number | null;
    dialog_name: string | null;
    dialog_npcs: DialogNpcRef[];
}

defineProps<{
    event: EventData;
    base_npcs: BaseNpcRef[];
    dialog_options: DialogOptionRef[];
}>();

const formatDate = (value: string | null): string => {
    if (!value) return "brak";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat("pl-PL", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        timeZoneName: "short",
    }).format(date);
};
</script>

<template>
    <AppLayout>
        <ItemHeader :route-back="route('seasonal-events.index')">
            <template #header>
                #{{ event.id }} - {{ event.name }}
            </template>
        </ItemHeader>

        <div class="card mb-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div><b>Slug:</b> {{ event.slug }}</div>
                <div>
                    <b>Status teraz:</b>
                    <Tag :severity="event.is_currently_active ? 'success' : 'secondary'" :value="event.is_currently_active ? 'Aktywne' : 'Nieaktywne'" />
                </div>
                <div><b>Tryb ręczny:</b> {{ event.active === null ? 'Zakres dat' : (event.active ? 'Aktywne' : 'Nieaktywne') }}</div>
                <div><b>Zakres:</b> {{ formatDate(event.starts_at) }} - {{ formatDate(event.ends_at) }}</div>
            </div>
        </div>

        <div class="card mb-4">
            <h3 class="mb-3">Base NPC powiązane z wydarzeniem ({{ base_npcs.length }})</h3>
            <DataTable :value="base_npcs" paginator :rows="20">
                <Column field="id" header="ID" style="width: 8%"/>
                <Column header="Base NPC">
                    <template #body="{ data }">
                        <div class="flex items-center justify-between gap-3">
                            <span>{{ data.name }}</span>
                            <Link :href="route('base-npcs.show', { baseNpc: data.id })">
                                <Button size="small" label="Przejdź" icon="pi pi-arrow-right"/>
                            </Link>
                        </div>
                    </template>
                </Column>
                <Column header="Lvl + Rank" style="width: 16%">
                    <template #body="{ data }">
                        <Tag severity="secondary" :value="`Lvl ${data.lvl} / ${data.rank || '-'}`" />
                    </template>
                </Column>
                <Column field="locations_count" header="Lokalizacje" style="width: 14%"/>
                <Column header="Powiązane NPC (5 pierwszych)">
                    <template #body="{ data }">
                        <div v-if="!data.npcs_preview.length">Brak</div>
                        <div v-else class="flex flex-wrap gap-2">
                            <Link
                                v-for="npc in data.npcs_preview"
                                :key="npc.id"
                                :href="route('npcs.show', { npc: npc.id })"
                            >
                                <Button size="small" severity="secondary" :label="`#${npc.id}`"/>
                            </Link>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <div class="card">
            <h3 class="mb-3">Opcje dialogowe z warunkiem tego wydarzenia ({{ dialog_options.length }})</h3>
            <DataTable :value="dialog_options" paginator :rows="20">
                <Column field="option_id" header="Option ID" style="width: 10%"/>
                <Column field="option_label" header="Treść opcji"/>
                <Column header="Dialog">
                    <template #body="{ data }">
                        <div v-if="data.dialog_id" class="flex items-center justify-between gap-3">
                            <span>#{{ data.dialog_id }} {{ data.dialog_name || 'Bez nazwy' }}</span>
                            <Link :href="route('dialogs.show', { dialog: data.dialog_id })">
                                <Button size="small" label="Przejdź" icon="pi pi-arrow-right"/>
                            </Link>
                        </div>
                        <span v-else>-</span>
                    </template>
                </Column>
                <Column header="NPC dialogu">
                    <template #body="{ data }">
                        <span v-if="!data.dialog_npcs.length">Brak</span>
                        <div v-else class="flex flex-wrap gap-2">
                            <Tag
                                v-for="npc in data.dialog_npcs"
                                :key="npc.id"
                                severity="secondary"
                                :value="`#${npc.id} ${npc.base_npc_name || 'NPC'}`"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
