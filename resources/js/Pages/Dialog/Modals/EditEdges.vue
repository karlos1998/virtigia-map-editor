<script setup lang="ts">
import Button from 'primevue/button';
import Drawer from 'primevue/drawer';
import Message from 'primevue/message';
import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import AccordionHeader from 'primevue/accordionheader';
import AccordionContent from 'primevue/accordioncontent';
import { computed, ref, watch } from "vue";
import { useToast } from "primevue";
import { DialogNodeOptionEdgeWithRules } from "@/Resources/DialogOption.resource";
import EditRules from "../Componnts/EditRules.vue";
import axios from "axios";
import { route } from "ziggy-js";

// Define props and emits
const props = defineProps<{
    visible: boolean,
    edges: DialogNodeOptionEdgeWithRules[],
    parent: string,
    dialog_id: number,
}>();

const emit = defineEmits<{
    'update:visible': [value: boolean],
    close: [data: { edges?: DialogNodeOptionEdgeWithRules[] }]
}>();

const form = ref({
    edges: [] as DialogNodeOptionEdgeWithRules[]
});

const toast = useToast();
const formLoaded = ref(false);

// Use the visible prop to control the drawer's visibility
const drawerVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
});

const closeDrawer = () => {
    // Update the visible prop through the computed property
    drawerVisible.value = false;

    // Emit close event
    emit('close', {});
};

watch(() => props.visible, () => {
    // Initialize form with edges data from props
    form.value.edges = props.edges.map(edge => ({
        ...edge,
        rules: edge.rules || {}
    })) || [];

    formLoaded.value = true;
});

const processing = ref(false);

const save = () => {
    processing.value = true;

    console.log('dialog_id:', props.dialog_id);
    console.log('parent:', props.parent);

    // Make API call to save edge rules
    axios.patch(route('dialogs.nodes.start-edges.update', {
        dialog: props.dialog_id,
        dialogNode: props.parent,
    }), { edges: form.value.edges })
        .then(({data}) => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Reguły przejścia zostały zapisane', life: 3000 });

            // Update the visible prop through the computed property
            drawerVisible.value = false;

            // Emit close event with the updated edges
            emit('close', { edges: form.value.edges });
        })
        .catch((error) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił problem podczas zapisywania reguł przejścia', life: 3000 });
            console.error('Error saving edge rules:', error);
        })
        .finally(() => {
            processing.value = false;
        });
};
</script>

<template>
    <Drawer
        v-model:visible="drawerVisible"
        position="right"
        :style="{ width: '50%' }"
        :modal="true"
        :dismissable="false"
        :closable="false"
        header="Edycja reguł przejścia do kolejnych dialogów"
    >
        <div class="p-4 flex flex-col gap-4" v-if="formLoaded">
            <div class="card">
                <h3 class="text-xl mb-3">Reguły przejścia do kolejnych dialogów</h3>
                <Accordion v-if="form.edges && form.edges.length > 0">
                    <AccordionPanel v-for="(edge, index) in form.edges" :key="edge.edge_id">
                        <AccordionHeader>
                            {{edge.node.content}}
                        </AccordionHeader>
                        <AccordionContent>
                            <EditRules v-model:rules="form.edges[index].rules" />
                        </AccordionContent>
                    </AccordionPanel>
                </Accordion>
                <Message v-else severity="warn">Brak kolejnych kroków dialogowych</Message>
            </div>

            <div class="flex gap-3 justify-end mt-4">
                <Button :loading="processing" @click="closeDrawer" severity="secondary">Anuluj</Button>
                <Button :loading="processing" @click="save" severity="primary">Zapisz</Button>
            </div>
        </div>
    </Drawer>
</template>

<style scoped>
.card {
    background-color: var(--surface-card);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    margin-bottom: 1rem;
}

:deep(.p-drawer-content) {
    padding: 0;
}

:deep(.p-accordion .p-accordion-header:not(.p-disabled).p-highlight .p-accordion-header-link) {
    background-color: var(--primary-color);
    color: var(--primary-color-text);
}

:deep(.p-accordion .p-accordion-header:not(.p-disabled).p-highlight:hover .p-accordion-header-link) {
    background-color: var(--primary-600);
}
</style>
