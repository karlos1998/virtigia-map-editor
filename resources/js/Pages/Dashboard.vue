<script setup lang="ts">
import AppLayout from '@/layout/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed } from 'vue';

import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';

type RouteAware = {
    route_name?: string | null;
    route_param?: number | string | null;
};

type DashboardFilters = {
    days: number;
    world: string | null;
    from: string;
    to: string;
    period_options: number[];
};

type Summary = {
    total_activities: number;
    active_users: number;
    estimated_hours: number;
    work_sessions: number;
    touched_objects: number;
    last_activity_label?: string | null;
    busiest_day?: DailyActivity | null;
    top_user?: UserActivity | null;
};

type DailyActivity = {
    date: string;
    label: string;
    count: number;
    active_users: number;
    estimated_hours: number;
};

type HourlyActivity = {
    hour: number;
    label: string;
    count: number;
};

type HeatmapCell = {
    hour: number;
    count: number;
    intensity: number;
};

type HeatmapRow = {
    weekday: number;
    label: string;
    cells: HeatmapCell[];
};

type WorkHeatmap = {
    max: number;
    hours: number[];
    rows: HeatmapRow[];
};

type ActivityArea = {
    area: string;
    label: string;
    count: number;
    users_count: number;
};

type EventBreakdown = {
    event: string;
    label: string;
    action_label: string;
    severity: string;
    count: number;
};

type UserActivity = RouteAware & {
    id: number;
    name: string;
    activities: number;
    active_days: number;
    estimated_hours: number;
    sessions: number;
    share: number;
    dominant_area: string;
    dominant_action: string;
    last_activity_label?: string | null;
};

type FocusObject = RouteAware & {
    subject_label: string;
    subject_id: number;
    subject_name: string;
    area: string;
    area_label: string;
    count: number;
    users_count: number;
    last_event_label: string;
    last_activity_label?: string | null;
};

type AttachmentFeed = {
    id: number;
    action_label: string;
    severity: string;
    item_id?: number | null;
    item_name: string;
    item_route_name?: string | null;
    item_route_param?: number | null;
    target_name: string;
    target_label: string;
    target_route_name?: string | null;
    target_route_param?: number | null;
    position?: number | string | null;
    causer_name: string;
    created_at_label?: string | null;
};

type RecentActivity = RouteAware & {
    id: number;
    event_label: string;
    event_severity: string;
    subject_label: string;
    subject_name: string;
    area_label: string;
    causer_name: string;
    summary: string;
    created_at_label?: string | null;
};

const props = defineProps<{
    filters: DashboardFilters;
    summary: Summary;
    dailyActivity: DailyActivity[];
    hourlyActivity: HourlyActivity[];
    workHeatmap: WorkHeatmap;
    activityAreas: ActivityArea[];
    eventBreakdown: EventBreakdown[];
    userActivity: UserActivity[];
    focusObjects: FocusObject[];
    questFocus: FocusObject[];
    npcFocus: FocusObject[];
    attachmentFeed: AttachmentFeed[];
    attachmentSummary: Array<{
        label: string;
        action_label: string;
        severity: string;
        count: number;
    }>;
    recentActivities: RecentActivity[];
}>();

const palette = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2', '#db2777', '#475569'];

const dailyChartData = computed(() => ({
    labels: props.dailyActivity.map((point) => point.label),
    datasets: [
        {
            type: 'bar',
            label: 'Aktywności',
            data: props.dailyActivity.map((point) => point.count),
            backgroundColor: '#2563eb',
            borderRadius: 5,
            yAxisID: 'y',
        },
        {
            type: 'line',
            label: 'Szac. godziny',
            data: props.dailyActivity.map((point) => point.estimated_hours),
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.16)',
            pointBackgroundColor: '#f59e0b',
            tension: 0.35,
            fill: true,
            yAxisID: 'y1',
        },
    ],
}));

const dailyChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            position: 'bottom',
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                precision: 0,
            },
        },
        y1: {
            beginAtZero: true,
            position: 'right',
            grid: {
                drawOnChartArea: false,
            },
        },
    },
};

