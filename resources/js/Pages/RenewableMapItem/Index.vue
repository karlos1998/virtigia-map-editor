<script setup lang="ts">
import AdvanceTable from '@/karlos3098-LaravelPrimevueTable/Components/AdvanceTable.vue';
import AdvanceColumn from '@/karlos3098-LaravelPrimevueTable/Components/AdvanceColumn.vue';
import AppLayout from "@/layout/AppLayout.vue";

const props = defineProps<{ items: any }>();
</script>

<template>
    <AppLayout>
        <div>
            <h1 class="text-3xl font-bold mb-4">Przedmioty odnawialne na wszystkich mapach</h1>
            <AdvanceTable prop-name="items">
                <AdvanceColumn field="id" header="ID"/>
                <AdvanceColumn header="Przedmiot" field="item">
                    <template #body="{ data }">
                        <img v-tip.item="data.item" :src="data.item.src" alt="Item"
                             style="height:32px;vertical-align:middle;margin-right:8px;"/>
                        <span>{{ data.item.name }}</span>
                    </template>
                </AdvanceColumn>
                <AdvanceColumn header="Lokalizacja" field="map">
                    <template #body="{ data }">
                        <span>[{{ data.map.id }}] {{ data.map.name }} ({{data.x}}, {{data.y}})</span>
                    </template>
                </AdvanceColumn>
                <AdvanceColumn field="respawn_time_seconds" header="Respawn (s)"/>
            </AdvanceTable>
        </div>
    </AppLayout>
</template>
