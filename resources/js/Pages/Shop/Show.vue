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
import {useConfirm, useToast} from "primevue";
import {ref} from "vue";
import RockAdapter from "../../RockTip/components/rockAdapter.vue";

const props = defineProps<{
    shop: ShopResource
    items: BaseItemWithPosition
}>()

const toast = useToast();

const primeDialog = useDialog();
const addItemDialogInstance = ref();
const addItem = (event: MouseEvent) => {

    if ((event.target as HTMLElement).closest('.item')) {
        return;
    }

    const rect = (event.target as HTMLElement).getBoundingClientRect();
    const offsetX = event.clientX - rect.left;
    const offsetY = event.clientY - rect.top;

    const relativeX = Math.floor(offsetX / 32) % 8;
    const relativeY = Math.floor(offsetY / 32);
    const position = relativeX + relativeY * 8;

    if(addItemDialogInstance.value?.visible) {
        return;
    }

    addItemDialogInstance.value = primeDialog.open(AddShopItemDialog, {
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



const confirm = useConfirm();
const deleteItem = (event, position: number) => {
    confirm.require({
        target: event.currentTarget,
        message: 'Usunąć ten przedmiot ze sklepu?',
        icon: 'pi pi-info-circle',
        rejectProps: {
            label: 'Anuluj',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Usuń',
            severity: 'danger'
        },
        accept: () => {

            router.delete(route('shops.items.destroy', {
                shop: props.shop.id,
                position,
            }), {
                onError: (errors) =>  {
                    toast.add({
                        severity: 'error',
                        summary: 'Wystąpił bład',
                        detail: Object.values(errors)[0],
                        life: 5000,
                    })
                },
                onSuccess: () => {
                    toast.add({ severity: 'info', summary: 'Sukces', detail: 'Przedmiot usunięty ze sklepu', life: 3000 });
                }
            })
        },
        reject: () => {
            // toast.add({ severity: 'error', summary: 'Błąd', detail: 'Unknown error', life: 3000 });
        }
    });
};

</script>
<template>

    <AppLayout>

        <ConfirmPopup />

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
                    <Item :title="`[${item.id}] ${item.name}`" @click="deleteItem($event, item.position)" v-for="item in items" :item />

<!--                    <rockAdapter-->
<!--                        v-for="item in items"-->
<!--                        :item-payload="{-->
<!--                schema: {-->
<!--                    position: {-->
<!--                        x: (item.position % 8) * (32 + 5),-->
<!--                        y: Math.floor((item.position) / 8) * (32 + 5),-->
<!--                    },-->
<!--                    inner: {-->
<!--                        ...item,-->
<!--                        src: `https://s3.letscode.it/virtigia-assets/img/${item.src}` + item.src,-->
<!--                    },-->
<!--                    hero: {-->
<!--                        profession: 'w',-->
<!--                        level: 100,-->
<!--                    }-->
<!--                }-->
<!--            }" direction="bottom"/>-->

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