const hourlyChartData = computed(() => ({
    labels: props.hourlyActivity.map((point) => point.label),
    datasets: [
        {
            label: 'Aktywności',
            data: props.hourlyActivity.map((point) => point.count),
            backgroundColor: '#0891b2',
            borderRadius: 5,
        },
    ],
}));

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                precision: 0,
            },
        },
    },
};

const areaChartData = computed(() => ({
    labels: props.activityAreas.map((area) => area.label),
    datasets: [
        {
            data: props.activityAreas.map((area) => area.count),
            backgroundColor: palette,
            borderWidth: 0,
        },
    ],
}));

const eventChartData = computed(() => ({
    labels: props.eventBreakdown.map((event) => event.label),
    datasets: [
        {
            label: 'Zdarzenia',
            data: props.eventBreakdown.map((event) => event.count),
            backgroundColor: props.eventBreakdown.map((_, index) => palette[index % palette.length]),
            borderRadius: 5,
        },
    ],
}));

const horizontalBarOptions = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
    },
    scales: {
        x: {
            beginAtZero: true,
            ticks: {
                precision: 0,
            },
        },
    },
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '62%',
    plugins: {
        legend: {
            position: 'bottom',
        },
    },
};

const topArea = computed(() => props.activityAreas[0]?.label ?? 'Brak danych');
const busiestHour = computed(() => [...props.hourlyActivity].sort((a, b) => b.count - a.count)[0]);

const navigatePeriod = (days: number) => {
    router.get(route('dashboard'), { days }, {
        preserveScroll: true,
        preserveState: false,
    });
};

const resolveRoute = (item: RouteAware | { route_name?: string | null; route_param?: number | string | null } | null) => {
    if (!item?.route_name || item.route_param === null || item.route_param === undefined) {
        return null;
    }

    try {
        return route(item.route_name, item.route_param);
    } catch {
        return null;
    }
};

const resolveAttachmentItemRoute = (item: AttachmentFeed) => {
    return resolveRoute({
        route_name: item.item_route_name,
        route_param: item.item_route_param,
    });
};

const resolveAttachmentTargetRoute = (item: AttachmentFeed) => {
    return resolveRoute({
        route_name: item.target_route_name,
        route_param: item.target_route_param,
    });
};

const formatNumber = (value?: number | null) => {
    return new Intl.NumberFormat('pl-PL').format(value ?? 0);
};

const formatHours = (value?: number | null) => {
    return `${new Intl.NumberFormat('pl-PL', { maximumFractionDigits: 1 }).format(value ?? 0)} h`;
};

const initials = (name?: string | null) => {
    if (!name) {
        return '?';
    }

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
};

const heatmapCellStyle = (cell: HeatmapCell) => {
    const opacity = cell.count === 0 ? 0.06 : Math.max(0.18, cell.intensity / 100);

    return {
        backgroundColor: `rgba(22, 163, 74, ${opacity})`,
    };
};
</script>

