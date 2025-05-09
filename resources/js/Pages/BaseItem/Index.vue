<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {BaseItemResource} from "@/Resources/BaseItem.resource";
import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";
import RockAdapter from "../../RockTip/components/rockAdapter.vue";

type Data = {
    data: BaseItemResource
}
</script>

<template>
    <AppLayout>
        <div class="card">
            <AdvanceTable
                prop-name="items"
            >
                <AdvanceColumn field="id" header="ID" style="width: 5%" />

                <template #header="{ globalFilterValue, globalFilterUpdated }">

                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <h4 class="m-0">Lista Bazowych Przedmiotów</h4>
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

                <AdvanceColumn header="Podgląd">
                    <template #body="{ data }: Data">
                        <!-- todo ! tip nie dziala ;c -->
                        <img
                            class="h-12 w-12 object-cover"
                            :src="data.src"
                            v-tip.item.top.show-id="data"
                            alt=""
                        />
                    </template>
                </AdvanceColumn>

<!--                <AdvanceColumn field="src" header="Grafika">-->
<!--                    <template #body="{ data }: Data">-->
<!--                        <img alt="" :src="`https://s3.letscode.it/virtigia-assets/img/${data.src}`" />-->
<!--                    </template>-->
<!--                </AdvanceColumn>-->

                <AdvanceColumn field="name" header="Name" />

                <AdvanceColumn field="category_name" header="Kategoria">
                    <template #body="{ data }: Data">
                        {{data.category_name }}
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="currency_name" header="Waluta">
                    <template #body="{ data }: Data">
                        {{data.currency_name }}
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="need_professions" header="Wymagana profesja">
                    <template #body="{ data }: Data">
                        {{data.need_professions.join(', ') || 'Dowolna' }}
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="need_level" header="Wymagany poziom">
                    <template #body="{ data }: Data">
                        {{data.need_level || '-' }}
                    </template>
                </AdvanceColumn>

                <AdvanceColumn field="in_use" header="W użyciu">
                    <template #body="{ data }: Data">
                        <Tag
                            v-if="data.in_use"
                            value="W użuciu"
                            severity="success"
                        />
                        <Tag
                            v-else
                            value="Nie używany"
                            severity="info"
                        />
                    </template>
                </AdvanceColumn>

                <Column header="Action" >
                    <template #body="slotProps">
                        <div style="white-space: nowrap">
                            <span class="p-buttonset">
                                <Link :href="route('base-items.show', slotProps.data.id)">
                                    <Button
                                        class="px-2"
                                        icon="pi pi-eye"
                                        label="Podgląd"
                                    />
                                </Link>
                            </span>
                        </div>
                    </template>
                </Column>


                <AdvanceColumn field="src" header="Link do grafiki">
                    <template #body="{ data }: Data">
                        {{data.src}}
                    </template>
                </AdvanceColumn>

            </AdvanceTable>
        </div>
    </AppLayout>

</template>

<style scoped>
</style>
