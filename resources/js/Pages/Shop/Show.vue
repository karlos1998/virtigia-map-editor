<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import {useDialog} from "primevue/usedialog";
import AddShopItemDialog from "@/Pages/Shop/Components/AddShopItemDialog.vue";
import {route} from "ziggy-js";
import {router} from "@inertiajs/vue3";
import {ShopResource} from "@/Resources/Shop.resource";
import {BaseItemResource, BaseItemWithPosition} from "@/Resources/BaseItem.resource";
import ItemHeader from "@/Components/ItemHeader.vue";
import {useConfirm, useToast} from "primevue";
import {ref} from "vue";
import RockAdapter from "../../RockTip/components/rockAdapter.vue";
import RockTip from "../../RockTip/components/rockTip.vue";

// @ts-ignore
import { itemTip } from "../../old-createItemTip";
import Item from "@/Components/Item.vue";
import InputNumber from "primevue/inputnumber";
import Button from "primevue/button";
import AutoComplete from "primevue/autocomplete";
import axios from "axios";

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


const bindsItemsPermanently = ref(props.shop.binds_items_permanently);

const toggleBindsItemsPermanently = () => {
    router.post(route('shops.toggle-binds-items-permanently', {
        shop: props.shop.id,
    }), {}, {
        preserveScroll: true,
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Wystąpił bład',
                detail: Object.values(errors)[0],
                life: 5000,
            });
        },
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Sukces',
                detail: 'Ustawienie zostało zmienione',
                life: 3000
            });
        }
    });
};

const test = ref(false);

const buyPricePercent = ref(props.shop.buy_price_percent);
const sellPricePercent = ref(props.shop.sell_price_percent);
const maxBuyPrice = ref(props.shop.max_buy_price);
const currencyItemSelected = ref<BaseItemResource | null>(props.shop.currency_item ?? null);
const currencySuggestions = ref<BaseItemResource[]>([]);
const filterCurrencyItems = async ({query}: { query: string }) => {
    const {data} = await axios.get(route('base-items.search'), {params: {query}});
    currencySuggestions.value = data;
};
const clearCurrencyItem = () => {
    currencyItemSelected.value = null;

};

const savePriceSettings = () => {
    router.patch(route('shops.update', props.shop.id), {
        buy_price_percent: buyPricePercent.value,
        sell_price_percent: sellPricePercent.value,
        max_buy_price: maxBuyPrice.value,
        currency_item_id: currencyItemSelected.value ? currencyItemSelected.value.id : null
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Zapisano ustawienia cen',
                detail: '',
                life: 3000
            });
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Wystąpił błąd',
                detail: Object.values(errors)[0],
                life: 5000,
            });
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

        <div class="card mb-3">
            <div class="flex align-items-center">
                <label for="binds-items-permanently" class="mr-2">Wiąże itemy na stałe po kupieniu:</label>
                <InputSwitch
                    id="binds-items-permanently"
                    v-model="bindsItemsPermanently"
                    @change="toggleBindsItemsPermanently"
                />
            </div>
        </div>

        TEST: {{shop.currency_item_id}}

        <!-- card for shop price settings -->
        <div class="card mb-3">
            <h3 class="mb-2">Ustawienia cen sklepu</h3>
            <div class="formgrid grid gap-3 mb-3">
                <div class="field col-12 md:col-4">
                    <label for="buy-price-percent">% ceny skupu (0-100)</label>
                    <InputNumber id="buy-price-percent" v-model="buyPricePercent" :min="0" :max="100"/>
                </div>
                <div class="field col-12 md:col-4">
                    <label for="sell-price-percent">% ceny sprzedaży (100-1000)</label>
                    <InputNumber id="sell-price-percent" v-model="sellPricePercent" :min="100" :max="1000"/>
                </div>
                <div class="field col-12 md:col-4">
                    <label for="max-buy-price">Max cena skupu za przedmiot</label>
                    <InputNumber id="max-buy-price" v-model="maxBuyPrice" :min="0" :max="1000000"/>
                </div>
                <div class="field col-12 md:col-4">
                    <label for="currency-item">Przedmiot jako waluta (opcjonalnie)</label>
                    <AutoComplete
                        v-model="currencyItemSelected"
                        :suggestions="currencySuggestions"
                        @complete="filterCurrencyItems"
                        placeholder="Szukaj przedmiotu..."
                        class="w-full"
                        :option-label="(option: BaseItemResource|null) => option ? `[${option.id}] ${option.name}` : ''"
                        :dropdown="true"
                    >
                        <template #option="{option}">
                            <div class="name-item flex items-center gap-2 p-1">
                                <img :src="option.src" class="h-8 w-8 object-cover" v-tip.item.top.show-id="option"
                                     :alt="option.name"/>
                                <span class="font-semibold text-gray-800">[{{ option.id }}] {{ option.name }}</span>
                            </div>
                        </template>
                        <template #value="{value}">
                            <div v-if="value" class="flex items-center gap-2">
                                <img :src="value.src" class="h-8 w-8 object-cover" v-tip.item.top.show-id="value"
                                     :alt="value.name"/>
                                <span class="font-semibold text-gray-800">[{{ value.id }}] {{ value.name }}</span>
                            </div>
                        </template>
                    </AutoComplete>
                    <Button text class="ml-2" severity="secondary" @click="clearCurrencyItem"
                            v-if="currencyItemSelected">Wyczyść
                    </Button>
                </div>
            </div>
            <Button label="Zapisz" @click="savePriceSettings" icon="pi pi-save" class="p-button-success"/>
        </div>

        <div class="card">