<template>
    <AppLayout>
        <div class="dashboard-page">
            <header class="dashboard-header">
                <div>
                    <span class="dashboard-kicker">Aktywność zespołu</span>
                    <h1>Dashboard pracy nad światem</h1>
                    <div class="dashboard-subline">
                        <span>{{ filters.from }} - {{ filters.to }}</span>
                        <span v-if="filters.world">Świat: {{ filters.world }}</span>
                    </div>
                </div>

                <div class="period-switcher">
                    <Button
                        v-for="days in filters.period_options"
                        :key="days"
                        :label="`${days} dni`"
                        size="small"
                        :outlined="days !== filters.days"
                        :severity="days === filters.days ? undefined : 'secondary'"
                        @click="navigatePeriod(days)"
                    />
                </div>
            </header>

            <div class="metric-grid">
                <Card class="metric-card">
                    <template #content>
                        <div class="metric-icon metric-blue">
                            <i class="pi pi-bolt"></i>
                        </div>
                        <span>Aktywności</span>
                        <strong>{{ formatNumber(summary.total_activities) }}</strong>
                        <small>Ostatnio: {{ summary.last_activity_label || 'brak' }}</small>
                    </template>
                </Card>

                <Card class="metric-card">
                    <template #content>
                        <div class="metric-icon metric-green">
                            <i class="pi pi-users"></i>
                        </div>
                        <span>Aktywni userzy</span>
                        <strong>{{ formatNumber(summary.active_users) }}</strong>
                        <small>Top: {{ summary.top_user?.name || 'brak' }}</small>
                    </template>
                </Card>

                <Card class="metric-card">
                    <template #content>
                        <div class="metric-icon metric-amber">
                            <i class="pi pi-clock"></i>
                        </div>
                        <span>Szac. czas pracy</span>
                        <strong>{{ formatHours(summary.estimated_hours) }}</strong>
                        <small>{{ formatNumber(summary.work_sessions) }} sesji</small>
                    </template>
                </Card>

                <Card class="metric-card">
                    <template #content>
                        <div class="metric-icon metric-rose">
                            <i class="pi pi-sitemap"></i>
                        </div>
                        <span>Ruszane obiekty</span>
                        <strong>{{ formatNumber(summary.touched_objects) }}</strong>
                        <small>Głównie: {{ topArea }}</small>
                    </template>
                </Card>
            </div>

            <div class="dashboard-grid">
                <section class="dashboard-panel span-8">
                    <div class="panel-header">
                        <div>
                            <h2>Tempo pracy</h2>
                            <p>{{ summary.busiest_day?.date || 'Brak aktywności' }}</p>
                        </div>
                        <Tag :value="`${formatNumber(summary.busiest_day?.count)} zdarzeń`" severity="info" />
                    </div>
                    <div class="chart-box tall">
                        <Chart type="bar" :data="dailyChartData" :options="dailyChartOptions" />
                    </div>
                </section>

                <section class="dashboard-panel span-4">
                    <div class="panel-header">
                        <div>
                            <h2>Obszary pracy</h2>
                            <p>{{ topArea }}</p>
                        </div>
                    </div>
                    <div class="chart-box compact">
                        <Chart type="doughnut" :data="areaChartData" :options="doughnutOptions" />
                    </div>
                    <div class="area-list">
                        <div v-for="area in activityAreas" :key="area.area" class="area-row">
                            <span>{{ area.label }}</span>
                            <strong>{{ formatNumber(area.count) }}</strong>
                        </div>
                    </div>
                </section>

                <section class="dashboard-panel span-7">
                    <div class="panel-header">
                        <div>
                            <h2>Kto robi najwięcej</h2>
                            <p>{{ userActivity.length }} aktywnych w okresie</p>
                        </div>
                    </div>
                    <DataTable :value="userActivity" size="small" class="dashboard-table" responsiveLayout="scroll">
                        <Column header="User">
                            <template #body="{ data }">
                                <div class="user-cell">
                                    <Avatar :label="initials(data.name)" shape="circle" />
                                    <div>
                                        <Link v-if="resolveRoute(data)" :href="resolveRoute(data)" class="table-link">
                                            {{ data.name }}
                                        </Link>
                                        <strong v-else>{{ data.name }}</strong>
                                        <small>{{ data.last_activity_label || 'brak ostatniej aktywności' }}</small>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column header="Aktywności">
                            <template #body="{ data }">
                                <div class="activity-share">
                                    <strong>{{ formatNumber(data.activities) }}</strong>
                                    <ProgressBar :value="data.share" :showValue="false" />
                                </div>
                            </template>
                        </Column>
                        <Column header="Czas">
                            <template #body="{ data }">
                                <strong>{{ formatHours(data.estimated_hours) }}</strong>
                                <small class="muted">{{ data.active_days }} dni</small>
                            </template>
                        </Column>
                        <Column header="Głównie">
                            <template #body="{ data }">
                                <div class="tag-stack">
                                    <Tag :value="data.dominant_area" severity="info" />
                                    <Tag :value="data.dominant_action" severity="secondary" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </section>

                <section class="dashboard-panel span-5">
                    <div class="panel-header">
                        <div>
                            <h2>Kiedy pracują</h2>
                            <p v-if="busiestHour">{{ busiestHour.label }} · {{ formatNumber(busiestHour.count) }}</p>
                        </div>
                    </div>
                    <div class="chart-box compact">
                        <Chart type="bar" :data="hourlyChartData" :options="barChartOptions" />
                    </div>
                    <div class="heatmap-scroll">
                        <div class="heatmap-grid">
                            <div class="heatmap-corner"></div>
                            <div v-for="hour in workHeatmap.hours" :key="hour" class="heatmap-hour">
                                {{ hour }}
                            </div>
                            <template v-for="row in workHeatmap.rows" :key="row.weekday">
                                <div class="heatmap-day">{{ row.label }}</div>
                                <div
                                    v-for="cell in row.cells"
                                    :key="`${row.weekday}-${cell.hour}`"
                                    class="heatmap-cell"
                                    :style="heatmapCellStyle(cell)"
                                    :title="`${row.label} ${cell.hour}:00 · ${cell.count}`"
                                >
                                    <span v-if="cell.count > 0">{{ cell.count }}</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </section>

                <section class="dashboard-panel span-5">
                    <div class="panel-header">
                        <div>
                            <h2>Co robią</h2>
                            <p>{{ eventBreakdown[0]?.label || 'Brak danych' }}</p>
                        </div>
                    </div>
                    <div class="chart-box medium">
                        <Chart type="bar" :data="eventChartData" :options="horizontalBarOptions" />
                    </div>
                    <div class="event-list">
                        <div v-for="event in eventBreakdown.slice(0, 6)" :key="event.event" class="event-row">
                            <Tag :value="event.action_label" :severity="event.severity" />
                            <span>{{ event.label }}</span>
                            <strong>{{ formatNumber(event.count) }}</strong>
                        </div>
                    </div>
                </section>

                <section class="dashboard-panel span-7">
                    <div class="panel-header">
                        <div>
                            <h2>Nad czym pracują</h2>
                            <p>{{ focusObjects.length }} najczęściej ruszanych obiektów</p>
                        </div>
                    </div>
                    <DataTable :value="focusObjects" size="small" class="dashboard-table" responsiveLayout="scroll">
                        <Column header="Obiekt">
                            <template #body="{ data }">
                                <div class="object-cell">
                                    <Tag :value="data.subject_label" severity="secondary" />
                                    <div>
                                        <Link v-if="resolveRoute(data)" :href="resolveRoute(data)" class="table-link">
                                            {{ data.subject_name }}
                                        </Link>
                                        <strong v-else>{{ data.subject_name }}</strong>
                                        <small>{{ data.area_label }}</small>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column header="Aktywności" field="count" sortable>
                            <template #body="{ data }">
                                <strong>{{ formatNumber(data.count) }}</strong>
                            </template>
                        </Column>
                        <Column header="Userzy">
                            <template #body="{ data }">
                                {{ formatNumber(data.users_count) }}
                            </template>
                        </Column>
                        <Column header="Ostatnio">
                            <template #body="{ data }">
                                <strong>{{ data.last_event_label }}</strong>
                                <small class="muted">{{ data.last_activity_label }}</small>
                            </template>
                        </Column>
                    </DataTable>
                </section>

                <section class="dashboard-panel span-6">
                    <div class="panel-header">
                        <div>
                            <h2>Questy</h2>
                            <p>{{ questFocus.length }} aktywnych obiektów</p>
                        </div>
                    </div>
                    <div class="focus-list">
                        <div v-for="quest in questFocus" :key="`${quest.subject_label}-${quest.subject_id}`" class="focus-row">
                            <div>
                                <Link v-if="resolveRoute(quest)" :href="resolveRoute(quest)" class="table-link">
                                    {{ quest.subject_name }}
                                </Link>
                                <strong v-else>{{ quest.subject_name }}</strong>
                                <small>{{ quest.subject_label }} · {{ quest.last_event_label }}</small>
                            </div>
                            <Tag :value="formatNumber(quest.count)" severity="info" />
                        </div>
                        <div v-if="questFocus.length === 0" class="empty-state">Brak aktywności questowej.</div>
                    </div>
                </section>

                <section class="dashboard-panel span-6">
                    <div class="panel-header">
                        <div>
                            <h2>NPC</h2>
                            <p>{{ npcFocus.length }} aktywnych obiektów</p>
                        </div>
                    </div>
                    <div class="focus-list">
                        <div v-for="npc in npcFocus" :key="`${npc.subject_label}-${npc.subject_id}`" class="focus-row">
                            <div>
                                <Link v-if="resolveRoute(npc)" :href="resolveRoute(npc)" class="table-link">
                                    {{ npc.subject_name }}
                                </Link>
                                <strong v-else>{{ npc.subject_name }}</strong>
                                <small>{{ npc.subject_label }} · {{ npc.last_event_label }}</small>
                            </div>
                            <Tag :value="formatNumber(npc.count)" severity="success" />
                        </div>
                        <div v-if="npcFocus.length === 0" class="empty-state">Brak aktywności przy NPC.</div>
                    </div>
                </section>

                <section class="dashboard-panel span-7">
                    <div class="panel-header">
                        <div>
                            <h2>Podpinanie itemów</h2>
                            <p>{{ attachmentFeed.length }} ostatnich zmian</p>
                        </div>
                        <div class="summary-tags">
                            <Tag
                                v-for="item in attachmentSummary"
                                :key="`${item.label}-${item.action_label}`"
                                :value="`${item.action_label}: ${item.count}`"
                                :severity="item.severity"
                            />
                        </div>
                    </div>
                    <DataTable :value="attachmentFeed" size="small" class="dashboard-table" responsiveLayout="scroll">
                        <Column header="Akcja">
                            <template #body="{ data }">
                                <Tag :value="data.action_label" :severity="data.severity" />
                            </template>
                        </Column>
                        <Column header="Item">
                            <template #body="{ data }">
                                <Link v-if="resolveAttachmentItemRoute(data)" :href="resolveAttachmentItemRoute(data)" class="table-link">
                                    {{ data.item_name }}
                                </Link>
                                <strong v-else>{{ data.item_name }}</strong>
                            </template>
                        </Column>
                        <Column header="Gdzie">
                            <template #body="{ data }">
                                <div class="object-cell compact-cell">
                                    <Tag :value="data.target_label" severity="secondary" />
                                    <Link v-if="resolveAttachmentTargetRoute(data)" :href="resolveAttachmentTargetRoute(data)" class="table-link">
                                        {{ data.target_name }}
                                    </Link>
                                    <strong v-else>{{ data.target_name }}</strong>
                                </div>
                            </template>
                        </Column>
                        <Column header="User">
                            <template #body="{ data }">
                                {{ data.causer_name }}
                            </template>
                        </Column>
                        <Column header="Kiedy">
                            <template #body="{ data }">
                                {{ data.created_at_label }}
                            </template>
                        </Column>
                    </DataTable>
                </section>

                <section class="dashboard-panel span-5">
                    <div class="panel-header">
                        <div>
                            <h2>Ostatnie zdarzenia</h2>
                            <p>{{ recentActivities.length }} wpisów</p>
                        </div>
                        <Link :href="route('activity-logs.index')">
                            <Button label="Logi" icon="pi pi-external-link" size="small" text />
                        </Link>
                    </div>
                    <div class="recent-list">
                        <div v-for="activity in recentActivities" :key="activity.id" class="recent-row">
                            <Tag :value="activity.event_label" :severity="activity.event_severity" />
                            <div>
                                <Link v-if="resolveRoute(activity)" :href="resolveRoute(activity)" class="table-link">
                                    {{ activity.summary }}
                                </Link>
                                <strong v-else>{{ activity.summary }}</strong>
                                <small>{{ activity.causer_name }} · {{ activity.created_at_label }}</small>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.dashboard-page {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.dashboard-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.25rem 0 0.5rem;
}

