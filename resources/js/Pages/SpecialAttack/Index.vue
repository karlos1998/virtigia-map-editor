<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {SpecialAttackResource} from "@/Resources/SpecialAttack.resource";
import {Link} from '@inertiajs/vue3';
import {route} from "ziggy-js";

type Data = {
    data: SpecialAttackResource
}
</script>

<template>
    <AppLayout>

        <div class="card">
            <div class="flex gap-2">
                <Link :href="route('special-attacks.create')">
                    <Button label="Dodaj cios specjalny" icon="pi pi-plus"/>
                </Link>
            </div>
        </div>

        <div class="card">

            <AdvanceTable
                prop-name="specialAttacks"
            >

                <template #header="{ globalFilterValue, globalFilterUpdated }">

                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Lista Ciosów Specjalnych</h4>
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search"/>
                            </InputIcon>
                            <InputText
                                :value="globalFilterValue"
                                @update:model-value="globalFilterUpdated"
                                placeholder="Szukaj"
                            />
                        </IconField>
                    </div>
                </template>

                <AdvanceColumn field="id" header="ID" style="width: 5%"/>

                <AdvanceColumn field="name" header="Nazwa" style="width: 25%"/>

                <AdvanceColumn field="attack_type" header="Typ ataku" style="width: 15%">
                    <template #body="{ data }: Data">
                        <Badge style="background: #4caf50" class="w-full">
                            {{ data.attack_type }}
                        </Badge>
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="charge_turns" header="Tur ładowania" style="width: 10%">
                    <template #body="{ data }: Data">
                        <Badge style="background: #ff9800" class="w-full">
                            {{ data.charge_turns }}
                        </Badge>
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="target" header="Cel" style="width: 15%">
                    <template #body="{ data }: Data">
                        <Badge style="background: #2196f3" class="w-full">
                            {{ data.target }}
                        </Badge>
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="base_npcs_count" header="Ilość Base NPC" style="width: 15%">
                    <template #body="{ data }: Data">
                        <Badge style="background: #9c27b0" class="w-full">
                            {{ data.base_npcs_count }}
                        </Badge>
                    </template>
                </AdvanceColumn>

                <Column header="Action" style="width: 15%">
                    <template #body="{data}">
                        <div style="white-space: nowrap">
                            <span class="p-buttonset">
                                <Link
                                    :href="route('special-attacks.show', {specialAttack: data.id})"
                                >
                                    <Button
                                        class="px-2"
                                        icon="pi pi-eye"
                                        label="Podgląd"
                                    />
                                </Link>

                                <Link
                                    :href="route('special-attacks.edit', {specialAttack: data.id})"
                                >
                                    <Button
                                        class="px-2"
                                        icon="pi pi-pencil"
                                        severity="secondary"
                                        label="Edytuj"
                                    />
                                </Link>
                            </span>
                        </div>
                    </template>
                </Column>
            </AdvanceTable>
        </div>
    </AppLayout>

</template>

<style scoped>
</style>
