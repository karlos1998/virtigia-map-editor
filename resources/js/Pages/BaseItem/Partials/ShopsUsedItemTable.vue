<script setup lang="ts">
import {BaseNpcResource} from "../../../Resources/BaseNpc.resource";
import {route} from "ziggy-js";
import {Link} from "@inertiajs/vue3";
import {ShopResource} from "../../../Resources/Shop.resource";

defineProps<{
    shops: ShopResource[]
}>()

type Data = {
    data: ShopResource
}
</script>

<template>
    <DataTable :value="shops" tableStyle="min-width: 50rem">
        <template #header>
            Lista sklepów, w których można kupić ten przedmiot
        </template>

        <template #empty>
            Brak powiązań
        </template>

        <Column field="id" header="Id"></Column>

        <Column field="name" header="Nazwa"></Column>

        <Column header="Action" style="width: 20%">
            <template #body="{data}">
                <Link
                    :href="route('shops.show', {shop: data.id})"
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
