<script setup lang="ts">

import {useForm, usePage} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {DropdownListType} from "@/Resources/DropdownList.type";
import {ref, watch} from "vue";
import {useToast} from "primevue";
import {BaseItemResource} from "@/Resources/BaseItem.resource";

const toast = useToast();
const visible = defineModel<boolean>('visible');

const {baseItem} = defineProps<{
    baseItem: BaseItemResource
}>()

const { baseItemCategoryList, baseItemRarityList, baseItemCurrencyList } = usePage<{
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
})

watch(visible, () => {
    form.name = baseItem.name;
    form.category = baseItem.category;
    form.rarity = baseItem.rarity;
    form.price = baseItem.price;
    form.currency = baseItem.currency;
})



const confirm = () => {
    form
        .patch(route('base-items.update', {baseItem: baseItem.id}), {
            onSuccess: () => {
                visible.value = false;
            }
        })
}

</script>
<template>
    <Dialog v-model:visible="visible" modal header="Dane podstawowe">

        <div class="space-y-4">

            <IftaLabel>
                <InputText
                    id="name"
                    v-model="form.name"
                    class="w-full md:w-14rem"
                />
                <label for="category">Nazwa</label>
            </IftaLabel>

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
                    class="w-full md:w-14rem"
                />
                <label for="category">Kategoria</label>
            </IftaLabel>

            <IftaLabel>
                <Dropdown
                    input-id="rarity"
                    v-model="form.rarity"
                    :options="baseItemRarityList"
                    optionLabel="label"
                    option-value="value"
                    placeholder="Wybierz unikalność"
                    checkmark
                    :highlightOnSelect="false"
                    class="w-full md:w-14rem"
                />
                <label for="rarity">Unikalność</label>
            </IftaLabel>

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
                    class="w-full md:w-14rem"
                />
                <label for="currency">Waluta</label>
            </IftaLabel>

            <IftaLabel>
                <InputNumber
                    input-id="price"
                    v-model="form.price"
                    showButtons
                    buttonLayout="horizontal"
                    :step="5000"
                    :max="1000000000"
                    :min="0"
                />
                <label for="price">Wartość</label>
            </IftaLabel>

            <Button
                label="Zapisz zmiany"
                :loading="form.processing"
                @click="confirm"
            />

        </div>
    </Dialog>
</template>

<style scoped>

</style>
