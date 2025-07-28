<script setup lang="ts">
import { useConfirm, useToast } from "primevue";
import { useForm, usePage, router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { BaseNpcResource } from "@/Resources/BaseNpc.resource";
import { ref, computed } from "vue";

const props = defineProps<{
    baseNpc: BaseNpcResource
}>();

const confirm = useConfirm();
const toast = useToast();
const isModalVisible = ref(false);
const isLoading = ref(true);
const transferForm = useForm({
    targetBaseNpcId: null as number | null
});

// Get the lazy-loaded similarBaseNpcs prop from the page
const page = usePage<{ similarBaseNpcs: BaseNpcResource[] }>();
const similarBaseNpcs = computed(() => {
    return page.props.similarBaseNpcs || [];
});

const openMergeModal = () => {
    isModalVisible.value = true;
    router.reload({
        only: ['similarBaseNpcs'],
        onSuccess: () => {
            isLoading.value = false;
        }
    });
};

const transferNpcs = (targetBaseNpc: BaseNpcResource) => {
    confirm.require({
        message: `Czy na pewno chcesz przenieść wszystkie wystąpienia z "${props.baseNpc.name}" do "${targetBaseNpc.name}"?`,
        icon: 'pi pi-info-circle',
        rejectProps: {
            label: 'Anuluj',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Przenieś',
            severity: 'primary'
        },
        accept: () => {
            transferForm.targetBaseNpcId = targetBaseNpc.id;
            transferForm.post(route('base-npcs.transfer-npcs', { sourceBaseNpc: props.baseNpc.id }), {
                onSuccess: (response) => {
                    toast.add({
                        severity: 'success',
                        summary: 'Sukces',
                        detail: response.data.message,
                        life: 7000
                    });
                    isModalVisible.value = false;
                },
                onError: () => {
                    toast.add({
                        severity: 'error',
                        summary: 'Błąd',
                        detail: 'Przenoszenie nie powiodło się',
                        life: 3000
                    });
                }
            });
        },
    });
};
</script>

<template>
    <Button severity="primary" label="Złącz z innym base npc" class="w-full mb-2" @click="openMergeModal" />

    <Dialog v-model:visible="isModalVisible" modal header="Złącz z innym base npc" style="width: 50vw">
        <div v-if="isLoading" class="flex justify-center">
            <ProgressSpinner />
        </div>
        <div v-else-if="similarBaseNpcs.length === 0" class="p-4 text-center">
            <Message severity="info">
                Nie znaleziono podobnych base NPC z dokładnie tą samą nazwą.
            </Message>
        </div>
        <div v-else>
            <Message severity="info" class="mb-4">
                Poniżej znajdują się base NPC z dokładnie tą samą nazwą. Wybierz jeden, aby przenieść do niego wszystkie wystąpienia z bieżącego base NPC.
            </Message>

            <div v-for="similarBaseNpc in similarBaseNpcs" :key="similarBaseNpc.id" class="mb-3 p-3 border-1 border-round surface-border">
                <div class="flex align-items-center justify-content-between">
                    <div class="flex align-items-center">
                        <img :src="similarBaseNpc.src" alt="" class="mr-2" style="width: 32px; height: 32px;" />
                        <div>
                            <div class="font-bold">#{{ similarBaseNpc.id }} - {{ similarBaseNpc.name }}</div>
                            <div class="text-sm text-color-secondary">
                                Lvl: {{ similarBaseNpc.lvl }},
                                Ranga: {{ similarBaseNpc.rank }},
                                Kategoria: {{ similarBaseNpc.category }}
                            </div>
                        </div>
                    </div>
                    <Button
                        label="Przenieś wystąpienia do tego base npc"
                        @click="transferNpcs(similarBaseNpc)"
                        :loading="transferForm.processing && transferForm.targetBaseNpcId === similarBaseNpc.id"
                    />
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Zamknij" @click="isModalVisible = false" />
        </template>
    </Dialog>
</template>

<style scoped>
</style>
