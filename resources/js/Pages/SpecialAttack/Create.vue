<script setup lang="ts">
import {useForm, usePage} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {useToast} from "primevue";
import AppLayout from "../../layout/AppLayout.vue";
import ItemHeader from "../../Components/ItemHeader.vue";
import {DropdownListType} from "@/Resources/DropdownList.type";

const toast = useToast();

const {availableAttackTypes, availableTargets} = usePage<{
    availableAttackTypes: DropdownListType
    availableTargets: DropdownListType
}>().props

const form = useForm({
    name: '',
    attack_type: '',
    charge_turns: 0,
    target: '',
    random_target: false,
});

const submit = () => {
    form.post(route('special-attacks.store'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Sukces',
                detail: 'Cios specjalny został utworzony pomyślnie',
                life: 3000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: 'Nie udało się utworzyć ciosu specjalnego',
                life: 3000
            });
        }
    });
};
</script>

<template>
    <AppLayout>
        <ItemHeader :route-back="route('special-attacks.index')">
            <template #header>
                Nowy cios specjalny
            </template>
        </ItemHeader>

        <div class="card">
            <form @submit.prevent="submit" class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="space-y-4">
                        <IftaLabel>
                            <InputText
                                id="name"
                                v-model="form.name"
                                class="w-full"
                                :class="{'p-invalid': form.errors.name}"
                            />
                            <label for="name">Nazwa *</label>
                        </IftaLabel>
                        <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>

                        <IftaLabel>
                            <Dropdown
                                input-id="attack_type"
                                v-model="form.attack_type"
                                :options="availableAttackTypes"
                                optionLabel="label"
                                option-value="value"
                                placeholder="Wybierz typ ataku"
                                checkmark
                                :highlightOnSelect="false"
                                class="w-full"
                                :class="{'p-invalid': form.errors.attack_type}"
                            />
                            <label for="attack_type">Typ ataku *</label>
                        </IftaLabel>
                        <small v-if="form.errors.attack_type" class="p-error">{{ form.errors.attack_type }}</small>

                        <IftaLabel>
                            <Dropdown
                                input-id="target"
                                v-model="form.target"
                                :options="availableTargets"
                                optionLabel="label"
                                option-value="value"
                                placeholder="Wybierz cel"
                                checkmark
                                :highlightOnSelect="false"
                                class="w-full"
                                :class="{'p-invalid': form.errors.target}"
                            />
                            <label for="target">Cel *</label>
                        </IftaLabel>
                        <small v-if="form.errors.target" class="p-error">{{ form.errors.target }}</small>
                    </div>

                    <div class="space-y-4">
                        <IftaLabel>
                            <InputNumber
                                input-id="charge_turns"
                                v-model="form.charge_turns"
                                showButtons
                                buttonLayout="horizontal"
                                :step="1"
                                :max="10"
                                :min="0"
                                class="w-full"
                                :class="{'p-invalid': form.errors.charge_turns}"
                            />
                            <label for="charge_turns">Tur ładowania *</label>
                        </IftaLabel>
                        <small v-if="form.errors.charge_turns" class="p-error">{{ form.errors.charge_turns }}</small>

                        <IftaLabel>
                            <InputSwitch
                                input-id="random_target"
                                v-model="form.random_target"
                                :class="{'p-invalid': form.errors.random_target}"
                            />
                            <label for="random_target">Losowy cel *</label>
                        </IftaLabel>
                        <small v-if="form.errors.random_target" class="p-error">{{ form.errors.random_target }}</small>
                        <small class="text-gray-500">Czy atak ma wybierać cel losowo</small>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-4 border-t">
                    <Button
                        type="button"
                        label="Anuluj"
                        severity="secondary"
                        @click="$inertia.visit(route('special-attacks.index'))"
                    />
                    <Button
                        type="submit"
                        label="Utwórz cios specjalny"
                        :loading="form.processing"
                        :disabled="!form.name || !form.attack_type || !form.target"
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
