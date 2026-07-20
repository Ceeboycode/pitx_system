<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

import {
    RiArrowLeftRightLine,
    RiBuildingLine,
    RiBusLine,
    RiDoorOpenLine,
    RiFileWarningLine,
    RiGitBranchLine,
    RiShieldCheckLine,
} from 'vue-remix-icons';

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
    company: { name: string; code: string } | null;
    vehicle: { plate_number: string; vehicle_type: string } | null;
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
    vehicle: { plate_number: string; vehicle_type: string } | null;
    company: { name: string; code: string } | null;
};

type SummaryReport = {
    system_health: { label: string; description: string };
    compliance: { label: string; description: string };
    operations: { label: string; description: string };
    companies: { label: string; description: string };
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
    { title: 'Dashboard', href: dashboard().url },
];

function formatStatus(value: string) {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function statusBadgeVariant(status: string) {
    const s = status.toLowerCase();
    if (['verified', 'healthy', 'stable', 'tracked'].includes(s))
        return 'success';
    if (
        [
            'active',
            'dispatched',
            'departed',
            'running',
            'docs_completed',
        ].includes(s)
    )
        return 'blue';
    if (
        ['pending', 'arrived', 'watchlist active', 'needs attention'].includes(
            s,
        )
    )
        return 'warning';
    if (['needs_revision'].includes(s)) return 'orange';
    if (['rejected', 'invalid', 'expired', 'empty'].includes(s))
        return 'destructive';
    if (['inactive', 'no activity'].includes(s)) return 'muted';
    return 'muted';
}

function progressWidth(value: number, max: number) {
    if (max <= 0) return '0%';
    return `${Math.max(8, Math.round((value / max) * 100))}%`;
}

const maxGateDispatchCount = computed(() => {
    const counts = props.gateActivity.map(
        (item) => item.dispatches_today_count,
    );
    return Math.max(...counts, 1);
});

const companyChartData = computed(() => {
    const total = props.stats.total_companies || 1;
    return [
        {
            label: 'Verified',
            value: props.stats.verified_companies,
            color: '#1d4ed8',
            percent: Math.round((props.stats.verified_companies / total) * 100),
        },
        {
            label: 'Pending / Workflow',
            value: props.stats.pending_companies,
            color: '#dc2626',
            percent: Math.round((props.stats.pending_companies / total) * 100),
        },
        {
            label: 'Other',
            value: Math.max(
                props.stats.total_companies -
                    props.stats.verified_companies -
                    props.stats.pending_companies,
                0,
            ),
            color: '#cbd5e1',
            percent: Math.max(
                0,
                100 -
                    Math.round((props.stats.verified_companies / total) * 100) -
                    Math.round((props.stats.pending_companies / total) * 100),
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
            color: '#1d4ed8',
            percent: Math.round((props.stats.arrived_today / total) * 100),
        },
        {
            label: 'Departed',
            value: props.stats.departed_today,
            color: '#dc2626',
            percent: Math.round((props.stats.departed_today / total) * 100),
        },
        {
            label: 'Dispatched',
            value: props.stats.dispatched_today,
            color: '#64748b',
            percent: Math.round((props.stats.dispatched_today / total) * 100),
        },
    ].filter((item) => item.value > 0);
});

function polarToCartesian(
    cx: number,
    cy: number,
    radius: number,
    angle: number,
) {
    const rad = ((angle - 90) * Math.PI) / 180;
    return { x: cx + radius * Math.cos(rad), y: cy + radius * Math.sin(rad) };
}

function describeArc(
    cx: number,
    cy: number,
    radius: number,
    startAngle: number,
    endAngle: number,
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

const companySegments = computed(() =>
    buildDonutSegments(companyChartData.value),
);
const dispatchSegments = computed(() =>
    buildDonutSegments(dispatchChartData.value),
);

function documentUrgencyVariant(daysLeft: number) {
    if (daysLeft <= 7) return 'destructive';
    if (daysLeft <= 15) return 'warning';
    return 'muted';
}

function documentUrgencyLabel(daysLeft: number) {
    if (daysLeft <= 7) return 'Urgent';
    if (daysLeft <= 15) return 'Soon';
    return 'Upcoming';
}

function alertItemClass(condition: boolean, danger = false) {
    if (!condition)
        return 'border border-custom-bg-dark bg-transparent dark:border-custom-bg-light';
    return danger
        ? 'border border-destructive/30 bg-destructive/10 text-destructive'
        : 'border border-custom-accent/30 bg-custom-accent/10 text-custom-shadow';
}

function rankBadgeClass(index: number) {
    if (index === 0) return 'bg-custom-primary text-custom-bg-light';
    if (index === 1) return 'bg-custom-bg-dark text-custom-shadow';
    return 'bg-custom-accent text-custom-bg-light';
}

const today = new Date().toLocaleDateString('en-PH', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full min-h-0 w-full flex-1 flex-col gap-4 overflow-y-auto no-scrollbar"
        >
        <!-- TODO: make the card bg a gradient -->
            <Card class="shrink-0 bg-custom-primary pb-0">
                <CardHeader
                    class="flex flex-row justify-between"
                >
                    <div>
                        <CardTitle class="text-custom-bg-light">
                            Good morning, {{ $page.props.auth.user.name }}
                        </CardTitle
                        >
                        <CardDescription class="mt-2 text-custom-bg-light">
                            See terminal and system overviews, manage your tasks.
                        </CardDescription>
                    </div>
                    <div class="text-right h-full flex items-center">
                        <div
                            class=" text-custom-bg-light text-sm"
                        >
                            {{ today }}
                        </div>
                    </div>
                </CardHeader>
                <CardContent
                    class="h-6 mt-6 rounded-b-md bg-[repeating-linear-gradient(135deg,var(--custom-bg-light),var(--custom-bg-light)_12px,var(--custom-bg-dark)_12px,var(--custom-bg-dark)_24px)]"
                ></CardContent>
            </Card>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle
                            >Companies</CardTitle
                        >
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-md bg-custom-bg dark:bg-custom-bg-dark"
                        >
                            <RiBuildingLine
                                class="h-4 w-4 text-custom-primary"
                            />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold tracking-tight">
                            {{ stats.total_companies }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            <span class="font-medium text-blue-700"
                                >{{ stats.verified_companies }} verified</span
                            >
                            · {{ stats.pending_companies }} in process
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                            >Fleet</CardTitle
                        >
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-md bg-custom-bg dark:bg-custom-bg-dark"
                        >
                            <RiBusLine class="h-4 w-4 text-custom-primary" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold tracking-tight">
                            {{ stats.total_vehicles }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            <span class="font-medium text-blue-700"
                                >{{ stats.active_vehicles }} active</span
                            >
                            vehicles
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                            >Network</CardTitle
                        >
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-md bg-custom-bg dark:bg-custom-bg-dark"
                        >
                            <RiGitBranchLine
                                class="text-custom-accent h-4 w-4"
                            />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold tracking-tight">
                            {{ stats.active_routes }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ stats.total_routes }} routes ·
                            <span class="font-medium text-red-600"
                                >{{ stats.active_gates }}/{{
                                    stats.total_gates
                                }}
                                gates</span
                            >
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                            >Dispatches Today</CardTitle
                        >
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-md bg-custom-bg dark:bg-custom-bg-dark"
                        >
                            <RiArrowLeftRightLine
                                class="text-custom-accent h-4 w-4"
                            />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold tracking-tight">
                            {{ stats.dispatches_today }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            <span class="font-medium text-red-600"
                                >{{ stats.total_pax_today }} passengers</span
                            >
                            logged today
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <Card class="xl:col-span-2">
                    <CardHeader class="pb-3">
                        <div class="flex items-center gap-2">
                            <CardTitle>System Summary Report</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="grid gap-3 md:grid-cols-2">
                        <div
                            class="rounded-xl border p-4 transition-colors hover:border-blue-700"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <RiShieldCheckLine
                                        class="h-4 w-4 text-blue-700"
                                    />
                                    <span class="text-sm font-semibold"
                                        >System Health</span
                                    >
                                </div>
                                <Badge
                                    :variant="
                                        statusBadgeVariant(
                                            summaryReport.system_health.label,
                                        )
                                    "
                                >
                                    {{ summaryReport.system_health.label }}
                                </Badge>
                            </div>
                            <Separator class="mb-3" />
                            <p
                                class="text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ summaryReport.system_health.description }}
                            </p>
                        </div>

                        <div
                            class="rounded-xl border p-4 transition-colors hover:border-blue-700"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <RiFileWarningLine
                                        class="h-4 w-4 text-red-600"
                                    />
                                    <span class="text-sm font-semibold"
                                        >Compliance</span
                                    >
                                </div>
                                <Badge
                                    :variant="
                                        statusBadgeVariant(
                                            summaryReport.compliance.label,
                                        )
                                    "
                                >
                                    {{ summaryReport.compliance.label }}
                                </Badge>
                            </div>
                            <Separator class="mb-3" />
                            <p
                                class="text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ summaryReport.compliance.description }}
                            </p>
                        </div>

                        <div
                            class="rounded-xl border p-4 transition-colors hover:border-blue-700"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <RiDoorOpenLine
                                        class="h-4 w-4 text-blue-700"
                                    />
                                    <span class="text-sm font-semibold"
                                        >Operations</span
                                    >
                                </div>
                                <Badge
                                    :variant="
                                        statusBadgeVariant(
                                            summaryReport.operations.label,
                                        )
                                    "
                                >
                                    {{ summaryReport.operations.label }}
                                </Badge>
                            </div>
                            <Separator class="mb-3" />
                            <p
                                class="text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ summaryReport.operations.description }}
                            </p>
                        </div>

                        <div
                            class="rounded-xl border p-4 transition-colors hover:border-blue-700"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <RiBuilding2Line
                                        class="h-4 w-4 text-red-600"
                                    />
                                    <span class="text-sm font-semibold"
                                        >Companies</span
                                    >
                                </div>
                                <Badge
                                    :variant="
                                        statusBadgeVariant(
                                            summaryReport.companies.label,
                                        )
                                    "
                                >
                                    {{ summaryReport.companies.label }}
                                </Badge>
                            </div>
                            <Separator class="mb-3" />
                            <p
                                class="text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ summaryReport.companies.description }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <div class="flex items-center gap-2">
                            <div class="h-5 w-1 rounded-full bg-red-600" />
                            <CardTitle>Alert Snapshot</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div
                            class="flex items-center justify-between rounded-lg px-4 py-3"
                            :class="
                                alertItemClass(
                                    stats.expiring_documents_count > 0,
                                    true,
                                )
                            "
                        >
                            <div class="flex items-center gap-2">
                                <RiFileWarningLine class="h-4 w-4 shrink-0" />
                                <span class="text-sm font-medium"
                                    >Expiring vehicle docs</span
                                >
                            </div>
                            <Badge
                                :variant="
                                    stats.expiring_documents_count > 0
                                        ? 'destructive'
                                        : 'success'
                                "
                            >
                                {{ stats.expiring_documents_count }}
                            </Badge>
                        </div>

                        <div
                            class="flex items-center justify-between rounded-lg px-4 py-3"
                            :class="alertItemClass(stats.pending_companies > 0)"
                        >
                            <div class="flex items-center gap-2">
                                <RiBuildingLine class="h-4 w-4 shrink-0" />
                                <span class="text-sm font-medium"
                                    >Pending companies</span
                                >
                            </div>
                            <Badge
                                :variant="
                                    stats.pending_companies > 0
                                        ? 'warning'
                                        : 'success'
                                "
                            >
                                {{ stats.pending_companies }}
                            </Badge>
                        </div>

                        <div
                            class="flex items-center justify-between rounded-lg px-4 py-3"
                            :class="
                                alertItemClass(
                                    stats.total_routes - stats.active_routes >
                                        0,
                                )
                            "
                        >
                            <div class="flex items-center gap-2">
                                <RiGitBranchLine class="h-4 w-4 shrink-0" />
                                <span class="text-sm font-medium"
                                    >Inactive routes</span
                                >
                            </div>
                            <Badge
                                :variant="
                                    stats.total_routes - stats.active_routes > 0
                                        ? 'muted'
                                        : 'success'
                                "
                            >
                                {{ stats.total_routes - stats.active_routes }}
                            </Badge>
                        </div>

                        <div
                            class="flex items-center justify-between rounded-lg px-4 py-3"
                            :class="
                                alertItemClass(
                                    stats.total_gates - stats.active_gates > 0,
                                )
                            "
                        >
                            <div class="flex items-center gap-2">
                                <RiDoorOpenLine class="h-4 w-4 shrink-0" />
                                <span class="text-sm font-medium"
                                    >Inactive gates</span
                                >
                            </div>
                            <Badge
                                :variant="
                                    stats.total_gates - stats.active_gates > 0
                                        ? 'muted'
                                        : 'success'
                                "
                            >
                                {{ stats.total_gates - stats.active_gates }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <Card>
                    <CardHeader class="pb-3">
                        <div class="flex items-center gap-2">
                            <CardTitle>Company KPI Distribution</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent
                        class="flex flex-col gap-6 lg:flex-row lg:items-center"
                    >
                        <!-- <div class="mx-auto flex items-center justify-center"> -->
                            <!-- <svg viewBox="0 0 120 120" class="h-48 w-48">
                                <circle
                                    cx="60"
                                    cy="60"
                                    r="42"
                                    fill="none"
                                    stroke="hsl(var(--muted))"
                                    stroke-width="14"
                                />
                                <path
                                    v-for="(segment, index) in companySegments"
                                    :key="index"
                                    :d="segment.path"
                                    fill="none"
                                    :stroke="segment.color"
                                    stroke-width="14"
                                    stroke-linecap="round"
                                />
                                <text
                                    x="60"
                                    y="54"
                                    text-anchor="middle"
                                    style="font-size: 8px; font-weight: 500"
                                    fill="#94a3b8"
                                >
                                    Companies
                                </text>
                                <text
                                    x="60"
                                    y="70"
                                    text-anchor="middle"
                                    style="font-size: 16px; font-weight: 700"
                                    fill="currentColor"
                                >
                                    {{ stats.total_companies }}
                                </text>
                            </svg> -->
                        <!-- </div> -->
                        <div class="flex-1 space-y-2">
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
                                        <div class="text-sm font-medium">
                                            {{ item.label }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ item.percent }}%
                                        </div>
                                    </div>
                                </div>
                                <div class="text-sm font-bold">
                                    {{ item.value }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <div class="flex items-center gap-2">
                            <CardTitle>Dispatch KPI Distribution</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent
                        class="flex flex-col gap-6 lg:flex-row lg:items-center"
                    >
                        <!-- <div class="mx-auto flex items-center justify-center"> -->
                            <!-- <svg viewBox="0 0 120 120" class="h-48 w-48">
                                <circle
                                    cx="60"
                                    cy="60"
                                    r="42"
                                    fill="none"
                                    stroke="hsl(var(--muted))"
                                    stroke-width="14"
                                />
                                <path
                                    v-for="(segment, index) in dispatchSegments"
                                    :key="index"
                                    :d="segment.path"
                                    fill="none"
                                    :stroke="segment.color"
                                    stroke-width="14"
                                    stroke-linecap="round"
                                />
                                <text
                                    x="60"
                                    y="54"
                                    text-anchor="middle"
                                    style="font-size: 8px; font-weight: 500"
                                    fill="#94a3b8"
                                >
                                    Dispatches
                                </text>
                                <text
                                    x="60"
                                    y="70"
                                    text-anchor="middle"
                                    style="font-size: 16px; font-weight: 700"
                                    fill="currentColor"
                                >
                                    {{ stats.dispatches_today }}
                                </text>
                            </svg> -->
                        <!-- </div> -->
                        <div class="flex-1 space-y-2">
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
                                        <div class="text-sm font-medium">
                                            {{ item.label }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ item.percent }}%
                                        </div>
                                    </div>
                                </div>
                                <div class="text-sm font-bold">
                                    {{ item.value }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <Card class="xl:col-span-2">
                    <CardHeader class="pb-3">
                        <div class="flex items-center gap-2">
                            <CardTitle>Recent Dispatches</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <div
                                class="min-w-[900px] overflow-hidden rounded-md border border-custom-bg-dark dark:border-custom-bg-light"
                            >
                                <div>
                                    <div
                                        class="grid grid-cols-7 gap-2 border-b border-custom-bg-dark bg-custom-bg text-xs tracking-wider uppercase dark:border-custom-bg-light dark:bg-custom-bg-light"
                                    >
                                        <div
                                            class="flex h-10 items-center pl-3 font-semibold tracking-widest text-custom-shadow/80"
                                        >
                                            Company
                                        </div>
                                        <div
                                            class="flex h-10 items-center font-semibold tracking-widest text-custom-shadow/80"
                                        >
                                            Vehicle
                                        </div>
                                        <div
                                            class="flex h-10 items-center font-semibold tracking-widest text-custom-shadow/80"
                                        >
                                            Route
                                        </div>
                                        <div
                                            class="flex h-10 items-center font-semibold tracking-widest text-custom-shadow/80"
                                        >
                                            Gate / Bay
                                        </div>
                                        <div
                                            class="flex h-10 items-center font-semibold tracking-widest text-custom-shadow/80"
                                        >
                                            PAX
                                        </div>
                                        <div
                                            class="flex h-10 items-center font-semibold tracking-widest text-custom-shadow/80"
                                        >
                                            Status
                                        </div>
                                        <div
                                            class="flex h-10 items-center pr-3 font-semibold tracking-widest text-custom-shadow/80"
                                        >
                                            Time
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div
                                        v-for="item in recentDispatches"
                                        :key="item.id"
                                        class="grid grid-cols-7 items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors last:border-b-0 hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light"
                                    >
                                        <div class="min-w-0 py-2 pl-3">
                                            <div class="text-sm font-semibold">
                                                {{ item.company?.name ?? '—' }}
                                            </div>
                                            <div
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ item.company?.code ?? '—' }}
                                            </div>
                                        </div>
                                        <div class="min-w-0 py-2">
                                            <div class="text-sm font-semibold">
                                                {{
                                                    item.vehicle
                                                        ?.plate_number ?? '—'
                                                }}
                                            </div>
                                            <div
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    item.vehicle
                                                        ?.vehicle_type ?? '—'
                                                }}
                                            </div>
                                        </div>
                                        <div
                                            class="min-w-0 truncate py-2 text-sm"
                                        >
                                            {{ item.route ?? '—' }}
                                        </div>
                                        <div class="min-w-0 py-2">
                                            <div class="text-sm">
                                                {{ item.gate ?? '—' }}
                                            </div>
                                            <div
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    item.bay_number ?? 'No bay'
                                                }}
                                            </div>
                                        </div>
                                        <div
                                            class="py-2 text-sm font-medium tabular-nums"
                                        >
                                            {{ item.pax_count }}
                                        </div>
                                        <div class="py-2">
                                            <Badge
                                                :variant="
                                                    statusBadgeVariant(
                                                        item.status,
                                                    )
                                                "
                                            >
                                                {{ formatStatus(item.status) }}
                                            </Badge>
                                        </div>
                                        <div
                                            class="py-2 pr-3 text-xs text-custom-shadow/70"
                                        >
                                            {{
                                                item.dispatched_at ??
                                                item.departed_at ??
                                                item.arrived_at ??
                                                item.created_at ??
                                                '—'
                                            }}
                                        </div>
                                    </div>
                                    <div v-if="recentDispatches.length === 0">
                                        <div
                                            class="col-span-7 flex h-24 items-center justify-center text-center text-custom-shadow/70"
                                        >
                                            No recent dispatches found.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <div class="flex items-center gap-2">
                            <CardTitle>Gate Activity</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div
                            v-for="gate in gateActivity"
                            :key="gate.id"
                            class="space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-semibold">
                                        {{ gate.gate_name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ gate.bays }} bays ·
                                        {{ formatStatus(gate.status) }}
                                    </div>
                                </div>
                                <div class="text-sm font-bold text-blue-700">
                                    {{ gate.dispatches_today_count }}
                                </div>
                            </div>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-2 rounded-full"
                                    :style="{
                                        width: progressWidth(
                                            gate.dispatches_today_count,
                                            maxGateDispatchCount,
                                        ),
                                        background:
                                            'linear-gradient(90deg, #1d4ed8, #dc2626)',
                                    }"
                                />
                            </div>
                        </div>
                        <div
                            v-if="gateActivity.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            No gate activity available.
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader class="pb-3">
                        <div class="flex items-center gap-2">
                            <CardTitle>Top Companies Today</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div
                            v-for="(company, index) in topCompanies"
                            :key="company.id"
                            class="flex items-center justify-between rounded-lg border px-4 py-3 transition-colors hover:bg-muted/40"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                    :class="rankBadgeClass(index)"
                                >
                                    {{ index + 1 }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold">
                                        {{ company.company_name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ company.company_code }} ·
                                        {{ company.vehicles_count }} vehicles
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge
                                    :variant="
                                        statusBadgeVariant(company.status)
                                    "
                                >
                                    {{ formatStatus(company.status) }}
                                </Badge>
                                <div
                                    class="min-w-[2rem] text-right text-sm font-bold"
                                >
                                    {{ company.dispatches_today_count }}
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="topCompanies.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            No company activity available.
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <div class="flex items-center gap-2">
                            <CardTitle>Company Status Breakdown</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div
                            v-for="item in companyStatus"
                            :key="item.status"
                            class="flex items-center justify-between rounded-lg border px-4 py-3 transition-colors hover:bg-muted/40"
                        >
                            <Badge :variant="statusBadgeVariant(item.status)">
                                {{ formatStatus(item.status) }}
                            </Badge>
                            <span class="text-sm font-bold">{{
                                item.total
                            }}</span>
                        </div>
                        <div
                            v-if="companyStatus.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            No company data available.
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div>
                                <CardTitle
                                    >Vehicle Documents Expiring Soon</CardTitle
                                >
                            </div>
                        </div>
                        <Badge
                            v-if="stats.expiring_documents_count > 0"
                            variant="destructive"
                            class="shrink-0"
                        >
                            {{ stats.expiring_documents_count }} at risk
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <div
                            class="min-w-[800px] overflow-hidden rounded-md border border-custom-bg-dark dark:border-custom-bg-light"
                        >
                            <div>
                                <div
                                    class="grid grid-cols-6 gap-2 border-b border-custom-bg-dark bg-custom-bg text-xs tracking-wider uppercase dark:border-custom-bg-light dark:bg-custom-bg-light"
                                >
                                    <div
                                        class="flex h-10 items-center pl-3 font-semibold tracking-widest text-custom-shadow/80"
                                    >
                                        Company
                                    </div>
                                    <div
                                        class="flex h-10 items-center font-semibold tracking-widest text-custom-shadow/80"
                                    >
                                        Vehicle
                                    </div>
                                    <div
                                        class="flex h-10 items-center font-semibold tracking-widest text-custom-shadow/80"
                                    >
                                        Document
                                    </div>
                                    <div
                                        class="flex h-10 items-center font-semibold tracking-widest text-custom-shadow/80"
                                    >
                                        Expiry
                                    </div>
                                    <div
                                        class="flex h-10 items-center font-semibold tracking-widest text-custom-shadow/80"
                                    >
                                        Days Left
                                    </div>
                                    <div
                                        class="flex h-10 items-center pr-3 font-semibold tracking-widest text-custom-shadow/80"
                                    >
                                        Priority
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div
                                    v-for="document in expiringVehicleDocuments"
                                    :key="document.id"
                                    class="grid grid-cols-6 items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors last:border-b-0 dark:border-custom-bg-light"
                                    :class="
                                        document.days_left <= 7
                                            ? 'bg-red-50 hover:bg-red-100'
                                            : document.days_left <= 15
                                              ? 'bg-amber-50 hover:bg-amber-100'
                                              : 'hover:bg-slate-50'
                                    "
                                >
                                    <div class="min-w-0 py-2 pl-3">
                                        <div class="text-sm font-semibold">
                                            {{ document.company?.name ?? '—' }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ document.company?.code ?? '—' }}
                                        </div>
                                    </div>
                                    <div class="min-w-0 py-2">
                                        <div class="text-sm font-semibold">
                                            {{
                                                document.vehicle
                                                    ?.plate_number ?? '—'
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                document.vehicle
                                                    ?.vehicle_type ?? '—'
                                            }}
                                        </div>
                                    </div>
                                    <div class="py-2 text-sm">
                                        {{
                                            formatStatus(document.document_type)
                                        }}
                                    </div>
                                    <div class="py-2 text-sm">
                                        {{ document.expires_at }}
                                    </div>
                                    <div class="py-2">
                                        <span
                                            class="text-sm font-bold"
                                            :class="
                                                document.days_left <= 7
                                                    ? 'text-red-600'
                                                    : document.days_left <= 15
                                                      ? 'text-amber-600'
                                                      : 'text-muted-foreground'
                                            "
                                        >
                                            {{ document.days_left }}d
                                        </span>
                                    </div>
                                    <div class="py-2 pr-3">
                                        <Badge
                                            :variant="
                                                documentUrgencyVariant(
                                                    document.days_left,
                                                )
                                            "
                                        >
                                            {{
                                                documentUrgencyLabel(
                                                    document.days_left,
                                                )
                                            }}
                                        </Badge>
                                    </div>
                                </div>
                                <div
                                    v-if="expiringVehicleDocuments.length === 0"
                                >
                                    <div
                                        class="col-span-6 flex h-24 items-center justify-center text-center text-custom-shadow/70"
                                    >
                                        No vehicle documents expiring within 30
                                        days.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
