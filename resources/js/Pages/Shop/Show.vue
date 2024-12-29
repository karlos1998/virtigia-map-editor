<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import {useDialog} from "primevue/usedialog";
import AddShopItemDialog from "@/Pages/Shop/Components/AddShopItemDialog.vue";
import {route} from "ziggy-js";
import {router} from "@inertiajs/vue3";
import {ShopResource} from "@/Resources/Shop.resource";
import {BaseItemResource, BaseItemWithPosition} from "@/Resources/BaseItem.resource";
import Item from "@advance-table/Components/Item.vue";
import ItemHeader from "@/Components/ItemHeader.vue";
import {useToast} from "primevue";

const props = defineProps<{
    shop: ShopResource
    items: BaseItemWithPosition
}>()

const toast = useToast();

const primeDialog = useDialog();

const addItem = (event: MouseEvent) => {

    const rect = (event.target as HTMLElement).getBoundingClientRect();
    const offsetX = event.clientX - rect.left;
    const offsetY = event.clientY - rect.top;

    const relativeX = Math.floor(offsetX / 32) % 8;
    const relativeY = Math.floor(offsetY / 32);
    const position = relativeX + relativeY * 8;

    primeDialog.open(AddShopItemDialog, {
        props: {
            header: 'Dodaj przedmiot do sklepu'
        },
        onClose(options) {

            if (options.data?.item) {
                const baseItemId = options.data.item.id;
                router.post(route('shops.items.store', {
                    shop: props.shop.id,
                }), {
                    baseItemId,
                    position,
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
    });
};

</script>
<template>

    <AppLayout>

        <ItemHeader
            :route-back="route('shops.index')"
        >
            <template #header>
                #{{ shop.id }} - {{ shop.name }}
            </template>
        </ItemHeader>

        <div class="card">
            <div class="shop">
                <div class="items-area" @click="addItem">
                    <Item v-for="item in items" :item />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.shop {
    width:269px;
    height:435px;

    background-image: url(@/assets/images/shop-main.png);
}

.items-area {
    width:256px;
    height:320px;
    left:7px;
    top:7px;
    position: relative;
}
</style>
