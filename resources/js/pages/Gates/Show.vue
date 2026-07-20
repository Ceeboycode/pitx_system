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
import { Archive, ArchiveX } from 'lucide-vue-next';
import { RiArrowLeftLine } from 'vue-remix-icons';
// import Separator from '@/components/ui/separator/Separator.vue'
import { Separator } from '@/components/ui/separator';

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
        <div class="flex h-full min-h-0 w-full flex-1 flex-col">
            <Card class="flex min-h-0 min-w-0 flex-1 flex-col">
                <CardHeader class="flex flex-row items-start gap-3">
                    <Button as-child variant="header-actions" size="icon">
                        <Link :href="index().url" aria-label="Back to gates">
                            <RiArrowLeftLine class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <CardTitle class="truncate font-semibold">{{
                            gate.gate_name
                        }}</CardTitle>
                        <CardDescription
                            >Review gate capacity, status, and
                            activity.</CardDescription
                        >
                    </div>
                    <Badge
                        :class="[
                            'mt-1 shrink-0 gap-1.5',
                            statusClass(gate.status),
                        ]"
                    >
                        <span
                            :class="[
                                'h-1.5 w-1.5 rounded-full',
                                statusDot(gate.status),
                            ]"
                        />
                        {{ gate.status === 'active' ? 'Active' : 'Inactive' }}
                    </Badge>
                </CardHeader>

                <CardContent
                    class="no-scrollbar min-h-0 flex-1 space-y-6 overflow-y-auto py-2"
                >
                    <section class="space-y-4">
                        <div
                            class="flex min-h-52 flex-col items-center justify-center rounded-md border border-custom-bg-dark bg-custom-bg p-6 text-center dark:border-custom-bg-light dark:bg-custom-bg-dark"
                        >
                            <p class="text-lg font-semibold">
                                {{ gate.gate_name }}
                            </p>
                            <p class="mt-1 text-sm text-custom-shadow/80">
                                Terminal gate overview
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div
                                class="space-y-2 rounded-md border border-custom-bg-dark bg-custom-bg p-3 dark:border-custom-bg-light dark:bg-custom-bg-dark"
                            >
                                <p class="text-xs text-custom-shadow/80">
                                    Status
                                </p>
                                <Badge
                                    :class="[
                                        'gap-1.5',
                                        statusClass(gate.status),
                                    ]"
                                    ><span
                                        :class="[
                                            'h-1.5 w-1.5 rounded-full',
                                            statusDot(gate.status),
                                        ]"
                                    />{{
                                        gate.status === 'active'
                                            ? 'Active'
                                            : 'Inactive'
                                    }}</Badge
                                >
                            </div>
                            <div
                                class="space-y-2 rounded-md border border-custom-bg-dark bg-custom-bg p-3 dark:border-custom-bg-light dark:bg-custom-bg-dark"
                            >
                                <p class="text-xs text-custom-shadow/80">
                                    Bay Capacity
                                </p>
                                <p class="text-lg font-semibold">
                                    {{ gate.bays }} bays
                                </p>
                            </div>
                            <div
                                class="space-y-2 rounded-md border border-custom-bg-dark bg-custom-bg p-3 dark:border-custom-bg-light dark:bg-custom-bg-dark"
                            >
                                <p class="text-xs text-custom-shadow/80">
                                    Gate ID
                                </p>
                                <p class="font-mono text-lg font-semibold">
                                    #{{ gate.id }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3 pt-2">
                            <p class="font-semibold text-custom-accent-3 text-base">
                                Details
                            </p>
                            <Separator class="flex-1" />
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="space-y-2">
                                <p class="text-sm font-medium">Gate Name</p>
                                <div
                                    class="flex h-9 items-center rounded-md border border-custom-bg-dark bg-custom-bg px-3 text-sm dark:border-custom-bg-light dark:bg-custom-bg-dark"
                                >
                                    {{ gate.gate_name }}
                                </div>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-medium">
                                    Number of Bays
                                </p>
                                <div
                                    class="flex h-9 items-center rounded-md border border-custom-bg-dark bg-custom-bg px-3 text-sm dark:border-custom-bg-light dark:bg-custom-bg-dark"
                                >
                                    {{ gate.bays }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3 pt-2">
                            <p class="font-semibold text-custom-accent-3 text-base">
                                Activity
                            </p>
                            <Separator class="flex-1" />
                        </div>
                        <div class="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <p class="text-xs text-custom-shadow/80">
                                    Created
                                </p>
                                <p>{{ formatDate(gate.created_at) }}</p>
                                <p
                                    v-if="gate.creator"
                                    class="text-xs text-muted-foreground"
                                >
                                    by {{ gate.creator.name }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-custom-shadow/80">
                                    Last updated
                                </p>
                                <p>{{ formatDate(gate.updated_at) }}</p>
                                <p
                                    v-if="gate.updater"
                                    class="text-xs text-muted-foreground"
                                >
                                    by {{ gate.updater.name }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <div
                        v-if="canArchiveGate"
                        class="flex justify-end border-t pt-4"
                    >
                        <Button
                            variant="destructive"
                            size="icon-text"
                            @click="archiveOpen = true"
                            ><Archive class="h-4 w-4" />Archive</Button
                        >
                    </div>
                </CardContent>
            </Card>

            <Dialog v-model:open="archiveOpen">
                <DialogContent class="px-6">
                    <DialogHeader class="px-0">
                        <DialogTitle>Archive Gate</DialogTitle>
                        <DialogDescription class="mt-4">
                            Are you sure you want to archive
                            <span class="font-semibold text-custom-accent-3">{{
                                gate.gate_name
                            }}</span
                            >? This action will remove it from active records.
                        </DialogDescription>
                    </DialogHeader>
                    <Separator class="mb-4" />
                    <DialogFooter class="gap-2 sm:justify-end">
                        <Button
                            variant="ghost-outline"
                            @click="archiveOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
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
