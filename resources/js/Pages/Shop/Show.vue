<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import {useDialog} from "primevue/usedialog";
import AddShopItemDialog from "@/Pages/Shop/Components/AddShopItemDialog.vue";


const primeDialog = useDialog();

const addItem = () => {
    primeDialog.open(AddShopItemDialog, {
        props: {
            header: 'Dodaj przedmiot do sklepu'
        },
        data: {
            content: ''
        },
        onClose(options) {

            if (options.data.content) {
                // state.value.content = options.data.content;
            }
        }
    });
};

</script>
<template>

    <AppLayout>

        <div class="card">Tutaj nazwa sklepu</div>

        <div class="card">
            <div class="shop" @click="addItem">

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
</style>
