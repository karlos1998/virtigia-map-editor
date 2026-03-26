<script setup lang="ts">

import {useForm, usePage} from "@inertiajs/vue3";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import {route} from "ziggy-js";
import {DropdownListType} from "@/Resources/DropdownList.type";
import TimeInput from "@/Components/TimeInput.vue";
import {computed, onBeforeUnmount, ref, watch} from "vue";

const visible = defineModel<boolean>('visible');

const {baseNpc} = defineProps<{
    baseNpc: BaseNpcResource
}>()

const dropChanceLabels = [
    { key: 'common', label: 'Common', color: '#94a3b8' },
    { key: 'unique', label: 'Unique', color: '#22c55e' },
    { key: 'heroic', label: 'Heroic', color: '#3b82f6' },
    { key: 'legendary', label: 'Legendary', color: '#f59e0b' },
    { key: 'artefact', label: 'Artefact', color: '#ef4444' },
];

const emptyDropChances = (): Array<number | null> => [null, null, null, null, null];

const normalizeDropChances = (dropChances: number[] | null): Array<number | null> => {
    return dropChances ? [...dropChances] : emptyDropChances();
};

const createDefaultDropChances = (): number[] => [0.7, 0.15, 0.1, 0.04, 0.01];

const dropChancesToBoundaries = (dropChances: number[] | null): number[] => {
    const source = dropChances ?? createDefaultDropChances();

    return [
        source[0],
        source[0] + source[1],
        source[0] + source[1] + source[2],
        source[0] + source[1] + source[2] + source[3],
    ].map((value) => Number((value * 100).toFixed(2)));
};

const boundariesToDropChances = (boundaries: number[]): number[] => {
    const normalizedBoundaries = boundaries.map((value) => value / 100);

    return [
        normalizedBoundaries[0],
        normalizedBoundaries[1] - normalizedBoundaries[0],
        normalizedBoundaries[2] - normalizedBoundaries[1],
        normalizedBoundaries[3] - normalizedBoundaries[2],
        1 - normalizedBoundaries[3],
    ].map((value) => Number(value.toFixed(4)));
};

const hasCustomDropChances = ref(baseNpc.drop_chances !== null);
const dropChanceSlider = ref<HTMLElement | null>(null);
const activeHandleIndex = ref<number | null>(null);
const boundaryPercentages = ref<number[]>(dropChancesToBoundaries(baseNpc.drop_chances));

const form = useForm({
    name: baseNpc.name,
    lvl: baseNpc.lvl,
    rank: baseNpc.rank,
    category: baseNpc.category,
    profession: baseNpc.profession,
    is_aggressive: baseNpc.is_aggressive || false,
    divine_intervention: baseNpc.divine_intervention,
    drop_chances: normalizeDropChances(baseNpc.drop_chances),
    min_respawn_time: baseNpc.min_respawn_time,
    max_respawn_time: baseNpc.max_respawn_time,
})

const applyDropChanceBoundaries = (boundaries: number[]): void => {
    boundaryPercentages.value = boundaries.map((value) => Number(value.toFixed(2)));
    form.drop_chances = boundariesToDropChances(boundaryPercentages.value);
};

const dropChanceSegments = computed(() => {
    const values = form.drop_chances.map((value) => value ?? 0);

    return dropChanceLabels.map((dropChance, index) => ({
        ...dropChance,
        value: values[index],
        percent: Number(((values[index] ?? 0) * 100).toFixed(2)),
    }));
});

watch(hasCustomDropChances, (enabled) => {
    if (!enabled) {
        return;
    }

    if (baseNpc.drop_chances === null && form.drop_chances.every((value) => value === null)) {
        form.drop_chances = createDefaultDropChances();
    }

    applyDropChanceBoundaries(dropChancesToBoundaries(form.drop_chances as number[]));
});

const syncDropChancesFromBaseNpc = (): void => {
    const normalizedDropChances = normalizeDropChances(baseNpc.drop_chances);
    form.drop_chances = normalizedDropChances;
    boundaryPercentages.value = dropChancesToBoundaries(baseNpc.drop_chances);
};

const cancel = () => {
    form.reset();
    hasCustomDropChances.value = baseNpc.drop_chances !== null;
    syncDropChancesFromBaseNpc();
}

const confirm = () => {
    form
        .transform((data) => ({
            ...data,
            drop_chances: hasCustomDropChances.value ? data.drop_chances : null,
        }))
        .patch(route('base-npcs.update', {baseNpc}), {
        onSuccess: () => {
            visible.value = false;
        }
    })
}