<!--            <div class="mb-8">-->
<!--                <rockAdapter-->
<!--                    :html-payload="{-->
<!--                        schema: {-->
<!--                            inner: 'Test'-->
<!--                        }-->
<!--                    }"-->
<!--                    direction="bottom"-->
<!--                >-->
<!--                    <div>HTML</div>-->
<!--                </rockAdapter>-->

<!--                <rockAdapter-->
<!--                    :npc-payload="{-->
<!--                        schema: {-->
<!--                            inner: {-->
<!--                                level: 12,-->
<!--                                rank: 'ELITE',-->
<!--                                name: 'test',-->
<!--                            },-->
<!--                            hero: {-->
<!--                                level: 1-->
<!--                            },-->
<!--                        }-->
<!--                    }"-->
<!--                    direction="bottom"-->
<!--                >-->
<!--                    <div>NPC</div>-->
<!--                </rockAdapter>-->
<!--            </div>-->
            <div class="shop">
                <div
                    class="items-area"
                    @click="addItem"
                >
                    <Item
                        v-if="test"
                        :title="`[${item.id}] ${item.name}`"
                        @click="deleteItem($event, item.position)"
                        v-for="item in items"
                        :item
                        v-tip="itemTip({ ...item, stat: item.stats })"
                    />
                    <Item
                        v-else
                        :title="`[${item.id}] ${item.name}`"
                        @click="deleteItem($event, item.position)"
                        v-for="item in items"
                        :item
                        v-tip.item.top.show-id="item"
                    />
<!--                    <div-->
<!--                        v-else-->
<!--                        v-for="item in items"-->
<!--                        @click="deleteItem($event, item.position)"-->
<!--                        class="item-box"-->
<!--                        :style="{-->
<!--                            'top': `${Math.floor((item.position) / 8) * (32)}px`,-->
<!--                            'left': `${(item.position % 8) * (32)}px`,-->
<!--                        }"-->
<!--                    >-->
<!--                        <rockAdapter-->
<!--                            :item-payload="{-->
<!--                                schema: {-->
<!--                                    position: {-->
<!--                                        x: 2,-->
<!--                                        y: 2,-->
<!--                                    },-->
<!--                                    inner: {-->
<!--                                        ...item,-->
<!--                                        src: `https://s3.letscode.it/virtigia-assets/img/${item.src}`,-->
<!--                                    },-->
<!--                                    hero: {-->
<!--                                        profession: 'w',-->
<!--                                        level: 100,-->
<!--                                    },-->
<!--                                    showId: true,-->
<!--                                }-->
<!--                            }"-->
<!--                            direction="bottom"-->
<!--                        />-->
<!--                    </div>-->

                </div>
            </div>
        </div>

        <!-- Dialogs związane ze sklepem -->
        <div class="card my-4 p-4">
            <h3 class="font-bold mb-2">Dialogi powiązane ze sklepem</h3>
            <div v-if="props.shop.dialogs.length">
                <ul>
                    <li v-for="dialog in props.shop.dialogs" :key="dialog.id" class="flex items-center gap-3 mb-2">
                        <span class="font-semibold">#{{ dialog.id }} {{ dialog.name }}</span>
                        <Button @click="router.get(route('dialogs.show', {dialog: dialog.id}))" size="small" text
                                icon="pi pi-arrow-right" label="Przejdź" class="ml-2"/>
                    </li>
                </ul>
            </div>
            <div v-else class="text-gray-500">Brak powiązanych dialogów.</div>
        </div>
        <!-- NPC związane ze sklepem -->
        <div class="card my-4 p-4">
            <h3 class="font-bold mb-2">NPC powiązane ze sklepem</h3>
            <div v-if="props.shop.npcs.length">
                <ul>
                    <li v-for="npc in props.shop.npcs" :key="npc.id" class="flex items-center gap-3 mb-2">
                        <img :src="npc.src" class="h-8 w-8 object-cover rounded" :alt="npc.name"/>
                        <span class="font-semibold">#{{ npc.id }} {{ npc.name }}</span>
                        <Button @click="router.get(route('npcs.show', {npc: npc.id}))" size="small" text
                                icon="pi pi-arrow-right" label="Przejdź" class="ml-2"/>
                    </li>
                </ul>
            </div>
            <div v-else class="text-gray-500">Brak powiązanych NPC.</div>
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


.item-box {
    position: absolute;
    width: 32px;
    height: 32px;
    cursor: pointer;
    background-size: cover;
    transition: transform 0.2s ease-in-out;
    box-shadow: none;
    background-color: rgba(100, 237, 226, 0.3);
}
</style>