.dashboard-header h1 {
    margin: 0.15rem 0 0;
    color: #111827;
    font-size: 1.8rem;
    font-weight: 800;
    line-height: 1.15;
}

.dashboard-kicker,
.dashboard-subline,
.muted,
.metric-card small,
.metric-card span,
.panel-header p,
.focus-row small,
.recent-row small,
.user-cell small,
.object-cell small {
    color: #64748b;
}

.dashboard-kicker {
    display: block;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
}

.dashboard-subline {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 0.35rem;
    font-size: 0.9rem;
}

.period-switcher {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.45rem;
}

.metric-grid,
.dashboard-grid {
    display: grid;
    gap: 1rem;
}

.metric-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.dashboard-grid {
    grid-template-columns: repeat(12, minmax(0, 1fr));
}

.span-4 {
    grid-column: span 4;
}

.span-5 {
    grid-column: span 5;
}

.span-6 {
    grid-column: span 6;
}

.span-7 {
    grid-column: span 7;
}

.span-8 {
    grid-column: span 8;
}

.metric-card :deep(.p-card-body),
.metric-card :deep(.p-card-content) {
    height: 100%;
}

.metric-card :deep(.p-card-content) {
    display: grid;
    gap: 0.35rem;
    padding: 1rem;
}

.metric-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: none;
}

