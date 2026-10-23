<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
const visible = defineModel<boolean>('visible');
const props = defineProps<{ npcIds: number[]; operation: string }>();
const emit = defineEmits<{ completed: [] }>();
const labels: Record<string, string> = {
    assign_dialog: 'Przypisz / zamień dialog', detach_dialog: 'Odepnij dialogi',
    enable: 'Włącz instancje', disable: 'Wyłącz instancje',
    enable_auto_dialog: 'Włącz automatyczny dialog', disable_auto_dialog: 'Wyłącz automatyczny dialog',
    enable_aggression: 'Włącz agresywność bazowych mobów', disable_aggression: 'Wyłącz agresywność bazowych mobów',
};
const title = computed(() => labels[props.operation]);
const selectedDialog = ref<{ id: number; name: string } | null>(null);
const suggestions = ref([]);
const searchError = ref('');
let requestId = 0;
const form = useForm({ npc_ids: [] as number[], operation: '', dialog_id: null as number | null, assignment_mode: 'empty' });
watch(visible, opened => {
    if (opened) {
        form.reset();
        form.clearErrors();
        selectedDialog.value = null;
        suggestions.value = [];
        searchError.value = '';
        requestId++;
    }
});
const search = async ({ query }: { query: string }) => {
    const current = ++requestId;
    try {
        const { data } = await axios.get(route('dialogs.search'), { params: { query } });
        if (current === requestId) suggestions.value = data.data ?? data;
        searchError.value = '';
    } catch {
        if (current === requestId) suggestions.value = [];
        searchError.value = 'Nie udało się wyszukać dialogów.';
    }
};
const submit = () => {
    form.npc_ids = [...props.npcIds];
    form.operation = props.operation;
    form.dialog_id = selectedDialog.value?.id ?? null;
    form.patch(route('npcs.bulk.update'), {
        preserveScroll: true,
        onSuccess: () => { visible.value = false; emit('completed'); },
    });
};
</script>

<template>
    <Dialog v-model:visible="visible" modal :header="title" :style="{ width: '36rem', maxWidth: '95vw' }" :closable="!form.processing">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <Message severity="info">Zaznaczono {{ npcIds.length }} instancji. Operacja dotyczy wszystkich lokalizacji tych instancji, także na innych mapach.</Message>
            <Message v-if="operation === 'enable_aggression' || operation === 'disable_aggression'" severity="warn">Agresywność jest cechą bazowego moba. Zmiana obejmie wszystkie jego instancje na wszystkich mapach, również niezaznaczone. Każdy bazowy mob zostanie zmieniony tylko raz. Wszystkie zaznaczone instancje muszą mieć kategorię MOB.</Message>
            <template v-if="operation === 'assign_dialog'">
                <label for="npc-bulk-dialog">Dialog do przypisania</label>
                <AutoComplete input-id="npc-bulk-dialog" v-model="selectedDialog" :suggestions="suggestions" option-label="name" force-selection placeholder="Wyszukaj dialog" @complete="search" />
                <label for="npc-bulk-mode">Sposób przypisania</label>
                <Select input-id="npc-bulk-mode" v-model="form.assignment_mode" :options="[{ label: 'Uzupełnij tylko instancje bez dialogu', value: 'empty' }, { label: 'Zastąp dialog we wszystkich zaznaczonych instancjach', value: 'replace' }]" option-label="label" option-value="value" />
            </template>
            <Message v-if="operation === 'detach_dialog'" severity="warn">Dialogi zostaną odpięte, a ich automatyczne uruchamianie wyłączone. Treść dialogów pozostanie zachowana.</Message>
            <Message v-if="operation === 'enable_auto_dialog'" severity="info">Wszystkie zaznaczone instancje muszą mieć dialog. Dotychczasowy zasięg uruchamiania zostanie zachowany.</Message>
            <Message v-if="searchError" severity="error">{{ searchError }}</Message>
            <Message v-for="(error, field) in form.errors" :key="field" severity="error">{{ error }}</Message>
            <div class="flex justify-end gap-2">
                <Button label="Anuluj" severity="secondary" :disabled="form.processing" @click="visible = false" />
                <Button type="submit" label="Zastosuj do zaznaczonych" :loading="form.processing" :disabled="npcIds.length === 0 || npcIds.length > 500 || (operation === 'assign_dialog' && !selectedDialog?.id)" />
            </div>
        </form>
    </Dialog>
</template>
