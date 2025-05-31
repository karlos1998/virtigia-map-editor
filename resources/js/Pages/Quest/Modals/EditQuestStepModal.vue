<script setup lang="ts">
import {useForm} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {useToast} from "primevue";
import { watch } from "vue";
import { QuestStepResource } from "@/Resources/Quest.resource";

const props = defineProps<{
    questId: number;
    step: QuestStepResource | null;
}>();

const visible = defineModel<boolean>('visible');
const toast = useToast();

const form = useForm({
    name: '',
    description: '',
})

watch(() => props.step, (newStep) => {
    if (newStep) {
        form.name = newStep.name;
        form.description = newStep.description;
    }
}, { immediate: true });

const submit = () => {
    if (!props.step) return;

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
            <div>
                <label for="name" class="font-semibold block mb-2">Nazwa</label>
                <InputText id="name" class="w-full" autocomplete="off" v-model="form.name" />
                <Message severity="error" size="small" variant="simple">{{ form.errors.name }}</Message>
            </div>

            <div>
                <label for="description" class="font-semibold block mb-2">Opis</label>
                <Textarea id="description" class="w-full" rows="10" v-model="form.description" />
                <Message severity="error" size="small" variant="simple">{{ form.errors.description }}</Message>
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
