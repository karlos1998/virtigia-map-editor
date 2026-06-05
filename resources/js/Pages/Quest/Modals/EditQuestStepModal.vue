<script setup lang="ts">
import {useForm} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {useToast} from "primevue";
import {watch, ref, computed} from "vue";
import { QuestStepResource } from "@/Resources/Quest.resource";
import axios from "axios";
import { BaseNpcResource } from "@/Resources/BaseNpc.resource";
import Checkbox from 'primevue/checkbox';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import AutoComplete from 'primevue/autocomplete';

const props = defineProps<{
    questId: number;
    step: QuestStepResource | null;
    steps: any[];
}>();

const visible = defineModel<boolean>('visible');
const toast = useToast();

// Auto progress settings
const autoProgress = ref(false);
const progressType = ref('time'); // 'time' or 'mobs'
const progressTime = ref(0); // in seconds
type MobSpeciesResource = { id: number, name: string }
type ProgressTarget = {
    type: 'base_npc' | 'mob_species',
    baseNpc?: BaseNpcResource | null,
    mobSpecies?: MobSpeciesResource | null,
    quantity: number
}

const selectedMobs = ref<ProgressTarget[]>([]);
const selectedBaseNpc = ref<BaseNpcResource | null>(null);
const selectedMobSpecies = ref<MobSpeciesResource | null>(null);
const mobQuantity = ref(1);
const targetType = ref<'base_npc' | 'mob_species'>('base_npc');
const filteredBaseNpcs = ref<BaseNpcResource[]>([]);
const filteredMobSpecies = ref<MobSpeciesResource[]>([]);

const form = useForm({
    name: '',
    description: '',
    visible_in_quest_list: false,
    auto_progress: false,
    progress_type: 'time',
    progress_time: 0,
    progress_mobs: [] as { type: 'base_npc' | 'mob_species', base_npc_id: number | null, mob_species_id: number | null, quantity: number }[],
    auto_advance_next_day: false,
    auto_advance_to_step_id: null
})

const stepOptions = computed(() => {
    const stepsList = props.step ? props.steps.filter(s => s.id !== props.step?.id) : props.steps;
    const mapped = stepsList.map(s => ({label: s.name, value: s.id}));
    return [{label: 'Wyzeruj quest', value: null}, ...mapped];
});

watch(() => props.step, (newStep) => {
    if (newStep) {
        console.log(newStep, 'newStep')
        form.name = newStep.name;
        form.description = newStep.description ?? '';
        form.visible_in_quest_list = newStep.visible_in_quest_list || false;

        // Initialize auto progress settings if they exist
        if (newStep.auto_progress) {
            autoProgress.value = true;
            progressType.value = newStep.auto_progress.type;

            if (newStep.auto_progress.type === 'time' && newStep.auto_progress.time_seconds) {
                progressTime.value = newStep.auto_progress.time_seconds;
            } else if (newStep.auto_progress.type === 'mobs' && newStep.auto_progress.mobs) {
                selectedMobs.value = newStep.auto_progress.mobs.map(mob => ({
                    type: mob.type || (mob.base_npc_id ? 'base_npc' : 'mob_species'),
                    baseNpc: mob.base_npc,
                    mobSpecies: mob.mob_species,
                    quantity: mob.quantity
                }));
            }
        } else {
            // Reset auto progress settings
            autoProgress.value = false;
            progressType.value = 'time';
            progressTime.value = 0;
            selectedMobs.value = [];
        }
        // auto advance fields
        form.auto_advance_next_day = newStep?.auto_advance_next_day ?? false;
        form.auto_advance_to_step_id = newStep?.auto_advance_to_step_id ?? null;
    } else {
        // Reset auto progress settings
        autoProgress.value = false;
        progressType.value = 'time';
        progressTime.value = 0;
        selectedMobs.value = [];
        // auto advance fields
        form.auto_advance_next_day = false;
        form.auto_advance_to_step_id = null;
    }
}, { immediate: true });