const updateBoundaryFromClientX = (clientX: number): void => {
    if (activeHandleIndex.value === null || !dropChanceSlider.value) {
        return;
    }

    const sliderRect = dropChanceSlider.value.getBoundingClientRect();
    const rawPercentage = ((clientX - sliderRect.left) / sliderRect.width) * 100;
    const previousBoundary = activeHandleIndex.value === 0 ? 0 : boundaryPercentages.value[activeHandleIndex.value - 1];
    const nextBoundary = activeHandleIndex.value === boundaryPercentages.value.length - 1
        ? 100
        : boundaryPercentages.value[activeHandleIndex.value + 1];

    const clampedPercentage = Math.min(Math.max(rawPercentage, previousBoundary), nextBoundary);
    const nextBoundaries = [...boundaryPercentages.value];
    nextBoundaries[activeHandleIndex.value] = clampedPercentage;

    applyDropChanceBoundaries(nextBoundaries);
};

const stopBoundaryDrag = (): void => {
    activeHandleIndex.value = null;
    window.removeEventListener('pointermove', handlePointerMove);
    window.removeEventListener('pointerup', stopBoundaryDrag);
};

const handlePointerMove = (event: PointerEvent): void => {
    updateBoundaryFromClientX(event.clientX);
};

const startBoundaryDrag = (index: number, event: PointerEvent): void => {
    activeHandleIndex.value = index;
    updateBoundaryFromClientX(event.clientX);
    window.addEventListener('pointermove', handlePointerMove);
    window.addEventListener('pointerup', stopBoundaryDrag);
};

onBeforeUnmount(() => {
    stopBoundaryDrag();
});

const availableRanks = usePage<{availableRanks: DropdownListType }>().props.availableRanks
const availableCategories = usePage<{availableCategories: DropdownListType }>().props.availableCategories
const availableProfessions = usePage<{availableProfessions: DropdownListType }>().props.availableProfessions

const divineInterventionOptions = [
    { value: null, label: 'Domyślnie dla silnika' },
    { value: true, label: 'Tak' },
    { value: false, label: 'Nie' },
]

