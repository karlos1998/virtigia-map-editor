<script setup lang="ts">
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";

import { Link } from '@inertiajs/vue3';
import { route } from "ziggy-js";
import { QuestResource } from "@/Resources/Quest.resource";

type Data = {
    data: QuestResource
}
</script>

<template>
    <AdvanceTable
        prop-name="quests"
    >
        <template #header="{ globalFilterValue, globalFilterUpdated }">
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <div class="font-extrabold">Lista Questów</div>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText
                        :value="globalFilterValue"
                        @update:model-value="globalFilterUpdated"
                        placeholder="Szukaj po nazwie"
                    />
                </IconField>
            </div>
        </template>

        <AdvanceColumn field="id" header="ID" style="width: 5%" />

        <AdvanceColumn field="name" header="Nazwa">
            <template #body="{ data }: Data">
                {{ data.name }}
            </template>
        </AdvanceColumn>

        <AdvanceColumn field="is_daily" header="Dzienny" style="width: 10%">
            <template #body="{ data }: Data">
                <span v-if="data.is_daily">Tak</span>
                <span v-else>Nie</span>
            </template>
        </AdvanceColumn>

        <AdvanceColumn field="created_at" header="Data utworzenia">
            <template #body="{ data }: Data">
                {{ new Date(data.created_at).toLocaleString() }}
            </template>
        </AdvanceColumn>

        <Column header="Akcje">
            <template #body="{data}">
                <Link
                    :href="route('quests.show', data.id)"
                >
                    <Button
                        class="px-2"
                        icon="pi pi-eye"
                        label="Podgląd"
                    />
                </Link>
            </template>
        </Column>
    </AdvanceTable>
</template>

<style scoped>
</style>
