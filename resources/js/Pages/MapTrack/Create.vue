<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import ItemHeader from '@/Components/ItemHeader.vue';
import { DialogCounterResource, dialogCounterScopeLabels } from '@/Resources/DialogCounter.resource';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Select from 'primevue/select';
import InputSwitch from 'primevue/inputswitch';

defineProps<{
    dialogCounters: DialogCounterResource[];
}>();

const form = useForm({
    name: '',
    dialog_counter_id: null as number | null,
    enabled: true,
});

const submit = () => form.post(route('map-tracks.store'));
</script>

<template>
    <AppLayout>
        <ItemHeader :route-back="route('map-tracks.index')">
            <template #header>Nowa trasa</template>
        </ItemHeader>

        <div class="card">
            <Message v-if="dialogCounters.length === 0" severity="warn" class="mb-4">
                Najpierw utwórz licznik dialogowy, który będzie przechowywał liczbę ukończonych okrążeń.
            </Message>

            <form class="flex max-w-3xl flex-col gap-5" @submit.prevent="submit">
                <div class="flex flex-col gap-2">
                    <label for="track-name" class="font-medium">Nazwa trasy</label>
                    <InputText id="track-name" v-model="form.name" :invalid="Boolean(form.errors.name)" />
                    <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="track-counter" class="font-medium">Licznik ukończonych okrążeń</label>
                    <Select
                        id="track-counter"
                        v-model="form.dialog_counter_id"
                        :options="dialogCounters"
                        option-label="name"
                        option-value="id"
                        filter
                        placeholder="Wybierz licznik dialogowy"
                        :invalid="Boolean(form.errors.dialog_counter_id)"
                    >
                        <template #option="{ option }">
                            <div class="flex w-full items-center justify-between gap-4">
                                <span>{{ option.name }}</span>
                                <Tag :value="dialogCounterScopeLabels[option.scope]" severity="secondary" />
                            </div>
                        </template>
                    </Select>
                    <small v-if="form.errors.dialog_counter_id" class="text-red-500">{{ form.errors.dialog_counter_id }}</small>
                </div>

                <label class="flex items-center gap-3">
                    <InputSwitch v-model="form.enabled" />
                    <span class="font-medium">Trasa aktywna</span>
                </label>

                <div class="flex justify-end gap-3 border-t pt-4">
                    <Button type="button" label="Anuluj" severity="secondary" @click="$inertia.visit(route('map-tracks.index'))" />
                    <Button
                        type="submit"
                        label="Utwórz i dodaj checkpointy"
                        icon="pi pi-arrow-right"
                        :loading="form.processing"
                        :disabled="!form.name || !form.dialog_counter_id"
                    />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
