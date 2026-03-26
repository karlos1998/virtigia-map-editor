<script setup lang="ts">
import axios from "axios";
import AppLayout from "@/layout/AppLayout.vue";
import {router} from "@inertiajs/vue3";
import {computed, ref} from "vue";
import {useToast} from "primevue";
import {route} from "ziggy-js";

interface MigrationItem {
    name: string
    executed: boolean
    batch: number | null
}

interface BulkGuaranteedLootExample {
    id: number
    name: string | null
    lvl: number
    guaranteed_loot: boolean
}

interface BulkGuaranteedLootPreview {
    count: number
    examples: BulkGuaranteedLootExample[]
}

const props = defineProps<{
    worldName: string
    migrations: MigrationItem[]
}>();

const toast = useToast();
const bulkGuaranteedLootLevel = ref<number>(1);
const bulkGuaranteedLootPreview = ref<BulkGuaranteedLootPreview | null>(null);
const isBulkGuaranteedLootPreviewVisible = ref(false);
const isBulkGuaranteedLootPreviewLoading = ref(false);
const isBulkGuaranteedLootApplying = ref(false);

const getBatchColor = computed(() => {
    const batchColors: Record<number, string> = {};
    let colorIndex = 0;
    const colors = ['bg-blue-50', 'bg-green-50', 'bg-yellow-50', 'bg-indigo-50', 'bg-purple-50', 'bg-pink-50'];

    return (batch: number | null): string => {
        if (!batch) {
            return '';
        }

        if (!batchColors[batch]) {
            batchColors[batch] = colors[colorIndex % colors.length];
            colorIndex++;
        }

        return batchColors[batch];
    };
});

const previewHasChanges = computed(() => (bulkGuaranteedLootPreview.value?.count ?? 0) > 0);

const resetBulkGuaranteedLootPreview = (): void => {
    bulkGuaranteedLootPreview.value = null;
    isBulkGuaranteedLootPreviewVisible.value = false;
};

const openBulkGuaranteedLootPreview = async (): Promise<void> => {
    isBulkGuaranteedLootPreviewLoading.value = true;

    try {
        const {data} = await axios.post<BulkGuaranteedLootPreview>(
            route('world-info.base-npcs.guaranteed-loot.preview'),
            {
                level: bulkGuaranteedLootLevel.value,
            },
        );

        bulkGuaranteedLootPreview.value = data;
        isBulkGuaranteedLootPreviewVisible.value = true;
    } catch (error: any) {
        const validationMessage = error?.response?.data?.errors
            ? Object.values(error.response.data.errors)[0]?.[0]
            : null;

        toast.add({
            severity: 'error',
            summary: 'Nie udało się pobrać podsumowania',
            detail: validationMessage ?? 'Spróbuj ponownie za chwilę.',
            life: 5000,
        });
    } finally {
        isBulkGuaranteedLootPreviewLoading.value = false;
    }
};

const applyBulkGuaranteedLoot = (): void => {
    if (!previewHasChanges.value) {
        toast.add({
            severity: 'info',
            summary: 'Brak zmian',
            detail: 'Nie znaleziono base NPC do zaktualizowania.',
            life: 4000,
        });
        resetBulkGuaranteedLootPreview();
        return;
    }

    router.patch(
        route('world-info.base-npcs.guaranteed-loot.apply'),
        {
            level: bulkGuaranteedLootLevel.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => {
                isBulkGuaranteedLootApplying.value = true;
            },
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Zmiany zapisane',
                    detail: `Ustawiono gwarantowany loot dla ${bulkGuaranteedLootPreview.value?.count ?? 0} base NPC.`,
                    life: 4000,
                });

                resetBulkGuaranteedLootPreview();
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Nie udało się zapisać zmian',
                    detail: Object.values(errors)[0] ?? 'Spróbuj ponownie za chwilę.',
                    life: 5000,
                });
            },
            onFinish: () => {
                isBulkGuaranteedLootApplying.value = false;
            },
        },
    );
};
</script>

