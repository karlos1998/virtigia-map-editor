<script setup lang="ts">
import {route} from "ziggy-js";
import {BaseItemWithRelations} from "@/Resources/BaseItem.resource";
import AppLayout from "../../layout/AppLayout.vue";
import ItemHeader from "../../Components/ItemHeader.vue";
import {router, useForm} from "@inertiajs/vue3";
import { onMounted, ref, computed } from 'vue';
import {useToast} from "primevue";
import AttributeEditor from "../../Components/AttributeEditor.vue";
import AttributePointsEditor from './Components/AttributePointsEditor.vue';
import JsonEditorVue from 'json-editor-vue'

const { baseItem } = defineProps<{
    baseItem: BaseItemWithRelations,
}>();

// Create form with full baseItem structure
const form = useForm({
    attributes: baseItem.attributes,
    attribute_points: baseItem.attribute_points || {},
    manual_attribute_points: baseItem.manual_attribute_points || {}
})

// Store scale result from AttributePointsEditor
const scaleResult = ref<any>(null);

// Handle scale result changes from AttributePointsEditor
const handleScaleResultChanged = (result: any) => {
    scaleResult.value = result;
};

// Computed property for tooltip - merge original attributes with scaled attributes
const tooltipAttributes = computed(() => {
    const baseAttributes = { ...form.attributes };

    // If we have scale result, merge it with base attributes
    if (scaleResult.value) {
        return {
            ...scaleResult.value
        };
    }

    return baseAttributes;
});

const toast = useToast();

onMounted(() => {
    toast.add({ severity: 'warn', summary: 'Uwaga', detail: 'W strefie pakowania jest artykuł, który nie powinien się tam znaleźć', life: 10000 });
})

const save = () => {
    // Prepare the final attributes by merging current attributes with scale result
    let finalAttributes = { ...form.attributes };

    // If we have scale result, merge it with current attributes
    // Scale result values take priority over current attributes
    if (scaleResult.value) {
        finalAttributes = { ...form.attributes, ...scaleResult.value };
    }

    // Create update data with all three fields
    const updateData = {
        attributes: finalAttributes,
        attribute_points: form.attribute_points || {},
        manual_attribute_points: form.manual_attribute_points || {}
    };

    // Send the update
    form
        .transform(() => updateData)
        .patch(route('base-items.attributes.update', {baseItem}), {
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Sukces',
                    detail: 'Przedmiot został zapisany z przeskalowanymi atrybutami',
                    life: 3000
                });
            },
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Błąd',
                    detail: 'Nie udało się zapisać',
                    life: 3000
                });
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
                v-tip.item.top.show-id="{...baseItem, attributes: tooltipAttributes}"
                alt=""
            /> ^ Podgląd edytowanego przedmiotu
            <div v-if="scaleResult" class="mt-2 text-sm text-green-600">
                Wyświetlane są przeskalowane atrybuty z kalkulatora punktów
            </div>
        </div>
        <Tabs value="0" class="card">
            <TabList>
                <Tab value="0">Kalkulator punktów</Tab>
                <Tab value="1">Edytor json</Tab>
<!--                <Tab value="2">Edytor atrybutów</Tab>-->
            </TabList>
            <TabPanels>
                <TabPanel value="0">
                    <AttributePointsEditor
                        v-model="form"
                        :base-item="baseItem"
                        @scale-result-changed="handleScaleResultChanged"
                    />
                </TabPanel>
                <TabPanel value="1">
                    <JsonEditorVue
                        v-model="form.attributes"
                        v-bind="{/* local props & attrs */}"
                    />
                </TabPanel>
                <TabPanel value="2">
                    <AttributeEditor v-model:attributes="form.attributes" />
                </TabPanel>
            </TabPanels>
        </Tabs>
    </AppLayout>
</template>
