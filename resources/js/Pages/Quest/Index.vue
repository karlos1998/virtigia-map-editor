<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import QuestsListTable from "@/Pages/Quest/Partials/QuestsListTable.vue";
import CreateQuestModal from "@/Pages/Quest/Modals/CreateQuestModal.vue";
import {ref} from "vue";

const isCreateQuestModalVisible = ref(false);
</script>

<template>
    <AppLayout>
        <div class="card">
            <CreateQuestModal v-model:visible="isCreateQuestModalVisible" />
            <Button label="Utwórz nowy" @click="isCreateQuestModalVisible = true" />
        </div>

        <div class="card">
            <QuestsListTable />
        </div>
    </AppLayout>
</template>

<style scoped>
</style>
