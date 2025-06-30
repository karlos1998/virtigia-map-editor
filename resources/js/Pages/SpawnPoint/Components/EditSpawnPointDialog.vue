<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { SpawnPointResource } from "@/Resources/SpawnPoint.resource";
import { ProfessionEnum } from "@/Enums/Profession.enum";
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Message from 'primevue/message';

const visible = defineModel<boolean>('visible');
const selectedProfession = defineModel<string>('selectedProfession');

const props = defineProps<{
    spawnPoint?: SpawnPointResource;
}>();

// Form for editing or creating a spawn point
const form = useForm({
    map_id: null,
    x: 1,
    y: 1,
    profession: null,
});


// Validate x and y coordinates
const validateCoordinates = () => {
    if (!form.map_id || form.map_id < 1) {
        form.setError('map_id', 'Map ID must be a positive number');
        return false;
    }

    if (!form.x || form.x < 1) {
        form.setError('x', 'X must be a positive number');
        return false;
    }

    if (!form.y || form.y < 1) {
        form.setError('y', 'Y must be a positive number');
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

// Save the spawn point
const save = () => {
    if (!validateCoordinates()) return;

    if (props.spawnPoint) {
        // Update existing spawn point
        form.patch(route('spawn-points.update', { spawnPoint: props.spawnPoint.id }), {
            onSuccess: () => {
                visible.value = false;
            }
        });
    } else {
        // Create new spawn point
        form.post(route('spawn-points.store'), {
            onSuccess: () => {
                visible.value = false;
            }
        });
    }
};

// Set default spawn point (map_id = 1, x = 1, y = 1)
const setDefault = () => {
    form.map_id = 1;
    form.x = 1;
    form.y = 1;
};


// Watch for changes in props.spawnPoint
watch(() => props.spawnPoint, (newSpawnPoint) => {
    if (newSpawnPoint) {
        form.map_id = newSpawnPoint.map_id;
        form.x = newSpawnPoint.x;
        form.y = newSpawnPoint.y;
        form.profession = newSpawnPoint.profession;
    } else {
        form.map_id = null;
        form.x = 1;
        form.y = 1;
        form.profession = selectedProfession.value;
    }
}, { immediate: true });
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :header="props.spawnPoint ? 'Edit Spawn Point' : 'Create Spawn Point'"
        :style="{ width: '450px' }"
    >
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-2">
                <label for="profession" class="font-semibold">Profession</label>
                <div class="text-lg font-bold">{{ props.spawnPoint?.profession_name || selectedProfession }}</div>
                <input type="hidden" v-model="form.profession" />
            </div>

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
                    :min="1"
                    class="w-full"
                />
                <Message v-if="form.errors.x" severity="error" :text="form.errors.x" />
            </div>

            <div class="flex flex-col gap-2">
                <label for="y" class="font-semibold">Y Coordinate</label>
                <InputNumber
                    id="y"
                    v-model="form.y"
                    :min="1"
                    class="w-full"
                />
                <Message v-if="form.errors.y" severity="error" :text="form.errors.y" />
            </div>
        </div>

        <div class="flex justify-between mt-6">
            <Button
                type="button"
                label="Set Default"
                severity="secondary"
                @click="setDefault"
                v-if="!props.spawnPoint"
            />
            <div class="flex gap-2 ml-auto">
                <Button type="button" label="Cancel" severity="secondary" @click="cancel" />
                <Button type="button" label="Save" :loading="form.processing" @click="save" />
            </div>
        </div>
    </Dialog>
</template>
