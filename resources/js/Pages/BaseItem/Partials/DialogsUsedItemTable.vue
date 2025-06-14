<script setup lang="ts">
import {DialogResource} from "../../../Resources/Dialog.resource";
import {route} from "ziggy-js";
import {Link} from "@inertiajs/vue3";

defineProps<{
    dialogs: DialogResource[]
}>()

type Data = {
    data: DialogResource
}
</script>

<template>
    <DataTable :value="dialogs" tableStyle="min-width: 50rem">
        <template #header>
            Lista dialogów, w których używany jest ten przedmiot
        </template>

        <template #empty>
            Brak powiązań
        </template>

        <Column field="id" header="Id"></Column>

        <Column field="name" header="Nazwa"></Column>

        <Column field="npcs_count" header="Liczba NPC"></Column>

        <Column header="Action" style="width: 20%">
            <template #body="{data}">
                <Link
                    :href="route('dialogs.show', {dialog: data.id})"
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
