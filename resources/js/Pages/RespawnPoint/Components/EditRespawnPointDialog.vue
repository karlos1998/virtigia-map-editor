<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { RespawnPointResource } from "@/Resources/RespawnPoint.resource";
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Message from 'primevue/message';

const visible = defineModel<boolean>('visible');

const props = defineProps<{
    respawnPoint?: RespawnPointResource;
}>();

// Form for editing or creating a respawn point
const form = useForm({
    map_id: null,
    x: 0,
    y: 0,
});

// Validate x and y coordinates
const validateCoordinates = () => {
    if (!form.map_id || form.map_id < 1) {
        form.setError('map_id', 'Map ID must be a positive number');
        return false;
    }

    if (form.x < 0) {
        form.setError('x', 'X must be a non-negative number');
        return false;
    }

    if (form.y < 0) {
        form.setError('y', 'Y must be a non-negative number');
        return false;
    }

    return true;
};

// Cancel and close the dialog
const cancel = () => {
    form.reset();
    form.clearErrors();
    visible.value = false;
};

// Save the respawn point
const save = () => {
    if (!validateCoordinates()) return;

    if (props.respawnPoint) {
        // Update existing respawn point
        form.patch(route('respawn-points.update', { respawnPoint: props.respawnPoint.id }), {
            onSuccess: () => {
                visible.value = false;
            }
        });
    } else {
        // Create new respawn point
        form.post(route('respawn-points.store'), {
            onSuccess: () => {
                visible.value = false;
            }
        });
    }
};

// Watch for changes in props.respawnPoint
watch(() => props.respawnPoint, (newRespawnPoint) => {
    if (newRespawnPoint) {
        form.map_id = newRespawnPoint.map_id;
        form.x = newRespawnPoint.x;
        form.y = newRespawnPoint.y;
    } else {
        form.map_id = null;
        form.x = 0;
        form.y = 0;
    }
}, { immediate: true });
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :header="props.respawnPoint ? 'Edit Respawn Point' : 'Create Respawn Point'"
        :style="{ width: '450px' }"
    >
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-2">
                <label for="map_id" class="font-semibold">Map ID</label>
                <InputNumber
                    id="map_id"
                    v-model="form.map_id"
                    :min="1"
                    class="w-full"
                />
                <Message v-if="form.errors.map_id" severity="error" :text="form.errors.map_id" />
            </div>

            <div class="flex flex-col gap-2">
                <label for="x" class="font-semibold">X Coordinate</label>
                <InputNumber
                    id="x"
                    v-model="form.x"
                    :min="0"
                    class="w-full"
                />
                <Message v-if="form.errors.x" severity="error" :text="form.errors.x" />
            </div>

            <div class="flex flex-col gap-2">
                <label for="y" class="font-semibold">Y Coordinate</label>
                <InputNumber
                    id="y"
                    v-model="form.y"
                    :min="0"
                    class="w-full"
                />
                <Message v-if="form.errors.y" severity="error" :text="form.errors.y" />
            </div>
        </div>

        <div class="flex justify-between mt-6">
            <div class="flex gap-2 ml-auto">
                <Button type="button" label="Cancel" severity="secondary" @click="cancel" />
                <Button type="button" label="Save" :loading="form.processing" @click="save" />
            </div>
        </div>
    </Dialog>
</template>
