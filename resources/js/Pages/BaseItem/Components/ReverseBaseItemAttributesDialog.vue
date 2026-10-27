<script setup lang="ts">
import type { BaseItemResource } from '@/Resources/BaseItem.resource';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

type Preview = {
    preview_token: string;
    original_attributes: Record<string, unknown>;
    recalculated_attributes: Record<string, unknown>;
    attribute_points: Record<string, number>;
    manual_attribute_points: Record<string, number>;
    attack_elements: string[];
};

const props = defineProps<{ baseItem: BaseItemResource }>();
const visible = defineModel<boolean>('visible', { default: false });
const preview = ref<Preview | null>(null);
const loading = ref(false);
const error = ref('');
const onlyDifferences = ref(false);
const form = useForm({ preview_token: '' });

const rows = computed(() => {
    if (!preview.value) return [];

    const original = preview.value.original_attributes;
    const recalculated = preview.value.recalculated_attributes;

    return [...new Set([...Object.keys(original), ...Object.keys(recalculated)])]
        .sort()
        .map(key => ({ key, before: original[key], after: recalculated[key], changed: JSON.stringify(original[key]) !== JSON.stringify(recalculated[key]) }));
});
const changedCount = computed(() => rows.value.filter(row => row.changed).length);
const visibleRows = computed(() => onlyDifferences.value ? rows.value.filter(row => row.changed) : rows.value);
const formatValue = (value: unknown): string => JSON.stringify(value) ?? '—';
const difference = (before: unknown, after: unknown): string => {
    if (typeof before === 'number' && typeof after === 'number') {
        const delta = Math.round((after - before) * 10000) / 10000;
        return delta > 0 ? `+${delta}` : String(delta);
    }
    return 'zmiana';
};

async function calculate(): Promise<void> {
    loading.value = true;
    error.value = '';
    preview.value = null;
    form.clearErrors();

    try {
        const response = await axios.post<Preview>(route('base-items.attribute-reverse.preview', { baseItem: props.baseItem.id }));
        preview.value = response.data;
        form.preview_token = response.data.preview_token;
    } catch (exception: unknown) {
        error.value = axios.isAxiosError(exception)
            ? exception.response?.data?.errors?.reverse?.[0] ?? 'Nie udało się przeliczyć przedmiotu. Spróbuj ponownie.'
            : 'Nie udało się przeliczyć przedmiotu. Spróbuj ponownie.';
    } finally {
        loading.value = false;
    }
}

function confirm(): void {
    form.patch(route('base-items.attribute-reverse.confirm', { baseItem: props.baseItem.id }), {
        preserveScroll: true,
        onSuccess: () => { visible.value = false; },
    });
}

watch(visible, opened => {
    if (opened) {
        onlyDifferences.value = false;
        void calculate();
    }
});
</script>

<template>
    <Dialog v-model:visible="visible" modal header="Przelicz punkty atrybutów" :style="{ width: '70rem' }" :breakpoints="{ '960px': '95vw' }" :closable="!loading && !form.processing" :close-on-escape="!loading && !form.processing">
        <div class="flex flex-col gap-4">
            <p>{{ baseItem.name }} (#{{ baseItem.id }}). Porównaj obecne atrybuty z wartościami odtworzonymi z obliczonych punktów. Zatwierdzenie zapisze punkty i odtworzone atrybuty.</p>
            <div v-if="loading" class="flex items-center gap-3 py-8" role="status">
                <i class="pi pi-spin pi-spinner" aria-hidden="true" /> Obliczanie punktów i ponowne przeliczanie atrybutów…
            </div>
            <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
            <Message v-for="(message, key) in form.errors" :key="key" severity="error" :closable="false">{{ message }}</Message>
            <template v-if="preview">
                <Message :severity="changedCount ? 'warn' : 'success'" :closable="false">
                    {{ changedCount ? `Liczba atrybutów z różnicami: ${changedCount}. Sprawdź je przed zatwierdzeniem.` : 'Odtworzone atrybuty są zgodne z obecnymi.' }}
                </Message>
                <div class="flex items-center gap-2">
                    <Checkbox v-model="onlyDifferences" input-id="reverse-only-differences" binary />
                    <label for="reverse-only-differences">Pokaż tylko różnice</label>
                </div>
                <div class="max-h-[26rem] overflow-auto rounded border border-surface-200 dark:border-surface-700">
                    <table class="w-full text-left text-sm">
                        <thead class="sticky top-0 bg-surface-100 dark:bg-surface-800">
                            <tr><th class="p-3">Atrybut</th><th class="p-3">Obecnie</th><th class="p-3">Po przeliczeniu</th><th class="p-3">Różnica</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in visibleRows" :key="row.key" :class="row.changed ? 'bg-yellow-50 dark:bg-yellow-950' : ''">
                                <td class="p-3 font-mono">{{ row.key }}</td>
                                <td class="p-3 whitespace-pre-wrap break-all">{{ formatValue(row.before) }}</td>
                                <td class="p-3 whitespace-pre-wrap break-all">{{ formatValue(row.after) }}</td>
                                <td class="p-3">{{ row.changed ? difference(row.before, row.after) : '—' }}</td>
                            </tr>
                            <tr v-if="!visibleRows.length"><td colspan="4" class="p-3 text-center">Brak atrybutów do wyświetlenia.</td></tr>
                        </tbody>
                    </table>
                </div>
                <Panel header="Obliczone punkty i rodzaje ataku" toggleable collapsed>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div><b>Punkty atrybutów</b><pre class="overflow-auto">{{ preview.attribute_points }}</pre></div>
                        <div><b>Punkty ręczne</b><pre class="overflow-auto">{{ preview.manual_attribute_points }}</pre></div>
                    </div>
                    <p>Rodzaje ataku: {{ preview.attack_elements.join(', ') || 'brak' }}</p>
                </Panel>
            </template>
        </div>
        <template #footer>
            <div class="flex w-full flex-wrap justify-end gap-2">
                <Button label="Anuluj" severity="secondary" :disabled="loading || form.processing" @click="visible = false" />
                <Button label="Przelicz ponownie" icon="pi pi-refresh" severity="secondary" :disabled="loading || form.processing" @click="calculate" />
                <Button label="Zatwierdź punkty i atrybuty" icon="pi pi-check" :disabled="!preview || loading || form.processing" :loading="form.processing" @click="confirm" />
            </div>
        </template>
    </Dialog>
</template>
