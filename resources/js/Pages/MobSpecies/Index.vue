<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {Link, router} from "@inertiajs/vue3";
import {route} from "ziggy-js";
import {ref} from "vue";

type MobSpeciesRow = {
    id: number
    name: string
    base_npcs_count: number
    base_npcs_preview?: string[]
}

type Data = { data: MobSpeciesRow }

const createVisible = ref(false);
const newSpeciesName = ref("");
const createProcessing = ref(false);

const createSpecies = () => {
    createProcessing.value = true;
    router.post(route('mob-species.store'), { name: newSpeciesName.value }, {
        preserveScroll: true,
        onSuccess: () => {
            createVisible.value = false;
            newSpeciesName.value = "";
        },
        onFinish: () => {
            createProcessing.value = false;
        }
    });
};
</script>

<template>
    <AppLayout>
        <div class="card">
            <Button label="Nowy gatunek" icon="pi pi-plus" @click="createVisible = true" />
        </div>

        <div class="card">
            <AdvanceTable prop-name="mobSpecies">
                <template #header="{ globalFilterValue, globalFilterUpdated }">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Lista gatunków mobów</h4>
                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText :value="globalFilterValue" @update:model-value="globalFilterUpdated" placeholder="Szukaj po nazwie" />
                        </IconField>
                    </div>
                </template>

                <AdvanceColumn field="id" header="ID" style="width: 8%" />
                <AdvanceColumn field="name" header="Nazwa" />
                <AdvanceColumn field="base_npcs_count" header="Ilość mobów" style="width: 12%" />
                <AdvanceColumn field="base_npcs_preview" header="Moby (podgląd)">
                    <template #body="{ data }: Data">
                        <span>{{ (data.base_npcs_preview || []).join(', ') }}</span>
                    </template>
                </AdvanceColumn>
                <Column header="Akcje" style="width: 14%">
                    <template #body="{ data }: Data">
                        <Link :href="route('mob-species.show', { mobSpecies: data.id })">
                            <Button icon="pi pi-eye" label="Podgląd" />
                        </Link>
                    </template>
                </Column>
            </AdvanceTable>
        </div>

        <Dialog v-model:visible="createVisible" modal header="Nowy gatunek" :style="{ width: '30rem' }">
            <div class="mb-3">
                <label class="font-semibold block mb-2">Nazwa</label>
                <InputText v-model="newSpeciesName" class="w-full" autocomplete="off" />
            </div>
            <div class="flex justify-end gap-2">
                <Button label="Anuluj" severity="secondary" @click="createVisible = false" />
                <Button :loading="createProcessing" label="Utwórz" @click="createSpecies" />
            </div>
        </Dialog>
    </AppLayout>
</template>