// Search for base NPCs
const searchBaseNpcs = async (query: string) => {
    const { data } = await axios.get(route('base-npcs.search', {query}))
    return data;
}
const searchMobSpecies = async (query: string) => {
    const { data } = await axios.get(route('mob-species.search', { query }))
    return data;
}

const filterBaseNpcs = async ({ query }: { query: string }) => {
    filteredBaseNpcs.value = await searchBaseNpcs(query);
};
const filterMobSpecies = async ({ query }: { query: string }) => {
    filteredMobSpecies.value = await searchMobSpecies(query);
};

// Add selected mob to the list
const addMob = () => {
    if (targetType.value === 'base_npc' && selectedBaseNpc.value && mobQuantity.value > 0) {
        selectedMobs.value.push({
            type: 'base_npc',
            baseNpc: selectedBaseNpc.value,
            quantity: mobQuantity.value
        });
        selectedBaseNpc.value = null;
        mobQuantity.value = 1;
        return;
    }

    if (targetType.value === 'mob_species' && selectedMobSpecies.value && mobQuantity.value > 0) {
        selectedMobs.value.push({
            type: 'mob_species',
            mobSpecies: selectedMobSpecies.value,
            quantity: mobQuantity.value
        });
        selectedMobSpecies.value = null;
        mobQuantity.value = 1;
    }
};

// Remove mob from the list
const removeMob = (index: number) => {
    selectedMobs.value.splice(index, 1);
};

// Update form data before submission
const updateFormData = () => {
    form.auto_progress = autoProgress.value;
    form.progress_type = progressType.value;
    form.progress_time = progressTime.value;
    form.progress_mobs = selectedMobs.value.map(mob => ({
        type: mob.type,
        base_npc_id: mob.type === 'base_npc' ? (mob.baseNpc?.id ?? null) : null,
        mob_species_id: mob.type === 'mob_species' ? (mob.mobSpecies?.id ?? null) : null,
        quantity: mob.quantity
    }));
    form.auto_advance_next_day = form.auto_advance_next_day ?? false;
    form.auto_advance_to_step_id = form.auto_advance_to_step_id ?? null;
};

const submit = () => {
    if (!props.step) return;

    updateFormData();

    form.patch(route('quests.steps.update', { quest: props.questId, step: props.step.id }), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Krok został zaktualizowany', life: 3000 });
            visible.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił problem podczas aktualizacji kroku', life: 6000 });
        },
        preserveScroll: true
    });
}
</script>

