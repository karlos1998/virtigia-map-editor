<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {Ref, ref} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import {MapResource} from "@/Resources/Map.resource";
import {BaseItemResource} from "@/Resources/BaseItem.resource";
import axios from "axios";
import {route} from "ziggy-js";
import {useForm} from "@inertiajs/vue3";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import {NpcResource} from "@/Resources/Npc.resource";

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        x: number
        y: number
        map: MapResource,
        lastSelectedNpc?: NpcResource,
    }
}> | null>('dialogRef');

const confirm = () => {
    form
        .transform((data) => {
            return {
                npc: data.npc?.id,
                location: {
                    mapId: dialogRef.value.data.map.id,
                    x: dialogRef.value.data.x,
                    y: dialogRef.value.data.y,
                }
            }
        })
        .post(route('npcs.store'), {
            onSuccess: () => {
                dialogRef.value.close({
                    remove: true,
                    npc: form.npc,
                });
            }
        })
}

const form = useForm<{
    npc: BaseNpcResource|null|string
    location: any,
}>({
    npc: dialogRef.value.data.lastSelectedNpc,
    location: undefined,
})


const filteredNpcs = ref<BaseNpcResource[]>([]);

const search = async  (query: string) => {
    const { data } = await axios.get(route('base-npcs.search', {query}))
    console.log(data);
    return data;
}
const filterNpcs = async ({ query }: { query: string }) => {
    filteredNpcs.value = await search(query);
};
</script>

<template>
    <div class="flex flex-col gap-2">

        <AutoComplete
            :disabled="form.processing"
            class="w-full p-0"
            v-model="form.npc"
            placeholder="Wyszukaj NPC"
            :suggestions="filteredNpcs"
            @complete="filterNpcs"
            :option-label="(contact: BaseItemResource|null) => `${contact?.name}`"
            fluid
        >
            <template #option="slotProps">
                <div class="name-item flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <img
                            class="w-12 object-cover"
                            v-tooltip="slotProps.option.src"
                            :src="'https://virtigia-assets.letscode.it/img/npc/' + slotProps.option.src"
                            alt="Option Image"
                        />
                        <div class="text-center">
                            <span class="font-semibold text-gray-800">
                                [{{ slotProps.option.id }}] {{ slotProps.option.name }}
                            </span>
                        </div>
                    </div>
                </div>

            </template>
        </AutoComplete>

        <div class="font-bold my-6">
            <span>{{dialogRef.data.map.name}}</span>
            <span class="ml-2">({{dialogRef.data.x}},{{dialogRef.data.y}})</span>
        </div>

        <div v-if="form.npc && typeof form.npc == 'object'">
            <img
                class="w-12 object-cover"
                :src="'https://virtigia-assets.letscode.it/img/npc/' + form.npc.src"
                alt="Option Image"
            />
            <div class="font-bold">{{ form.npc.name }}</div>
        </div>

        <div class="text-red-500 font-bold">{{ form.errors.npc }}</div>
        <div class="text-red-500 font-bold">{{ form.errors.location }}</div>

        <div class="flex gap-1">
            <Button :loading="form.processing" fluid @click="confirm">Dodaj</Button>
        </div>
    </div>
</template>

<style scoped>

</style>
