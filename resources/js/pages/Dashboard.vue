<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import {
    ArrowRightLeft,
    Building2,
    Bus,
    CheckCircle2,
    CircleAlert,
    FileWarning,
    GitBranch,
    MapPinned,
    ShieldCheck,
    Warehouse,
} from 'lucide-vue-next';

type StatusItem = {
    status: string;
    total: number;
};

type GateActivityItem = {
    id: number;
    gate_name: string;
    status: string;
    bays: number;
    dispatches_today_count: number;
};

type TopCompanyItem = {
    id: number;
    company_name: string;
    company_code: string;
    status: string;
    vehicles_count: number;
    dispatches_today_count: number;
};

type RecentDispatchItem = {
    id: number;
    company: {
        name: string;
        code: string;
    } | null;
    vehicle: {
        plate_number: string;
        vehicle_type: string;
    } | null;
    route: string | null;
    gate: string | null;
    bay_number: string | null;
    pax_count: number;
    status: string;
    arrived_at: string | null;
    departed_at: string | null;
    dispatched_at: string | null;
    created_at: string | null;
};

type ExpiringVehicleDocumentItem = {
    id: number;
    document_type: string;
    status: string;
    expires_at: string;
    days_left: number;
    vehicle: {
        plate_number: string;
        vehicle_type: string;
    } | null;
    company: {
        name: string;
        code: string;
    } | null;
};

type SummaryReport = {
    system_health: {
        label: string;
        description: string;
    };
    compliance: {
        label: string;
        description: string;
    };
    operations: {
        label: string;
        description: string;
    };
    companies: {
        label: string;
        description: string;
    };
};

