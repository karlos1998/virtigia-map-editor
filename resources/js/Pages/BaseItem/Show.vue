<script setup lang="ts">

import {BaseItemResource, BaseItemWithRelations} from "../../Resources/BaseItem.resource";
import AppLayout from "../../layout/AppLayout.vue";
import {route} from "ziggy-js";
import ItemHeader from "../../Components/ItemHeader.vue";

import { itemTip } from "../../old-createItemTip";
import rockAdapter from "../../RockTip/components/rockAdapter.vue";
import Item from "../../karlos3098-LaravelPrimevueTable/Components/Item.vue";
import BaseNpcsUsedItemTable from "./Partials/BaseNpcsUsedItemTable.vue";
import ShopsUsedItemTable from "./Partials/ShopsUsedItemTable.vue";
import {ref} from "vue";

const { baseItem } = defineProps<{
    baseItem: BaseItemWithRelations,
}>();

</script>

<template>
<!--    <div class="card">-->
<!--        <pre>{{baseItem}}</pre>-->
<!--    </div>-->

    <AppLayout>

        <ItemHeader
            :route-back="route('base-items.index')"
        >
            <template #header>
                #{{ baseItem.id }} - {{ baseItem.name }}

                <!--                <img alt="" :src="`https://s3.letscode.it/virtigia-assets/img/${baseItem.src}`" />-->


            </template>
        </ItemHeader>

        <div class="card">
            <div class="mb-4"><b>Nowe statystyki przedmiotu:</b></div>

            <img
                class="h-12 w-12 object-cover"
                :src="baseItem.src"
                v-tip.item.top.show-id="baseItem"
                alt=""
            />


        </div>


        <div class="card" >
            <Panel header="Debug JSON" toggleable collapsed>
                <pre>{{baseItem}}</pre>
            </Panel>
        </div>

        <div class="card" >

            <div class="mb-4"><b>Oryginalne statystyki przedmiotu z margonem:</b></div>
            <div v-html="itemTip({ ...baseItem, stat: baseItem.stats })" />

            <div class="mt-4">Grafika: {{ `${baseItem.src}` }}</div>
        </div>


        <div class="card" >
            <BaseNpcsUsedItemTable :base-npcs="baseItem.baseNpcs" />
        </div>

        <div class="card" >
            <ShopsUsedItemTable :shops="baseItem.shops" />
        </div>

    </AppLayout>

</template>

<style>

</style>
