<script setup lang="ts">
import Button from 'primevue/button';
import { inject, Ref, ref } from "vue";
import { DynamicDialogInstance } from "primevue/dynamicdialogoptions";
import { BaseItemResource } from "@/Resources/BaseItem.resource";
import BaseItemSearchSelect from "@/Components/BaseItemSearchSelect.vue";

const dialogRef = inject<Ref<DynamicDialogInstance> | null>('dialogRef');

const save = () => {
    if (!dialogRef?.value) {
        return;
    }

    dialogRef.value.close({
        item: selectedItem.value,
    });
};

const selectedItem = ref<BaseItemResource | null>(null);
</script>

<template>
    <div class="flex flex-col gap-2">
        <BaseItemSearchSelect
            v-model="selectedItem"
            value-mode="object"
            placeholder="Wyszukaj przedmiot (nazwa lub #id)"
        />

        <Button fluid @click="save">Dodaj</Button>
    </div>
</template>

<style scoped>

</style>
