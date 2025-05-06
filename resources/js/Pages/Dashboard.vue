<script setup lang="ts">
import AppLayout from "../layout/AppLayout.vue";
import { ref, onMounted, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { route } from "ziggy-js";

// PrimeVue Components
import Message from 'primevue/message';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import Button from 'primevue/button';
import Panel from 'primevue/panel';

// Props
const props = defineProps({
    mostActiveUsers: Array,
    recentActivities: Array,
    activityByModelType: Array,
    baseItemCount: Number,
    baseItemsByCategory: Array,
    baseNpcCount: Number,
    baseNpcsByProfession: Array
});

// Chart data
const userActivityChartData = computed(() => {
    return {
        labels: props.mostActiveUsers?.map(user => user.name) || [],
        datasets: [
            {
                label: 'Activity Count',
                data: props.mostActiveUsers?.map(user => user.total_activities) || [],
                backgroundColor: ['#42A5F5', '#66BB6A', '#FFA726', '#26C6DA', '#7E57C2'],
                borderWidth: 0
            }
        ]
    };
});

const modelTypeChartData = computed(() => {
    return {
        labels: props.activityByModelType?.map(item => item.model) || [],
        datasets: [
            {
                label: 'Activity Count',
                data: props.activityByModelType?.map(item => item.count) || [],
                backgroundColor: ['#EC407A', '#AB47BC', '#5C6BC0', '#29B6F6', '#26A69A'],
                borderWidth: 0
            }
        ]
    };
});

const itemCategoryChartData = computed(() => {
    return {
        labels: props.baseItemsByCategory?.map(item => item.category) || [],
        datasets: [
            {
                label: 'Item Count',
                data: props.baseItemsByCategory?.map(item => item.count) || [],
                backgroundColor: ['#78909C', '#FF7043', '#FFCA28', '#26A69A', '#5C6BC0', '#EC407A'],
                borderWidth: 0
            }
        ]
    };
});

const npcProfessionChartData = computed(() => {
    return {
        labels: props.baseNpcsByProfession?.map(npc => npc.profession) || [],
        datasets: [
            {
                label: 'NPC Count',
                data: props.baseNpcsByProfession?.map(npc => npc.count) || [],
                backgroundColor: ['#26A69A', '#5C6BC0', '#EC407A', '#FFCA28', '#FF7043', '#78909C'],
                borderWidth: 0
            }
        ]
    };
});

// Chart options
const chartOptions = {
    plugins: {
        legend: {
            position: 'bottom'
        }
    },
    responsive: true,
    maintainAspectRatio: false
};

// Format date
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};

// Format subject type
const formatSubjectType = (subjectType) => {
    if (!subjectType) return '';
    const parts = subjectType.split('\\');
    return parts[parts.length - 1];
};

// Get event tag severity
const getEventSeverity = (event) => {
    switch (event) {
        case 'created':
            return 'success';
        case 'updated':
            return 'info';
        case 'deleted':
            return 'danger';
        case 'attach-base-npc-loots':
        case 'attach-to-base-npc-loots':
            return 'success';
        case 'detach-base-npc-loots':
        case 'detach-from-base-npc-loots':
            return 'danger';
        default:
            return 'warning';
    }
};

// Get event display name
const getEventDisplayName = (event) => {
    switch (event) {
        case 'created':
            return 'Utworzono';
        case 'updated':
            return 'Zaktualizowano';
        case 'deleted':
            return 'Usunięto';
        case 'attach-base-npc-loots':
        case 'attach-to-base-npc-loots':
            return 'Dodano loot';
        case 'detach-base-npc-loots':
        case 'detach-from-base-npc-loots':
            return 'Usunięto loot';
        default:
            return event;
    }
};

// Get subject name from properties
const getSubjectName = (properties) => {
    if (!properties) return '';

    if (properties.base_npc && properties.base_npc.name) {
        return properties.base_npc.name;
    }

    if (properties.base_item && properties.base_item.name) {
        return properties.base_item.name;
    }

    if (properties.attributes && properties.attributes.name) {
        return properties.attributes.name;
    }

    if (properties.attributes && properties.attributes.id) {
        return `#${properties.attributes.id}`;
    }

    return '';
};

