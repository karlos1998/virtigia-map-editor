<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { useConfirm, useToast } from 'primevue';
import { route } from 'ziggy-js';

type ChangeSet = {
    id: string;
    title: string;
    prompt: string | null;
    status: 'draft' | 'applied' | 'reverted';
    revision: number;
    operations: Array<{ type: string }>;
    result: Record<string, unknown> | null;
    created_at: string | null;
    applied_at: string | null;
    reverted_at: string | null;
    user: { id: number; name: string } | null;
    can_revert: boolean;
};

defineProps<{
    changeSets: ChangeSet[];
    world: string;
}>();

const confirm = useConfirm();
const toast = useToast();

const statusLabel = (status: ChangeSet['status']) => ({
    draft: 'Szkic',
    applied: 'Zastosowany',
    reverted: 'Cofnięty',
}[status]);

const statusSeverity = (status: ChangeSet['status']) => ({
    draft: 'warn',
    applied: 'success',
    reverted: 'secondary',
}[status]);

const formatDate = (value: string | null) => value
    ? new Date(value).toLocaleString('pl-PL')
    : '—';

const operationSummary = (operations: ChangeSet['operations']) => operations
    .reduce<Record<string, number>>((counts, operation) => {
        counts[operation.type] = (counts[operation.type] ?? 0) + 1;
        return counts;
    }, {});

const confirmRevert = (changeSet: ChangeSet) => {
    confirm.require({
        header: 'Cofnięcie commita AI',
        message: `Cofnąć „${changeSet.title}”? Operacja zostanie odrzucona, jeśli dane były później edytowane.`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: { label: 'Anuluj', severity: 'secondary' },
        acceptProps: { label: 'Cofnij commit', severity: 'danger' },
        accept: () => router.post(route('ai-change-sets.revert', { aiChangeSet: changeSet.id }), {}, {
            preserveScroll: true,
            onSuccess: () => toast.add({ severity: 'success', summary: 'Commit cofnięty', life: 3000 }),
        }),
    });
};
</script>

<template>
    <AppLayout>
        <div class="card">
            <DataTable :value="changeSets" paginator :rows="15" striped-rows sort-field="created_at" :sort-order="-1" data-key="id">
                <template #header>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h4 class="m-0">Commity AI</h4>
                            <div class="mt-1 text-sm text-surface-500">Świat: {{ world }}</div>
                        </div>
                        <Tag value="Tworzenie itemów, BaseNPC i assetów jest zablokowane" severity="info" />
                    </div>
                </template>

                <Column expander style="width: 3rem" />
                <Column field="title" header="Zmiana" sortable>
                    <template #body="{ data }">
                        <div class="font-medium">{{ data.title }}</div>
                        <div class="mt-1 text-xs text-surface-500">{{ data.id }}</div>
                    </template>
                </Column>
                <Column header="Autor" sortable sort-field="user.name">
                    <template #body="{ data }">{{ data.user?.name ?? 'Nieznany' }}</template>
                </Column>
                <Column field="status" header="Status" sortable>
                    <template #body="{ data }">
                        <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
                    </template>
                </Column>
                <Column header="Operacje">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-1">
                            <Tag
                                v-for="(count, type) in operationSummary(data.operations)"
                                :key="type"
                                :value="`${type} × ${count}`"
                                severity="secondary"
                            />
                        </div>
                    </template>
                </Column>
                <Column field="created_at" header="Utworzono" sortable>
                    <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                </Column>
                <Column header="Akcje" style="width: 12rem">
                    <template #body="{ data }">
                        <Button
                            v-if="data.can_revert"
                            label="Cofnij"
                            icon="pi pi-undo"
                            size="small"
                            severity="danger"
                            outlined
                            @click="confirmRevert(data)"
                        />
                        <span v-else class="text-sm text-surface-400">—</span>
                    </template>
                </Column>

                <template #expansion="{ data }">
                    <div class="grid gap-4 p-3 lg:grid-cols-2">
                        <div>
                            <h5>Oryginalne polecenie</h5>
                            <p class="whitespace-pre-wrap text-sm">{{ data.prompt || 'Brak zapisanego polecenia.' }}</p>
                        </div>
                        <div>
                            <h5>Wynik</h5>
                            <pre class="max-h-80 overflow-auto rounded bg-surface-100 p-3 text-xs dark:bg-surface-800">{{ JSON.stringify(data.result, null, 2) }}</pre>
                        </div>
                    </div>
                </template>

                <template #empty>
                    <div class="py-6 text-center text-surface-500">AI nie utworzyło jeszcze żadnych commitów na tym świecie.</div>
                </template>
            </DataTable>
        </div>

        <ConfirmDialog />
    </AppLayout>
</template>
