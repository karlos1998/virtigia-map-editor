<script setup lang="ts">

import {useForm, usePage} from "@inertiajs/vue3";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
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

const { baseItemCategoryList, baseItemRarityList } = usePage<{
    baseItemCategoryList: DropdownListType
    baseItemRarityList: DropdownListType
}>().props

const form = useForm({
    name: '',
    category: '',
    rarity: '',
})

watch(visible, () => {
    form.name = baseItem.name;
    form.category = baseItem.category;
    form.rarity = baseItem.rarity;
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
