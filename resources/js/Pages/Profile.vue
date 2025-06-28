<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
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

// Props
const props = defineProps({
    userActivities: Array,
    activityStats: Array,
    eventStats: Array
});

// Get user data from Inertia page props
const user = computed(() => usePage().props.auth.user);

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
        labels: props.activityStats?.map(stat => stat.model) || [],
        datasets: [
            {
                data: props.activityStats?.map(stat => stat.count) || [],
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
        labels: props.eventStats?.map(stat => stat.event) || [],
        datasets: [
            {
                data: props.eventStats?.map(stat => stat.count) || [],
                backgroundColor: [
                    '#66BB6A', '#42A5F5', '#FFA726', '#EC407A', '#7E57C2'
                ]
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
    }
};
</script>

<template>
    <AppLayout title="My Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                My Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Profile Header Card -->
                <!-- Profile Card with smaller background image -->
                <div class="mb-6">
                    <div class="flex flex-col md:flex-row gap-6">
                        <!-- Left side: User info card -->
                        <Card class="flex-1">
                            <template #content>
                                <div class="flex flex-col md:flex-row items-center gap-6 p-4">
                                    <!-- Avatar - Fixed to show only 32x32 from top-left -->
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
                                        <p class="text-gray-600">{{ user.login }}</p>

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
                                </div>
                            </template>
                        </Card>

                        <!-- Right side: Background image card (smaller) -->
                        <Card v-if="user.forum_background_src" class="md:w-1/3 lg:w-1/4 overflow-hidden">
                            <template #header>
                                <div class="flex items-center">
                                    <i class="pi pi-image mr-2 text-primary-500"></i>
                                    <span class="font-medium">Background Image</span>
                                </div>
                            </template>
                            <template #content>
                                <div class="flex justify-center items-center p-4 bg-gray-50 rounded-lg">
                                    <div class="overflow-hidden border border-gray-200 rounded shadow-sm" style="width: 370px; height: 84px;">
                                        <img
                                            :src="user.forum_background_src"
                                            class="w-full h-full object-cover"
                                            alt="Profile Background"
                                        />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </div>

                <!-- Tabs for Profile Information and Activity -->
                <TabView>
                    <!-- Profile Information Tab -->
                    <TabPanel header="Profile Information">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Contact Information -->
                            <div class="md:col-span-1">
                                <Panel header="Contact Information" toggleable class="h-full">
                                    <div class="space-y-4">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-500">Email</h3>
                                            <p class="mt-1 text-sm text-gray-900">{{ user.email }}</p>
                                        </div>

                                        <Divider />

                                        <div>
                                            <h3 class="text-sm font-medium text-gray-500">User ID</h3>
                                            <p class="mt-1 text-sm text-gray-900">{{ user.id }}</p>
                                        </div>
                                    </div>
                                </Panel>
                            </div>

                            <!-- Permissions -->
                            <div class="md:col-span-2">
                                <Panel header="Permissions" toggleable class="h-full">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                        <div
                                            v-for="(permission, index) in user.permissions"
                                            :key="index"
                                            class="p-3 bg-gray-50 rounded-lg border border-gray-100"
                                        >
                                            <div class="flex items-center">
                                                <i class="pi pi-check-circle text-green-500 mr-2"></i>
                                                <span class="text-sm">{{ permission }}</span>
                                            </div>
                                        </div>

                                        <div v-if="!user.permissions || user.permissions.length === 0" class="col-span-full">
                                            <p class="text-gray-500 italic">No specific permissions assigned.</p>
                                        </div>
                                    </div>
                                </Panel>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- Activity Statistics Tab -->
                    <TabPanel header="Activity Statistics">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Activity by Model Type Chart -->
                            <div>
                                <Panel header="Activity by Model Type" toggleable>
                                    <div class="h-80">
                                        <Chart type="pie" :data="activityByModelChartData" :options="chartOptions" />
                                    </div>
                                </Panel>
                            </div>

                            <!-- Activity by Event Type Chart -->
                            <div>
                                <Panel header="Activity by Event Type" toggleable>
                                    <div class="h-80">
                                        <Chart type="doughnut" :data="activityByEventChartData" :options="chartOptions" />
                                    </div>
                                </Panel>
                            </div>
                        </div>

                        <!-- Recent Activity Table -->
                        <Panel header="Recent Activity" toggleable>
                            <DataTable :value="userActivities" stripedRows paginator :rows="5"
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
                </TabView>
            </div>
        </div>
    </AppLayout>
</template>
