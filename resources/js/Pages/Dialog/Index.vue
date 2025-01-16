<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import DialogsListTable from "@/Pages/Dialog/Partials/DialogsListTable.vue";
import CreateDialogNodeModal from "../../Components/CreateDialogNodeModal.vue";
import {ref} from "vue";

const isCreateDialogNodeModalVisible = ref(false);
</script>

<template>
    <AppLayout>

        <div class="card">
            <CreateDialogNodeModal v-model:visible="isCreateDialogNodeModalVisible" />
            <Button label="Utwórz nowy" @click="isCreateDialogNodeModalVisible = true" />
        </div>

        <div class="card">
            <DialogsListTable />
        </div>
    </AppLayout>

</template>

<style scoped>
</style>
