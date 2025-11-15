<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { ref, computed } from "vue";
import { route } from "ziggy-js";
import { useToast, useConfirm } from "primevue";
import { useForm, router } from "@inertiajs/vue3";
import CreateQuestStepModal from "@/Pages/Quest/Modals/CreateQuestStepModal.vue";
import EditQuestStepModal from "@/Pages/Quest/Modals/EditQuestStepModal.vue";
import EditQuestNameModal from "@/Pages/Quest/Modals/EditQuestNameModal.vue";
import {
    QuestWithStepsResource,
    QuestStepResource,
    SimpleDialogResource,
    SimpleDialogNodeResource,
    SimpleDialogNodeOptionResource,
    SimpleDialogEdgeResource
} from "@/Resources/Quest.resource";

const props = defineProps<{
    quest: QuestWithStepsResource
}>();

const toast = useToast();
const confirm = useConfirm();
const isCreateQuestStepModalVisible = ref(false);
const isEditQuestStepModalVisible = ref(false);
const isEditQuestNameModalVisible = ref(false);
const selectedStep = ref<QuestStepResource | null>(null);

const editStep = (step: QuestStepResource) => {
    selectedStep.value = step;
    isEditQuestStepModalVisible.value = true;
};

const deleteStep = (stepId: number) => {
    confirm.require({
        message: 'Czy na pewno chcesz usunąć ten krok?',
        header: 'Potwierdzenie usunięcia',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Usuń',
        rejectLabel: 'Anuluj',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('quests.steps.destroy', { quest: props.quest.id, step: stepId }), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Udało się', detail: 'Krok został usunięty', life: 3000 });
                },
                onError: (errors) => {
                    toast.add({ severity: 'error', summary: 'Błąd', detail: errors.message, life: 6000 });
                }
            });
        }
    });
};

const deleteQuest = () => {
    confirm.require({
        message: 'Czy na pewno chcesz usunąć ten quest?',
        header: 'Potwierdzenie usunięcia',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Usuń',
        rejectLabel: 'Anuluj',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('quests.destroy', { quest: props.quest.id }), {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Udało się', detail: 'Quest został usunięty', life: 3000 });
                },
                onError: (errors) => {
                    toast.add({ severity: 'error', summary: 'Błąd', detail: errors.message, life: 6000 });
                }
            });
        }
    });
};

// Format auto progress information
const formatAutoProgress = (autoProgress: any) => {
    if (!autoProgress) return 'Brak';

    if (autoProgress.type === 'time') {
        const seconds = autoProgress.time_seconds || 0;
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `Czas: ${minutes > 0 ? `${minutes} min ` : ''}${remainingSeconds > 0 ? `${remainingSeconds} sek` : ''}`;
    } else if (autoProgress.type === 'mobs') {
        return `Moby: ${autoProgress.mobs.map(mob => `${mob.quantity}x ${mob.base_npc?.name || 'Mob #' + mob.base_npc_id}`).join(', ')}`;
    }

    return 'Nieznany typ';
};
</script>

