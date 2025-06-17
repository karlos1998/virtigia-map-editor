<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { RespawnPointResource } from "@/Resources/RespawnPoint.resource";
import DataView from 'primevue/dataview';
import Tag from 'primevue/tag';

defineProps<{
  respawnPoints: RespawnPointResource[]
}>();
</script>

<template>
  <AppLayout>
    <div class="card">
      <div class="flex flex-wrap gap-2 items-center justify-between mb-4">
        <h4 class="m-0">Lista punktów odrodzenia</h4>
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
