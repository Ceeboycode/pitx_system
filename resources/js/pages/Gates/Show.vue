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
import {
    Archive,
    ArrowLeft,
    CalendarDays,
    DoorOpen,
    Layers,
    Milestone,
    UserRound,
    ArchiveX,
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

const canArchiveGate = computed(() => can('gates.archive'));
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
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

            <!-- Header card -->
            <Card>
                <CardHeader class="py-0">
                    <div class="flex items-center gap-4">
                        <div
                            class="relative h-32 w-32 shrink-0 overflow-hidden rounded-lg border-2 bg-primary shadow-sm flex items-center justify-center"
                        >
                            <DoorOpen class="h-10 w-10 text-white" />
                        </div>

                        <div class="gap-2 w-full">
                            <div class="flex flex-row gap-2 pb-2 w-full items-center">
                                <h1 class="text-2xl leading-tight font-bold tracking-tight">
                                    {{ gate.gate_name }}
                                </h1>
                                <div class="ml-2 flex flex-1 items-center">
                                    <hr class="h-px w-full border border-rose-500" />
                                    <div class="border-7 border-rose-500 rounded-xs">
                                        <div class="border-3 border-white rounded-xs"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge :class="['gap-1.5', statusClass(gate.status)]">
                                        <span :class="['h-1.5 w-1.5 rounded-full', statusDot(gate.status)]" />
                                        {{ gate.status === 'active' ? 'Active' : 'Inactive' }}
                                    </Badge>
                                    <Badge class="border-0 bg-muted font-mono text-foreground">
                                        <Milestone class="h-3 w-3 mr-1" />
                                        {{ gate.bays }} bays
                                    </Badge>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <Button
                                        as-child
                                        variant="outline"
                                        class="rounded-lg bg-card border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer"
                                    >
                                        <Link :href="index().url">
                                            <ArrowLeft class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="canArchiveGate"
                                        variant="outline"
                                        class="group/segment rounded-lg bg-card border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700 gap-0 cursor-pointer"
                                        @click="archiveOpen = true"
                                    >
                                        <Archive class="h-4 w-4 shrink-0" />
                                        <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-32 group-hover/segment:opacity-100">
                                            Archive Gate
                                        </span>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Details card -->
            <Card class="py-6">
                <CardHeader class="flex items-center justify-between">
                    <div>
                        <CardTitle>Gate Details</CardTitle>
                        <!-- <CardDescription>Configuration and audit information.</CardDescription> -->
                    </div>
                </CardHeader>
                <CardContent class="px-6 grid divide-y gap-y-2 pt-2 border-t border-slate-100">
                    <div class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Gate Name
                        </span>
                        <span class="text-sm font-semibold">{{ gate.gate_name }}</span>
                    </div>
                    <div class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Status
                        </span>
                        <Badge :class="['mt-1 gap-1.5', statusClass(gate.status)]">
                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(gate.status)]" />
                            {{ gate.status === 'active' ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                    <div class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Bays
                        </span>
                        <span class="rounded bg-muted px-2 py-0.5 font-mono text-sm font-semibold tabular-nums">
                            {{ gate.bays }}
                        </span>
                    </div>
                    <div class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Created
                        </span>
                        <span class="text-sm">
                            {{ formatDate(gate.created_at) }}
                            <template v-if="gate.creator">
                                · <span class="font-medium">{{ gate.creator.name }}</span>
                            </template>
                        </span>
                    </div>
                    <div class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Last Updated
                        </span>
                        <span class="text-sm">
                            {{ formatDate(gate.updated_at) }}
                            <template v-if="gate.updater">
                                · <span class="font-medium">{{ gate.updater.name }}</span>
                            </template>
                        </span>
                    </div>
                </CardContent>
            </Card>

            <!-- Archive dialog -->
            <Dialog :open="archiveOpen" @update:open="archiveOpen = $event">
                <DialogContent class="sm:max-w-md p-4">
                    <DialogHeader>
                        <DialogTitle>Archive Gate</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to archive
                            <span class="font-semibold text-foreground">{{ gate.gate_name }}</span>?
                            This action will remove it from active records.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2 sm:justify-end">
                        <Button variant="outline" @click="archiveOpen = false"
                            class="cursor-pointer hover:bg-slate-100"
                        >
                            Cancel
                        </Button>
                        <Button
                            class="bg-destructive text-destructive-foreground cursor-pointer hover:bg-destructive/90"
                            @click="archiveGate"
                        >
                            <ArchiveX class="h-4 w-4" />
                            Archive
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
