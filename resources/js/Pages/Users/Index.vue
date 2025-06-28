<script setup lang="ts">
import AppLayout from "@/layout/AppLayout.vue";
import { Link } from '@inertiajs/vue3';
import { route } from "ziggy-js";
import { ref, computed } from "vue";

// PrimeVue Components
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Column from 'primevue/column';
import AdvanceTable from "@advance-table/Components/AdvanceTable.vue";
import AdvanceColumn from "@advance-table/Components/AdvanceColumn.vue";
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

// Props
defineProps({
    users: Array
});

// Refs
const filters = ref({
    global: { value: null, matchMode: 'contains' }
});

// Format date
const formatDate = (dateString) => {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleString();
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

// Get role severity
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
</script>

<template>
    <AppLayout title="Users">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Users
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <template #title>
                        <div class="flex items-center">
                            <i class="pi pi-users mr-2 text-primary-500"></i>
                            <span>All Users</span>
                        </div>
                    </template>
                    <template #content>
                        <AdvanceTable
                            prop-name="users"
                            :global-filter-fields="['name', 'email']"
                            :filters="filters"
                            filter-display="menu"
                            striped-rows
                            paginator
                            :rows="10"
                            table-style="min-width: 50rem"
                            class="p-datatable-sm"
                            sort-field="last_activity_at"
                            :sort-order="-1"
                        >
                            <template #header="{ globalFilterValue, globalFilterUpdated }">
                                <div class="flex flex-wrap gap-2 items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <h4 class="m-0">All Users</h4>
                                    </div>
                                    <IconField>
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText
                                            :value="globalFilterValue"
                                            @update:model-value="globalFilterUpdated"
                                            placeholder="Search"
                                        />
                                    </IconField>
                                </div>
                            </template>

                            <AdvanceColumn field="id" header="ID" sortable style="width: 5%" />

                            <AdvanceColumn field="name" header="Name" sortable style="width: 20%">
                                <template #body="{ data }">
                                    <div class="flex items-center">
                                        <div v-if="data.src" class="mr-2">
                                            <div
                                                class="rounded-full overflow-hidden"
                                                style="width: 32px; height: 32px;"
                                            >
                                                <div
                                                    style="width: 32px; height: 32px;"
                                                    :style="{
                                                        backgroundImage: `url(${data.src})`,
                                                        backgroundPosition: '0 0',
                                                        backgroundRepeat: 'no-repeat'
                                                    }"
                                                ></div>
                                            </div>
                                        </div>
                                        <Avatar
                                            v-else
                                            icon="pi pi-user"
                                            shape="circle"
                                            size="small"
                                            class="mr-2"
                                            style="width: 32px;"
                                        />
                                        <div>
                                            <div class="font-medium">{{ data.name }}</div>
                                        </div>
                                    </div>
                                </template>
                            </AdvanceColumn>

                            <AdvanceColumn field="email" header="Email" sortable style="width: 20%" />

                            <AdvanceColumn field="roles" header="Roles" style="width: 15%">
                                <template #body="{ data }">
                                    <div class="flex flex-wrap gap-1">
                                        <Tag
                                            v-for="(role, index) in formatRoles(data.roles)"
                                            :key="index"
                                            :value="role"
                                            :severity="getRoleSeverity(data.roles[index])"
                                        />
                                    </div>
                                </template>
                            </AdvanceColumn>

                            <AdvanceColumn field="total_activities" header="Activities" sortable style="width: 10%">
                                <template #body="{ data }">
                                    <div class="text-center">
                                        <span class="font-semibold">{{ data.total_activities }}</span>
                                    </div>
                                </template>
                            </AdvanceColumn>

                            <AdvanceColumn field="last_activity_at" header="Last Activity" sortable style="width: 20%">
                                <template #body="{ data }">
                                    <div>
                                        <div>{{ formatDate(data.last_activity_at) }}</div>
                                        <div v-if="data.last_activity_type" class="mt-1">
                                            <Tag
                                                :value="data.last_activity_type"
                                                :severity="getEventSeverity(data.last_activity_type)"
                                                size="small"
                                            />
                                        </div>
                                    </div>
                                </template>
                            </AdvanceColumn>

                            <AdvanceColumn header="Actions" style="width: 10%">
                                <template #body="{ data }">
                                    <div class="flex justify-center">
                                        <Link :href="route('users.show', data.id)">
                                            <Button
                                                icon="pi pi-eye"
                                                rounded
                                                text
                                                severity="info"
                                                aria-label="View Details"
                                                v-tooltip.top="'View Details'"
                                            />
                                        </Link>
                                    </div>
                                </template>
                            </AdvanceColumn>
                        </AdvanceTable>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
