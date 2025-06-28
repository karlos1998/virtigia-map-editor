<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { route } from "ziggy-js";

// PrimeVue Components
import Card from 'primevue/card';
import Avatar from 'primevue/avatar';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import Panel from 'primevue/panel';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Chart from 'primevue/chart';
import Button from 'primevue/button';
import Timeline from 'primevue/timeline';
import Badge from 'primevue/badge';

// Props
const props = defineProps({
    user: Object,
    recentActivities: Array,
    activityByModelType: Array,
    activityByEventType: Array,
    activityByDate: Array,
    activityByHour: Array,
    workTimeStats: Object,
    totalActivities: Number
});

// Format roles for display
const formatRoles = (roles) => {
    if (!roles || !Array.isArray(roles)) return [];

    // Map each role to its display_name or name property if it's an object
    return roles.map(role => {
        if (typeof role === 'string') {
            return role;
        } else if (role && typeof role === 'object') {
            return role.display_name || role.name || 'Unknown Role';
        }
        return 'Unknown Role';
    });
};

// Get severity for role tag
const getRoleSeverity = (role) => {
    const severities = {
        admin: 'danger',
        administrator: 'danger',
        moderator: 'warning',
        editor: 'info',
        user: 'success'
    };

    // Check if role is a string, otherwise try to extract the name or use a default
    const roleName = typeof role === 'string'
        ? role.toLowerCase()
        : (role && typeof role === 'object' && role.name
            ? role.name.toLowerCase()
            : 'user');

    return severities[roleName] || 'info';
};

// Format date
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};

// Format date (short)
const formatDateShort = (dateString) => {
    return new Date(dateString).toLocaleDateString();
};

// Format subject type
const formatSubjectType = (subjectType) => {
    if (!subjectType) return '';
    const parts = subjectType.split('\\');
    return parts[parts.length - 1];
};

// Get event tag severity
const getEventSeverity = (event) => {
    const severities = {
        created: 'success',
        updated: 'info',
        deleted: 'danger'
    };

    return severities[event] || 'secondary';
};

// Chart data for activity by model type
const activityByModelChartData = computed(() => {
    return {
        labels: props.activityByModelType?.map(stat => stat.model) || [],
        datasets: [
            {
                data: props.activityByModelType?.map(stat => stat.count) || [],
                backgroundColor: [
                    '#42A5F5', '#66BB6A', '#FFA726', '#26C6DA', '#7E57C2',
                    '#EC407A', '#AB47BC', '#5C6BC0', '#29B6F6', '#26A69A'
                ]
            }
        ]
    };
});

// Chart data for activity by event type
const activityByEventChartData = computed(() => {
    return {
        labels: props.activityByEventType?.map(stat => stat.event) || [],
        datasets: [
            {
                data: props.activityByEventType?.map(stat => stat.count) || [],
                backgroundColor: [
                    '#66BB6A', '#42A5F5', '#FFA726', '#EC407A', '#7E57C2'
                ]
            }
        ]
    };
});

// Chart data for activity by hour
const activityByHourChartData = computed(() => {
    // Create an array of 24 hours (0-23) with zero counts
    const hours = Array.from({ length: 24 }, (_, i) => i);
    const counts = Array(24).fill(0);

    // Fill in the actual counts
    props.activityByHour?.forEach(item => {
        counts[item.hour] = item.count;
    });

    return {
        labels: hours.map(hour => `${hour}:00`),
        datasets: [
            {
                label: 'Activity Count',
                data: counts,
                backgroundColor: '#42A5F5',
                borderColor: '#42A5F5',
                borderWidth: 1
            }
        ]
    };
});

