<script setup lang="ts">
import {BaseNpcResource} from "../../../Resources/BaseNpc.resource";
import {route} from "ziggy-js";
import {Link} from "@inertiajs/vue3";

defineProps<{
    baseNpcs: BaseNpcResource[]
}>()

type Data = {
    data: BaseNpcResource
}
</script>

<template>
    <DataTable :value="baseNpcs" tableStyle="min-width: 50rem">
        <template #header>
            Lista powiązanych bazowych NPC, z których można zdobyć przedmiot
        </template>

        <template #empty>
            Brak powiązań
        </template>

        <Column field="id" header="Id"></Column>

        <Column>
            <template #body="{ data }: Data">

                <img
                    v-tip.npc="data"
                    :src="data.src"
                />
            </template>
        </Column>

        <Column field="name" header="Nazwa"></Column>

        <Column field="lvl" header="Lvl"></Column>

        <Column header="Action" style="width: 20%">
            <template #body="{data}">
                <Link
                    :href="route('base-npcs.show', {baseNpc: data.id})"
                >
                    <Button
                        class="px-2"
                        icon="pi pi-eye"
                        label="Podgląd"
                    />
                </Link>
            </template>
        </Column>

    </DataTable>
</template>
