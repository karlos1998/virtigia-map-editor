<script setup lang="ts">

import AppLayout from "@/layout/AppLayout.vue";
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import {BaseItemResource} from "@/Resources/BaseItem.resource";
import { Link } from '@inertiajs/vue3';
import {route} from "ziggy-js";

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
                        <div class="flex items-center gap-2">
                            <Link :href="route('base-items.create')">
                                <Button
                                    icon="pi pi-plus"
                                    label="Nowy przedmiot"
                                    severity="success"
                                />
                            </Link>
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

                <AdvanceColumn field="rarity" header="Rzadkość">
                    <template #body="{ data }: Data">
                        {{ data.rarity }}
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
                            value="W użyciu"
                            severity="success"
                        />
                        <Tag
                            v-else
                            value="Nie używany"
                            severity="info"
                        />
                    </template>
                </AdvanceColumn>

                <AdvanceColumn header="Gdzie zdobyć" style="min-width: 28rem;">
                    <template #body="{ data }: Data">
                        <div v-if="data.usage_sources.length" class="flex flex-col gap-2">
                            <div
                                v-for="source in data.usage_sources.slice(0, 3)"
                                :key="`${data.id}-${source.type}-${source.shop?.id ?? 'no-shop'}-${source.npc?.id ?? 'no-npc'}-${source.location?.map_id ?? 'no-map'}-${source.location?.x ?? 'x'}-${source.location?.y ?? 'y'}`"
                                class="flex items-center gap-3 rounded border border-surface-200 px-3 py-2 dark:border-surface-700"
                            >
                                <Link
                                    v-if="source.npc?.src"
                                    :href="route('base-npcs.show', source.npc.id)"
                                >
                                    <img
                                        :src="source.npc.src"
                                        :alt="source.npc.name"
                                        class="h-10 w-10 rounded object-cover"
                                    />
                                </Link>

                                <div class="flex flex-col gap-1 text-sm">
                                    <div class="font-medium">
                                        <span v-if="source.location">
                                            {{ source.location.label }}
                                        </span>
                                        <Link
                                            v-else-if="source.shop"
                                            :href="route('shops.show', source.shop.id)"
                                            class="font-medium text-primary no-underline hover:underline"
                                        >
                                            Shop #{{ source.shop.id }} bez przypisanej lokalizacji NPC
                                        </Link>
                                        <span v-else>
                                            Brak lokalizacji
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-1 text-surface-500 dark:text-surface-400">
                                        <Link
                                            v-if="source.shop"
                                            :href="route('shops.show', source.shop.id)"
                                            class="no-underline hover:underline"
                                        >
                                            {{ source.shop.name }} (#{{ source.shop.id }})
                                        </Link>

                                        <span v-if="source.shop && source.npc">•</span>

                                        <Link
                                            v-if="source.npc"
                                            :href="route('base-npcs.show', source.npc.id)"
                                            class="no-underline hover:underline"
                                        >
                                            {{ source.type === 'loot' ? 'Loot: ' : '' }}{{ source.npc.name }}
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="data.usage_source_count > 3"
                                class="text-xs text-surface-500 dark:text-surface-400"
                            >
                                +{{ data.usage_source_count - 3 }} kolejnych lokalizacji
                            </div>
                        </div>

                        <span v-else class="text-sm text-surface-500 dark:text-surface-400">
                            -
                        </span>
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