// Get subject ID
const getSubjectId = (data) => {
    if (!data) return null;

    if (data.subject_id) {
        return data.subject_id;
    }

    if (data.properties) {
        if (data.properties.base_npc && data.properties.base_npc.id) {
            return data.properties.base_npc.id;
        }

        if (data.properties.base_item && data.properties.base_item.id) {
            return data.properties.base_item.id;
        }

        if (data.properties.attributes && data.properties.attributes.id) {
            return data.properties.attributes.id;
        }
    }

    return null;
};

// Get route for subject
const getSubjectRoute = (data) => {
    if (!data || !data.subject_type) return null;

    const subjectType = formatSubjectType(data.subject_type);
    const subjectId = getSubjectId(data);

    if (!subjectId) return null;

    if (subjectType === 'BaseNpc') {
        return route('base-npcs.show', subjectId);
    } else if (subjectType === 'BaseItem') {
        return route('base-items.show', subjectId);
    } else if (subjectType === 'Npc') {
        return route('npcs.show', subjectId);
    }

    return null;
};

// Get event description
const getEventDescription = (data) => {
    if (!data) return '';

    if (data.event === 'attach-base-npc-loots' || data.event === 'attach-to-base-npc-loots') {
        const itemName = data.properties?.base_item?.name || 'przedmiot';
        const npcName = data.properties?.base_npc?.name || 'NPC';
        return `Dodano ${itemName} do łupów ${npcName}`;
    }

    if (data.event === 'detach-base-npc-loots' || data.event === 'detach-from-base-npc-loots') {
        const itemName = data.properties?.base_item?.name || 'przedmiot';
        const npcName = data.properties?.base_npc?.name || 'NPC';
        return `Usunięto ${itemName} z łupów ${npcName}`;
    }

    if (data.event === 'created') {
        return `Utworzono ${formatSubjectType(data.subject_type)} ${getSubjectName(data.properties)}`;
    }

    if (data.event === 'updated') {
        return `Zaktualizowano ${formatSubjectType(data.subject_type)} ${getSubjectName(data.properties)}`;
    }

    if (data.event === 'deleted') {
        return `Usunięto ${formatSubjectType(data.subject_type)} ${getSubjectName(data.properties)}`;
    }

    return data.description || data.event;
};
</script>