// Chart data for daily activity
const dailyActivityChartData = computed(() => {
    if (!props.workTimeStats?.daily_stats) return null;

    return {
        labels: props.workTimeStats.daily_stats.map(day => day.date),
        datasets: [
            {
                type: 'bar',
                label: 'Activities',
                backgroundColor: '#42A5F5',
                data: props.workTimeStats.daily_stats.map(day => day.count)
            },
            {
                type: 'line',
                label: 'Hours',
                borderColor: '#FFA726',
                pointBackgroundColor: '#FFA726',
                backgroundColor: 'rgba(255, 167, 38, 0.2)',
                data: props.workTimeStats.daily_stats.map(day => day.hours),
                fill: true
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

// Bar chart options
const barChartOptions = {
    plugins: {
        legend: {
            position: 'bottom'
        }
    },
    scales: {
        y: {
            beginAtZero: true
        }
    },
    responsive: true,
    maintainAspectRatio: false
};

// Mixed chart options
const mixedChartOptions = {
    plugins: {
        legend: {
            position: 'bottom'
        },
        tooltip: {
            mode: 'index',
            intersect: false
        }
    },
    scales: {
        y: {
            beginAtZero: true
        }
    },
    responsive: true,
    maintainAspectRatio: false
};
</script>

<template>
    <AppLayout :title="`User: ${user.name}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    User: {{ user.name }}
                </h2>
                <Link :href="route('users.index')">
                    <Button label="Back to Users" icon="pi pi-arrow-left" text />
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- User Header Card -->
                <Card class="mb-6">
                    <template #content>
                        <div class="flex flex-col md:flex-row items-center gap-6 p-4">
                            <!-- Avatar -->
                            <div>
                                <div
                                    v-if="user.src"
                                    class="rounded-full border-4 border-white shadow-lg overflow-hidden"
                                >
                                    <div
                                        class="w-32 h-32"
                                        :style="{
                                            backgroundImage: `url(${user.src})`,
                                            backgroundPosition: '0 0',
                                            width: '32px',
                                                    height: '32px',
                                            backgroundRepeat: 'no-repeat'
                                        }"
                                    ></div>
                                </div>
                                <Avatar
                                    v-else
                                    :label="user.name ? user.name.charAt(0).toUpperCase() : 'U'"
                                    size="xlarge"
                                    shape="circle"
                                    class="w-32 h-32 border-4 border-white shadow-lg bg-primary-500 text-white"
                                />
                            </div>

                            <!-- User Info -->
                            <div class="text-center md:text-left flex-1">
                                <h1 class="text-2xl font-bold text-gray-900">{{ user.name }}</h1>
                                <p class="text-gray-600">{{ user.email }}</p>

                                <!-- Roles -->
                                <div class="mt-2 flex flex-wrap gap-2 justify-center md:justify-start">
                                    <Tag
                                        v-for="(role, index) in formatRoles(user.roles)"
                                        :key="index"
                                        :value="role"
                                        :severity="getRoleSeverity(user.roles[index])"
                                    />
                                </div>
                            </div>

                            <!-- Activity Summary -->
                            <div class="text-center bg-gray-50 p-4 rounded-lg border border-gray-100">
                                <h3 class="text-lg font-semibold mb-2">Activity Summary</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="text-3xl font-bold text-primary-500">{{ totalActivities }}</div>
                                        <div class="text-sm text-gray-500">Total Activities</div>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-bold text-primary-500">{{ workTimeStats.total_days }}</div>
                                        <div class="text-sm text-gray-500">Days Active</div>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-bold text-primary-500">{{ workTimeStats.total_hours }}</div>
                                        <div class="text-sm text-gray-500">Est. Hours</div>
                                    </div>
                                    <div>
                                        <div class="text-3xl font-bold text-primary-500">{{ workTimeStats.avg_hours_per_day }}</div>
                                        <div class="text-sm text-gray-500">Avg Hours/Day</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Tabs for User Information and Activity -->
                <TabView>
                    <!-- Activity Overview Tab -->
                    <TabPanel header="Activity Overview">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Activity by Model Type Chart -->
                            <Panel header="What They Work On" toggleable>
                                <div class="h-80">
                                    <Chart type="pie" :data="activityByModelChartData" :options="chartOptions" />
                                </div>
                            </Panel>

                            <!-- Activity by Event Type Chart -->
                            <Panel header="Types of Activities" toggleable>
                                <div class="h-80">
                                    <Chart type="doughnut" :data="activityByEventChartData" :options="chartOptions" />
                                </div>
                            </Panel>
                        </div>

                        <!-- Recent Activity Table -->
                        <Panel header="Recent Activities" toggleable>
                            <DataTable :value="recentActivities" stripedRows paginator :rows="10"
                                      tableStyle="min-width: 50rem" class="p-datatable-sm">
                                <Column field="id" header="ID" style="width: 5%"></Column>
                                <Column field="event" header="Event" style="width: 15%">
                                    <template #body="{ data }">
                                        <Tag :value="data.event" :severity="getEventSeverity(data.event)" />
                                    </template>
                                </Column>
                                <Column field="subject_type" header="Model" style="width: 20%">
                                    <template #body="{ data }">
                                        {{ formatSubjectType(data.subject_type) }}
                                    </template>
                                </Column>
                                <Column field="subject_id" header="Subject ID" style="width: 10%"></Column>
                                <Column field="created_at" header="Date" style="width: 20%">
                                    <template #body="{ data }">
                                        {{ formatDate(data.created_at) }}
                                    </template>
                                </Column>
                            </DataTable>
                        </Panel>
                    </TabPanel>

                    <!-- Work Patterns Tab -->
                    <TabPanel header="Work Patterns">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Activity by Hour Chart -->
                            <Panel header="When They Usually Work" toggleable>
                                <div class="h-80">
                                    <Chart type="bar" :data="activityByHourChartData" :options="barChartOptions" />
                                </div>
                                <div class="mt-4 text-center" v-if="workTimeStats.most_active_hour">
                                    <p class="text-sm text-gray-600">
                                        Most active hour: <span class="font-semibold">{{ workTimeStats.most_active_hour.hour }}:00</span>
                                        with <span class="font-semibold">{{ workTimeStats.most_active_hour.count }}</span> activities
                                    </p>
                                </div>
                            </Panel>

                            <!-- Daily Activity Chart -->
                            <Panel header="Daily Activity & Hours" toggleable>
                                <div class="h-80">
                                    <Chart type="bar" :data="dailyActivityChartData" :options="mixedChartOptions" />
                                </div>
                                <div class="mt-4 text-center" v-if="workTimeStats.most_active_day">
                                    <p class="text-sm text-gray-600">
                                        Most active day: <span class="font-semibold">{{ workTimeStats.most_active_day.date }}</span>
                                        with <span class="font-semibold">{{ workTimeStats.most_active_day.count }}</span> activities
                                    </p>
                                </div>
                            </Panel>
                        </div>

                        <!-- Work Time Statistics -->
                        <Panel header="Work Time Analysis" toggleable>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <Card>
                                    <template #title>
                                        <div class="flex items-center">
                                            <i class="pi pi-calendar mr-2 text-primary-500"></i>
                                            <span>Total Days Active</span>
                                        </div>
                                    </template>
                                    <template #content>
                                        <div class="text-center">
                                            <div class="text-4xl font-bold text-primary-500 mb-2">{{ workTimeStats.total_days }}</div>
                                            <p class="text-gray-600">days of activity recorded</p>
                                        </div>
                                    </template>
                                </Card>

                                <Card>
                                    <template #title>
                                        <div class="flex items-center">
                                            <i class="pi pi-clock mr-2 text-primary-500"></i>
                                            <span>Estimated Work Time</span>
                                        </div>
                                    </template>
                                    <template #content>
                                        <div class="text-center">
                                            <div class="text-4xl font-bold text-primary-500 mb-2">{{ workTimeStats.total_hours }}</div>
                                            <p class="text-gray-600">hours of work estimated</p>
                                        </div>
                                    </template>
                                </Card>

                                <Card>
                                    <template #title>
                                        <div class="flex items-center">
                                            <i class="pi pi-chart-line mr-2 text-primary-500"></i>
                                            <span>Average Per Day</span>
                                        </div>
                                    </template>
                                    <template #content>
                                        <div class="text-center">
                                            <div class="text-4xl font-bold text-primary-500 mb-2">{{ workTimeStats.avg_hours_per_day }}</div>
                                            <p class="text-gray-600">hours per active day</p>
                                        </div>
                                    </template>
                                </Card>
                            </div>

                            <Divider />

                            <h3 class="text-lg font-semibold mb-4">Daily Work Records</h3>
                            <DataTable :value="workTimeStats.daily_stats" stripedRows paginator :rows="5"
                                      tableStyle="min-width: 50rem" class="p-datatable-sm">
                                <Column field="date" header="Date" sortable>
                                    <template #body="{ data }">
                                        {{ formatDateShort(data.date) }}
                                    </template>
                                </Column>
                                <Column field="count" header="Activities" sortable>
                                    <template #body="{ data }">
                                        <Badge :value="data.count" severity="info" />
                                    </template>
                                </Column>
                                <Column field="hours" header="Estimated Hours" sortable>
                                    <template #body="{ data }">
                                        <div class="flex items-center">
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="bg-primary-500 h-2.5 rounded-full" :style="{ width: `${(data.hours / 12) * 100}%` }"></div>
                                            </div>
                                            <span class="ml-2">{{ data.hours }}</span>
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </Panel>
                    </TabPanel>

                    <!-- Activity Timeline Tab -->
                    <TabPanel header="Activity Timeline">
                        <Panel header="Recent Activity Timeline" toggleable>
                            <Timeline :value="activityByDate" class="w-full">
                                <template #content="slotProps">
                                    <Card class="mb-3">
                                        <template #title>
                                            <div class="flex justify-between items-center">
                                                <span>{{ formatDateShort(slotProps.item.date) }}</span>
                                                <Badge :value="slotProps.item.count" severity="info" />
                                            </div>
                                        </template>
                                        <template #content>
                                            <ul class="list-disc pl-5 space-y-2">
                                                <li v-for="activity in slotProps.item.activities.slice(0, 5)" :key="activity.id" class="text-sm">
                                                    <div class="flex items-center">
                                                        <Tag :value="activity.event" :severity="getEventSeverity(activity.event)" class="mr-2" />
                                                        <span>{{ formatSubjectType(activity.subject_type) }} #{{ activity.subject_id }}</span>
                                                        <span class="ml-auto text-gray-500 text-xs">{{ new Date(activity.created_at).toLocaleTimeString() }}</span>
                                                    </div>
                                                </li>
                                            </ul>
                                            <div v-if="slotProps.item.activities.length > 5" class="mt-2 text-center text-sm text-gray-500">
                                                + {{ slotProps.item.activities.length - 5 }} more activities
                                            </div>
                                        </template>
                                    </Card>
                                </template>
                                <template #opposite>
                                    <div class="flex flex-col items-center justify-center h-full">
                                        <span class="inline-flex justify-center items-center w-8 h-8 bg-primary-500 text-white rounded-full">
                                            <i class="pi pi-calendar"></i>
                                        </span>
                                    </div>
                                </template>
                            </Timeline>
                        </Panel>
                    </TabPanel>
                </TabView>
            </div>
        </div>
    </AppLayout>
</template>
