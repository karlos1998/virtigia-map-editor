<script setup lang="ts">

import {BaseNpcWithLoots} from "../../../Resources/BaseNpc.resource";
import {router} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import AddShopItemDialog from "../../Shop/Components/AddShopItemDialog.vue";
import AddBaseNpcLootsDialog from "../Components/AddBaseNpcLootsDialog.vue";
import {useDialog} from "primevue/usedialog";
import {useToast} from "primevue";
import {BaseItemResource} from "../../../Resources/BaseItem.resource";
import {ref} from 'vue';

const { baseNpc } = defineProps<{
    baseNpc: BaseNpcWithLoots
}>()

const primeDialog = useDialog();
const toast = useToast();
const guaranteed = ref(baseNpc.guaranteed_loot ?? false);
const showAttachItemModal = () => {
    primeDialog.open(AddShopItemDialog, {
        props: {
            header: 'Dodaj przedmiot do zdobycia',
            modal: true,
        },
        onClose(options) {

            if (options.data?.item) {
                const baseItemId = options.data.item.id;
                router.post(route('base-npcs.loots.attach', {
                    baseNpc: baseNpc.id
                }), {
                    baseItemId,
                }, {
                    onError: (errors) =>  {
                        toast.add({
                            severity: 'error',
                            summary: 'Wystąpił bład',
                            detail: Object.values(errors)[0],
                            life: 5000,
                        })
                    }
                })
            }
        }
    })
}

const showAttachLootsFromBaseNpcModal = () => {
    primeDialog.open(AddBaseNpcLootsDialog, {
        props: {
            header: 'Dodaj looty z innego potwora',
            modal: true,
        },
        onClose(options) {
            if (options.data?.baseNpc) {
                const sourceBaseNpcId = options.data.baseNpc.id;
                router.post(route('base-npcs.loots.attach-from-base-npc', {
                    baseNpc: baseNpc.id
                }), {
                    sourceBaseNpcId,
                }, {
                    onError: (errors) =>  {
                        toast.add({
                            severity: 'error',
                            summary: 'Wystąpił bład',
                            detail: Object.values(errors)[0],
                            life: 5000,
                        })
                    }
                })
            }
        }
    })
}

const detachItem = (item: BaseItemResource) => {
    router.delete(route('base-npcs.loots.detach', {
        baseNpc: baseNpc.id,
        loot: item.id,
    }), {
        preserveScroll: true,
    });
}

const toggleGuaranteed = () => {
    router.patch(route('base-npcs.guaranteed.toggle', {baseNpc: baseNpc.id}), {
        guaranteed: guaranteed.value,
    }, {
        preserveState: true,
        onSuccess: () => {
            toast.add({severity: 'success', summary: 'Zapisano', detail: 'Ustawienie zapisane', life: 3000});
        },
        onError: (errors) => {
            toast.add({severity: 'error', summary: 'Błąd', detail: Object.values(errors)[0], life: 5000});
        }
    });
}
</script>
<template>
    <div class="flex gap-2 mb-2 items-center">
        <Button label="Dodaj przedmiot" @click="showAttachItemModal" />
        <Button label="Dodaj looty z innego potwora" @click="showAttachLootsFromBaseNpcModal" />
        <div class="ml-4 flex items-center gap-2">
            <label class="text-sm text-surface-600">Gwarantowany loot</label>
            <InputSwitch v-model="guaranteed" @change="toggleGuaranteed"/>
        </div>
    </div>


    <DataView data-key="id" :value="baseNpc.loots">
        <template #list="slotProps: {items: BaseItemResource[]}">
            <div class="flex flex-col">
                <div v-for="(item, index) in slotProps.items" :key="index">
                    <div class="flex flex-col sm:flex-row sm:items-center p-6 gap-4" :class="{ 'border-t border-surface-200 dark:border-surface-700': index !== 0 }">
                        <div class="md:w-40 relative">
                            <img
                                class="block xl:block mx-auto rounded"
                                :src="item.src" :alt="item.name"
                                v-tip.item.top.show-id="item"
                            />
                            <div class="absolute bg-black/70 rounded-border" style="left: 4px; top: 4px">
<!--                                <Tag :value="item.inventoryStatus" :severity="getSeverity(item)"></Tag>-->
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row justify-between md:items-center flex-1 gap-6">
                            <div class="flex flex-row md:flex-col justify-between items-start gap-2">
                                <div>
<!--                                    <span class="font-medium text-surface-500 dark:text-surface-400 text-sm">{{ item.category }}</span>-->
                                    <div class="text-lg font-medium mt-2">{{ item.name }}</div>
                                </div>
                                <div  v-if="item.rarity != 'common'" class="bg-surface-100 p-1" style="border-radius: 30px">
                                    <div class="bg-surface-0 flex items-center gap-2 justify-center py-1 px-2" style="border-radius: 30px; box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.04), 0px 1px 2px 0px rgba(0, 0, 0, 0.06)">
                                        <span  class="text-surface-900 font-medium text-sm">{{ item.rarity }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col md:items-end gap-8">
<!--                                <span class="text-xl font-semibold">${{ item.price }}</span>-->
                                <div class="flex flex-row-reverse md:flex-row gap-2">
                                    <Button severity="danger" icon="pi pi-times" outlined @click="detachItem(item)" />
<!--                                    <Button icon="pi pi-shopping-cart" label="Buy Now" :disabled="item.inventoryStatus === 'OUTOFSTOCK'" class="flex-auto md:flex-initial whitespace-nowrap"></Button>-->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </DataView>

</template>
<style scoped>

</style>
