<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';

type Option = { id: number; name: string };
type SearchKind = 'map' | 'dialog' | 'base';
type BooleanFilter = 'has_dialog' | 'enabled' | 'in_group' | 'auto_start_dialog' | 'is_aggressive';
type SelectFilter = BooleanFilter | 'category' | 'rank' | 'placement';
export type NpcFilters = Partial<Record<BooleanFilter | 'map_id' | 'base_npc_id' | 'dialog_id' | 'min_lvl' | 'max_lvl', number | null>>
    & Partial<Record<'category' | 'rank' | 'placement', string | null>>;
const props = withDefaults(defineProps<{
    filters: NpcFilters;
    selectedMap?: Option | null;
    selectedDialog?: Option | null;
    selectedBaseNpc?: Option | null;
    baseList?: boolean;
}>(), { baseList: false });
const emit = defineEmits<{ applied: [] }>();
const page = usePage<{ availableRanks: { label: string; value: string }[] }>();
const values = reactive<NpcFilters>({ ...props.filters });
const selectedMap = ref(props.selectedMap ?? null);
const selectedDialog = ref(props.selectedDialog ?? null);
const selectedBaseNpc = ref(props.selectedBaseNpc ?? null);
const suggestions = reactive<Record<SearchKind, Option[]>>({ map: [], dialog: [], base: [] });
const requestIds: Partial<Record<SearchKind, number>> = {};
const searchError = ref('');
const collapsed = ref(Object.values(props.filters).every(value => value == null || value === ''));
const booleanOptions = [{ label: 'Tak', value: 1 }, { label: 'Nie', value: 0 }];
const selectFilters = computed<Array<{ key: SelectFilter; label: string; options: { label: string; value: string | number }[] }>>(() => [
    { key: 'has_dialog', label: 'Ma dialog', options: booleanOptions },
    { key: 'category', label: 'Kategoria', options: [{ label: 'NPC', value: 'NPC' }, { label: 'MOB', value: 'MOB' }] },
    { key: 'rank', label: 'Ranga', options: page.props.availableRanks },
    { key: 'enabled', label: 'Włączony', options: booleanOptions },
    { key: 'in_group', label: 'W grupie', options: booleanOptions },
    { key: 'auto_start_dialog', label: 'Automatyczny dialog', options: booleanOptions },
    { key: 'is_aggressive', label: 'Agresywny', options: booleanOptions },
    { key: 'placement', label: 'Lokalizacje instancji', options: [{ label: 'Bez lokalizacji', value: 'none' }, { label: 'Jedna lokalizacja', value: 'one' }, { label: 'Wiele lokalizacji', value: 'many' }] },
]);
const filterErrors = computed(() => Object.entries(page.props.errors).filter(([key]) => [
    'map_id', 'base_npc_id', 'dialog_id', 'has_dialog', 'enabled', 'in_group', 'auto_start_dialog',
    'is_aggressive', 'category', 'rank', 'min_lvl', 'max_lvl', 'placement',
].includes(key)));
watch(() => props.filters, filters => {
    (Object.keys(values) as (keyof NpcFilters)[]).forEach(key => delete values[key]);
    Object.assign(values, filters);
    selectedMap.value = props.selectedMap ?? null;
    selectedDialog.value = props.selectedDialog ?? null;
    selectedBaseNpc.value = props.selectedBaseNpc ?? null;
});
const search = async (kind: SearchKind, query: string) => {
    const requestId = requestIds[kind] = (requestIds[kind] ?? 0) + 1;
    if (query.trim().length < 2) {
        suggestions[kind] = [];
        return;
    }
    const routes = { map: 'maps.search', dialog: 'dialogs.search', base: 'base-npcs.search' };
    try {
        const { data } = await axios.get(route(routes[kind]), { params: kind === 'map' ? { search: query } : { query } });
        if (requestIds[kind] === requestId) suggestions[kind] = data.data ?? data;
        searchError.value = '';
    } catch {
        if (requestIds[kind] === requestId) suggestions[kind] = [];
        searchError.value = 'Nie udało się wyszukać danych. Spróbuj ponownie.';
    }
};
const apply = () => {
    const filters = { ...values, map_id: selectedMap.value?.id, dialog_id: selectedDialog.value?.id, base_npc_id: selectedBaseNpc.value?.id };
    const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => value != null && value !== ''));
    router.get(route(props.baseList ? 'base-npcs.index' : 'npcs.index'), params, {
        preserveState: true, preserveScroll: true, replace: true,
        onSuccess: () => emit('applied'),
    });
};
const clear = () => {
    (Object.keys(values) as (keyof NpcFilters)[]).forEach(key => delete values[key]);
    selectedMap.value = selectedDialog.value = selectedBaseNpc.value = null;
    apply();
};
</script>

<template>
    <Fieldset v-model:collapsed="collapsed" legend="Filtry zaawansowane" toggleable class="mb-4">
        <form class="flex flex-col gap-4" @submit.prevent="apply">
            <p v-if="baseList" class="text-sm text-surface-500 dark:text-surface-400">Filtry mapy, dialogu i statusu muszą spełniać te same instancje bazowego NPC.</p>
            <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-4">
                <div class="flex flex-col gap-2">
                    <label for="npc-filter-map">Mapa</label>
                    <AutoComplete input-id="npc-filter-map" v-model="selectedMap" :suggestions="suggestions.map" option-label="name" force-selection placeholder="Wyszukaj mapę" @complete="search('map', $event.query)" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="npc-filter-dialog">Konkretny dialog</label>
                    <AutoComplete input-id="npc-filter-dialog" v-model="selectedDialog" :suggestions="suggestions.dialog" option-label="name" force-selection placeholder="Wyszukaj dialog" @complete="search('dialog', $event.query)" />
                </div>
                <div v-if="!baseList" class="flex flex-col gap-2">
                    <label for="npc-filter-base">Bazowy NPC</label>
                    <AutoComplete input-id="npc-filter-base" v-model="selectedBaseNpc" :suggestions="suggestions.base" option-label="name" force-selection placeholder="Wyszukaj bazowego NPC" @complete="search('base', $event.query)" />
                </div>
                <div v-for="filter in selectFilters" :key="filter.key" class="flex flex-col gap-2">
                    <label :for="`npc-filter-${filter.key}`">{{ filter.label }}</label>
                    <Select :input-id="`npc-filter-${filter.key}`" v-model="values[filter.key]" :options="filter.options" option-label="label" option-value="value" show-clear placeholder="Dowolny" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="npc-min-lvl">Poziom od</label>
                    <InputNumber input-id="npc-min-lvl" v-model="values.min_lvl" :min="0" :max="1000" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="npc-max-lvl">Poziom do</label>
                    <InputNumber input-id="npc-max-lvl" v-model="values.max_lvl" :min="0" :max="1000" />
                </div>
            </div>
            <Message v-if="searchError" severity="error">{{ searchError }}</Message>
            <Message v-for="[field, error] in filterErrors" :key="field" severity="error">{{ error }}</Message>
            <div class="flex gap-2">
                <Button type="submit" label="Zastosuj filtry" icon="pi pi-filter" />
                <Button type="button" label="Wyczyść filtry" severity="secondary" outlined @click="clear" />
            </div>
        </form>
    </Fieldset>
</template>
