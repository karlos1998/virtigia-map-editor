<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {Ref, ref} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";
import axios from "axios";
import {route} from "ziggy-js";
import {BaseItemResource} from "@/Resources/BaseItem.resource";
import RockAdapter from "../../../RockTip/components/rockAdapter.vue";

const dialogRef = inject<Ref<DynamicDialogInstance> | null>('dialogRef');

const save = () => {
    dialogRef.value.close({
        item: selectedItem.value,
    });
}

const search = async  (query: string) => {
    const { data } = await axios.get(route('base-items.search', {query}))
    console.log(data);
    return data;
}

const selectedItem = ref<BaseItemResource|null>();

const filteredItems = ref<BaseItemResource[]>([]);

const filterItems = async ({ query }: { query: string }) => {
    filteredItems.value = await search(query);
};

</script>

<template>
    <div class="flex flex-col gap-2">

        <AutoComplete
            class="w-full p-0"
            v-model="selectedItem"
            placeholder="Wyszukaj przedmiot"
            :suggestions="filteredItems"
            @complete="filterItems"
            :option-label="(contact: BaseItemResource|null) => `${contact?.name}`"
            fluid
        >
            <template #option="slotProps">
                <div class="name-item flex items-center justify-between">

                    <div class="flex items-center space-x-4">
<!--                        <div style="width:32px; height: 32px;">-->
<!--                            <rockAdapter-->
<!--                                :item-payload="{-->
<!--                                    schema: {-->
<!--                                        position: {-->
<!--                                            x: 2,-->
<!--                                            y: 2,-->
<!--                                        },-->
<!--                                        inner: {-->
<!--                                            ...slotProps.option,-->
<!--                                            src: `https://s3.letscode.it/virtigia-assets/img/${slotProps.option.src}`,-->
<!--                                        },-->
<!--                                        hero: {-->
<!--                                            profession: 'w',-->
<!--                                            level: 100,-->
<!--                                        }-->
<!--                                    }-->
<!--                                }"-->
<!--                                direction="bottom"-->
<!--                            />-->
<!--                        </div>-->
                        <img
                            class="h-12 w-12 object-cover"
                            :src="'https://s3.letscode.it/virtigia-assets/img/' + slotProps.option.src"
                            alt="Option Image"
                            v-tip.item.top.show-id="slotProps.option"
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

        <Button fluid @click="save">Dodaj</Button>
    </div>
</template>

<style scoped>

</style>
