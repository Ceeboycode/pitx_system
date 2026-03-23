<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { edit, index } from '@/routes/gates';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

import {
    ArrowLeft,
    CalendarDays,
    DoorOpen,
    Layers,
    Pencil,
    UserRound,
} from 'lucide-vue-next';

/* ── Types ──────────────────────────────────────────────────────── */
type UserMini = { id: number; name: string };

type Gate = {
    id: number;
    gate_name: string;
    status: 'active' | 'inactive';
    bays: number;
    created_at?: string | null;
    updated_at?: string | null;
    creator?: UserMini | null;
    updater?: UserMini | null;
};

/* ── Props ───────────────────────────────────────────────────────── */
const props = defineProps<{ gate: Gate }>();

/* ── Breadcrumbs ─────────────────────────────────────────────────── */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gates', href: index().url },
    { title: props.gate.gate_name, href: '#' },
];

/* ── Helpers ─────────────────────────────────────────────────────── */
function statusClass(status: Gate['status']): string {
    return status === 'active'
        ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
        : 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(status: Gate['status']): string {
    return status === 'active' ? 'bg-emerald-500' : 'bg-slate-400';
}

function formatDate(value?: string | null): string {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head :title="`Gate — ${gate.gate_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">

            <!-- ── Page header ─────────────────────────────────────── -->
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                        <DoorOpen class="h-3.5 w-3.5" />
                        Gates · Details
                    </div>
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700">
                            <DoorOpen class="h-4 w-4 text-white" />
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ gate.gate_name }}</h1>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Gate profile, configuration, and audit details.
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <Button
                        as-child
                        size="sm"
                        variant="outline"
                        class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                    >
                        <Link :href="index().url">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Gates
                        </Link>
                    </Button>

                    <Button
                        as-child
                        size="sm"
                        class="rounded-lg bg-blue-700 text-white hover:bg-blue-800 border-0 shadow-sm"
                    >
                        <Link :href="edit(gate.id).url">
                            <Pencil class="mr-2 h-4 w-4" />
                            Edit Gate
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- ── Stat cards ──────────────────────────────────────── -->
            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700">
                        <DoorOpen class="h-4 w-4 text-white" />
                    </div>
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Gate Name</p>
                    <p class="mt-0.5 text-sm font-bold truncate">{{ gate.gate_name }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg"
                        :class="gate.status === 'active' ? 'bg-emerald-600' : 'bg-slate-400'"
                    >
                        <DoorOpen class="h-4 w-4 text-white" />
                    </div>
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Status</p>
                    <Badge :class="['mt-1 gap-1.5', statusClass(gate.status)]">
                        <span :class="['h-1.5 w-1.5 rounded-full', statusDot(gate.status)]" />
                        {{ gate.status === 'active' ? 'Active' : 'Inactive' }}
                    </Badge>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600">
                        <Layers class="h-4 w-4 text-white" />
                    </div>
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Bays</p>
                    <p class="mt-0.5 text-3xl font-bold tabular-nums">{{ gate.bays }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-600">
                        <UserRound class="h-4 w-4 text-white" />
                    </div>
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Created By</p>
                    <p class="mt-0.5 text-sm font-bold truncate">{{ gate.creator?.name ?? '—' }}</p>
                </div>

            </div>

            <!-- ── Details card ────────────────────────────────────── -->
            <Card>
                <CardHeader class="border-b border-slate-100 pb-4">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <DoorOpen class="h-4 w-4 text-blue-700" />
                        Gate Details
                    </CardTitle>
                    <CardDescription>Full configuration and audit information.</CardDescription>
                </CardHeader>

                <CardContent class="divide-y divide-slate-100 p-0">

                    <div class="flex items-center justify-between px-6 py-3">
                        <span class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Gate Name</span>
                        <span class="text-sm font-semibold">{{ gate.gate_name }}</span>
                    </div>

                    <div class="flex items-center justify-between px-6 py-3">
                        <span class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Status</span>
                        <Badge :class="['gap-1.5', statusClass(gate.status)]">
                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(gate.status)]" />
                            {{ gate.status === 'active' ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>

                    <div class="flex items-center justify-between px-6 py-3">
                        <span class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Bays</span>
                        <span class="rounded bg-muted px-2 py-0.5 font-mono text-sm font-semibold tabular-nums">{{ gate.bays }}</span>
                    </div>

                    <Separator />

                    <!-- Audit info -->
                    <div class="flex items-center justify-between px-6 py-3">
                        <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
                            <CalendarDays class="h-3.5 w-3.5" />
                            Created
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-muted-foreground">{{ formatDate(gate.created_at) }}</p>
                            <p v-if="gate.creator" class="text-xs text-muted-foreground">
                                by <span class="font-medium text-foreground">{{ gate.creator.name }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between px-6 py-3">
                        <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
                            <CalendarDays class="h-3.5 w-3.5" />
                            Last Updated
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-muted-foreground">{{ formatDate(gate.updated_at) }}</p>
                            <p v-if="gate.updater" class="text-xs text-muted-foreground">
                                by <span class="font-medium text-foreground">{{ gate.updater.name }}</span>
                            </p>
                            <p v-else class="text-xs text-muted-foreground">—</p>
                        </div>
                    </div>

                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>