<script setup lang="ts">
import {PureNpcWithOnlyLocationsResource} from "@/Resources/Npc.resource";

defineProps<{
    npc: PureNpcWithOnlyLocationsResource
}>()
</script>


<template>
    <span class="text-lg" v-if="npc.locations.length > 0">
                    <span>{{ npc.locations[0].map_name }}
                        ({{npc.locations[0].x}},{{npc.locations[0].y}})</span>
                    <Tag
                        v-if="npc.locations.length > 1"
                        :value="'+ ' + (npc.locations.length - 1) + ' dodatkowych'"
                        severity="info"
                        v-tooltip="'Dodatkowe respy tego konkretnego moba. Po jego zabiciu może pojawić się w kilku lokalizacjach.'"
                    />
                </span>
    <Tag
        v-else
        value="Wykryto problem! Ten mob nie ma respów!"
        severity="danger"
    />
</template>
