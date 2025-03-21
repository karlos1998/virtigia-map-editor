<script setup lang="ts">
import {route} from "ziggy-js";
import {BaseItemWithRelations} from "../../Resources/BaseItem.resource";
import AppLayout from "../../layout/AppLayout.vue";
import ItemHeader from "../../Components/ItemHeader.vue";
import {router, useForm} from "@inertiajs/vue3";
import {onMounted, ref} from "vue";
import JsonEditorVue from 'json-editor-vue'
import {useToast} from "primevue";

const { baseItem } = defineProps<{
    baseItem: BaseItemWithRelations,
}>();


const form = useForm({
    attributes: baseItem.attributes,
})

const toast = useToast();

onMounted(() => {
    toast.add({ severity: 'warn', summary: 'Uwaga', detail: 'W strefie pakowania jest artykuł, który nie powinien się tam znaleźć', life: 10000 });
})

const save = () => {
    form
        .patch(route('base-items.attributes.update', {baseItem}), {
            onError: () => {
                toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zapisać', life: 3000 });
            }
        })
}
</script>

<template>
    <AppLayout>

        <ItemHeader
            :route-back="route('base-items.show', {baseItem})"
            route-back-label="Powrót do podglądu"
        >
            <template #header>
                #{{ baseItem.id }} - {{ baseItem.name }}

            </template>

            <template #right-buttons>
                <button
                    class="px-4 py-2 text-white bg-green-500 hover:bg-green-600 rounded shadow mr-2"
                    @click="save"
                    :loading="form.processing"
                >
                    <i class="pi pi-save mr-2"></i>
                    Zapisz
                </button>
            </template>
        </ItemHeader>

        <div class="card">
            <img
                class="h-12 w-12 object-cover"
                :src="baseItem.src"
                v-tip.item.top.show-id="{...baseItem, attributes: form.attributes}"
                alt=""
            /> ^ Podgląd edytowanego przedmiotu
        </div>

        <div class="card">
            <JsonEditorVue
                v-model="form.attributes"
                v-bind="{/* local props & attrs */}"
            />
        </div>
    </AppLayout>
</template>
