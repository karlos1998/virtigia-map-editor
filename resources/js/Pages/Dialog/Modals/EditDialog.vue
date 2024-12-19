<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import {Ref, ref} from "vue";

import { inject, onMounted } from "vue";
import {DynamicDialogInstance} from "primevue/dynamicdialogoptions";

const dialogRef = inject<Ref<DynamicDialogInstance & {
    data: {
        label: string,
        content: string,
    }
}> | null>('dialogRef');

const label = ref('');
const content = ref('');
onMounted(() => {
    label.value = dialogRef.value.data?.label ?? '';
    content.value = dialogRef.value.data?.content ?? '';
})

const save = () => {
    dialogRef.value.close({
        label: label.value,
        content: content.value,
    });
}
</script>

<template>
    <div class="flex flex-col gap-2">
<!--        <Textarea v-model="label" rows="1" cols="50" />-->
        <Textarea v-model="content" rows="5" cols="50" />
        <Button fluid @click="save">Save</Button>
    </div>
</template>

<style scoped>

</style>
