<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {computed, Ref, ref, watch} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {useForm, usePage} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {MultiSelectFilterEvent, useToast} from "primevue";
import axios from "axios";
import {DialogNodeOptionEdgeWithRules, DialogOptionResource} from "@/Resources/DialogOption.resource";
import {DropdownListType} from "@/Resources/DropdownList.type";
import EditRules from "../Componnts/EditRules.vue";
import {DialogNodeRulesResource} from "@/Resources/DialogNodeRules.resource";
import TreeSelectAdapter from "../Componnts/TreeSelectAdapter.vue";
import { useQuestStepSelection } from "../Composables/useQuestStepSelection";

const dialogNodeOptionAdditionalActionsList = ref(usePage<{dialogNodeOptionAdditionalActionsList: DropdownListType}>().props.dialogNodeOptionAdditionalActionsList)
const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        option: DialogOptionResource,
        parent: string,
        dialog_id: number
    }
}> | null>('dialogRef');

type FormOptionData = {
    label: string
    additional_action: any// todo
    additional_action_data?: string
    rules: DialogNodeRulesResource
    edges: DialogNodeOptionEdgeWithRules[]
}

const form = useForm<FormOptionData>({
    label: '',
    additional_action: null,
    additional_action_data: '',
    rules: {},
    edges: [],
})

const toast = useToast();

// Use the quest step selection composable
const { questNodes, loading, loadQuests, loadQuestStepById, onQuestNodeExpand } = useQuestStepSelection();

const formLoaded = ref(false);

// Watch for changes to additional_action and clear additional_action_data if needed
watch(() => form.additional_action, (newValue) => {
    if (newValue !== 'SET_QUEST_STEP') {
        form.additional_action_data = '';
    }
});

onMounted(() => {
    if(!dialogRef) return;

    form.label = dialogRef.value.data.option.label ?? '';
    form.additional_action = dialogRef.value.data.option.additional_action ?? '';
    form.additional_action_data = dialogRef.value.data.option.additional_action_data ?? '';
    form.rules = dialogRef.value.data.option.rules ?? {};

    form.edges = dialogRef.value.data.option.edges.map(edge => ({
        ...edge,
        rules: edge.rules || {}
    })) || [];

    // Load quests for the TreeSelect
    loadQuests();

    // Check if a quest step is already selected and load its details
    if (form.additional_action === 'SET_QUEST_STEP' && form.additional_action_data && form.additional_action_data.startsWith('s-')) {
        // Extract step ID from the value (format: "s-{id}")
        const stepId = parseInt(form.additional_action_data.substring(2));
        if (!isNaN(stepId)) {
            loadQuestStepById(stepId);
        }
    }

    formLoaded.value = true
})

const processing = ref(false);

const save = () => {
    //axios zeby form inertia nie przeladowywal strony

    if(!dialogRef) return;

    // Validate SET_QUEST_STEP action data
    if (form.additional_action === 'SET_QUEST_STEP') {
        if (!form.additional_action_data) {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybierz krok questa', life: 3000 });
            return;
        }

        // Make sure only quest steps (s-X) are selected, not quests (q-X)
        if (form.additional_action_data.startsWith('q-')) {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wybierz krok questa, nie cały quest', life: 3000 });
            return;
        }
    }

    processing.value = true;

    console.log('save option', form.data())

    const transformData = (data: FormOptionData) => {
        const d = { ...data };

        // If additional_action is not SET_QUEST_STEP, remove additional_action_data
        if (d.additional_action !== 'SET_QUEST_STEP') {
            d.additional_action_data = undefined;
        }

        return d;
    }

    const data = transformData(form.data());
    axios.patch<DialogOptionResource>(route('dialogs.nodes.options.update', {
        dialogNodeOption: dialogRef.value.data?.option?.id,
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data?.option?.node_id,
    }), data)
        .then(({data}) => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Opcja dialogowa została edytowana', life: 3000 });
            dialogRef.value.close({
                dialogOption: data,
            });
        })
        .catch(() => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił problem podczas edycji opcji dialogowej', life: 3000 });
        })
        .finally(() => {
            processing.value = false;
        })

    // form
    //     .transform(data => {
    //         const d = data;
    //         for(const ruleId in data.rules) {
    //             if(ruleId == 'questStep' && d.rules[ruleId] && d.rules[ruleId].value && typeof d.rules[ruleId].value == 'object') {
    //                 d.rules[ruleId].value = Object.keys(data.rules[ruleId].value)[0];
    //             }
    //         }
    //         return d;
    //     })
    //     .patch(route('dialogs.nodes.options.update', {
    //     dialogNodeOption: dialogRef.value.data?.option?.id,
    //     dialog: dialogRef.value.data.dialog_id,
    //     dialogNode: dialogRef.value.data?.option?.node_id,
    // }), {
    //     preserveState: true,
    //     onSuccess: () => {
    //         toast.add({ severity: 'success', summary: 'Udało się', detail: 'Opcja dialogowa została edytowana', life: 3000 });
    //         dialogRef.value.close({
    //             dialogOption: form.data(),
    //         });
    //     },
    //     onError: () => {
    //         toast.add({ severity: 'error', summary: 'Błąd', detail: 'Wystąpił problem podczas edycji opcji dialogowej', life: 3000 });
    //     }
    // })
}

