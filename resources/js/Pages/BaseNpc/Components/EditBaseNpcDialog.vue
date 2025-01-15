<script setup lang="ts">

import {useForm, usePage} from "@inertiajs/vue3";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import {route} from "ziggy-js";
import {DropdownListType} from "@/Resources/DropdownList.type";

const visible = defineModel<boolean>('visible');

const {baseNpc} = defineProps<{
    baseNpc: BaseNpcResource
}>()

const form = useForm({
    name: baseNpc.name,
    lvl: baseNpc.lvl,
    rank: baseNpc.rank,
    category: baseNpc.category,
})

const cancel = () => {
    form.reset();
}

const confirm = () => {
    form.patch(route('base-npcs.update', {baseNpc}), {
        onSuccess: () => {
            visible.value = false;
        }
    })
}

const availableRanks = usePage<{availableRanks: DropdownListType }>().props.availableRanks

const availableCategories = usePage<{availableRanks: DropdownListType }>().props.availableCategories

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

        <div class="flex justify-end gap-2">
            <Button type="button" label="Anuluj" severity="secondary" @click="cancel"></Button>
            <Button :loading="form.processing" type="button" label="Zapisz" @click="confirm"></Button>
        </div>
    </Dialog>
</template>

<style scoped>

</style>