.metric-card strong {
    color: #0f172a;
    font-size: 1.75rem;
    line-height: 1;
}

.metric-card span {
    font-size: 0.85rem;
    font-weight: 700;
}

.metric-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 8px;
}

.metric-blue {
    background: #dbeafe;
    color: #1d4ed8;
}

.metric-green {
    background: #dcfce7;
    color: #15803d;
}

.metric-amber {
    background: #fef3c7;
    color: #b45309;
}

.metric-rose {
    background: #ffe4e6;
    color: #be123c;
}

.dashboard-panel {
    min-width: 0;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    padding: 1rem;
}

.panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.9rem;
}

.panel-header h2 {
    margin: 0;
    color: #0f172a;
    font-size: 1rem;
    font-weight: 800;
}

.panel-header p {
    margin: 0.25rem 0 0;
    font-size: 0.85rem;
}

.chart-box {
    min-height: 18rem;
}

.chart-box.compact {
    min-height: 14rem;
}

.chart-box.medium {
    min-height: 19rem;
}

.chart-box.tall {
    min-height: 23rem;
}

.area-list,
.event-list,
.focus-list,
.recent-list {
    display: grid;
    gap: 0.6rem;
}

.area-row,
.event-row,
.focus-row,
.recent-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.event-row,
.focus-row,
.recent-row {
    justify-content: flex-start;
    border-top: 1px solid #eef2f7;
    padding-top: 0.6rem;
}

