<script setup lang="ts">
import {inject, onMounted, Ref, ref, watch} from "vue";
import {debounce} from "chart.js/helpers";
import axios from "axios";
import {route} from "ziggy-js";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {DialogResource} from "../../../Resources/Dialog.resource";
import {useForm} from "@inertiajs/vue3";
import {NpcResource, NpcWithDetails} from "../../../Resources/Npc.resource";

const visible = defineModel<boolean>('visible');

const { npc } = defineProps<{
    npc: NpcWithDetails
}>()

const dropdownDialogs = ref<DialogResource[]>([]);

const searchOptions = debounce((event) => {
    axios.get(route('dialogs.search', { query: event[0].query }))
        .then(response => {
            dropdownDialogs.value = response.data;
        });
}, 100);

const form = useForm<{
    dialog?: DialogResource,
}>({
    dialog: npc.dialog,
})

const save = (remove: boolean) => {

    console.log('')

    form
        .transform((data) => ({
            dialog: remove ? null : data.dialog?.id
        }))
        .patch(route('npcs.update', {
        npc: npc.id,
    }), {
            onSuccess: () => {
                visible.value = false;
            }
        })
}

const cancel = () => {
    visible.value = false;
}

const remove = () => {
    save(true);
}

</script>
<template>
    <Dialog v-model:visible="visible" modal header="Wybierz dialog">
        <div class="font-bold text-lg flex flex-row gap-1 w-full">
            <AutoComplete v-model="form.dialog" :suggestions="dropdownDialogs" optionLabel="name"
                          placeholder="Szukaj dialogu" class="w-full" @complete="searchOptions"
            >
                <template #option="slotProps">
                    <div class="flex align-items-center">
                        <div>{{ slotProps.option.name }}</div>
                    </div>
                </template>
            </AutoComplete>
        </div>
        <Message severity="error" size="small" variant="simple">{{ form.errors.dialog }}</Message>

        <div class="flex justify-end gap-2 mt-6">
            <Button type="button" label="Anuluj" severity="secondary" @click="cancel" />
            <Button type="button" label="Odłącz" severity="warn" @click="remove" />
            <Button :loading="form.processing" type="button" label="Zapisz" @click="save(false)" />
        </div>
    </Dialog>
</template>

<style scoped>

</style>
