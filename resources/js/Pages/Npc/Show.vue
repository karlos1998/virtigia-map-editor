<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import {BaseNpcResource} from "@/Resources/BaseNpc.resource";
import {NpcResource, NpcWithDetails} from "@/Resources/Npc.resource";
import {route} from "ziggy-js";
import {router} from "@inertiajs/vue3";
import ItemHeader from "@/Components/ItemHeader.vue";
import DetailsCardList from "@/Components/DetailsCardList.vue";
import DetailsCardListItem from "@/Components/DetailsCardListItem.vue";
import { Link } from '@inertiajs/vue3';

defineProps<{
    baseNpc: BaseNpcResource
    npc: NpcWithDetails
}>()

</script>
<template>
    <AppLayout>

        <ItemHeader
            :route-back="route('npcs.index')"
        >
            <template #header>
                <img v-tooltip="baseNpc.src" :src="'https://s3.letscode.it/virtigia-assets/img/npc/' + baseNpc.src"  alt=""/>
                #{{ npc.id }} - {{ npc.name }}
            </template>
        </ItemHeader>

        <Message class="mb-8">
            <div>Przeglądasz konkretnego NPC umieszczonego w konkretnym miescu na mapie. Jeśli widzisz kilka miejsc występowania znaczy, że po zabiciu może odrodzić się w jednej z tych lokalizacji, ale to wciąż ten sam NPC którego można spotkać tylko w jednym miejscu na raz.</div>
        </Message>

        <DetailsCardList title="Informacje Podstawowe" >
            <DetailsCardListItem label="Nazwa" :value="npc.name" />
            <DetailsCardListItem label="Link do grafiki" :value="'https://s3.letscode.it/virtigia-assets/img/npc/' + baseNpc.src" />
            <DetailsCardListItem label="Lvl" :value="npc.lvl" />
            <DetailsCardListItem label="Type" :value="npc.type" />
            <DetailsCardListItem label="W Grupie" :value="npc.grp" />
            <DetailsCardListItem label="Bazowy NPC">
                <template #value>
                    <Link :href="route('base-npcs.show', baseNpc.id)">
                        <Button label="Przejdź" size="small" severity="info" />
                    </Link>
                </template>
            </DetailsCardListItem>
        </DetailsCardList>

        <DetailsCardList title="Opcje zaawansowane" >
            <DetailsCardListItem label="Dialog">
                <template v-if="npc.dialog" #value>
                    <div class="flex items-center ">
                        <div class="w-1/2 md:w-1/3">
                            <Tag value="Posiada" />
                            <Tag
                                v-if="npc.dialog.npcs_count > 1"
                                class="ml-2"
                                :value="npc.dialog.npcs_count - 1"
                                v-tooltip="`Z tego dialogu korzysta jeszcze ${npc.dialog.npcs_count - 1} npc. `"
                                severity="info"
                            />
                        </div>
                        <div class="flex-grow">
                            <Link
                                :href="route('dialogs.show', npc.dialog.id)"
                                >
                                <Button label="Podgląd" size="small" />
                            </Link>
                        </div>
                    </div>
                </template>
            </DetailsCardListItem>
        </DetailsCardList>

        <DetailsCardList title="Miejsce/a wystąpienia" >
            <DetailsCardListItem
                v-for="location in npc.locations"
                :value="`(${location.x}, ${location.y})`"
            >
                <template #label>
                    <Link :href="route('maps.show', location.map_id)">
                        <Button label="Pokaż mapę" size="small" />
                    </Link>
                    <span class="ml-4 font-bold">{{location.map_name}}</span>
                </template>
            </DetailsCardListItem>
        </DetailsCardList>
    </AppLayout>
</template>

<style scoped>

</style>
