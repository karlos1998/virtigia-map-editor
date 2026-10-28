<script setup lang="ts">
import { nextTick, ref } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import { useToast } from 'primevue';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { dialogCounterScopeLabels, type DialogCounterResource } from '@/Resources/DialogCounter.resource';
import { insertDialogCounterReference } from '../Composables/insertDialogCounterReference';

const content = defineModel<string>({ required: true });
const textarea = ref<InstanceType<typeof Textarea> | null>(null);
const insertMenu = ref<InstanceType<typeof Menu> | null>(null);
const counterSelect = ref<InstanceType<typeof Select> | null>(null);
const showCounterPicker = ref(false);
const loading = ref(false);
const counters = ref<Array<DialogCounterResource & { label: string }>>([]);
const selectedCounterId = ref<number | null>(null);
const toast = useToast();

const openCounterPicker = async (): Promise<void> => {
    selectedCounterId.value = null;
    counters.value = [];
    showCounterPicker.value = true;
    loading.value = true;

    try {
        const { data } = await axios.get<DialogCounterResource[]>(route('web-api.dialog-counters.index'));
        counters.value = data.map(counter => ({
            ...counter,
            label: `${counter.name} — ${dialogCounterScopeLabels[counter.scope]}`,
        }));
    } catch {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się pobrać liczników dialogowych.', life: 6000 });
    } finally {
        loading.value = false;

        if (showCounterPicker.value) {
            await nextTick();
            counterSelect.value?.show();
        }
    }
};

const insertMenuItems = [
    { label: 'Wartość licznika', icon: 'pi pi-hashtag', command: openCounterPicker },
];

const insertCounter = async (): Promise<void> => {
    if (selectedCounterId.value === null) {
        return;
    }

    const result = insertDialogCounterReference(content.value, selectedCounterId.value);
    if (result.content.length > 2000) {
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Treść dialogu może mieć maksymalnie 2000 znaków.', life: 6000 });

        return;
    }

    content.value = result.content;
    showCounterPicker.value = false;
    await nextTick();
    const element = textarea.value?.$el as HTMLTextAreaElement | undefined;
    element?.focus();
    element?.setSelectionRange(result.cursor, result.cursor);
};
</script>

<template>
    <div class="flex flex-col gap-2">
        <Textarea ref="textarea" v-model="content" rows="5" cols="50" :maxlength="2000" aria-label="Treść dialogu NPC" aria-describedby="dialog-content-hint" />
        <small id="dialog-content-hint">
            Możesz wstawić do tekstu wartość licznika, np. <code>[[counter:1]]</code>.
            Użyj „Wstaw do treści” → „Wartość licznika” i wyszukaj licznik po nazwie — nie musisz znać jego ID.
            Znacznik zostanie dopisany na końcu tekstu; możesz go potem przenieść w dowolne miejsce.
        </small>
        <div>
            <Button
                label="Wstaw do treści"
                icon="pi pi-plus"
                severity="secondary"
                size="small"
                aria-haspopup="true"
                aria-controls="dialog-content-insert-menu"
                @click="insertMenu?.toggle($event)"
            />
            <Menu id="dialog-content-insert-menu" ref="insertMenu" :model="insertMenuItems" popup />
        </div>

        <div v-if="showCounterPicker" class="flex flex-col gap-2 rounded-lg border border-surface-200 p-3 dark:border-surface-700">
            <div class="flex flex-col gap-3">
                <label for="dialog-content-counter">Wybierz licznik po nazwie</label>
                <Select
                    ref="counterSelect"
                    v-model="selectedCounterId"
                    inputId="dialog-content-counter"
                    :options="counters"
                    optionLabel="label"
                    optionValue="id"
                    :loading="loading"
                    :disabled="loading"
                    filter
                    :filterFields="['name']"
                    filterPlaceholder="Szukaj licznika po nazwie"
                    autoFilterFocus
                    placeholder="Wpisz lub wybierz nazwę licznika"
                    emptyMessage="Brak liczników dialogowych"
                    emptyFilterMessage="Nie znaleziono licznika o tej nazwie"
                    class="w-full"
                    @change="insertCounter"
                />
                <small>W rozmowie gracz zobaczy aktualną wartość wybranego licznika. Domyślna wartość nieużywanego licznika to 0.</small>
            </div>
            <div>
                <Button label="Anuluj" severity="secondary" size="small" text @click="showCounterPicker = false" />
            </div>
        </div>
    </div>
</template>
