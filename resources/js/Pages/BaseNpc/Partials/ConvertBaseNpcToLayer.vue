<script setup lang="ts">
import { BaseNpcResource } from "@/Resources/BaseNpc.resource";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

const props = defineProps<{
  baseNpc: BaseNpcResource;
}>();

const isConfirmDialogVisible = ref(false);
const isRevertConfirmDialogVisible = ref(false);

const layerDepthOptions = [
  { value: "Y_SORT", label: "Wg pozycji", description: "Postać niżej jest na wierzchu, postać wyżej (za warstwą) jest zasłonięta. Np. kolumny, ściany, posągi." },
  { value: "ABOVE", label: "Zawsze nad postaciami", description: "Zawsze zasłania postacie. Np. pajęczyny, korony drzew, dachy." },
  { value: "BELOW", label: "Zawsze pod postaciami", description: "Nigdy nie zasłania postaci. Np. dywany, plamy, ślady na ziemi." },
];

const layerDepth = ref(props.baseNpc.layer_depth ?? "Y_SORT");
const savingLayerDepth = ref(false);

const updateLayerDepth = () => {
  savingLayerDepth.value = true;
  router.patch(route("base-npcs.layer-depth.update", props.baseNpc.id), { layer_depth: layerDepth.value }, {
    preserveScroll: true,
    onFinish: () => {
      savingLayerDepth.value = false;
    },
  });
};

const convertToLayer = () => {
  router.post(route("base-npcs.convert-to-layer", props.baseNpc.id), {}, {
    onSuccess: () => {
      isConfirmDialogVisible.value = false;
    },
  });
};

const revertFromLayer = () => {
  router.post(route("base-npcs.revert-from-layer", props.baseNpc.id), {}, {
    onSuccess: () => {
      isRevertConfirmDialogVisible.value = false;
    },
  });
};
</script>

<template>
  <div class="card">
    <div class="flex justify-content-between align-items-center mb-3">
      <h3>Konwersja na warstwę</h3>
    </div>

    <div class="p-3 border-1 border-round border-primary mb-3">
      <div class="flex align-items-center mb-3">
        <i class="pi pi-info-circle mr-2 text-primary"></i>
        <span>
          <strong>Status warstwy:</strong>
          <Tag v-if="baseNpc.type === 4" severity="success" value="Warstwa" class="ml-2" />
          <Tag v-else severity="info" value="Zwykły NPC" class="ml-2" />
        </span>
      </div>
      <p>
        Warstwa to specjalny typ NPC, który może być używany do tworzenia złożonych struktur na mapie.
        Konwersja na warstwę zmienia typ NPC na 4. Możesz również cofnąć tę zmianę, przywracając typ 0.
      </p>
    </div>

    <div v-if="baseNpc.type === 4" class="mb-3">
      <label for="layer-depth" class="block font-bold mb-2">Rysowanie względem postaci</label>
      <Select
        inputId="layer-depth"
        v-model="layerDepth"
        :options="layerDepthOptions"
        optionLabel="label"
        optionValue="value"
        :disabled="savingLayerDepth"
        class="w-full"
        @change="updateLayerDepth"
      />
      <small class="block mt-2">
        {{ layerDepthOptions.find(option => option.value === layerDepth)?.description }}
      </small>
    </div>

    <div class="flex justify-content-end">
      <Button
        v-if="baseNpc.type !== 4"
        label="Przerób na warstwę"
        icon="pi pi-arrow-up"
        @click="isConfirmDialogVisible = true"
        class="p-button-primary"
      />
      <Button
        v-else
        label="Przywróć zwykły typ"
        icon="pi pi-arrow-down"
        @click="isRevertConfirmDialogVisible = true"
        class="p-button-secondary"
      />
    </div>

    <Dialog
      v-model:visible="isConfirmDialogVisible"
      header="Potwierdź konwersję na warstwę"
      :modal="true"
      :closable="true"
    >
      <p>Czy na pewno chcesz przekonwertować ten NPC na warstwę (typ 4)?</p>
      <template #footer>
        <Button label="Anuluj" icon="pi pi-times" @click="isConfirmDialogVisible = false" class="p-button-text" />
        <Button label="Potwierdź" icon="pi pi-check" @click="convertToLayer" class="p-button-primary" />
      </template>
    </Dialog>

    <Dialog
      v-model:visible="isRevertConfirmDialogVisible"
      header="Potwierdź przywrócenie zwykłego typu"
      :modal="true"
      :closable="true"
    >
      <p>Czy na pewno chcesz przywrócić ten NPC do zwykłego typu (typ 0)?</p>
      <template #footer>
        <Button label="Anuluj" icon="pi pi-times" @click="isRevertConfirmDialogVisible = false" class="p-button-text" />
        <Button label="Potwierdź" icon="pi pi-check" @click="revertFromLayer" class="p-button-primary" />
      </template>
    </Dialog>
  </div>
</template>
