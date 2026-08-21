<script setup lang="ts">
import {route} from "ziggy-js";
import DetailsCardList from "../../../Components/DetailsCardList.vue";
import DetailsCardListItem from "../../../Components/DetailsCardListItem.vue";
import {NpcResource, NpcWithDetails} from "../../../Resources/Npc.resource";
import {Link, router} from '@inertiajs/vue3';
import {ref} from "vue";
import SelectDialogModal from "../Components/SelectDialogModal.vue";
import {BaseNpcResource} from "../../../Resources/BaseNpc.resource";
import InputSwitch from "primevue/inputswitch";
import InputNumber from "primevue/inputnumber";


const props = defineProps<{
    npc: NpcWithDetails
    baseNpc: BaseNpcResource
}>()

const isSelectDialogModalVisible = ref(false);
const lastSavedAutoStartDialogRange = ref(props.npc.auto_start_dialog_range);

const updateAutoStartDialog = (enabled: boolean) => {
    router.patch(route('npcs.update', {npc: props.npc.id}), {
        auto_start_dialog: enabled,
    }, {
        preserveScroll: true,
        onError: () => {
            props.npc.auto_start_dialog = !enabled;
        },
    });
};

const updateAutoStartDialogRange = () => {
    const range = props.npc.auto_start_dialog_range;
    if (!Number.isInteger(range) || range < 1 || range > 10 || range === lastSavedAutoStartDialogRange.value) {
        props.npc.auto_start_dialog_range = lastSavedAutoStartDialogRange.value;
        return;
    }

    router.patch(route('npcs.update', {npc: props.npc.id}), {
        auto_start_dialog_range: range,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            lastSavedAutoStartDialogRange.value = range;
        },
        onError: () => {
            props.npc.auto_start_dialog_range = lastSavedAutoStartDialogRange.value;
        },
    });
};

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
        <DetailsCardListItem label="Automatyczne rozpoczęcie dialogu">
            <template #value>
                <div class="flex flex-col gap-2">
                    <InputSwitch
                        v-model="npc.auto_start_dialog"
                        @update:model-value="updateAutoStartDialog"
                    />
                    <div class="flex items-center gap-2">
                        <label :for="`auto-start-dialog-range-${npc.id}`">Zasięg w kratkach</label>
                        <InputNumber
                            :input-id="`auto-start-dialog-range-${npc.id}`"
                            v-model="npc.auto_start_dialog_range"
                            :min="1"
                            :max="10"
                            show-buttons
                            @blur="updateAutoStartDialogRange"
                        />
                    </div>
                    <Message v-if="!npc.dialog" severity="warn" size="small">
                        Ustawienie zadziała dopiero po przypięciu dialogu.
                    </Message>
                    <Message v-if="baseNpc.category !== 'NPC'" severity="warn" size="small">
                        Automatyczny dialog działa wyłącznie dla bazowego typu NPC.
                    </Message>
                </div>
            </template>
        </DetailsCardListItem>
    </DetailsCardList>
</template>

<style scoped>

</style>
