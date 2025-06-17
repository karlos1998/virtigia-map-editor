<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { SpawnPointResource } from "@/Resources/SpawnPoint.resource";
import DataView from 'primevue/dataview';
import Tag from 'primevue/tag';
import { Link } from '@inertiajs/vue3';
import { route } from "ziggy-js";

defineProps<{
  spawnPoints: SpawnPointResource[]
}>();
</script>

<template>
  <AppLayout>
    <div class="card">
      <div class="flex flex-wrap gap-2 items-center justify-between mb-4">
        <h4 class="m-0">Lista punktów startowych profesji</h4>
      </div>

      <DataView :value="spawnPoints" layout="list" data-key="id" :paginator="false">
        <template #list="slotProps">
          <div class="grid grid-nogutter">
            <div v-for="(spawnPoint, index) in slotProps.items" :key="spawnPoint.id" class="col-12">
              <div class="flex flex-column xl:flex-row xl:align-items-start p-4 gap-4" :class="{ 'border-top-1 surface-border': index !== 0 }">
                <div class="flex flex-column sm:flex-row justify-content-between align-items-center xl:align-items-start flex-1 gap-4">
                  <div class="flex flex-column align-items-center sm:align-items-start gap-3">
                    <div class="text-2xl font-bold text-900">{{ spawnPoint.profession_name }}</div>
                    <div class="flex align-items-center gap-3">
                      <span class="flex align-items-center gap-2">
                        <i class="pi pi-map"></i>
                        <span class="font-semibold">
                          Mapa:
                          <Link
                            v-if="spawnPoint.map_id"
                            :href="route('maps.show', {map: spawnPoint.map_id})"
                            class="text-primary hover:underline"
                          >
                            {{ spawnPoint.map_name }}
                          </Link>
                          <span v-else>Brak</span>
                        </span>
                      </span>
                    </div>
                    <div class="flex align-items-center gap-3">
                      <span class="flex align-items-center gap-2">
                        <i class="pi pi-map-marker"></i>
                        <span class="font-semibold">Koordynaty: ({{ spawnPoint.x }}, {{ spawnPoint.y }})</span>
                      </span>
                    </div>
                  </div>
                  <div class="flex sm:flex-column align-items-center sm:align-items-end gap-3 sm:gap-2">
                    <Tag :value="spawnPoint.profession" severity="info" />
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
