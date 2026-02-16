<script setup lang="ts">
import {useForm} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {useToast} from "primevue";
import AppLayout from "@/layout/AppLayout.vue";
import ItemHeader from "@/Components/ItemHeader.vue";

const toast = useToast();

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('dialog-counters.store'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Sukces',
                detail: 'Licznik dialogowy został utworzony',
                life: 3000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: 'Nie udało się utworzyć licznika',
                life: 3000
            });
        }
    });
};
</script>

<template>
    <AppLayout>
        <ItemHeader :route-back="route('dialog-counters.index')">
            <template #header>
                Nowy licznik dialogowy
            </template>
        </ItemHeader>

        <div class="card">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-4">
                    <IftaLabel>
                        <InputText
                            id="name"
                            v-model="form.name"
                            class="w-full"
                            :class="{'p-invalid': form.errors.name}"
                            maxlength="255"
                        />
                        <label for="name">Nazwa *</label>
                    </IftaLabel>
                    <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                </div>

                <div class="flex justify-end space-x-4 pt-4 border-t">
                    <Button
                        type="button"
                        label="Anuluj"
                        severity="secondary"
                        @click="$inertia.visit(route('dialog-counters.index'))"
                    />
                    <Button
                        type="submit"
                        label="Utwórz licznik"
                        :loading="form.processing"
                        :disabled="!form.name"
                    />
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.p-error {
    color: #e24c4c;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.p-invalid {
    border-color: #e24c4c;
}
</style>
