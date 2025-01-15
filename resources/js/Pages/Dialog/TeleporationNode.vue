<script setup lang="ts">
import { Handle, NodeProps, Position } from '@vue-flow/core';
import { useDialog } from 'primevue/usedialog';
import axios from 'axios';
import { route } from 'ziggy-js';
import { debounce } from 'chart.js/helpers';
import { ref } from 'vue';
import {DialogNodeTeleportationDataResource} from "../../Resources/DialogNodeTeleportationData.resource";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import TeleportationSelectModal from "../../Components/TeleportationSelectModal.vue";

const primeDialog = useDialog();

const props = defineProps<{
    data: {
        action_data?: DialogNodeTeleportationDataResource
    }
}>();

console.log('props', props);
</script>

<script lang="ts">
import {ref} from "vue";

export default {
    inheritAttrs: true
};


const isTeleportationSelectModalVisible = ref(false);
const editNode = () => {
    isTeleportationSelectModalVisible.value = true;
}
</script>

<template>

    <TeleportationSelectModal v-model:visible="isTeleportationSelectModalVisible" v-bind="data.action_data" />

    <div class="vue-flow__node-default">
        <Handle class="dialog-input" type="target" :position="Position.Left" />

        <Button severity="info" size="small" class="align-self-end" @click="editNode">
            <FontAwesomeIcon icon="edit" />
        </Button>

        <div v-if="data.action_data.teleportation">
            [{{data.action_data.teleportation.mapId}}] {{data.action_data.teleportation.mapName}} ({{data.action_data.teleportation.x}}, {{data.action_data.teleportation.y}})
        </div>

    </div>
</template>

<style scoped lang="scss">
//.vue-flow__node-default {
//    @apply text-white text-left flex flex-row gap-1 p-2;
//}

.dialog-input {
    @apply top-6 bg-red-400;
}

.dialog-output {
    @apply bg-blue-400 right-0;
}

</style>
