<script setup lang="ts">

import {BaseNpcWithLoots} from "../../../Resources/BaseNpc.resource";
import {router} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import AddShopItemDialog from "../../Shop/Components/AddShopItemDialog.vue";
import {useDialog} from "primevue/usedialog";
import {useToast} from "primevue";

const { baseNpc } = defineProps<{
    baseNpc: BaseNpcWithLoots
}>()

const primeDialog = useDialog();
const toast = useToast();
const showAddItemModal = () => {
    primeDialog.open(AddShopItemDialog, {
        props: {
            header: 'Dodaj przedmiot do zdobycia',
            modal: true,
        },
        onClose(options) {

            if (options.data?.item) {
                const baseItemId = options.data.item.id;
                router.post(route('base-npcs.loots.store', {
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
</script>
<template>
        <Button label="Dodaj przedmiot" @click="showAddItemModal" />
    {{baseNpc.loots}}
</template>
<style scoped>

</style>
