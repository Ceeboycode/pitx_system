<script setup lang="ts">
/* ======================================================
   Shared UI
====================================================== */
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

/* ======================================================
   Layout, Routing & Inertia
====================================================== */
import AppLayout from '@/layouts/AppLayout.vue';
import {
    create,
    destroy,
    edit,
    index,
    show,
    toggleStatus,
    trash,
} from '@/actions/App/Http/Controllers/RouteController';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

/* ======================================================
   Icons
====================================================== */
import {
    Archive,
    ChevronRight,
    Download,
    Eye,
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    Route as RouteIcon,
    Upload,
} from 'lucide-vue-next';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Gate {
    id: number;
    gate_name: string;
}

interface RouteRow {
    id: number;
    route_name: string;
    status: 'active' | 'inactive';
    created_at_human: string | null;
    gate: Gate | null;
}

// ─── Props ────────────────────────────────────────────────────────────────────

const props = withDefaults(
    defineProps<{
        routes: any;
        filters?: { search: string | null };
    }>(),
    { filters: () => ({ search: null }) },
);

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
];

// ─── Archive dialog ───────────────────────────────────────────────────────────

const archivingId  = ref<number | null>(null);
const archiveOpen  = ref(false);

function openArchiveDialog(route: RouteRow) {
    archivingRoute.value = route;
    archiveOpen.value    = true;
}

function confirmArchive() {
    if (!archivingId.value) return;
    router.delete(destroy(archivingId.value).url, {
        preserveScroll: true,
        onFinish: () => {
            archivingRoute.value = null;
            archiveOpen.value    = false;
        },
    });
}

// ─── Toggle status ────────────────────────────────────────────────────────────

function handleToggleStatus(id: number) {
    router.patch(toggleStatus(id).url, {}, { preserveScroll: true });
}

/* ── Status helpers ──────────────────────────────────────────────── */
function statusClass(status: RouteRow['status']): string {
    return status === 'active'
        ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
        : 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(status: RouteRow['status']): string {
    return status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400';
}

function toggleStatusClass(status: RouteRow['status']): string {
    return status === 'active'
        ? 'text-rose-600 focus:bg-rose-50 focus:text-rose-600'
        : 'text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700';
}
</script>

<template>
    <Head title="Routes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-5">
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            Routes
                        </CardTitle>
                        <CardDescription class="mt-1">
                            Manage and view all available routes in the system.
                        </CardDescription>
                    </div>

                    <CardAction>
                        <Button as-child size="sm" variant="outline" class="mr-2">
                            <Link :href="trash().url">
                                <Archive class="mr-2 h-4 w-4" />
                                View Archived
                            </Link>
                        </Button>
                        <Button as-child size="sm">
                            <Link :href="create().url">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Route
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">

                    <!-- Search + Import/Export -->
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters.search"
                                placeholder="Search routes…"
                                :only="['routes', 'filters']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex gap-2 sm:justify-end">
                            <Button size="sm" variant="outline">
                                <Upload class="mr-2 h-4 w-4" />Import
                            </Button>
                            <Button size="sm" variant="outline">
                                <Download class="mr-2 h-4 w-4" />Export
                            </Button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Route Name</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Gate</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Status</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Created</TableHead>
                                    <TableHead class="text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="routeItem in (props.routes.data as RouteRow[])"
                                :key="routeItem.id"
                            >
                                <!-- Route name -->
                                <TableCell class="font-medium capitalize">
                                    {{ routeItem.route_name }}
                                </TableCell>

                                <!-- Gate -->
                                <TableCell class="text-muted-foreground">
                                    {{ routeItem.gate?.gate_name ?? '—' }}
                                </TableCell>

                                <!-- Status badge -->
                                <TableCell>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            routeItem.status === 'active'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-zinc-100 text-zinc-600',
                                        ]"
                                    >
                                        <!-- pulsing dot -->
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 rounded-full',
                                                routeItem.status === 'active'
                                                    ? 'bg-green-500 animate-pulse'
                                                    : 'bg-zinc-400',
                                            ]"
                                        />
                                        {{ routeItem.status === 'active' ? 'Active' : 'Inactive' }}
                                    </span>
                                </TableCell>

                                <!-- Created at -->
                                <TableCell class="text-muted-foreground text-sm">
                                    {{ routeItem.created_at_human ?? '—' }}
                                </TableCell>

                                <!-- 3-dot actions -->
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon" class="h-8 w-8">
                                                <MoreHorizontal class="h-4 w-4" />
                                                <span class="sr-only">Open menu</span>
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end" class="w-44">
                                            <!-- View -->
                                            <DropdownMenuItem as-child>
                                                <Link :href="show(routeItem.id).url" class="flex items-center gap-2 cursor-pointer">
                                                    <Eye class="h-4 w-4" />
                                                    View
                                                </Link>
                                            </DropdownMenuItem>

                                            <!-- Edit -->
                                            <DropdownMenuItem as-child>
                                                <Link :href="edit(routeItem.id).url" class="flex items-center gap-2 cursor-pointer">
                                                    <EditIcon class="h-4 w-4" />
                                                    Edit
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <!-- Toggle Active / Inactive -->
                                            <DropdownMenuItem
                                                class="flex items-center gap-2 cursor-pointer"
                                                :class="routeItem.status === 'active' ? 'text-amber-600 focus:text-amber-700' : 'text-green-600 focus:text-green-700'"
                                                @click="handleToggleStatus(routeItem.id)"
                                            >
                                                <Zap v-if="routeItem.status === 'inactive'" class="h-4 w-4" />
                                                <PowerOff v-else class="h-4 w-4" />
                                                {{ routeItem.status === 'active' ? 'Set Inactive' : 'Set Active' }}
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <!-- Archive -->
                                            <DropdownMenuItem
                                                class="flex items-center gap-2 cursor-pointer text-destructive focus:text-destructive"
                                                @click="openArchiveDialog(routeItem.id)"
                                            >
                                                <ArchiveIcon class="h-4 w-4" />
                                                Archive
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>

                            <!-- Empty state -->
                            <TableRow v-if="props.routes.data.length === 0">
                                <TableCell colspan="5" class="py-10 text-center text-muted-foreground">
                                    No routes found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <InertiaPagination
                        :links="props.routes.links"
                        :meta="{
                            from: props.routes.from,
                            to: props.routes.to,
                            total: props.routes.total,
                        }"
                    />
                </CardContent>
            </Card>
        </div>

        <!-- Archive confirmation dialog (controlled, not nested in loop) -->
        <Dialog :open="archiveOpen" @update:open="archiveOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Archive :size="18" class="text-muted-foreground" />
                        Archive Route
                    </DialogTitle>
                    <DialogDescription>
                        Are you sure you want to archive this route?
                        You can restore it later from the Archived Routes page.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="outline" @click="archivingId = null">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button variant="destructive" @click="confirmArchive">
                        <ArchiveIcon class="mr-2 h-4 w-4" />
                        Archive
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
