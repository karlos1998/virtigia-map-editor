<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { ref } from "vue";
import { route } from "ziggy-js";
import { useToast } from "primevue";
import { useForm } from "@inertiajs/vue3";
import CreateQuestStepModal from "@/Pages/Quest/Modals/CreateQuestStepModal.vue";
import EditQuestStepModal from "@/Pages/Quest/Modals/EditQuestStepModal.vue";
import { QuestWithStepsResource, QuestStepResource } from "@/Resources/Quest.resource";

const props = defineProps<{
    quest: QuestWithStepsResource
}>();

const toast = useToast();
const isCreateQuestStepModalVisible = ref(false);
const isEditQuestStepModalVisible = ref(false);
const selectedStep = ref<QuestStepResource | null>(null);

const editStep = (step: QuestStepResource) => {
    selectedStep.value = step;
    isEditQuestStepModalVisible.value = true;
};

const deleteStep = (stepId: number) => {
    if (confirm('Czy na pewno chcesz usunąć ten krok?')) {
        axios.delete(route('quests.steps.destroy', { quest: props.quest.id, step: stepId }))
            .then(() => {
                toast.add({ severity: 'success', summary: 'Udało się', detail: 'Krok został usunięty', life: 3000 });
                window.location.reload();
            })
            .catch(({response}) => {
                toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
            });
    }
};
</script>

<template>
    <AppLayout>
        <CreateQuestStepModal
            v-model:visible="isCreateQuestStepModalVisible"
            :quest-id="quest.id"
        />
        <EditQuestStepModal
            v-model:visible="isEditQuestStepModalVisible"
            :quest-id="quest.id"
            :step="selectedStep"
        />

        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">{{ quest.name }}</h1>
                <div>
                    <Button label="Dodaj krok" icon="pi pi-plus" @click="isCreateQuestStepModalVisible = true" />
                </div>
            </div>
            <div class="mb-4">
                <p><strong>ID:</strong> {{ quest.id }}</p>
                <p><strong>Data utworzenia:</strong> {{ new Date(quest.created_at).toLocaleString() }}</p>
                <p><strong>Data aktualizacji:</strong> {{ new Date(quest.updated_at).toLocaleString() }}</p>
            </div>
        </div>

        <div class="card">
            <h2 class="text-xl font-bold mb-4">Kroki questa</h2>

            <div v-if="quest.steps.length === 0" class="p-4 text-center text-gray-500">
                Brak kroków dla tego questa. Dodaj pierwszy krok używając przycisku "Dodaj krok".
            </div>

            <Accordion v-else>
                <AccordionPanel v-for="step in quest.steps" :key="step.id">

                    <AccordionHeader>{{step.name}}</AccordionHeader>

                    <AccordionContent>
                        <div class="p-4">
                            <div class="whitespace-pre-wrap mb-4">{{ step.description }}</div>
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" label="Edytuj" @click="editStep(step)" />
                                <Button icon="pi pi-trash" label="Usuń" severity="danger" @click="deleteStep(step.id)" />
                            </div>
                        </div>
                    </AccordionContent>

                </AccordionPanel>
            </Accordion>
        </div>
    </AppLayout>
</template>

<style scoped>
.whitespace-pre-wrap {
    white-space: pre-wrap;
}
</style>
