<script setup lang="ts">
/* ======================================================
   Shared UI
====================================================== */
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

/* shadcn-vue */
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
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
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
    ArchiveIcon,
    Download,
    Edit as EditIcon,
    Eye,
    MoreHorizontal,
    Plus,
    PowerOff,
    Upload,
    Zap,
} from 'lucide-vue-next';

/* ======================================================
   Vue Core
====================================================== */
import { ref } from 'vue';

/* ======================================================
   Permissions
====================================================== */
import { can } from '@/lib/can';

const canCreate      = can('routes.create');
const canUpdate      = can('routes.update');
const canDelete      = can('routes.delete');
const canViewTrash   = can('routes.viewTrash');
const canToggle      = can('routes.toggleStatus');

/* ======================================================
   Types
====================================================== */
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

/* ======================================================
   Props
====================================================== */
const props = withDefaults(
    defineProps<{
        routes: any;
        filters?: { search: string | null };
    }>(),
    { filters: () => ({ search: null }) },
);

/* ======================================================
   Breadcrumbs
====================================================== */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
];

/* ======================================================
   Archive dialog
====================================================== */
const archivingId = ref<number | null>(null);
const archiveOpen = ref(false);

function openArchiveDialog(id: number) {
    archivingId.value = id;
    archiveOpen.value = true;
}

function confirmArchive() {
    if (!archivingId.value) return;

    router.delete(destroy(archivingId.value).url, {
        preserveScroll: true,
        onFinish: () => {
            archivingId.value = null;
            archiveOpen.value = false;
        },
    });
}

/* ======================================================
   Toggle status
====================================================== */
function handleToggleStatus(id: number) {
    router.patch(toggleStatus(id).url, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Routes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-10 mt-3">
                <CardHeader>
                    <CardTitle>Routes</CardTitle>
                    <CardDescription>
                        Manage and view all available routes in the system.
                    </CardDescription>

                    <CardAction>
                        <Button v-if="canViewTrash" as-child size="sm" variant="outline" class="mr-2">
                            <Link :href="trash().url">
                                <Archive class="mr-2 h-4 w-4" />
                                View Archived
                            </Link>
                        </Button>

                        <Button v-if="canCreate" as-child size="sm">
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
                                placeholder="Search routes..."
                                :only="['routes', 'filters']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex gap-2 sm:justify-end">
                            <Button size="sm" variant="outline">
                                <Upload class="mr-2 h-4 w-4" />
                                Import
                            </Button>
                            <Button size="sm" variant="outline">
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </div>
                    </div>

                    <!-- Table -->
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Route Name</TableHead>
                                <TableHead>Gate</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="routeItem in (props.routes.data as RouteRow[])"
                                :key="routeItem.id"
                            >
                                <TableCell class="font-medium capitalize">
                                    {{ routeItem.route_name }}
                                </TableCell>

                                <TableCell class="text-muted-foreground">
                                    {{ routeItem.gate?.gate_name ?? '—' }}
                                </TableCell>

                                <TableCell>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            routeItem.status === 'active'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-zinc-100 text-zinc-600',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 rounded-full',
                                                routeItem.status === 'active'
                                                    ? 'animate-pulse bg-green-500'
                                                    : 'bg-zinc-400',
                                            ]"
                                        />
                                        {{ routeItem.status === 'active' ? 'Active' : 'Inactive' }}
                                    </span>
                                </TableCell>

                                <TableCell class="text-sm text-muted-foreground">
                                    {{ routeItem.created_at_human ?? '—' }}
                                </TableCell>

                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon" class="h-8 w-8">
                                                <MoreHorizontal class="h-4 w-4" />
                                                <span class="sr-only">Open menu</span>
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end" class="w-44">
                                            <!-- View — always visible -->
                                            <DropdownMenuItem as-child>
                                                <Link
                                                    :href="show(routeItem.id).url"
                                                    class="flex cursor-pointer items-center gap-2"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                    View
                                                </Link>
                                            </DropdownMenuItem>

                                            <!-- Edit -->
                                            <DropdownMenuItem v-if="canUpdate" as-child>
                                                <Link
                                                    :href="edit(routeItem.id).url"
                                                    class="flex cursor-pointer items-center gap-2"
                                                >
                                                    <EditIcon class="h-4 w-4" />
                                                    Edit
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator v-if="canToggle || canDelete" />

                                            <!-- Toggle status -->
                                            <DropdownMenuItem
                                                v-if="canToggle"
                                                class="flex cursor-pointer items-center gap-2"
                                                :class="
                                                    routeItem.status === 'active'
                                                        ? 'text-amber-600 focus:text-amber-700'
                                                        : 'text-green-600 focus:text-green-700'
                                                "
                                                @click="handleToggleStatus(routeItem.id)"
                                            >
                                                <Zap v-if="routeItem.status === 'inactive'" class="h-4 w-4" />
                                                <PowerOff v-else class="h-4 w-4" />
                                                {{ routeItem.status === 'active' ? 'Set Inactive' : 'Set Active' }}
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator v-if="canToggle && canDelete" />

                                            <!-- Archive -->
                                            <DropdownMenuItem
                                                v-if="canDelete"
                                                class="flex cursor-pointer items-center gap-2 text-destructive focus:text-destructive"
                                                @click="openArchiveDialog(routeItem.id)"
                                            >
                                                <ArchiveIcon class="h-4 w-4" />
                                                Archive
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="props.routes.data.length === 0">
                                <TableCell
                                    colspan="5"
                                    class="py-10 text-center text-muted-foreground"
                                >
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
    </AppLayout>

    <!-- Archive confirmation dialog -->
    <AlertDialog v-model:open="archiveOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle class="flex items-center gap-2">
                    <ArchiveIcon class="h-5 w-5 text-muted-foreground" />
                    Archive Route
                </AlertDialogTitle>
                <AlertDialogDescription>
                    Are you sure you want to archive this route?
                    You can restore it later from the Archived Routes page.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel @click="archivingId = null">Cancel</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    @click="confirmArchive"
                >
                    <ArchiveIcon class="mr-2 h-4 w-4" />
                    Archive
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
