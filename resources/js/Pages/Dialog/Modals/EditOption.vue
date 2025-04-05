<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {computed, Ref, ref} from "vue";

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

const dialogNodeOptionAdditionalActionsList = ref(usePage<{dialogNodeOptionAdditionalActionsList: DropdownListType}>().props.dialogNodeOptionAdditionalActionsList)
const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        option: DialogOptionResource,
        parent: string,
        dialog_id: number
    }
}> | null>('dialogRef');

const form = useForm<{
    label: string
    additional_action: any// todo
    rules: DialogNodeRulesResource
    edges: DialogNodeOptionEdgeWithRules[],
}>({
    label: '',
    additional_action: null,
    rules: {},
    edges: [],
})

const toast = useToast();

const formLoaded = ref(false);

onMounted(() => {
    if(!dialogRef) return;

    form.label = dialogRef.value.data.option.label ?? '';
    form.additional_action = dialogRef.value.data.option.additional_action ?? '';
    form.rules = dialogRef.value.data.option.rules ?? {};

    form.edges = dialogRef.value.data.option.edges.map(edge => ({
        ...edge,
        rules: edge.rules || {}
    })) || [];

    formLoaded.value = true

})

const processing = ref(false);

const save = () => {
    //axios zeby form inertia nie przeladowywal strony

    if(!dialogRef) return;

    processing.value = true;

    const data = form.data();

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