const remove = () => {

    if(!dialogRef) return;

    processing.value = true;

    axios.delete(route('dialogs.nodes.options.destroy', {
        dialogNodeOption: dialogRef.value.data?.option?.id,
        dialog: dialogRef.value.data.dialog_id,
        dialogNode: dialogRef.value.data?.option?.node_id,
    }))
        .then(() => {
            toast.add({ severity: 'success', summary: 'Udało się', detail: 'Opcja dialogowa została usunięta', life: 3000 });
            dialogRef.value.close({
                remove: true,
            });
        })
        .catch(({response}) => {
            toast.add({ severity: 'error', summary: 'Błąd', detail: response.data.message, life: 6000 });
        })
        .finally(() => {
            processing.value = false;
        })

}


</script>

<template>
    <div class="flex flex-col gap-2" v-if="formLoaded">

        <InputGroup>
            <Button icon="pi pi-times" severity="danger" aria-label="Cancel" @click="form.additional_action = null" />
            <Select v-model="form.additional_action" :options="dialogNodeOptionAdditionalActionsList" optionLabel="label" option-value="value" placeholder="Wybierz dodatkową akcje" class="w-full md:w-56" />
        </InputGroup>

        <!-- TreeSelectAdapter for SET_QUEST_STEP action -->
        <div v-if="form.additional_action === 'SET_QUEST_STEP'" class="mt-2">
            <label class="block mb-2 font-medium">Wybierz krok questa:</label>
            <TreeSelectAdapter
                v-model="form.additional_action_data"
                :loading="loading"
                :options="questNodes"
                :onNodeExpand="onQuestNodeExpand"
            />
            <small class="text-gray-500 mt-1 block">Uwaga: Można wybrać tylko krok questa (s-X), nie cały quest (q-X).</small>
        </div>

        <Textarea v-model="form.label" rows="5" cols="50" maxlength="250" />

        <Message severity="error" size="small" variant="simple">{{ form.errors.label }}</Message>


        <Accordion value="0">
            <AccordionPanel value="0">
                <AccordionHeader>Reguły dostępu opcji dialogowej</AccordionHeader>
                <AccordionContent>
                    <EditRules v-model:rules="form.rules" />
                </AccordionContent>
            </AccordionPanel>
            <AccordionPanel value="1">
                <AccordionHeader>Reguły przejścia do kolejnych dialogów</AccordionHeader>
                <AccordionContent>

                    <Accordion value="1">
                        <AccordionPanel v-for="(edge, index) in form.edges" :value="edge.edge_id" >
                            <AccordionHeader>{{edge.node.content || edge.node.type }}</AccordionHeader>
                            <AccordionContent>
                                <EditRules v-model:rules="form.edges[index].rules" />
                            </AccordionContent>
                        </AccordionPanel>
                    </Accordion>

                </AccordionContent>
            </AccordionPanel>
        </Accordion>


        <div class="flex gap-1">
            <Button  :loading="processing"  fluid @click="remove" severity="danger">Usuń</Button>
            <Button :loading="processing" fluid @click="save">Zapisz</Button>
        </div>
    </div>
</template>

<style scoped>

</style>
