<script setup lang="ts">
import {useForm, usePage} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {useToast} from "primevue";
import AppLayout from "../../layout/AppLayout.vue";
import ItemHeader from "../../Components/ItemHeader.vue";
import {DropdownListType} from "@/Resources/DropdownList.type";

const toast = useToast();

const {availableAttackTypes, availableTargets, availableEffectTypes, availableElements} = usePage<{
    availableAttackTypes: DropdownListType
    availableTargets: DropdownListType
    availableEffectTypes: DropdownListType
    availableElements: DropdownListType
}>().props

const form = useForm({
    name: '',
    attack_type: '',
    charge_turns: 0,
    target: '',
    random_target: false,
    effects: [] as Array<{type: string, value: number, duration: number}>,
    damages: [] as Array<{element: string, min_damage: number, max_damage: number}>,
});

const addEffect = () => {
    form.effects.push({type: '', value: 0, duration: 0});
};

const removeEffect = (index: number) => {
    form.effects.splice(index, 1);
};

const addDamage = () => {
    form.damages.push({element: '', min_damage: 0, max_damage: 0});
};

const removeDamage = (index: number) => {
    form.damages.splice(index, 1);
};

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

                <!-- Efekty -->
                <div class="card">
                    <div class="flex justify-between items-center mb-4">
                        <h3>Efekty</h3>
                        <Button
                            type="button"
                            label="Dodaj efekt"
                            icon="pi pi-plus"
                            @click="addEffect"
                            severity="success"
                            size="small"
                        />
                    </div>

                    <div v-if="form.effects.length === 0" class="text-gray-500 text-center py-4">
                        Brak efektów. Kliknij "Dodaj efekt" aby dodać nowy efekt.
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="(effect, index) in form.effects" :key="index"
                             class="p-4 border rounded-lg bg-gray-50 dark:bg-gray-800">
                            <div class="flex justify-end mb-2">
                                <Button
                                    type="button"
                                    icon="pi pi-trash"
                                    @click="removeEffect(index)"
                                    severity="danger"
                                    text
                                    size="small"
                                />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <IftaLabel>
                                    <Dropdown
                                        :input-id="`effect-type-${index}`"
                                        v-model="effect.type"
                                        :options="availableEffectTypes"
                                        optionLabel="label"
                                        option-value="value"
                                        placeholder="Wybierz typ efektu"
                                        checkmark
                                        :highlightOnSelect="false"
                                        class="w-full"
                                    />
                                    <label :for="`effect-type-${index}`">Typ efektu</label>
                                </IftaLabel>

                                <IftaLabel>
                                    <InputNumber
                                        :input-id="`effect-value-${index}`"
                                        v-model="effect.value"
                                        :minFractionDigits="0"
                                        :maxFractionDigits="2"
                                        class="w-full"
                                    />
                                    <label :for="`effect-value-${index}`">Wartość</label>
                                </IftaLabel>

                                <IftaLabel>
                                    <InputNumber
                                        :input-id="`effect-duration-${index}`"
                                        v-model="effect.duration"
                                        :min="0"
                                        showButtons
                                        buttonLayout="horizontal"
                                        :step="1"
                                        class="w-full"
                                    />
                                    <label :for="`effect-duration-${index}`">Czas trwania (tur)</label>
                                </IftaLabel>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Obrażenia -->
                <div class="card">
                    <div class="flex justify-between items-center mb-4">
                        <h3>Obrażenia</h3>
                        <Button
                            type="button"
                            label="Dodaj obrażenia"
                            icon="pi pi-plus"
                            @click="addDamage"
                            severity="success"
                            size="small"
                        />
                    </div>

                    <div v-if="form.damages.length === 0" class="text-gray-500 text-center py-4">
                        Brak obrażeń. Kliknij "Dodaj obrażenia" aby dodać nowe obrażenia.
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="(damage, index) in form.damages" :key="index"
                             class="p-4 border rounded-lg bg-gray-50 dark:bg-gray-800">
                            <div class="flex justify-end mb-2">
                                <Button
                                    type="button"
                                    icon="pi pi-trash"
                                    @click="removeDamage(index)"
                                    severity="danger"
                                    text
                                    size="small"
                                />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <IftaLabel>
                                    <Dropdown
                                        :input-id="`damage-element-${index}`"
                                        v-model="damage.element"
                                        :options="availableElements"
                                        optionLabel="label"
                                        option-value="value"
                                        placeholder="Wybierz element"
                                        checkmark
                                        :highlightOnSelect="false"
                                        class="w-full"
                                    />
                                    <label :for="`damage-element-${index}`">Element</label>
                                </IftaLabel>

                                <IftaLabel>
                                    <InputNumber
                                        :input-id="`damage-min-${index}`"
                                        v-model="damage.min_damage"
                                        :min="0"
                                        showButtons
                                        buttonLayout="horizontal"
                                        :step="1"
                                        class="w-full"
                                    />
                                    <label :for="`damage-min-${index}`">Min obrażenia</label>
                                </IftaLabel>

                                <IftaLabel>
                                    <InputNumber
                                        :input-id="`damage-max-${index}`"
                                        v-model="damage.max_damage"
                                        :min="0"
                                        showButtons
                                        buttonLayout="horizontal"
                                        :step="1"
                                        class="w-full"
                                    />
                                    <label :for="`damage-max-${index}`">Max obrażenia</label>
                                </IftaLabel>
                            </div>
                        </div>
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
