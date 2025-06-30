<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { SpawnPointResource } from "@/Resources/SpawnPoint.resource";
import { ProfessionEnum } from "@/Enums/Profession.enum";
import DataView from 'primevue/dataview';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { Link, router } from '@inertiajs/vue3';
import { route } from "ziggy-js";
import { ref, computed } from 'vue';
import EditSpawnPointDialog from './Components/EditSpawnPointDialog.vue';

const props = defineProps<{
  spawnPoints: SpawnPointResource[];
  maps: any[];
  professions: { value: string; label: string }[];
}>();

const isEditDialogVisible = ref(false);
const selectedSpawnPoint = ref<SpawnPointResource | null>(null);
const selectedProfession = ref<string>('');

// Open edit dialog for existing spawn point
const editSpawnPoint = (spawnPoint: SpawnPointResource) => {
  selectedSpawnPoint.value = spawnPoint;
  isEditDialogVisible.value = true;
};

// Open create dialog for a new spawn point
const createSpawnPoint = (profession: string) => {
  selectedSpawnPoint.value = null;
  selectedProfession.value = profession;
  isEditDialogVisible.value = true;
};

// Set default spawn points for missing professions
const setDefaultForMissing = () => {
  router.post(route('spawn-points.set-default-for-missing'));
};

// Get missing professions
const missingProfessions = computed(() => {
  const existingProfessions = props.spawnPoints.map(sp => sp.profession);
  return props.professions.filter(p => !existingProfessions.includes(p.value));
});

// Check if a profession has a spawn point
const hasSpawnPoint = (profession: string) => {
  return props.spawnPoints.some(sp => sp.profession === profession);
};
</script>

<template>
  <AppLayout>
    <EditSpawnPointDialog
      v-model:visible="isEditDialogVisible"
      v-model:selectedProfession="selectedProfession"
      :spawn-point="selectedSpawnPoint"
    />

    <div class="card">
      <div class="flex flex-wrap gap-2 items-center justify-between mb-4">
        <h4 class="m-0">Lista punktów startowych profesji</h4>
        <div class="flex gap-2">
          <Button
            v-if="missingProfessions.length > 0"
            label="Ustaw domyślne dla brakujących profesji"
            icon="pi pi-plus"
            @click="setDefaultForMissing"
          />
        </div>
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
                    <Button
                      icon="pi pi-pencil"
                      text
                      rounded
                      @click="editSpawnPoint(spawnPoint)"
                      class="p-button-sm"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </DataView>

      <!-- Missing Professions Section -->
      <div v-if="missingProfessions.length > 0" class="mt-6">
        <h4>Brakujące profesje</h4>
        <div class="grid grid-nogutter">
          <div v-for="profession in missingProfessions" :key="profession.value" class="col-12 mb-2">
            <div class="flex justify-content-between align-items-center p-3 border-1 surface-border border-round">
              <div class="font-semibold">{{ profession.label }}</div>
              <Button
                label="Dodaj punkt startowy"
                icon="pi pi-plus"
                size="small"
                @click="createSpawnPoint(profession.value)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
</style>
