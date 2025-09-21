<script setup lang="ts">
import {useForm, usePage} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {DropdownListType} from "@/Resources/DropdownList.type";
import {ref} from "vue";
import {useToast} from "primevue";
import AppLayout from "../../layout/AppLayout.vue";
import ItemHeader from "../../Components/ItemHeader.vue";

const toast = useToast();

const {baseItemCategoryList, baseItemRarityList, baseItemCurrencyList} = usePage<{
    baseItemCategoryList: DropdownListType
    baseItemRarityList: DropdownListType
    baseItemCurrencyList: DropdownListType
}>().props

const form = useForm({
    name: '',
    category: '',
    rarity: '',
    price: 0,
    currency: '',
    image: null as string | null,
});

const selectedFile = ref<File | null>(null);

const onFileSelect = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (file) {
        selectedFile.value = file;

        // Create an image element to check dimensions
        const img = new Image();
        img.onload = () => {
            if (img.width !== 32 || img.height !== 32) {
                toast.add({
                    severity: 'warn',
                    summary: 'Nieprawidłowe wymiary',
                    detail: 'Obraz musi mieć wymiary 32x32 pikseli',
                    life: 5000
                });
                // Clear the file input
                if (input) {
                    input.value = '';
                }
                selectedFile.value = null;
                form.image = null;
                return;
            }

            // Convert to base64
            const reader = new FileReader();
            reader.onload = (e) => {
                form.image = e.target?.result as string;
            };
            reader.readAsDataURL(file);
        };

        img.src = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('base-items.store'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Sukces',
                detail: 'Przedmiot został utworzony pomyślnie',
                life: 3000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Błąd',
                detail: 'Nie udało się utworzyć przedmiotu',
                life: 3000
            });
        }
    });
};

</script>

<template>
    <AppLayout>
        <ItemHeader :route-back="route('base-items.index')">
            <template #header>
                Nowy przedmiot
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
                                input-id="category"
                                v-model="form.category"
                                :options="baseItemCategoryList"
                                optionLabel="label"
                                option-value="value"
                                placeholder="Wybierz kategorię"
                                checkmark
                                :highlightOnSelect="false"
                                class="w-full"
                                :class="{'p-invalid': form.errors.category}"
                            />
                            <label for="category">Kategoria *</label>
                        </IftaLabel>
                        <small v-if="form.errors.category" class="p-error">{{ form.errors.category }}</small>

                        <IftaLabel>
                            <Dropdown
                                input-id="rarity"
                                v-model="form.rarity"
                                :options="baseItemRarityList"
                                optionLabel="label"
                                option-value="value"
                                placeholder="Wybierz rzadkość"
                                checkmark
                                :highlightOnSelect="false"
                                class="w-full"
                                :class="{'p-invalid': form.errors.rarity}"
                            />
                            <label for="rarity">Rzadkość *</label>
                        </IftaLabel>
                        <small v-if="form.errors.rarity" class="p-error">{{ form.errors.rarity }}</small>
                    </div>

                    <div class="space-y-4">
                        <IftaLabel>
                            <Dropdown
                                input-id="currency"
                                v-model="form.currency"
                                :options="baseItemCurrencyList"
                                optionLabel="label"
                                option-value="value"
                                placeholder="Wybierz walutę"
                                checkmark
                                :highlightOnSelect="false"
                                class="w-full"
                                :class="{'p-invalid': form.errors.currency}"
                            />
                            <label for="currency">Waluta *</label>
                        </IftaLabel>
                        <small v-if="form.errors.currency" class="p-error">{{ form.errors.currency }}</small>

                        <IftaLabel>
                            <InputNumber
                                input-id="price"
                                v-model="form.price"
                                showButtons
                                buttonLayout="horizontal"
                                :step="5000"
                                :max="1000000000"
                                :min="0"
                                class="w-full"
                                :class="{'p-invalid': form.errors.price}"
                            />
                            <label for="price">Cena *</label>
                        </IftaLabel>
                        <small v-if="form.errors.price" class="p-error">{{ form.errors.price }}</small>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Grafika (32x32px)
                            </label>
                            <input
                                type="file"
                                accept="image/png,image/gif"
                                @change="onFileSelect"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100"
                            />
                            <small class="text-gray-500">Wymagane wymiary: 32x32 pikseli (PNG lub GIF)</small>
                            <small v-if="form.errors.image" class="p-error">{{ form.errors.image }}</small>

                            <div v-if="form.image" class="mt-2">
                                <img
                                    :src="form.image"
                                    alt="Preview"
                                    class="h-16 w-16 object-cover border-2 border-gray-300 rounded"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-4 border-t">
                    <Button
                        type="button"
                        label="Anuluj"
                        severity="secondary"
                        @click="$inertia.visit(route('base-items.index'))"
                    />
                    <Button
                        type="submit"
                        label="Utwórz przedmiot"
                        :loading="form.processing"
                        :disabled="!form.name || !form.category || !form.rarity || !form.currency || form.price < 0"
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
