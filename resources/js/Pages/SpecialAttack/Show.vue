<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import {SpecialAttackWithRelations} from "@/Resources/SpecialAttack.resource";
import {route} from "ziggy-js";
import ItemHeader from "@/Components/ItemHeader.vue";
import DetailsCardList from "@/Components/DetailsCardList.vue";
import DetailsCardListItem from "@/Components/DetailsCardListItem.vue";
import {Link} from "@inertiajs/vue3";

defineProps<{
    specialAttack: SpecialAttackWithRelations
}>()
</script>

<template>
    <AppLayout>

        <ItemHeader
            :route-back="route('special-attacks.index')"
        >
            <template #header>
                #{{ specialAttack.id }} - {{ specialAttack.name }}
            </template>
            <template #right-buttons>
                <Link :href="route('special-attacks.edit', {specialAttack: specialAttack.id})">
                    <Button label="Edytuj"/>
                </Link>
            </template>
        </ItemHeader>

        <Message class="mb-8" severity="contrast">
            <div>Przeglądasz szczegóły ciosu specjalnego. Tutaj możesz zobaczyć wszystkie informacje o tym ciosie, jego
                efekty, obrażenia oraz które Base NPC mogą go używać.
            </div>
        </Message>

        <DetailsCardList title="Informacje Podstawowe">
            <DetailsCardListItem label="Nazwa" :value="specialAttack.name"/>
            <DetailsCardListItem label="Typ ataku">
                <template #value>
                    <Tag v-if="specialAttack.attack_type === 'Specjalny'" severity="danger"
                         :value="specialAttack.attack_type"/>
                    <Tag v-else-if="specialAttack.attack_type === 'Normalny'" severity="info"
                         :value="specialAttack.attack_type"/>
                    <Tag v-else severity="secondary" :value="specialAttack.attack_type"/>
                </template>
            </DetailsCardListItem>
            <DetailsCardListItem label="Tur ładowania" :value="specialAttack.charge_turns"/>
            <DetailsCardListItem label="Cel">
                <template #value>
                    <Tag v-if="specialAttack.target === 'Pojedynczy'" severity="danger" :value="specialAttack.target"/>
                    <Tag v-else-if="specialAttack.target === 'Wszyscy'" severity="warning"
                         :value="specialAttack.target"/>
                    <Tag v-else-if="specialAttack.target === 'Własny'" severity="success"
                         :value="specialAttack.target"/>
                    <Tag v-else-if="specialAttack.target === 'Linia'" severity="info" :value="specialAttack.target"/>
                    <Tag v-else severity="secondary" :value="specialAttack.target"/>
                </template>
            </DetailsCardListItem>
            <DetailsCardListItem label="Losowy cel">
                <template #value>
                    <Tag v-if="specialAttack.random_target" severity="warning" value="Tak"/>
                    <Tag v-else severity="info" value="Nie"/>
                </template>
            </DetailsCardListItem>
            <DetailsCardListItem label="Ilość Base NPC" :value="specialAttack.base_npcs_count"/>
        </DetailsCardList>

        <div class="card" v-if="specialAttack.effects && specialAttack.effects.length > 0">
            <h5>Efekty</h5>
            <DataTable :value="specialAttack.effects" class="p-datatable-sm">
                <Column field="type" header="Typ efektu" style="width: 30%">
                    <template #body="{ data }">
                        <Tag :value="data.type" severity="info"/>
                    </template>
                </Column>
                <Column field="value" header="Wartość" style="width: 30%">
                    <template #body="{ data }">
                        <Badge :value="data.value" severity="success"/>
                    </template>
                </Column>
                <Column field="duration" header="Czas trwania" style="width: 30%">
                    <template #body="{ data }">
                        <Badge v-if="data.duration === 0" value="Natychmiastowy" severity="warning"/>
                        <Badge v-else :value="`${data.duration} tur`" severity="info"/>
                    </template>
                </Column>
            </DataTable>
        </div>

        <div class="card" v-if="specialAttack.damages && specialAttack.damages.length > 0">
            <h5>Obrażenia</h5>
            <DataTable :value="specialAttack.damages" class="p-datatable-sm">
                <Column field="element" header="Element" style="width: 30%">
                    <template #body="{ data }">
                        <Tag :value="data.element" severity="danger"/>
                    </template>
                </Column>
                <Column header="Obrażenia" style="width: 70%">
                    <template #body="{ data }">
                        <Badge :value="`${data.min_damage} - ${data.max_damage}`" severity="warning"/>
                    </template>
                </Column>
            </DataTable>
        </div>

        <div class="card" v-if="specialAttack.baseNpcs && specialAttack.baseNpcs.length > 0">
            <h5>Base NPC używające tego ciosu</h5>
            <DataTable :value="specialAttack.baseNpcs" class="p-datatable-sm">
                <Column field="id" header="ID" style="width: 20%">
                    <template #body="{ data }">
                        <Badge :value="`#${data.id}`" severity="info"/>
                    </template>
                </Column>
                <Column field="name" header="Nazwa" style="width: 60%"/>
                <Column header="Akcje" style="width: 20%">
                    <template #body="{ data }">
                        <Link :href="route('base-npcs.show', {baseNpc: data.id})">
                            <Button icon="pi pi-eye" severity="info" size="small" label="Zobacz"/>
                        </Link>
                    </template>
                </Column>
            </DataTable>
        </div>

        <div class="card" v-else>
            <h5>Base NPC używające tego ciosu</h5>
            <p class="text-gray-600">Ten cios specjalny nie jest jeszcze przypisany do żadnego Base NPC.</p>
        </div>

    </AppLayout>
</template>

<style scoped>

</style>
