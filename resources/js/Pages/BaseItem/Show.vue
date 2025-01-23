<script setup lang="ts">

import {BaseItemResource} from "../../Resources/BaseItem.resource";
import ItemParser from "./ItemParser.vue";
import AppLayout from "../../layout/AppLayout.vue";
import {route} from "ziggy-js";
import ItemHeader from "../../Components/ItemHeader.vue";

const { baseItem } = defineProps<{
    baseItem: BaseItemResource,
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
            </template>
        </ItemHeader>

        <div class="card">
            <ItemParser
                :config="{
                    legendaryBonusThick: true,
                    separateDescription: true,
                    colorizeAttributes: true,
                    previewRightCorner: true,
                    previewUnderName: true,
                    previewShow: true
                }"
                :from-string="JSON.stringify({
                    inner: baseItem,
                    hero: {
                        lvl: 100,
                        profession: 'b',
                    }
                })"
            />
        </div>

        <div class="card">
            test
        </div>
    </AppLayout>

</template>

<style>

</style>