<template>
    <AppLayout title="World Information">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                World Information: {{ props.worldName }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-3xl space-y-2">
                                <h3 class="text-lg font-medium">Zmiany grupowe</h3>
                                <p class="text-sm text-gray-600">
                                    Ustaw gwarantowany loot hurtowo dla wszystkich base NPC typu MOB z levelem od 1 do wskazanej wartości,
                                    które mają już przypisany co najmniej jeden loot.
                                    Przed zapisem zobaczysz podsumowanie zmian oraz 15 przykładowych NPC.
                                </p>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                <div class="flex flex-col gap-2">
                                    <label for="bulk-guaranteed-loot-level" class="text-sm font-medium text-gray-700">
                                        Maksymalny level
                                    </label>
                                    <InputNumber
                                        id="bulk-guaranteed-loot-level"
                                        v-model="bulkGuaranteedLootLevel"
                                        :min="1"
                                        :max="1000"
                                        input-class="w-full"
                                        show-buttons
                                    />
                                </div>

                                <Button
                                    label="Ustaw gwarantowany loot"
                                    icon="pi pi-check-square"
                                    :loading="isBulkGuaranteedLootPreviewLoading"
                                    @click="openBulkGuaranteedLootPreview"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium mb-4">Migration Status</h3>
                        <p class="mb-4">
                            This page shows the status of migrations for the current world. Migrations marked in green have been executed,
                            while those in red have not. Executed migrations are grouped by batch number.
                        </p>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Migration
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Batch
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="migration in props.migrations"
                                        :key="migration.name"
                                        :class="[migration.executed ? getBatchColor(migration.batch) : '']"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ migration.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span
                                                :class="migration.executed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            >
                                                {{ migration.executed ? 'Executed' : 'Not Executed' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ migration.batch ? migration.batch : '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Dialog
            v-model:visible="isBulkGuaranteedLootPreviewVisible"
            modal
            header="Podsumowanie zmian grupowych"
            :style="{ width: '48rem' }"
            @hide="bulkGuaranteedLootPreview = null"
        >
            <div class="space-y-6" v-if="bulkGuaranteedLootPreview">
                <div class="rounded-lg border border-surface-200 bg-surface-50 p-4">
                    <div class="text-sm text-surface-600">Wybrany próg levelu</div>
                    <div class="mt-1 text-2xl font-semibold text-surface-900">{{ bulkGuaranteedLootLevel }}</div>
                </div>

                <Message v-if="previewHasChanges" severity="warn">
                    Zmiana ustawi <strong>guaranteed loot = true</strong> dla
                    <strong>{{ bulkGuaranteedLootPreview.count }}</strong> base NPC typu MOB, które mają looty.
                </Message>

                <Message v-else severity="success">
                    Brak base NPC do zmiany dla wybranego levelu.
                </Message>

                <div v-if="bulkGuaranteedLootPreview.examples.length > 0" class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-base font-semibold text-surface-900">Przykładowe NPC, którym zmieni się wartość</h4>
                        <Tag :value="`${bulkGuaranteedLootPreview.examples.length} / ${bulkGuaranteedLootPreview.count}`" severity="info" />
                    </div>

                    <div class="max-h-96 overflow-y-auto rounded-lg border border-surface-200">
                        <table class="min-w-full divide-y divide-surface-200">
                            <thead class="bg-surface-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-600">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-600">Nazwa</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-surface-600">Lvl</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-200 bg-white">
                                <tr v-for="example in bulkGuaranteedLootPreview.examples" :key="example.id">
                                    <td class="px-4 py-3 text-sm text-surface-700">#{{ example.id }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-surface-900">{{ example.name || 'Bez nazwy' }}</td>
                                    <td class="px-4 py-3 text-sm text-surface-700">{{ example.lvl }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <Button
                        label="Anuluj"
                        severity="secondary"
                        outlined
                        @click="resetBulkGuaranteedLootPreview"
                    />
                    <Button
                        label="Potwierdź zmiany"
                        icon="pi pi-save"
                        :disabled="!previewHasChanges"
                        :loading="isBulkGuaranteedLootApplying"
                        @click="applyBulkGuaranteedLoot"
                    />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
