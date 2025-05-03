<script setup lang="ts">

import {BaseItemResource, BaseItemWithRelations} from "../../Resources/BaseItem.resource";
import AppLayout from "../../layout/AppLayout.vue";
import {route} from "ziggy-js";
import ItemHeader from "../../Components/ItemHeader.vue";

import { itemTip } from "../../old-createItemTip";
import rockAdapter from "../../RockTip/components/rockAdapter.vue";
import Item from "../../karlos3098-LaravelPrimevueTable/Components/Item.vue";
import BaseNpcsUsedItemTable from "./Partials/BaseNpcsUsedItemTable.vue";
import ShopsUsedItemTable from "./Partials/ShopsUsedItemTable.vue";
import {ref} from "vue";
import {Link, router} from "@inertiajs/vue3";
import {useConfirm, useToast} from "primevue";
import EditBaseItemSrcDialog from "./Components/EditBaseItemSrcDialog.vue";
import EditBaseItemDialog from "@/Pages/BaseItem/Components/EditBaseItemDialog.vue";
import BaseItemActivityLogsTable from "./Partials/BaseItemActivityLogsTable.vue";

const { baseItem, logs } = defineProps<{
    baseItem: BaseItemWithRelations,
    logs?: any[]
}>();

const confirm = useConfirm();
const toast = useToast();

const deleteConfirm = () => {
    confirm.require({
        message: 'Czy na pewno chcesz usunąc ten przedmiot?',
        header: 'Uwaga',
        icon: 'pi pi-info-circle',
        position: 'top',
        rejectProps: {
            label: 'Anuluj',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Usuń',
            severity: 'danger'
        },
        accept: () => {
            router.delete(route('base-items.delete', {baseItem}), {
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się usunąć przedmiotu', life: 3000 });
                },
                onSuccess: () => {
                    toast.add({ severity: 'info', summary: 'Usunieto', detail: 'Przedmiot został usunięty', life: 3000 });
                }
            })
        },
        reject: () => {

        }
    });
};


const copyConfirm = () => {
    confirm.require({
        message: 'Czy na pewno chcesz skopiować ten przedmiot?',
        header: 'Uwaga',
        icon: 'pi pi-info-circle',
        position: 'bottomleft',
        rejectProps: {
            label: 'Anuluj',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Kopiuj',
            severity: 'success'
        },
        accept: () => {
            router.post(route('base-items.copy', {baseItem}), {}, {
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się skopiować przedmiotu', life: 3000 });
                },
                onSuccess: () => {
                    toast.add({ severity: 'info', summary: 'Usunieto', detail: 'Przedmiot został Skopiowany', life: 3000 });
                }
            })
        },
        reject: () => {

        }
    });
};

const isEditBaseItemSrcDialogVisible = ref(false);

const isEditBaseItemDialogVisible = ref(false);
</script>

<template>

    <AppLayout>

        <ConfirmDialog/>

        <EditBaseItemSrcDialog :baseItem v-model:visible="isEditBaseItemSrcDialogVisible" />

        <EditBaseItemDialog v-model:visible="isEditBaseItemDialogVisible"  :baseItem />

        <ItemHeader
            :route-back="route('base-items.index')"
        >
            <template #header>
                #{{ baseItem.id }} - {{ baseItem.name }}
            </template>

            <template #right-buttons>
                <button
                    v-if="!baseItem.in_use"
                    class="px-4 py-2 text-white bg-red-500 hover:bg-red-600 rounded shadow mr-2"
                    @click="deleteConfirm"
                >
                    <i class="pi pi-trash mr-2"></i>
                    Usuń
                </button>

                <button
                    class="px-4 py-2 text-white bg-cyan-500 hover:bg-cyan-600 rounded shadow mr-2"
                    @click="isEditBaseItemDialogVisible = true"
                >
                    <i class="pi pi-folder mr-2"></i>
                    Edytuj
                </button>

                <button
                    class="px-4 py-2 text-white bg-yellow-500 hover:bg-yellow-600 rounded shadow mr-2"
                    @click="isEditBaseItemSrcDialogVisible = true"
                >
                    <i class="pi pi-folder mr-2"></i>
                    Edytuj grafikę
                </button>

                <button
                    class="px-4 py-2 text-white bg-lime-500 hover:bg-yellow-600 rounded shadow mr-2"
                    @click="copyConfirm"
                >
                    <i class="pi pi-link mr-2"></i>
                    Kopiuj przedmiot
                </button>

                <Link :href="route('base-items.edit', {baseItem})" type="button" class="font-medium px-4 py-2 text-white bg-purple-500 hover:bg-purple-600 rounded shadow mr-2">
                    Edytuj statystyki
                </Link>
            </template>

        </ItemHeader>

        <Message class="mb-6" v-if="baseItem.in_use">
            Przedmiot możesz usunąć jedynie gdy nie jest używany w grze.
        </Message>

        <div class="card">
            <div class="mb-4"><b>Nowe statystyki przedmiotu:</b></div>

            <img
                class="h-12 w-12 object-cover"
                :src="baseItem.src"
                v-tip.item.top.show-id="baseItem"
                alt=""
            />


        </div>


        <div class="card" >
            <Panel header="Debug JSON" toggleable collapsed>
                <pre>{{baseItem}}</pre>
            </Panel>
        </div>

        <div class="card" >

            <div class="mb-4"><b>Oryginalne statystyki przedmiotu z margonem:</b></div>
            <div v-html="itemTip({ ...baseItem, stat: baseItem.stats })" />

            <div class="mt-4">Grafika: {{ `${baseItem.src}` }}</div>
        </div>


        <div class="card" >
            <BaseNpcsUsedItemTable :base-npcs="baseItem.baseNpcs" />
        </div>

        <div class="card" >
            <ShopsUsedItemTable :shops="baseItem.shops" />
        </div>

        <BaseItemActivityLogsTable v-if="logs" :logs="logs" :base-item-id="baseItem.id" />

    </AppLayout>

</template>

<style>

</style>
