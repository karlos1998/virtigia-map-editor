<script setup lang="ts">

import ShopListTable from "@/Pages/Shop/Partials/ShopListTable.vue";
import AppLayout from "@/layout/AppLayout.vue";
import CreateNewShopDialog from "./Components/CreateNewShopDialog.vue";
import {ref} from "vue";

const isCreateShopModalVisible = ref(false);
</script>
<template>
    <AppLayout>

        <div class="card">
            <CreateNewShopDialog v-model:visible="isCreateShopModalVisible" />
            <Button @click="isCreateShopModalVisible = true" label="Utwórz nowy" />
        </div>

        <div class="card">
            <ShopListTable/>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
