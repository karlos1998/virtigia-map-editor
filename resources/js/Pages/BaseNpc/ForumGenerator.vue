<script setup lang="ts">
import { computed, ref } from "vue";
import AppLayout from "@/layout/AppLayout.vue";
import { route } from "ziggy-js";
import { usePage } from "@inertiajs/vue3";
import { DropdownListType } from "@/Resources/DropdownList.type";

const { availableRanks } = usePage<{
    availableRanks: DropdownListType
}>().props;

const selectedRank = ref<string>(availableRanks[0]?.value ?? "");

const generatorUrl = computed(() => route("base-npcs.forum-generator", {
    rank: selectedRank.value,
}));

const submit = (): void => {
    window.open(generatorUrl.value, "_blank", "noopener,noreferrer");
};
</script>

<template>
    <AppLayout>
        <div class="mx-auto flex max-w-3xl flex-col gap-4">
            <div class="card">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold">Generator dla forum</h1>
                    <p class="text-surface-500 dark:text-surface-400">
                        Wybierz rangę bazowych NPC, a następnie wygeneruj gotowy szablon w nowej karcie.
                    </p>
                </div>

                <form class="flex flex-col gap-6" @submit.prevent="submit">
                    <div class="flex flex-col gap-2">
                        <label for="rank" class="font-semibold">Szablon / ranga NPC</label>
                        <Dropdown
                            input-id="rank"
                            v-model="selectedRank"
                            :options="availableRanks"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full"
                        />
                    </div>

                    <div class="rounded-lg border border-surface-200 bg-surface-50 p-4 text-sm text-surface-600 dark:border-surface-700 dark:bg-surface-900/40 dark:text-surface-300">
                        Generator otworzy istniejący endpoint z parametrem `rank` w nowej karcie.
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            label="Wróć do bazowych NPC"
                            severity="secondary"
                            outlined
                            @click="$inertia.visit(route('base-npcs.index'))"
                        />
                        <Button
                            type="submit"
                            label="Generuj"
                            icon="pi pi-external-link"
                            :disabled="!selectedRank"
                        />
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
