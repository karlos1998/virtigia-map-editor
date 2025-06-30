<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import { Link } from '@inertiajs/vue3';
import { route } from "ziggy-js";
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {AdvanceTableResponse, TableData} from "@/karlos3098-LaravelPrimevueTable/Services/tableService";
import {TitanDoorResource} from "@/Resources/TitanDoor.resource";

type Data = {
    data: TitanDoorResource
};

const selectedDoors = ref<number[]>([]);
const selectAll = ref(false);
const isLevelRestrictionDialogVisible = ref(false);

const form = useForm({
    door_ids: [] as number[],
    min_diff: 50,
    max_diff: 50,
});

const toggleSelectAll = () => {
    selectAll.value = !selectAll.value;
    if (selectAll.value) {
        selectedDoors.value = props.doors.data.map(door => door.id);
    } else {
        selectedDoors.value = [];
    }
};

const toggleDoorSelection = (doorId: number) => {
    const index = selectedDoors.value.indexOf(doorId);
    if (index === -1) {
        selectedDoors.value.push(doorId);
    } else {
        selectedDoors.value.splice(index, 1);
    }
};

const openLevelRestrictionDialog = () => {
    if (selectedDoors.value.length === 0) {
        alert('Proszę wybrać co najmniej jedne drzwi');
        return;
    }
    isLevelRestrictionDialogVisible.value = true;
};

const applyLevelRestrictions = () => {
    form.door_ids = selectedDoors.value;
    form.post(route('doors.update-level-restrictions'), {
        onSuccess: () => {
            isLevelRestrictionDialogVisible.value = false;
        },
        onError: (errors) => {
            console.error('Error updating level restrictions:', errors);
        }
    });
};

const props = defineProps<{
    doors: AdvanceTableResponse<TitanDoorResource>
}>();

</script>

<template>
    <AppLayout>
        <div class="card">
            <h1>Titan Doors</h1>
            <p>List of doors that lead to maps with Titan NPCs</p>
        </div>

        <div class="card">
            <div class="flex justify-end mb-3">
                <Button
                    label="Ustaw ograniczenia lvlowe"
                    icon="pi pi-cog"
                    @click="openLevelRestrictionDialog"
                    :disabled="selectedDoors.length === 0"
                />
            </div>

            <AdvanceTable
                prop-name="doors"
            >
                <template #header="{ globalFilterValue, globalFilterUpdated }">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Lista drzwi prowadzących do Tytanów</h4>
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText
                                :value="globalFilterValue"
                                @update:model-value="globalFilterUpdated"
                                placeholder="Szukaj"
                            />
                        </IconField>
                    </div>
                </template>

                <Column header="Select" style="width: 5%">
                    <template #header>
                        <div class="flex justify-center">
                            <input
                                type="checkbox"
                                :checked="selectAll"
                                @change="toggleSelectAll"
                                class="cursor-pointer"
                            />
                        </div>
                    </template>
                    <template #body="{ data }">
                        <div class="flex justify-center">
                            <input
                                type="checkbox"
                                :checked="selectedDoors.includes(data.id)"
                                @change="toggleDoorSelection(data.id)"
                                class="cursor-pointer"
                            />
                        </div>
                    </template>
                </Column>

                <AdvanceColumn field="id" header="ID" style="width: 5%" />

                <AdvanceColumn field="map.name" header="Source Map">
                    <template #body="{ data }: Data">
                        {{ data.source_map?.name }} ({{ data.x }}, {{ data.y }})
                    </template>
                </AdvanceColumn>


                <AdvanceColumn field="targetMap.name" header="Target Map (Titan)" >
                    <template #body="{ data }: Data">
                        {{ data.target_map?.name }} ({{ data.go_x }}, {{ data.go_y }})
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="titan" header="Titan">
                    <template #body="{ data }: Data">
                        <div v-if="data.titan">
                            <div class="font-bold">{{ data.titan.name }}</div>
                            <div class="text-sm">Level: {{ data.titan.level }}</div>
                        </div>
                        <div v-else>No Titan Found</div>
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="level" header="Level Requirement">
                    <template #body="{ data }: Data">
                        <div v-if="data.min_lvl || data.max_lvl">
                            {{ data.min_lvl || 'None' }} - {{ data.max_lvl || 'None' }}
                        </div>
                        <div v-else>No Requirement</div>
                    </template>
                </AdvanceColumn>

                <Column header="Action" style="width: 20%">
                    <template #body="{ data }">
                        <div style="white-space: nowrap">
                            <span class="p-buttonset">
                                <Link :href="route('maps.show', { map: data.map_id })">
                                    <Button
                                        class="px-2"
                                        icon="pi pi-eye"
                                        label="View Source Map"
                                    />
                                </Link>
                                <Link :href="route('maps.show', { map: data.go_map_id })">
                                    <Button
                                        class="px-2"
                                        icon="pi pi-eye"
                                        label="View Target Map"
                                    />
                                </Link>
                            </span>
                        </div>
                    </template>
                </Column>
            </AdvanceTable>
        </div>
    </AppLayout>

    <Dialog
        v-model:visible="isLevelRestrictionDialogVisible"
        modal
        header="Ustaw ograniczenia lvlowe"
        :style="{ width: '450px' }"
    >
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-4">
                <label for="min_diff" class="font-semibold w-48">Minimalna różnica poziomu:</label>
                <InputNumber
                    id="min_diff"
                    v-model="form.min_diff"
                    :min="0"
                    :max="100"
                    class="w-24"
                />
            </div>

            <div class="flex items-center gap-4">
                <label for="max_diff" class="font-semibold w-48">Maksymalna różnica poziomu:</label>
                <InputNumber
                    id="max_diff"
                    v-model="form.max_diff"
                    :min="0"
                    :max="100"
                    class="w-24"
                />
            </div>

            <p class="text-sm text-gray-600 mt-2">
                Dla każdych drzwi zostanie ustawiony poziom minimalny jako poziom tytana - minimalna różnica,
                a poziom maksymalny jako poziom tytana + maksymalna różnica.
            </p>
        </div>

        <div class="flex justify-end gap-2 mt-4">
            <Button
                type="button"
                label="Anuluj"
                severity="secondary"
                @click="isLevelRestrictionDialogVisible = false"
            />
            <Button
                type="button"
                label="Zastosuj"
                @click="applyLevelRestrictions"
            />
        </div>
    </Dialog>
</template>

<style scoped>
</style>