<template>
    <AppLayout>
        <ConfirmDialog />

        <CreateQuestStepModal
            v-model:visible="isCreateQuestStepModalVisible"
            :quest-id="quest.id"
            :steps="quest.steps"
        />
        <EditQuestStepModal
            v-model:visible="isEditQuestStepModalVisible"
            :quest-id="quest.id"
            :step="selectedStep"
            :steps="quest.steps"
        />
        <EditQuestNameModal
            v-model:visible="isEditQuestNameModalVisible"
            :quest="quest"
        />

        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold">{{ quest.name }}</h1>
                    <Button icon="pi pi-pencil" text @click="isEditQuestNameModalVisible = true" />
                </div>
                <div class="flex gap-2">
                    <Button label="Dodaj krok" icon="pi pi-plus" @click="isCreateQuestStepModalVisible = true" />
                    <Button label="Usuń quest" icon="pi pi-trash" severity="danger" @click="deleteQuest" />
                </div>
            </div>
            <div class="mb-4">
                <p><strong>ID:</strong> {{ quest.id }}</p>
                <p><strong>Data utworzenia:</strong> {{ new Date(quest.created_at).toLocaleString() }}</p>
                <p><strong>Data aktualizacji:</strong> {{ new Date(quest.updated_at).toLocaleString() }}</p>
            </div>

            <!-- Quest Dialogs Section -->
            <div v-if="quest.dialogs && quest.dialogs.length > 0" class="mb-4">
                <h3 class="text-lg font-semibold mb-2">Dialogi powiązane z questem:</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    <div v-for="dialog in quest.dialogs" :key="dialog.id" class="p-2 border rounded bg-gray-50">
                        <div class="flex items-center">
                            <i class="pi pi-comments mr-2 text-blue-500"></i>
                            <span>{{ dialog.name }} (ID: {{ dialog.id }})</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quest Dialogs Nodes Section -->
            <div v-if="quest.nodes && quest.nodes.length > 0" class="mb-4">
                <h3 class="text-lg font-semibold mb-2">Odpowiedzi dialogowe powiązane z questem:</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    <div v-for="node in quest.nodes" :key="node.id" class="p-2 border rounded bg-gray-50">
                        <div class="flex items-center">
                            <i class="pi pi-comments mr-2 text-blue-500"></i>
                            <span>{{ node.content }} (ID: {{ node.id }})</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quest Node Options Section -->
            <div v-if="quest.nodeOptions && quest.nodeOptions.length > 0" class="mb-4">
                <h3 class="text-lg font-semibold mb-2">Opcje dialogowe powiązane z questem:</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div v-for="option in quest.nodeOptions" :key="option.id" class="p-2 border rounded bg-gray-50">
                        <div class="flex items-center">
                            <i class="pi pi-reply mr-2 text-green-500"></i>
                            <span>{{ option.label }} (ID: {{ option.id }})</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="text-xl font-bold mb-4">Kroki questa</h2>

            <div v-if="quest.steps.length === 0" class="p-4 text-center text-gray-500">
                Brak kroków dla tego questa. Dodaj pierwszy krok używając przycisku "Dodaj krok".
            </div>

            <Accordion v-else>
                <AccordionPanel v-for="step in quest.steps" :key="step.id" :header="step.name">
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <h3 class="text-lg font-semibold mb-2">Informacje podstawowe</h3>
                                <p><strong>ID:</strong> {{ step.id }}</p>
                                <p><strong>Nazwa:</strong> {{ step.name }}</p>
                                <p><strong>Opis:</strong> <span class="whitespace-pre-wrap">{{ step.description }}</span></p>
                                <p><strong>Widoczny na liście questów:</strong> {{ step.visible_in_quest_list ? 'Tak' : 'Nie' }}</p>
                                <p><strong>Automatyczny postęp:</strong> {{ formatAutoProgress(step.auto_progress) }}</p>
                            </div>

                            <div>
                                <!-- Step Dialogs Section -->
                                <div v-if="step.dialogs && step.dialogs.length > 0" class="mb-4">
                                    <h3 class="text-lg font-semibold mb-2">Dialogi powiązane z krokiem:</h3>
                                    <div class="grid grid-cols-1 gap-2">
                                        <div v-for="dialog in step.dialogs" :key="dialog.id" class="p-2 border rounded bg-gray-50">
                                            <div class="flex items-center">
                                                <i class="pi pi-comments mr-2 text-blue-500"></i>
                                                <span>{{ dialog.name }} (ID: {{ dialog.id }})</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step Nodes Section -->
                                <div v-if="step.nodes && step.nodes.length > 0" class="mb-4">
                                    <h3 class="text-lg font-semibold mb-2">Odpowiedzi dialogowe powiązane z krokiem:</h3>
                                    <div class="grid grid-cols-1 gap-2">
                                        <div v-for="node in step.nodes" :key="node.id" class="p-2 border rounded bg-gray-50">
                                            <div class="flex items-center">
                                                <i class="pi pi-arrow-down mr-2 text-green-500"></i>
                                                <span>{{ node.content }} (ID: {{ node.id }})</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step Node Options Section -->
                                <div v-if="step.nodeOptions && step.nodeOptions.length > 0" class="mb-4">
                                    <h3 class="text-lg font-semibold mb-2">Opcje dialogowe powiązane z krokiem:</h3>
                                    <div class="grid grid-cols-1 gap-2">
                                        <div v-for="option in step.nodeOptions" :key="option.id" class="p-2 border rounded bg-gray-50">
                                            <div class="flex items-center">
                                                <i class="pi pi-reply mr-2 text-green-500"></i>
                                                <span>{{ option.label }} (ID: {{ option.id }})</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button icon="pi pi-pencil" label="Edytuj" @click="editStep(step)" />
                            <Button icon="pi pi-trash" label="Usuń" severity="danger" @click="deleteStep(step.id)" />
                        </div>
                    </div>
                </AccordionPanel>
            </Accordion>
        </div>

        <!-- Debug section - can be removed in production -->
<!--        <div class="card mt-4">-->
<!--            <Accordion>-->
<!--                <AccordionPanel header="Debug - Raw Quest Data">-->
<!--                    <pre class="text-xs overflow-auto max-h-96">{{ JSON.stringify(quest, null, 2) }}</pre>-->
<!--                </AccordionPanel>-->
<!--            </Accordion>-->
<!--        </div>-->
    </AppLayout>
</template>

<style scoped>
.whitespace-pre-wrap {
    white-space: pre-wrap;
}
</style>