</script>
<template>
    <Dialog v-model:visible="visible" modal header="Edycja">
        <span class="text-surface-500 dark:text-surface-400 block mb-8">Przemyśl, zanim edytujesz</span>
        <div class="flex items-center gap-4 mb-4">
            <label for="name" class="font-semibold w-24">Nazwa</label>
            <InputText id="name" class="flex-auto" autocomplete="off" v-model="form.name" />
        </div>
        <Message severity="error" size="small" variant="simple">{{ form.errors.name }}</Message>

        <div class="flex items-center gap-4 mb-8">
            <label for="lvl" class="font-semibold w-24">Poziom</label>
            <InputNumber id="lvl" class="flex-auto" autocomplete="off" v-model="form.lvl" />
        </div>
        <Message severity="error" size="small" variant="simple">{{ form.errors.lvl }}</Message>

        <div class="flex items-center gap-4 mb-8">
            <label for="rank" class="font-semibold w-24">Ranga</label>
            <Select
                class="flex-auto"
                name="rank"
                v-model="form.rank"
                :options="availableRanks"
                option-value="value"
                option-label="label"
            />
        </div>
        <Message severity="error" size="small" variant="simple">{{ form.errors.rank }}</Message>

        <div class="flex items-center gap-4 mb-8">
            <label for="category" class="font-semibold w-24">Kategoria</label>
            <Select
                class="flex-auto"
                name="category"
                v-model="form.category"
                :options="availableCategories"
                option-value="value"
                option-label="label"
            />
        </div>
        <Message severity="error" size="small" variant="simple">{{ form.errors.category }}</Message>

        <div class="flex items-center gap-4 mb-8">
            <label for="category" class="font-semibold w-24">Profesja</label>
            <Select
                class="flex-auto"
                name="category"
                v-model="form.profession"
                :options="availableProfessions"
                option-value="value"
                option-label="label"
            />
        </div>
        <Message severity="error" size="small" variant="simple">{{ form.errors.category }}</Message>

        <div class="flex items-center gap-4 mb-8">
            <label for="is_aggressive" class="font-semibold w-24">Agresywny</label>
            <Checkbox id="is_aggressive" v-model="form.is_aggressive" :binary="true" />
        </div>
        <Message severity="error" size="small" variant="simple">{{ form.errors.is_aggressive }}</Message>

        <div class="flex items-center gap-4 mb-8">
            <label for="divine_intervention" class="font-semibold w-24">Boska interwencja</label>
            <Select
                class="flex-auto"
                name="divine_intervention"
                id="divine_intervention"
                v-model="form.divine_intervention"
                :options="divineInterventionOptions"
                option-value="value"
                option-label="label"
            />
        </div>
        <Message severity="error" size="small" variant="simple">{{ form.errors.divine_intervention }}</Message>

        <div class="mb-8">
            <TimeInput
                v-model="form.min_respawn_time"
                label="Minimalny czas respawnu"
                :error="form.errors.min_respawn_time"
            />
        </div>

        <div class="mb-8">
            <TimeInput
                v-model="form.max_respawn_time"
                label="Maksymalny czas respawnu"
                :error="form.errors.max_respawn_time"
            />
        </div>

        <div class="mb-8 flex items-center justify-between gap-4">
            <div>
                <label for="drop_chances_enabled" class="font-semibold">Niestandardowe szanse dropu</label>
                <p class="text-sm text-surface-500 dark:text-surface-400">
                    Ustaw 5 wartości dla common, unique, heroic, legendary i artefact.
                </p>
            </div>
            <ToggleSwitch id="drop_chances_enabled" v-model="hasCustomDropChances" />
        </div>

        <div v-if="hasCustomDropChances" class="mb-8 grid gap-4">
            <div class="grid gap-4 rounded-xl border border-surface-200 p-4 dark:border-surface-700">
                <div class="flex items-center justify-between gap-4">
                    <span class="font-semibold">Rozkład lootów</span>
                    <span class="text-sm text-surface-500 dark:text-surface-400">Przeciągnij 4 znaczniki na pasku</span>
                </div>

                <div class="drop-chance-editor">
                    <div ref="dropChanceSlider" class="drop-chance-track">
                        <div
                            v-for="segment in dropChanceSegments"
                            :key="segment.key"
                            class="drop-chance-segment"
                            :style="{ width: `${segment.percent}%`, backgroundColor: segment.color }"
                        />

                        <button
                            v-for="(boundary, index) in boundaryPercentages"
                            :key="index"
                            type="button"
                            class="drop-chance-handle"
                            :style="{ left: `${boundary}%` }"
                            @pointerdown.prevent="startBoundaryDrag(index, $event)"
                        />
                    </div>
                </div>

                <div class="grid gap-2 md:grid-cols-5">
                    <div
                        v-for="segment in dropChanceSegments"
                        :key="`${segment.key}-legend`"
                        class="rounded-lg border border-surface-200 p-3 dark:border-surface-700"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: segment.color }" />
                            <span class="font-semibold">{{ segment.label }}</span>
                        </div>
                        <div class="text-sm text-surface-500 dark:text-surface-400">{{ segment.percent.toFixed(2) }}%</div>
                    </div>
                </div>
            </div>
            <Message severity="error" size="small" variant="simple">{{ form.errors.drop_chances }}</Message>
            <Message severity="error" size="small" variant="simple">{{ form.errors['drop_chances.0'] }}</Message>
            <Message severity="error" size="small" variant="simple">{{ form.errors['drop_chances.1'] }}</Message>
            <Message severity="error" size="small" variant="simple">{{ form.errors['drop_chances.2'] }}</Message>
            <Message severity="error" size="small" variant="simple">{{ form.errors['drop_chances.3'] }}</Message>
            <Message severity="error" size="small" variant="simple">{{ form.errors['drop_chances.4'] }}</Message>
        </div>

        <div class="flex justify-end gap-2">
            <Button type="button" label="Anuluj" severity="secondary" @click="cancel"></Button>
            <Button :loading="form.processing" type="button" label="Zapisz" @click="confirm"></Button>
        </div>
    </Dialog>
</template>

<style scoped>
.drop-chance-editor {
    padding: 0.5rem 0;
}

.drop-chance-track {
    position: relative;
    display: flex;
    overflow: visible;
    height: 2.5rem;
    border-radius: 9999px;
    background: rgb(226 232 240);
    box-shadow: inset 0 0 0 1px rgb(148 163 184 / 0.25);
}

.drop-chance-segment:first-child {
    border-top-left-radius: 9999px;
    border-bottom-left-radius: 9999px;
}

.drop-chance-segment:last-child {
    border-top-right-radius: 9999px;
    border-bottom-right-radius: 9999px;
}

.drop-chance-handle {
    position: absolute;
    top: 50%;
    z-index: 2;
    width: 1.15rem;
    height: 3.25rem;
    border: 3px solid white;
    border-radius: 9999px;
    background: rgb(15 23 42);
    box-shadow: 0 8px 20px rgb(15 23 42 / 0.25);
    transform: translate(-50%, -50%);
    cursor: ew-resize;
}

.dark .drop-chance-track {
    background: rgb(51 65 85);
    box-shadow: inset 0 0 0 1px rgb(148 163 184 / 0.2);
}

.dark .drop-chance-handle {
    border-color: rgb(30 41 59);
    background: rgb(248 250 252);
}
</style>
