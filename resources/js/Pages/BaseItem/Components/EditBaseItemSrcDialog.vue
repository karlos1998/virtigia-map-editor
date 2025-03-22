<script setup lang="ts">

import {useForm, usePage} from "@inertiajs/vue3";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import {route} from "ziggy-js";
import {DropdownListType} from "@/Resources/DropdownList.type";
import {ref} from "vue";
import {useToast} from "primevue";
import {BaseItemResource} from "../../../Resources/BaseItem.resource";

const visible = defineModel<boolean>('visible');

const {baseItem} = defineProps<{
    baseItem: BaseItemResource
}>()

const form = useForm({
    image: null,
    name: null,
})

const cancel = () => {

}

const toast = useToast();

const confirm = () => {
    form.patch(route('base-items.image.update', {baseItem}), {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Grafika została zmieniona', life: 3000 });
            visible.value = false;
        }
    })
}

function onFileSelect(event) {
    const file = event.files[0];
    const reader = new FileReader();

    reader.onload = async (e) => {
        form.image = e.target.result;
        form.name = file.name
    };

    reader.readAsDataURL(file);
}

</script>
<template>
    <Dialog v-model:visible="visible" modal header="Zmiana grafiki">
        <div class="flex flex-col gap-6 items-center justify-center">
            <FileUpload mode="basic" @select="onFileSelect" customUpload auto severity="secondary" class="p-button-outlined" />
            <img v-if="form.image" :src="form.image" alt="Image" class="shadow-md rounded-xl w-full sm:w-64 mt-6" />
            <Button
                v-if="form.image"
                label="Wyślij"
                @click="confirm"
            />

            <Message v-if="form.errors.image" severity="error" size="small" variant="simple">{{ form.errors.image }}</Message>

        </div>
    </Dialog>
</template>

<style scoped>

</style>