<template>
    <Dialog v-model:visible="visible" modal header="Edycja kroku questa" :style="{ width: '40rem' }">
        <div class="flex flex-col gap-4 mb-4" v-if="step">
            <div class="flex items-center mb-2">
                <Checkbox v-model="form.visible_in_quest_list" :binary="true" inputId="visibleInQuestList" />
                <label for="visibleInQuestList" class="ml-2 font-semibold">Widoczny w liscie questow</label>
            </div>

            <div>
                <label for="name" class="font-semibold block mb-2">Nazwa</label>
                <InputText id="name" class="w-full" autocomplete="off" v-model="form.name" />
                <Message severity="error" size="small" variant="simple">{{ form.errors.name }}</Message>
            </div>

            <div>
                <label for="description" class="font-semibold block mb-2">Opis (opcjonalnie)</label>
                <Textarea id="description" class="w-full" rows="10" v-model="form.description" />
                <Message severity="error" size="small" variant="simple">{{ form.errors.description }}</Message>
            </div>

            <div class="mt-4">
                <div class="flex items-center mb-2">
                    <Checkbox v-model="autoProgress" :binary="true" inputId="autoProgress" />
                    <label for="autoProgress" class="ml-2 font-semibold">Automatyczne przechodzenie do następnego kroku</label>
                </div>

                <div v-if="autoProgress" class="pl-6 mt-2 flex flex-col gap-4">
                    <div>
                        <label for="progressType" class="font-semibold block mb-2">Typ przejścia</label>
                        <Dropdown v-model="progressType" :options="[
                            { label: 'Automatycznie przechodź po określonym czasie', value: 'time' },
                            { label: 'Po zabiciu mobów', value: 'mobs' }
                        ]" optionLabel="label" optionValue="value" class="w-full" />
                    </div>

                    <div v-if="progressType === 'time'">
                        <label for="progressTime" class="font-semibold block mb-2">Czas (w sekundach)</label>
                        <InputNumber v-model="progressTime" inputId="progressTime" class="w-full" :min="0" />
                    </div>

                    <div v-if="progressType === 'mobs'" class="flex flex-col gap-2">
                        <label class="font-semibold block">Moby do zabicia</label>

                        <div class="mb-2">
                            <Dropdown
                                v-model="targetType"
                                :options="[
                                    { label: 'Konkretny NPC', value: 'base_npc' },
                                    { label: 'Gatunek (MobSpecies)', value: 'mob_species' }
                                ]"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full"
                            />
                        </div>

                        <div class="flex gap-2 mb-2">
                            <AutoComplete
                                v-if="targetType === 'base_npc'"
                                class="flex-grow"
                                v-model="selectedBaseNpc"
                                placeholder="Wyszukaj potwora"
                                :suggestions="filteredBaseNpcs"
                                @complete="filterBaseNpcs"
                                :option-label="(baseNpc: BaseNpcResource|null) => baseNpc?.name || ''"
                                fluid
                            >
                                <template #option="slotProps">
                                    <div class="flex items-center space-x-4">
                                        <img
                                            class="h-12 w-12 object-cover"
                                            :src="slotProps.option.src"
                                            alt="Option Image"
                                        />
                                        <div>
                                            <span class="font-semibold">
                                                [{{ slotProps.option.id }}] {{ slotProps.option.name }}
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </AutoComplete>

                            <AutoComplete
                                v-else
                                class="flex-grow"
                                v-model="selectedMobSpecies"
                                placeholder="Wyszukaj gatunek"
                                :suggestions="filteredMobSpecies"
                                @complete="filterMobSpecies"
                                :option-label="(species: MobSpeciesResource|null) => species?.name || ''"
                                fluid
                            />

                            <InputNumber v-model="mobQuantity" :min="1" placeholder="Ilość" class="w-24" />

                            <Button icon="pi pi-plus" @click="addMob" :disabled="targetType === 'base_npc' ? !selectedBaseNpc : !selectedMobSpecies" />
                        </div>

                        <div v-if="selectedMobs.length > 0" class="mt-2">
                            <ul class="list-none p-0 m-0">
                                <li v-for="(mob, index) in selectedMobs" :key="index" class="flex items-center justify-between p-2 border-b">
                                    <div class="flex items-center gap-2">
                                        <img v-if="mob.type === 'base_npc' && mob.baseNpc" :src="mob.baseNpc.src" class="h-8 w-8 object-cover" />
                                        <span v-if="mob.type === 'base_npc' && mob.baseNpc">[id: {{mob.baseNpc.id}}] {{ mob.baseNpc.name }} ({{ mob.quantity }})</span>
                                        <span v-else-if="mob.type === 'mob_species' && mob.mobSpecies">[gatunek: {{mob.mobSpecies.id}}] {{ mob.mobSpecies.name }} ({{ mob.quantity }})</span>
                                    </div>
                                    <Button icon="pi pi-times" severity="danger" text @click="removeMob(index)" />
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="step" class="mt-2">
                <div class="flex items-center mb-2">
                    <Checkbox v-model="form.auto_advance_next_day" :binary="true" inputId="autoAdvanceNextDay"/>
                    <label for="autoAdvanceNextDay" class="ml-2 font-semibold">Automatycznie przejść następnego dnia do
                        innego kroku</label>
                </div>

                <div class="mt-2">
                    <label class="font-semibold block mb-2">Krok docelowy (opcjonalne)</label>
                    <Dropdown v-model="form.auto_advance_to_step_id"
                              :options="stepOptions" optionLabel="label" optionValue="value"
                              class="w-full" placeholder="Wybierz krok"/>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <Button type="button" label="Anuluj" severity="secondary" @click="visible = false"></Button>
            <Button :loading="form.processing" type="button" label="Zapisz" @click="submit"></Button>
        </div>
    </Dialog>
</template>

<style scoped>
</style>