.focus-row > div,
.recent-row > div,
.user-cell > div,
.object-cell > div {
    display: grid;
    min-width: 0;
    gap: 0.2rem;
}

.focus-row strong,
.recent-row strong,
.object-cell strong,
.user-cell strong,
.table-link {
    overflow: hidden;
    color: #0f172a;
    font-weight: 700;
    text-decoration: none;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.table-link:hover {
    color: #2563eb;
}

.focus-row .p-tag,
.area-row strong {
    margin-left: auto;
}

.user-cell,
.object-cell {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
}

.compact-cell {
    gap: 0.45rem;
}

.activity-share {
    display: grid;
    gap: 0.35rem;
    min-width: 6rem;
}

.activity-share :deep(.p-progressbar) {
    height: 0.4rem;
}

.tag-stack,
.summary-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.heatmap-scroll {
    overflow-x: auto;
}

.heatmap-grid {
    display: grid;
    grid-template-columns: 3rem repeat(24, minmax(1.55rem, 1fr));
    gap: 0.25rem;
    min-width: 48rem;
}

.heatmap-corner,
.heatmap-hour,
.heatmap-day {
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 700;
    text-align: center;
}

.heatmap-day {
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.heatmap-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 1.45rem;
    border-radius: 5px;
    color: #14532d;
    font-size: 0.65rem;
    font-weight: 800;
}

.dashboard-table {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.dashboard-table :deep(.p-datatable-table) {
    min-width: 42rem;
}

.dashboard-table :deep(.p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #475569;
    font-size: 0.75rem;
    text-transform: uppercase;
}

.dashboard-table :deep(.p-datatable-tbody > tr > td) {
    vertical-align: top;
}

.empty-state {
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    color: #64748b;
    padding: 1rem;
    text-align: center;
}

@media (max-width: 1200px) {
    .metric-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .span-4,
    .span-5,
    .span-6,
    .span-7,
    .span-8 {
        grid-column: span 12;
    }
}

@media (max-width: 720px) {
    .dashboard-header {
        flex-direction: column;
    }

    .period-switcher {
        justify-content: flex-start;
    }

    .metric-grid {
        grid-template-columns: 1fr;
    }

    .dashboard-header h1 {
        font-size: 1.45rem;
    }
}
</style>
