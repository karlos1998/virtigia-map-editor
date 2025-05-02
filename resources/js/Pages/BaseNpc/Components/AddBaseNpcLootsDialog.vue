<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {Ref, ref} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import axios from "axios";
import {route} from "ziggy-js";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import RockAdapter from "../../../RockTip/components/rockAdapter.vue";

const dialogRef = inject<Ref<DynamicDialogInstance> | null>('dialogRef');

const save = () => {
    dialogRef.value.close({
        baseNpc: selectedBaseNpc.value,
    });
}

const search = async (query: string) => {
    const { data } = await axios.get(route('base-npcs.search', {query}))
    console.log(data);
    return data;
}

const selectedBaseNpc = ref<BaseNpcResource|null>();

const filteredBaseNpcs = ref<BaseNpcResource[]>([]);

const filterBaseNpcs = async ({ query }: { query: string }) => {
    filteredBaseNpcs.value = await search(query);
};

</script>

<template>
    <div class="flex flex-col gap-2">

        <AutoComplete
            class="w-full p-0"
            v-model="selectedBaseNpc"
            placeholder="Wyszukaj potwora"
            :suggestions="filteredBaseNpcs"
            @complete="filterBaseNpcs"
            :option-label="(baseNpc: BaseNpcResource|null) => `${baseNpc?.name}`"
            fluid
        >
            <template #option="slotProps">
                <div class="name-item flex items-center justify-between">

                    <div class="flex items-center space-x-4">
                        <img
                            class="h-12 w-12 object-cover"
                            :src="slotProps.option.src"
                            alt="Option Image"
                            v-tip.npc.top.show-id="slotProps.option"
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

        <Button fluid @click="save">Dodaj looty z tego potwora</Button>
    </div>
</template>

<style scoped>

</style>
