<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {Ref, ref} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        option: {
            label: string,
            id: string
        },
        parent: string
    }
}> | null>('dialogRef');

const value = ref('');
onMounted(() => {
    value.value = dialogRef.value.data?.option?.label ?? '';
})

const save = () => {
    dialogRef.value.close({
        label: value.value,
    });
}

const remove = () => {
    dialogRef.value.close({
        remove: true,
    });
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <Textarea v-model="value" rows="5" cols="50" />
        <div class="flex gap-1">
            <Button fluid @click="remove" severity="danger">Remove</Button>
            <Button fluid @click="save">Save</Button>
        </div>
    </div>
</template>

<style scoped>

</style>
