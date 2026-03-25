<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { ref, computed } from "vue";
import { route } from "ziggy-js";
import { useToast, useConfirm } from "primevue";
import { Link, router } from "@inertiajs/vue3";
import CreateQuestStepModal from "@/Pages/Quest/Modals/CreateQuestStepModal.vue";
import EditQuestStepModal from "@/Pages/Quest/Modals/EditQuestStepModal.vue";
import EditQuestNameModal from "@/Pages/Quest/Modals/EditQuestNameModal.vue";
import {
    QuestWithStepsResource,
    QuestStepResource,
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

const isDailyQuest = computed(() => {
    return props.quest.steps && props.quest.steps.some((s: any) => s.auto_advance_next_day === true);
});

const autoAdvanceText = (step: any) => {
    if (!step.auto_advance_next_day) return null;

    if (step.auto_advance_to_step_id === null || step.auto_advance_to_step_id === undefined) {
        return 'Uwaga: następnego dnia ten krok wyzeruje questa (ustawi brak aktywnego kroku).';
    }

    const target = props.quest.steps.find((s: any) => s.id === step.auto_advance_to_step_id);
    if (target) {
        return `Uwaga: następnego dnia ten krok ustawi krok questa na: ${target.name} (ID: ${target.id}).`;
    }

    return 'Uwaga: następnego dnia ten krok ustawi krok questa na krok o podanym ID.';
}

const formatNodeContent = (content?: string | null) => {
    if (!content || content.trim() === '') {
        return 'Brak treści';
    }

    return content;
}
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

            <div class="mb-4">
                <Message v-if="isDailyQuest" severity="warn" :closable="false">
                    <div class="font-bold text-lg">Uwaga — QUEST DZIENNY</div>
                    <div>Ten quest zawiera kroki, które następnego dnia automatycznie zmieniają lub zerują aktywny
                        krok.
                    </div>
                </Message>
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
                                <div class="mb-4">
                                    <h3 class="text-lg font-semibold mb-2">Jak osiągnąć ten krok</h3>

                                    <div v-if="step.guides && step.guides.length > 0" class="flex flex-col gap-3">
                                        <div v-for="(guide, guideIndex) in step.guides" :key="`${step.id}-${guideIndex}`" class="rounded-xl border border-surface-200 p-4 dark:border-surface-700">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <div>
                                                    <div class="font-semibold">
                                                        Dialog: {{ guide.dialog.name }} (ID: {{ guide.dialog.id }})
                                                    </div>
                                                    <div class="text-sm text-surface-500 dark:text-surface-400">
                                                        <span v-if="guide.starts_on_dialog_open">
                                                            Krok ustawia się od razu po rozpoczęciu rozmowy.
                                                        </span>
                                                        <span v-else>
                                                            Aby dojść do tego kroku, kliknij kolejne opcje poniżej.
                                                        </span>
                                                    </div>
                                                </div>

                                                <Link :href="route('dialogs.show', { dialog: guide.dialog.id })" class="no-underline">
                                                    <Button label="Otwórz dialog" size="small" severity="secondary" />
                                                </Link>
                                            </div>

                                            <div v-if="guide.npcs.length" class="mb-4 flex flex-col gap-3">
                                                <div v-for="npc in guide.npcs" :key="npc.id" class="flex gap-3 rounded-lg bg-surface-50 p-3 dark:bg-surface-900/40">
                                                    <Link :href="route('npcs.show', { npc: npc.id })" class="shrink-0">
                                                        <img v-if="npc.src" :src="npc.src" :alt="npc.name" class="h-14 w-14 rounded object-cover" />
                                                    </Link>

                                                    <div class="min-w-0 flex-1">
                                                        <div class="font-medium">
                                                            <Link :href="route('npcs.show', { npc: npc.id })" class="no-underline hover:underline">
                                                                {{ npc.name }} (NPC ID: {{ npc.id }})
                                                            </Link>
                                                        </div>

                                                        <div v-if="npc.locations.length" class="mt-1 flex flex-col gap-1 text-sm text-surface-600 dark:text-surface-300">
                                                            <div v-for="location in npc.locations" :key="location.id">
                                                                {{ location.label }}
                                                            </div>
                                                        </div>

                                                        <div v-else class="mt-1 text-sm text-surface-500 dark:text-surface-400">
                                                            Brak przypisanej lokalizacji.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div v-if="guide.click_steps.length" class="flex flex-col gap-3">
                                                <div v-for="(clickStep, clickIndex) in guide.click_steps" :key="`${step.id}-${guideIndex}-${clickIndex}`" class="rounded-lg border border-surface-200 p-3 dark:border-surface-700">
                                                    <div class="font-medium">
                                                        {{ clickIndex + 1 }}.
                                                    </div>

                                                    <div class="mt-2 flex flex-col gap-2">
                                                        <div class="rounded border border-sky-200 bg-sky-50 p-3 text-sm dark:border-sky-900/60 dark:bg-sky-950/30">
                                                            <div class="text-xs font-medium uppercase tracking-wide text-sky-700 dark:text-sky-300">
                                                                NPC mówi
                                                            </div>
                                                            <div class="mt-1 whitespace-pre-wrap text-surface-800 dark:text-surface-100">
                                                                {{ formatNodeContent(clickStep.node?.content) }}
                                                            </div>
                                                        </div>

                                                        <div v-if="clickStep.type === 'option'" class="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm dark:border-emerald-900/60 dark:bg-emerald-950/30">
                                                            <div class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                                                Kliknij odpowiedź
                                                            </div>
                                                            <div class="mt-1 whitespace-pre-wrap font-medium text-surface-800 dark:text-surface-100">
                                                                {{ clickStep.option?.label }}
                                                            </div>
                                                        </div>

                                                        <div v-else class="rounded border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900/60 dark:bg-amber-950/30">
                                                            <div class="text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                                                Co dalej
                                                            </div>
                                                            <div class="mt-1 text-surface-800 dark:text-surface-100">
                                                                Rozmowa przechodzi dalej automatycznie.
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div v-if="clickStep.option_requirements.length" class="mt-2 flex flex-col gap-2">
                                                        <div class="text-sm font-medium">Warunki opcji:</div>
                                                        <div v-for="(requirement, requirementIndex) in clickStep.option_requirements" :key="`${step.id}-${guideIndex}-${clickIndex}-option-${requirementIndex}`" class="rounded bg-surface-50 p-2 text-sm dark:bg-surface-900/40">
                                                            <div>{{ requirement.text }}</div>

                                                            <div v-if="requirement.item?.usage_sources?.length" class="mt-2 flex flex-col gap-2">
                                                                <div class="text-xs font-medium text-surface-500 dark:text-surface-400">Skąd wziąć ten item:</div>
                                                                <div v-for="(source, sourceIndex) in requirement.item.usage_sources.slice(0, 2)" :key="`${requirement.item.id}-${sourceIndex}`" class="text-xs text-surface-600 dark:text-surface-300">
                                                                    <span v-if="source.location">{{ source.location.label }}</span>
                                                                    <span v-else-if="source.shop">Shop #{{ source.shop.id }}</span>
                                                                    <span v-else>Brak lokalizacji</span>
                                                                    <span v-if="source.shop">
                                                                        •
                                                                        <Link :href="route('shops.show', { shop: source.shop.id })" class="no-underline hover:underline">
                                                                            {{ source.shop.name }} (#{{ source.shop.id }})
                                                                        </Link>
                                                                    </span>
                                                                    <span v-if="source.npc">
                                                                        •
                                                                        <Link :href="route('base-npcs.show', { baseNpc: source.npc.id })" class="no-underline hover:underline">
                                                                            {{ source.npc.name }}
                                                                        </Link>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div v-if="clickStep.edge_requirements.length" class="mt-2 flex flex-col gap-2">
                                                        <div class="text-sm font-medium">Warunki przejścia:</div>
                                                        <div v-for="(requirement, requirementIndex) in clickStep.edge_requirements" :key="`${step.id}-${guideIndex}-${clickIndex}-edge-${requirementIndex}`" class="rounded bg-surface-50 p-2 text-sm dark:bg-surface-900/40">
                                                            <div>{{ requirement.text }}</div>
                                                        </div>
                                                    </div>

                                                    <div class="mt-2 text-sm text-surface-500 dark:text-surface-400">
                                                        Następna wypowiedź NPC: "{{ formatNodeContent(clickStep.to_node?.content) }}"
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-3 text-sm text-surface-500 dark:text-surface-400">
                                                Wypowiedź ustawiająca krok: "{{ formatNodeContent(guide.target_node?.content) }}" (ID: {{ guide.target_node?.id }})
                                            </div>
                                        </div>
                                    </div>

                                    <Message v-else severity="secondary" :closable="false">
                                        Brak wygenerowanej instrukcji dla tego kroku.
                                    </Message>
                                </div>

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
                        <Message v-if="autoAdvanceText(step)" severity="warn" :closable="false">{{
                                autoAdvanceText(step)
                            }}
                        </Message>
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
