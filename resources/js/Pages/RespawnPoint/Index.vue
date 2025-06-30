<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from "@/layout/AppLayout.vue";
import { RespawnPointResource } from "@/Resources/RespawnPoint.resource";
import DataView from 'primevue/dataview';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { router } from '@inertiajs/vue3';
import EditRespawnPointDialog from './Components/EditRespawnPointDialog.vue';

const props = defineProps<{
  respawnPoints: RespawnPointResource[]
}>();

const confirm = useConfirm();
const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const selectedRespawnPoint = ref<RespawnPointResource | null>(null);

const openCreateDialog = () => {
  selectedRespawnPoint.value = null;
  showCreateDialog.value = true;
};

const openEditDialog = (respawnPoint: RespawnPointResource) => {
  selectedRespawnPoint.value = respawnPoint;
  showEditDialog.value = true;
};

const confirmDelete = (respawnPoint: RespawnPointResource) => {
  confirm.require({
    message: 'Are you sure you want to delete this respawn point?',
    header: 'Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(route('respawn-points.destroy', { respawnPoint: respawnPoint.id }));
    }
  });
};
</script>

<template>
  <AppLayout>
    <ConfirmDialog />
    <EditRespawnPointDialog v-model:visible="showCreateDialog" />
    <EditRespawnPointDialog v-model:visible="showEditDialog" :respawn-point="selectedRespawnPoint" />

    <div class="card">
      <div class="flex flex-wrap gap-2 items-center justify-between mb-4">
        <h4 class="m-0">Lista punktów odrodzenia</h4>
        <Button label="Dodaj punkt odrodzenia" icon="pi pi-plus" @click="openCreateDialog" />
      </div>

      <DataView :value="respawnPoints" layout="list" data-key="id" :paginator="false">
        <template #list="slotProps">
          <div class="grid grid-nogutter">
            <div v-for="(respawnPoint, index) in slotProps.items" :key="respawnPoint.id" class="col-12">
              <div class="flex flex-column xl:flex-row xl:align-items-start p-4 gap-4" :class="{ 'border-top-1 surface-border': index !== 0 }">
                <div class="flex flex-column sm:flex-row justify-content-between align-items-center xl:align-items-start flex-1 gap-4">
                  <div class="flex flex-column align-items-center sm:align-items-start gap-3">
                    <div class="text-2xl font-bold text-900">{{ respawnPoint.map_name }}</div>
                    <div class="flex align-items-center gap-3">
                      <span class="flex align-items-center gap-2">
                        <i class="pi pi-map-marker"></i>
                        <span class="font-semibold">Koordynaty: ({{ respawnPoint.x }}, {{ respawnPoint.y }})</span>
                      </span>
                    </div>
                  </div>
                  <div class="flex sm:flex-column align-items-center sm:align-items-end gap-3 sm:gap-2">
                    <Tag severity="info" value="Powiązane mapy" />
                    <span class="text-xl font-semibold">{{ respawnPoint.maps_count }}</span>
                    <div class="flex gap-2">
                      <Button icon="pi pi-pencil" severity="secondary" rounded text @click="openEditDialog(respawnPoint)" />
                      <Button icon="pi pi-trash" severity="danger" rounded text @click="confirmDelete(respawnPoint)" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </DataView>
    </div>
  </AppLayout>
</template>

<style scoped>
</style>
