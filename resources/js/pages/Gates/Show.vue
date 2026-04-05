<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/can';
import { destroy, index } from '@/routes/gates';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import { Separator } from '@/components/ui/separator';

import {
    Archive,
    ArrowLeft,
    CalendarDays,
    DoorOpen,
    Layers,
    UserRound,
} from 'lucide-vue-next';

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

const props = defineProps<{ gate: Gate }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gates', href: index().url },
    { title: props.gate.gate_name, href: '#' },
];

const canArchiveGate = computed(() => can('gates.delete'));
const archiveOpen = ref(false);

function archiveGate() {
    router.delete(destroy(props.gate.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            archiveOpen.value = false;
            toast.success('Gate archived successfully.');
        },
    });
}

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
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6"
        >
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <div
                        class="flex items-center gap-2 text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                    >
                        <DoorOpen class="h-3.5 w-3.5" />
                        Gates · Details
                    </div>
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700"
                        >
                            <DoorOpen class="h-4 w-4 text-white" />
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight">
                            {{ gate.gate_name }}
                        </h1>
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
                        v-if="canArchiveGate"
                        size="sm"
                        variant="outline"
                        class="rounded-lg border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                        @click="archiveOpen = true"
                    >
                        <Archive class="mr-2 h-4 w-4" />
                        Archive
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <div
                    class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <div
                        class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700"
                    >
                        <DoorOpen class="h-4 w-4 text-white" />
                    </div>
                    <p
                        class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                    >
                        Gate Name
                    </p>
                    <p class="mt-0.5 truncate text-sm font-bold">
                        {{ gate.gate_name }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <div
                        class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg"
                        :class="
                            gate.status === 'active'
                                ? 'bg-emerald-600'
                                : 'bg-slate-400'
                        "
                    >
                        <DoorOpen class="h-4 w-4 text-white" />
                    </div>
                    <p
                        class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                    >
                        Status
                    </p>
                    <Badge :class="['mt-1 gap-1.5', statusClass(gate.status)]">
                        <span
                            :class="[
                                'h-1.5 w-1.5 rounded-full',
                                statusDot(gate.status),
                            ]"
                        />
                        {{ gate.status === 'active' ? 'Active' : 'Inactive' }}
                    </Badge>
                </div>

                <div
                    class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <div
                        class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600"
                    >
                        <Layers class="h-4 w-4 text-white" />
                    </div>
                    <p
                        class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                    >
                        Bays
                    </p>
                    <p class="mt-0.5 text-3xl font-bold tabular-nums">
                        {{ gate.bays }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <div
                        class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-600"
                    >
                        <UserRound class="h-4 w-4 text-white" />
                    </div>
                    <p
                        class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                    >
                        Created By
                    </p>
                    <p class="mt-0.5 truncate text-sm font-bold">
                        {{ gate.creator?.name ?? '—' }}
                    </p>
                </div>
            </div>

            <Card>
                <CardHeader class="border-b border-slate-100 pb-4">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <DoorOpen class="h-4 w-4 text-blue-700" />
                        Gate Details
                    </CardTitle>
                    <CardDescription
                        >Full configuration and audit
                        information.</CardDescription
                    >
                </CardHeader>

                <CardContent class="divide-y divide-slate-100 p-0">
                    <div class="flex items-center justify-between px-6 py-3">
                        <span
                            class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                            >Gate Name</span
                        >
                        <span class="text-sm font-semibold">{{
                            gate.gate_name
                        }}</span>
                    </div>

                    <div class="flex items-center justify-between px-6 py-3">
                        <span
                            class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                            >Status</span
                        >
                        <Badge :class="['gap-1.5', statusClass(gate.status)]">
                            <span
                                :class="[
                                    'h-1.5 w-1.5 rounded-full',
                                    statusDot(gate.status),
                                ]"
                            />
                            {{
                                gate.status === 'active' ? 'Active' : 'Inactive'
                            }}
                        </Badge>
                    </div>

                    <div class="flex items-center justify-between px-6 py-3">
                        <span
                            class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                            >Bays</span
                        >
                        <span
                            class="rounded bg-muted px-2 py-0.5 font-mono text-sm font-semibold tabular-nums"
                            >{{ gate.bays }}</span
                        >
                    </div>

                    <Separator />

                    <div class="flex items-center justify-between px-6 py-3">
                        <div
                            class="flex items-center gap-2 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            <CalendarDays class="h-3.5 w-3.5" />
                            Created
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-muted-foreground">
                                {{ formatDate(gate.created_at) }}
                            </p>
                            <p
                                v-if="gate.creator"
                                class="text-xs text-muted-foreground"
                            >
                                by
                                <span class="font-medium text-foreground">{{
                                    gate.creator.name
                                }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between px-6 py-3">
                        <div
                            class="flex items-center gap-2 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            <CalendarDays class="h-3.5 w-3.5" />
                            Last Updated
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-muted-foreground">
                                {{ formatDate(gate.updated_at) }}
                            </p>
                            <p
                                v-if="gate.updater"
                                class="text-xs text-muted-foreground"
                            >
                                by
                                <span class="font-medium text-foreground">{{
                                    gate.updater.name
                                }}</span>
                            </p>
                            <p v-else class="text-xs text-muted-foreground">
                                —
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Dialog :open="archiveOpen" @update:open="archiveOpen = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Archive Gate</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to archive
                            <span class="font-semibold text-foreground">{{
                                gate.gate_name
                            }}</span
                            >? This action will remove it from active records.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter class="gap-2 sm:justify-end">
                        <Button variant="outline" @click="archiveOpen = false">
                            Cancel
                        </Button>

                        <Button
                            class="bg-rose-600 text-white hover:bg-rose-700"
                            @click="archiveGate"
                        >
                            Archive
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
