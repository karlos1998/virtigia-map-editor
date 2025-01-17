<script setup lang="ts">
import {route} from "ziggy-js";
import DetailsCardList from "../../../Components/DetailsCardList.vue";
import DetailsCardListItem from "../../../Components/DetailsCardListItem.vue";
import {NpcResource, NpcWithDetails} from "../../../Resources/Npc.resource";
import { Link } from '@inertiajs/vue3';
import {ref} from "vue";
import SelectDialogModal from "../Components/SelectDialogModal.vue";
import {BaseNpcResource} from "../../../Resources/BaseNpc.resource";


defineProps<{
    npc: NpcWithDetails
    baseNpc: BaseNpcResource
}>()

const isSelectDialogModalVisible = ref(false);

</script>

<template>

    <SelectDialogModal v-model:visible="isSelectDialogModalVisible" :npc />

    <DetailsCardList title="Opcje zaawansowane" >
        <DetailsCardListItem label="Dialog">
            <template #value>
                <div v-if="npc.dialog" class="flex items-center ">
                    <div class="w-1/2 md:w-1/3">
                        <Tag :value="`Posiada: ${npc.dialog.name}`" />
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
                    <div class="flex-grow">
                        <Button label="Edytuj" size="small" severity="warn" @click="isSelectDialogModalVisible = true" />
                    </div>
                </div>
                <div v-else>
                    <Button label="Edytuj" size="small" severity="warn" @click="isSelectDialogModalVisible = true" />
                </div>
                <Message v-if="baseNpc.category != 'NPC'" class="mt-1" severity="error">Uwaga! Dialog nigdy nie zostanie uruchomiony, ponieważ bazowy Npc nie jest zwykłym NPC. </Message>
            </template>
        </DetailsCardListItem>
    </DetailsCardList>
</template>

<style scoped>

</style>