const props = defineProps<{
    stats: {
        total_companies: number;
        verified_companies: number;
        pending_companies: number;
        total_vehicles: number;
        active_vehicles: number;
        total_routes: number;
        active_routes: number;
        total_gates: number;
        active_gates: number;
        dispatches_today: number;
        arrived_today: number;
        departed_today: number;
        dispatched_today: number;
        total_pax_today: number;
        expiring_documents_count: number;
    };
    companyStatus: StatusItem[];
    dispatchStatus: StatusItem[];
    gateActivity: GateActivityItem[];
    topCompanies: TopCompanyItem[];
    recentDispatches: RecentDispatchItem[];
    expiringVehicleDocuments: ExpiringVehicleDocumentItem[];
    summaryReport: SummaryReport;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

function formatStatus(value: string) {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function statusBadgeVariant(status: string) {
    const normalized = status.toLowerCase();

    if (['verified', 'active', 'dispatched', 'departed', 'stable', 'healthy', 'tracked', 'running'].includes(normalized)) {
        return 'default';
    }

    if (['pending', 'arrived', 'docs_completed', 'watchlist active'].includes(normalized)) {
        return 'secondary';
    }

    if (['needs_revision', 'inactive', 'needs attention', 'no activity'].includes(normalized)) {
        return 'outline';
    }

    if (['rejected', 'invalid', 'expired', 'empty'].includes(normalized)) {
        return 'destructive';
    }

    return 'secondary';
}

function progressWidth(value: number, max: number) {
    if (max <= 0) return '0%';
    return `${Math.max(8, Math.round((value / max) * 100))}%`;
}

const maxGateDispatchCount = computed(() => {
    const counts = props.gateActivity.map((item) => item.dispatches_today_count);
    return Math.max(...counts, 1);
});

const companyChartData = computed(() => {
    const total = props.stats.total_companies || 1;

    return [
        {
            label: 'Verified',
            value: props.stats.verified_companies,
            color: 'hsl(var(--primary))',
            percent: Math.round((props.stats.verified_companies / total) * 100),
        },
        {
            label: 'Pending / Workflow',
            value: props.stats.pending_companies,
            color: 'hsl(var(--muted-foreground))',
            percent: Math.round((props.stats.pending_companies / total) * 100),
        },
        {
            label: 'Other',
            value: Math.max(
                props.stats.total_companies -
                    props.stats.verified_companies -
                    props.stats.pending_companies,
                0
            ),
            color: 'hsl(var(--border))',
            percent: Math.max(
                0,
                100 -
                    Math.round((props.stats.verified_companies / total) * 100) -
                    Math.round((props.stats.pending_companies / total) * 100)
            ),
        },
    ].filter((item) => item.value > 0);
});

const dispatchChartData = computed(() => {
    const total = props.stats.dispatches_today || 1;

    return [
        {
            label: 'Arrived',
            value: props.stats.arrived_today,
            color: 'hsl(var(--primary))',
            percent: Math.round((props.stats.arrived_today / total) * 100),
        },
        {
            label: 'Departed',
            value: props.stats.departed_today,
            color: 'hsl(var(--muted-foreground))',
            percent: Math.round((props.stats.departed_today / total) * 100),
        },
        {
            label: 'Dispatched',
            value: props.stats.dispatched_today,
            color: 'hsl(var(--accent-foreground))',
            percent: Math.round((props.stats.dispatched_today / total) * 100),
        },
    ].filter((item) => item.value > 0);
});

function polarToCartesian(cx: number, cy: number, radius: number, angle: number) {
    const rad = ((angle - 90) * Math.PI) / 180;
    return {
        x: cx + radius * Math.cos(rad),
        y: cy + radius * Math.sin(rad),
    };
}

function describeArc(
    cx: number,
    cy: number,
    radius: number,
    startAngle: number,
    endAngle: number
) {
    const start = polarToCartesian(cx, cy, radius, endAngle);
    const end = polarToCartesian(cx, cy, radius, startAngle);
    const largeArcFlag = endAngle - startAngle <= 180 ? '0' : '1';

    return [
        'M',
        start.x,
        start.y,
        'A',
        radius,
        radius,
        0,
        largeArcFlag,
        0,
        end.x,
        end.y,
    ].join(' ');
}

function buildDonutSegments(data: { value: number; color: string }[]) {
    const total = data.reduce((sum, item) => sum + item.value, 0);

    if (!total) return [];

    let currentAngle = 0;

    return data.map((item) => {
        const angle = (item.value / total) * 360;
        const segment = {
            path: describeArc(60, 60, 42, currentAngle, currentAngle + angle),
            color: item.color,
        };
        currentAngle += angle;
        return segment;
    });
}

const companySegments = computed(() => buildDonutSegments(companyChartData.value));
const dispatchSegments = computed(() => buildDonutSegments(dispatchChartData.value));

function documentUrgencyLabel(daysLeft: number) {
    if (daysLeft <= 7) return 'Urgent';
    if (daysLeft <= 15) return 'Soon';
    return 'Upcoming';
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
                <p class="text-sm text-muted-foreground">
                    KPI overview, operational summary, and compliance watchlist for the system.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Companies</CardTitle>
                        <Building2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ stats.total_companies }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ stats.verified_companies }} verified · {{ stats.pending_companies }} in process
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Fleet</CardTitle>
                        <Bus class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ stats.total_vehicles }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ stats.active_vehicles }} active vehicles
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Network</CardTitle>
                        <GitBranch class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ stats.active_routes }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ stats.total_routes }} routes · {{ stats.active_gates }}/{{ stats.total_gates }} gates active
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Dispatches Today</CardTitle>
                        <ArrowRightLeft class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-semibold">{{ stats.dispatches_today }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ stats.total_pax_today }} passengers logged today
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <Card class="xl:col-span-2">
                    <CardHeader>
                        <CardTitle>System Summary Report</CardTitle>
                        <CardDescription>
                            Quick executive summary of operations, compliance, and platform readiness
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl border p-4">
                            <div class="mb-3 flex items-center gap-2">
                                <ShieldCheck class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-medium">System Health</span>
                            </div>
                            <Badge :variant="statusBadgeVariant(summaryReport.system_health.label)">
                                {{ summaryReport.system_health.label }}
                            </Badge>
                            <p class="mt-3 text-sm text-muted-foreground">
                                {{ summaryReport.system_health.description }}
                            </p>
                        </div>

                        <div class="rounded-xl border p-4">
                            <div class="mb-3 flex items-center gap-2">
                                <FileWarning class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-medium">Compliance</span>
                            </div>
                            <Badge :variant="statusBadgeVariant(summaryReport.compliance.label)">
                                {{ summaryReport.compliance.label }}
                            </Badge>
                            <p class="mt-3 text-sm text-muted-foreground">
                                {{ summaryReport.compliance.description }}
                            </p>
                        </div>

                        <div class="rounded-xl border p-4">
                            <div class="mb-3 flex items-center gap-2">
                                <Warehouse class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-medium">Operations</span>
                            </div>
                            <Badge :variant="statusBadgeVariant(summaryReport.operations.label)">
                                {{ summaryReport.operations.label }}
                            </Badge>
                            <p class="mt-3 text-sm text-muted-foreground">
                                {{ summaryReport.operations.description }}
                            </p>
                        </div>

                        <div class="rounded-xl border p-4">
                            <div class="mb-3 flex items-center gap-2">
                                <Building2 class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-medium">Companies</span>
                            </div>
                            <Badge :variant="statusBadgeVariant(summaryReport.companies.label)">
                                {{ summaryReport.companies.label }}
                            </Badge>
                            <p class="mt-3 text-sm text-muted-foreground">
                                {{ summaryReport.companies.description }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Alert Snapshot</CardTitle>
                        <CardDescription>Items needing attention right now</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="rounded-lg border p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Expiring vehicle documents</span>
                                <Badge :variant="stats.expiring_documents_count > 0 ? 'destructive' : 'default'">
                                    {{ stats.expiring_documents_count }}
                                </Badge>
                            </div>
                        </div>

                        <div class="rounded-lg border p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Pending companies</span>
                                <Badge variant="secondary">
                                    {{ stats.pending_companies }}
                                </Badge>
                            </div>
                        </div>

                        <div class="rounded-lg border p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Inactive routes</span>
                                <Badge :variant="stats.total_routes - stats.active_routes > 0 ? 'outline' : 'default'">
                                    {{ stats.total_routes - stats.active_routes }}
                                </Badge>
                            </div>
                        </div>

                        <div class="rounded-lg border p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Inactive gates</span>
                                <Badge :variant="stats.total_gates - stats.active_gates > 0 ? 'outline' : 'default'">
                                    {{ stats.total_gates - stats.active_gates }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Company KPI Distribution</CardTitle>
                        <CardDescription>Verification and workflow composition</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-6 lg:flex-row lg:items-center">
                        <div class="mx-auto flex items-center justify-center">
                            <svg viewBox="0 0 120 120" class="h-52 w-52">
                                <circle
                                    cx="60"
                                    cy="60"
                                    r="42"
                                    fill="none"
                                    stroke="hsl(var(--muted))"
                                    stroke-width="16"
                                />
                                <path
                                    v-for="(segment, index) in companySegments"
                                    :key="index"
                                    :d="segment.path"
                                    fill="none"
                                    :stroke="segment.color"
                                    stroke-width="16"
                                    stroke-linecap="round"
                                />
                                <text
                                    x="60"
                                    y="56"
                                    text-anchor="middle"
                                    class="fill-foreground text-[10px] font-medium"
                                >
                                    Companies
                                </text>
                                <text
                                    x="60"
                                    y="72"
                                    text-anchor="middle"
                                    class="fill-foreground text-[14px] font-semibold"
                                >
                                    {{ stats.total_companies }}
                                </text>
                            </svg>
                        </div>

                        <div class="flex-1 space-y-3">
                            <div
                                v-for="item in companyChartData"
                                :key="item.label"
                                class="flex items-center justify-between rounded-lg border px-4 py-3"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="h-3 w-3 rounded-full"
                                        :style="{ backgroundColor: item.color }"
                                    />
                                    <div>
                                        <div class="text-sm font-medium">{{ item.label }}</div>
                                        <div class="text-xs text-muted-foreground">{{ item.percent }}%</div>
                                    </div>
                                </div>

                                <div class="text-sm font-semibold">{{ item.value }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Dispatch KPI Distribution</CardTitle>
                        <CardDescription>Operational flow for today’s records</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-6 lg:flex-row lg:items-center">
                        <div class="mx-auto flex items-center justify-center">
                            <svg viewBox="0 0 120 120" class="h-52 w-52">
                                <circle
                                    cx="60"
                                    cy="60"
                                    r="42"
                                    fill="none"
                                    stroke="hsl(var(--muted))"
                                    stroke-width="16"
                                />
                                <path
                                    v-for="(segment, index) in dispatchSegments"
                                    :key="index"
                                    :d="segment.path"
                                    fill="none"
                                    :stroke="segment.color"
                                    stroke-width="16"
                                    stroke-linecap="round"
                                />
                                <text
                                    x="60"
                                    y="56"
                                    text-anchor="middle"
                                    class="fill-foreground text-[10px] font-medium"
                                >
                                    Dispatches
                                </text>
                                <text
                                    x="60"
                                    y="72"
                                    text-anchor="middle"
                                    class="fill-foreground text-[14px] font-semibold"
                                >
                                    {{ stats.dispatches_today }}
                                </text>
                            </svg>
                        </div>

                        <div class="flex-1 space-y-3">
                            <div
                                v-for="item in dispatchChartData"
                                :key="item.label"
                                class="flex items-center justify-between rounded-lg border px-4 py-3"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="h-3 w-3 rounded-full"
                                        :style="{ backgroundColor: item.color }"
                                    />
                                    <div>
                                        <div class="text-sm font-medium">{{ item.label }}</div>
                                        <div class="text-xs text-muted-foreground">{{ item.percent }}%</div>
                                    </div>
                                </div>

                                <div class="text-sm font-semibold">{{ item.value }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <Card class="xl:col-span-2">
                    <CardHeader>
                        <CardTitle>Recent Dispatches</CardTitle>
                        <CardDescription>Latest activity across the system</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Company</TableHead>
                                        <TableHead>Vehicle</TableHead>
                                        <TableHead>Route</TableHead>
                                        <TableHead>Gate / Bay</TableHead>
                                        <TableHead>PAX</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Time</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="item in recentDispatches" :key="item.id">
                                        <TableCell class="min-w-[180px]">
                                            <div class="font-medium">{{ item.company?.name ?? '—' }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ item.company?.code ?? '—' }}
                                            </div>
                                        </TableCell>

                                        <TableCell class="min-w-[150px]">
                                            <div class="font-medium">{{ item.vehicle?.plate_number ?? '—' }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ item.vehicle?.vehicle_type ?? '—' }}
                                            </div>
                                        </TableCell>

                                        <TableCell class="min-w-[160px]">{{ item.route ?? '—' }}</TableCell>

                                        <TableCell class="min-w-[120px]">
                                            <div>{{ item.gate ?? '—' }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ item.bay_number ?? 'No bay' }}
                                            </div>
                                        </TableCell>

                                        <TableCell>{{ item.pax_count }}</TableCell>

                                        <TableCell>
                                            <Badge :variant="statusBadgeVariant(item.status)">
                                                {{ formatStatus(item.status) }}
                                            </Badge>
                                        </TableCell>

                                        <TableCell class="min-w-[180px] text-xs text-muted-foreground">
                                            {{
                                                item.dispatched_at
                                                    ?? item.departed_at
                                                    ?? item.arrived_at
                                                    ?? item.created_at
                                                    ?? '—'
                                            }}
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="recentDispatches.length === 0">
                                        <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                                            No recent dispatches found.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Gate Activity</CardTitle>
                        <CardDescription>Dispatch load per gate today</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-for="gate in gateActivity"
                            :key="gate.id"
                            class="space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium">{{ gate.gate_name }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ gate.bays }} bays · {{ formatStatus(gate.status) }}
                                    </div>
                                </div>
                                <div class="text-sm font-medium">
                                    {{ gate.dispatches_today_count }}
                                </div>
                            </div>

                            <div class="h-2 rounded-full bg-muted">
                                <div
                                    class="h-2 rounded-full bg-primary"
                                    :style="{ width: progressWidth(gate.dispatches_today_count, maxGateDispatchCount) }"
                                />
                            </div>
                        </div>

                        <div v-if="gateActivity.length === 0" class="text-sm text-muted-foreground">
                            No gate activity available.
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Top Companies Today</CardTitle>
                        <CardDescription>Highest dispatch activity</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="company in topCompanies"
                            :key="company.id"
                            class="flex items-center justify-between rounded-lg border px-4 py-3"
                        >
                            <div>
                                <div class="font-medium">{{ company.company_name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ company.company_code }} · {{ company.vehicles_count }} vehicles
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <Badge :variant="statusBadgeVariant(company.status)">
                                    {{ formatStatus(company.status) }}
                                </Badge>
                                <div class="text-sm font-semibold">
                                    {{ company.dispatches_today_count }}
                                </div>
                            </div>
                        </div>

                        <div v-if="topCompanies.length === 0" class="text-sm text-muted-foreground">
                            No company activity available.
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Company Status Breakdown</CardTitle>
                        <CardDescription>Registration and verification workflow</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="item in companyStatus"
                            :key="item.status"
                            class="flex items-center justify-between rounded-lg border px-4 py-3"
                        >
                            <Badge :variant="statusBadgeVariant(item.status)">
                                {{ formatStatus(item.status) }}
                            </Badge>
                            <span class="text-sm font-semibold">{{ item.total }}</span>
                        </div>

                        <div v-if="companyStatus.length === 0" class="text-sm text-muted-foreground">
                            No company data available.
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Vehicle Documents Expiring Soon</CardTitle>
                    <CardDescription>
                        Compliance watchlist for the next 30 days
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Company</TableHead>
                                    <TableHead>Vehicle</TableHead>
                                    <TableHead>Document</TableHead>
                                    <TableHead>Expiry</TableHead>
                                    <TableHead>Days Left</TableHead>
                                    <TableHead>Priority</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="document in expiringVehicleDocuments"
                                    :key="document.id"
                                >
                                    <TableCell class="min-w-[180px]">
                                        <div class="font-medium">{{ document.company?.name ?? '—' }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ document.company?.code ?? '—' }}
                                        </div>
                                    </TableCell>

                                    <TableCell class="min-w-[160px]">
                                        <div class="font-medium">{{ document.vehicle?.plate_number ?? '—' }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ document.vehicle?.vehicle_type ?? '—' }}
                                        </div>
                                    </TableCell>

                                    <TableCell>{{ formatStatus(document.document_type) }}</TableCell>
                                    <TableCell>{{ document.expires_at }}</TableCell>
                                    <TableCell>{{ document.days_left }}</TableCell>

                                    <TableCell>
                                        <Badge
                                            :variant="document.days_left <= 7 ? 'destructive' : document.days_left <= 15 ? 'secondary' : 'outline'"
                                        >
                                            {{ documentUrgencyLabel(document.days_left) }}
                                        </Badge>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="expiringVehicleDocuments.length === 0">
                                    <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                                        No vehicle documents expiring within 30 days.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Verified Companies</CardTitle>
                        <CardDescription>Compliance-ready companies</CardDescription>
                    </CardHeader>
                    <CardContent class="flex items-center gap-3">
                        <CheckCircle2 class="h-8 w-8 text-primary" />
                        <div class="text-2xl font-semibold">{{ stats.verified_companies }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Active Routes</CardTitle>
                        <CardDescription>Current route availability</CardDescription>
                    </CardHeader>
                    <CardContent class="flex items-center gap-3">
                        <MapPinned class="h-8 w-8 text-primary" />
                        <div class="text-2xl font-semibold">{{ stats.active_routes }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Active Gates</CardTitle>
                        <CardDescription>Terminal gate readiness</CardDescription>
                    </CardHeader>
                    <CardContent class="flex items-center gap-3">
                        <Warehouse class="h-8 w-8 text-primary" />
                        <div class="text-2xl font-semibold">{{ stats.active_gates }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Risk Items</CardTitle>
                        <CardDescription>Compliance / operations watch</CardDescription>
                    </CardHeader>
                    <CardContent class="flex items-center gap-3">
                        <CircleAlert class="h-8 w-8 text-primary" />
                        <div class="text-2xl font-semibold">
                            {{ stats.expiring_documents_count + (stats.total_routes - stats.active_routes) + (stats.total_gates - stats.active_gates) }}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