<template>
    <AppLayout>
        <div class="grid">
            <!-- Welcome Message -->
            <div class="col-12">
                <Message severity="success" class="mb-4">
                    <div>Witaj w Edytorze Map, przybyszu ! :D</div>
                    <div>Bardzo się cieszę, że tu jesteś by pomoć nam odbudowywać świat Margatronu :)</div>
                    <div>Poniżej znajdziesz statystyki i informacje o aktywności w systemie.</div>
                </Message>
            </div>

            <!-- Summary Statistics Cards -->
            <div class="col-12 md:col-6 lg:col-3">
                <Card class="mb-4 shadow-2 h-full">
                    <template #title>
                        <div class="flex align-items-center">
                            <i class="pi pi-users text-primary mr-2" style="font-size: 1.5rem"></i>
                            <span>Najbardziej aktywni</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="text-3xl font-bold mb-3">{{ mostActiveUsers?.length || 0 }}</div>
                        <p class="text-sm text-gray-600 mb-0">Użytkownicy z największą liczbą działań</p>
                    </template>
                </Card>
            </div>

            <div class="col-12 md:col-6 lg:col-3">
                <Card class="mb-4 shadow-2 h-full">
                    <template #title>
                        <div class="flex align-items-center">
                            <i class="pi pi-inbox text-blue-500 mr-2" style="font-size: 1.5rem"></i>
                            <span>Przedmioty</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="text-3xl font-bold mb-3">{{ baseItemCount || 0 }}</div>
                        <p class="text-sm text-gray-600 mb-0">Łączna liczba przedmiotów w systemie</p>
                    </template>
                </Card>
            </div>

            <div class="col-12 md:col-6 lg:col-3">
                <Card class="mb-4 shadow-2 h-full">
                    <template #title>
                        <div class="flex align-items-center">
                            <i class="pi pi-user text-green-500 mr-2" style="font-size: 1.5rem"></i>
                            <span>NPC</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="text-3xl font-bold mb-3">{{ baseNpcCount || 0 }}</div>
                        <p class="text-sm text-gray-600 mb-0">Łączna liczba NPC w systemie</p>
                    </template>
                </Card>
            </div>

            <div class="col-12 md:col-6 lg:col-3">
                <Card class="mb-4 shadow-2 h-full">
                    <template #title>
                        <div class="flex align-items-center">
                            <i class="pi pi-history text-orange-500 mr-2" style="font-size: 1.5rem"></i>
                            <span>Ostatnie aktywności</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="text-3xl font-bold mb-3">{{ recentActivities?.length || 0 }}</div>
                        <p class="text-sm text-gray-600 mb-0">Liczba ostatnich działań w systemie</p>
                    </template>
                </Card>
            </div>

            <!-- Charts Section -->
            <div class="col-12 md:col-6">
                <Card class="mb-4 shadow-2">
                    <template #title>
                        <div class="flex align-items-center">
                            <i class="pi pi-chart-bar text-primary mr-2"></i>
                            <span>Najbardziej aktywni użytkownicy</span>
                        </div>
                    </template>
                    <template #content>
                        <div style="height: 300px">
                            <Chart type="bar" :data="userActivityChartData" :options="chartOptions" />
                        </div>
                    </template>
                </Card>
            </div>

            <div class="col-12 md:col-6">
                <Card class="mb-4 shadow-2">
                    <template #title>
                        <div class="flex align-items-center">
                            <i class="pi pi-chart-pie text-blue-500 mr-2"></i>
                            <span>Aktywność według typu modelu</span>
                        </div>
                    </template>
                    <template #content>
                        <div style="height: 300px">
                            <Chart type="pie" :data="modelTypeChartData" :options="chartOptions" />
                        </div>
                    </template>
                </Card>
            </div>

            <div class="col-12 md:col-6">
                <Card class="mb-4 shadow-2">
                    <template #title>
                        <div class="flex align-items-center">
                            <i class="pi pi-chart-pie text-green-500 mr-2"></i>
                            <span>Przedmioty według kategorii</span>
                        </div>
                    </template>
                    <template #content>
                        <div style="height: 300px">
                            <Chart type="doughnut" :data="itemCategoryChartData" :options="chartOptions" />
                        </div>
                    </template>
                </Card>
            </div>

            <div class="col-12 md:col-6">
                <Card class="mb-4 shadow-2">
                    <template #title>
                        <div class="flex align-items-center">
                            <i class="pi pi-chart-pie text-orange-500 mr-2"></i>
                            <span>NPC według profesji</span>
                        </div>
                    </template>
                    <template #content>
                        <div style="height: 300px">
                            <Chart type="doughnut" :data="npcProfessionChartData" :options="chartOptions" />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Recent Activity Table -->
            <div class="col-12">
                <Card class="mb-4 shadow-2">
                    <template #title>
                        <div class="flex align-items-center">
                            <i class="pi pi-history text-primary mr-2"></i>
                            <span>Ostatnie aktywności</span>
                        </div>
                    </template>
                    <template #content>
                        <DataTable :value="recentActivities" stripedRows paginator :rows="5"
                                  :rowsPerPageOptions="[5, 10, 20]" tableStyle="min-width: 50rem">
                            <Column field="event" header="Zdarzenie" style="width: 15%">
                                <template #body="{ data }">
                                    <Tag :severity="getEventSeverity(data.event)" :value="getEventDisplayName(data.event)" />
                                </template>
                            </Column>
                            <Column header="Opis" style="width: 35%">
                                <template #body="{ data }">
                                    <div>{{ getEventDescription(data) }}</div>
                                </template>
                            </Column>
                            <Column header="Obiekt" style="width: 20%">
                                <template #body="{ data }">
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-500 mr-2">{{ formatSubjectType(data.subject_type) }}</span>
                                        <Link v-if="getSubjectRoute(data)" :href="getSubjectRoute(data)" class="text-blue-500 hover:underline">
                                            {{ getSubjectName(data.properties) || `#${getSubjectId(data)}` }}
                                        </Link>
                                        <span v-else>{{ getSubjectName(data.properties) || `#${getSubjectId(data)}` }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="causer_name" header="Użytkownik" style="width: 15%">
                                <template #body="{ data }">
                                    <div class="flex items-center">
                                        <Avatar v-if="data.causer_name" icon="pi pi-user" shape="circle" size="small" class="mr-2" />
                                        <span>{{ data.causer_name || 'System' }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="created_at" header="Data" style="width: 15%">
                                <template #body="{ data }">
                                    {{ formatDate(data.created_at) }}
                                </template>
                            </Column>
                        </DataTable>
                        <div class="flex justify-content-end mt-3">
                            <Link :href="route('activity-logs.index')">
                                <Button label="Zobacz wszystkie logi" icon="pi pi-external-link" text />
                            </Link>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Most Active Users Table -->
            <div class="col-12 md:col-6">
                <Card class="mb-4 shadow-2">
                    <template #title>
                        <div class="flex align-items-center">
                            <i class="pi pi-users text-primary mr-2"></i>
                            <span>Najbardziej aktywni użytkownicy</span>
                        </div>
                    </template>
                    <template #content>
                        <DataTable :value="mostActiveUsers" stripedRows tableStyle="min-width: 30rem">
                            <Column field="name" header="Użytkownik">
                                <template #body="{ data }">
                                    <div class="flex items-center">
                                        <Avatar icon="pi pi-user" shape="circle" size="small" class="mr-2" />
                                        <span>{{ data.name }}</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="total_activities" header="Liczba aktywności">
                                <template #body="{ data }">
                                    <Tag :value="data.total_activities.toString()" severity="info" />
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </div>

            <!-- Quick Links -->
            <div class="col-12 md:col-6">
                <Card class="mb-4 shadow-2">
                    <template #title>
                        <div class="flex align-items-center">
                            <i class="pi pi-link text-primary mr-2"></i>
                            <span>Szybkie linki</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="grid">
                            <div class="col-6 mb-3">
                                <Link :href="route('base-items.index')" class="no-underline">
                                    <Button label="Przedmioty" icon="pi pi-inbox" class="p-button-outlined w-full" />
                                </Link>
                            </div>
                            <div class="col-6 mb-3">
                                <Link :href="route('base-npcs.index')" class="no-underline">
                                    <Button label="NPC" icon="pi pi-user" class="p-button-outlined w-full" />
                                </Link>
                            </div>
                            <div class="col-6 mb-3">
                                <Link :href="route('maps.index')" class="no-underline">
                                    <Button label="Mapy" icon="pi pi-map" class="p-button-outlined w-full" />
                                </Link>
                            </div>
                            <div class="col-6 mb-3">
                                <Link :href="route('activity-logs.index')" class="no-underline">
                                    <Button label="Logi" icon="pi pi-history" class="p-button-outlined w-full" />
                                </Link>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.grid {
    display: flex;
    flex-wrap: wrap;
    margin-right: -0.5rem;
    margin-left: -0.5rem;
    margin-top: -0.5rem;
}

.col-12 {
    flex: 0 0 100%;
    padding: 0.5rem;
}

@media screen and (min-width: 768px) {
    .md\:col-6 {
        flex: 0 0 50%;
    }
}

@media screen and (min-width: 992px) {
    .lg\:col-3 {
        flex: 0 0 25%;
    }
}

.h-full {
    height: 100%;
}

.shadow-2 {
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.items-center {
    align-items: center;
}

.justify-content-end {
    justify-content: flex-end;
}

.no-underline {
    text-decoration: none;
}
</style>
