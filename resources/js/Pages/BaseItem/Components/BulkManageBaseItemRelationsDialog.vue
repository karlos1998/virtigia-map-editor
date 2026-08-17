<script setup lang="ts">
import type { BaseItemResource } from '@/Resources/BaseItem.resource';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AutoComplete from 'primevue/autocomplete';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import { useToast } from 'primevue';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

export type BulkRelationOperation = 'attach_shop' | 'detach_relation';

type SearchTarget = {
    id: number;
    name: string;
};

const props = defineProps<{
    items: BaseItemResource[];
    operation: BulkRelationOperation | null;
}>();

const emit = defineEmits<{
    completed: [];
}>();

const visible = defineModel<boolean>('visible', { default: false });
const selectedTarget = ref<SearchTarget | null>(null);
const filteredTargets = ref<SearchTarget[]>([]);
const targetType = ref<'shop' | 'base_npc'>('shop');
const startPosition = ref(0);
const toast = useToast();

const form = useForm({
    item_ids: [] as number[],
    shop_id: null as number | null,
    start_position: 0,
    target_type: null as 'shop' | 'base_npc' | null,
    target_id: null as number | null,
});

const targetTypeOptions = [
    { label: 'Sklep', value: 'shop' },
    { label: 'Base NPC (loot)', value: 'base_npc' },
];

const dialogHeader = computed(() => props.operation === 'attach_shop'
    ? 'Przypisz przedmioty do sklepu'
    : 'Odepnij przedmioty od relacji');

const canSubmit = computed(() => (
    props.items.length > 0
    && selectedTarget.value !== null
    && !form.processing
));

watch(
    () => [visible.value, props.operation] as const,
    ([isVisible]) => {
        if (!isVisible) {
            return;
        }

        selectedTarget.value = null;
        filteredTargets.value = [];
        targetType.value = 'shop';
        startPosition.value = 0;
        form.clearErrors();
        form.reset();
    },
);

watch(targetType, () => {
    selectedTarget.value = null;
    filteredTargets.value = [];
});

const searchTargets = async ({ query }: { query: string }) => {
    const routeName = props.operation === 'attach_shop' || targetType.value === 'shop'
        ? 'shops.search'
        : 'base-npcs.search';
    const { data } = await axios.get(route(routeName, { query }));
    filteredTargets.value = Array.isArray(data) ? data : (data.data ?? []);
};

const complete = (detail: string) => {
    toast.add({ severity: 'success', summary: 'Udało się', detail, life: 3000 });
    visible.value = false;
    emit('completed');
    form.reset();
};

const showError = (errors: Record<string, string>) => {
    toast.add({
        severity: 'error',
        summary: 'Błąd',
        detail: Object.values(errors)[0] ?? 'Nie udało się wykonać operacji masowej.',
        life: 5000,
    });
};

const submit = () => {
    if (!props.operation || !selectedTarget.value) {
        return;
    }

    form.item_ids = props.items.map(item => item.id);

    if (props.operation === 'attach_shop') {
        form.shop_id = selectedTarget.value.id;
        form.start_position = startPosition.value;
        form.post(route('base-items.bulk.shop-items.attach'), {
            preserveScroll: true,
            onSuccess: () => complete('Wybrane przedmioty zostały przypisane do sklepu.'),
            onError: showError,
        });

        return;
    }

    form.target_type = targetType.value;
    form.target_id = selectedTarget.value.id;
    form.delete(route('base-items.bulk.relations.detach'), {
        preserveScroll: true,
        onSuccess: () => complete('Wybrane przedmioty zostały odpięte.'),
        onError: showError,
    });
};
</script>

<template>
    <Dialog v-model:visible="visible" modal :header="dialogHeader" :style="{ width: '36rem' }">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <Message severity="info" :closable="false">
                Operacja obejmie {{ items.length }} wybranych przedmiotów.
            </Message>

            <div v-if="operation === 'detach_relation'" class="flex flex-col gap-2">
                <label class="font-semibold">Typ relacji</label>
                <Dropdown
                    v-model="targetType"
                    :options="targetTypeOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                />
            </div>

            <div class="flex flex-col gap-2">
                <label class="font-semibold">
                    {{ operation === 'attach_shop' || targetType === 'shop' ? 'Sklep' : 'Base NPC' }}
                </label>
                <AutoComplete
                    v-model="selectedTarget"
                    :suggestions="filteredTargets"
                    option-label="name"
                    force-selection
                    dropdown
                    class="w-full"
                    input-class="w-full"
                    @complete="searchTargets"
                >
                    <template #option="{ option }">
                        {{ option.name }} (#{{ option.id }})
                    </template>
                </AutoComplete>
            </div>

            <div v-if="operation === 'attach_shop'" class="flex flex-col gap-2">
                <label class="font-semibold">Pozycja początkowa</label>
                <InputNumber v-model="startPosition" :min="0" :max="79" show-buttons class="w-full" />
                <small class="text-surface-500 dark:text-surface-400">
                    Przedmioty trafią kolejno na pierwsze wolne pozycje od tej wartości.
                </small>
            </div>

            <Message v-else severity="warn" :closable="false">
                Odpięte zostaną tylko zaznaczone przedmioty, które są obecnie przypisane do wybranego celu.
            </Message>

            <small v-for="(error, key) in form.errors" :key="key" class="text-red-500">{{ error }}</small>
        </form>

        <template #footer>
            <Button label="Anuluj" severity="secondary" outlined :disabled="form.processing" @click="visible = false" />
            <Button
                :label="operation === 'attach_shop' ? 'Przypisz' : 'Odepnij'"
                icon="pi pi-check"
                :severity="operation === 'detach_relation' ? 'danger' : undefined"
                :disabled="!canSubmit"
                :loading="form.processing"
                @click="submit"
            />
        </template>
    </Dialog>
</template>
