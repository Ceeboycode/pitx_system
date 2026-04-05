<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create, destroy, edit, index, trash } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
import { Badge } from '@/components/ui/badge';
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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import { can } from '@/lib/can';

import {
    Archive,
    ArchiveX,
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronRight,
    Filter,
    Key,
    MoreHorizontal,
    Pencil,
    Plus,
    ShieldCheck,
    X,
} from 'lucide-vue-next';

/* ── Permissions ─────────────────────────────────────────────────── */
const canCreate = can('roles.create');
const canUpdate = can('roles.update');
const canDelete = can('roles.archive');
const canViewTrash = can('roles.viewTrash');

/* ── Types ──────────────────────────────────────────────────────── */
type Permission = { id: number; name: string };

type Role = {
    id: number;
    name: string;
    type: 'internal' | 'external';
    permissions: Permission[];
};

type SortField = 'name' | 'type' | 'permissions_count' | 'created_at' | null;
type SortDir = 'asc' | 'desc';

/* ── Props ───────────────────────────────────────────────────────── */
const props = defineProps<{
    roles: { data: Role[]; links: any[] };
    filters: {
        search?: string | null;
        type?: string | null;
        sort_by?: SortField;
        sort_dir?: SortDir;
    };
}>();

/* ── Breadcrumbs ─────────────────────────────────────────────────── */
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: index().url }];

/* ── Filter & sort state ─────────────────────────────────────────── */
const search = ref(props.filters.search ?? '');
const roleType = ref(props.filters.type ?? 'all');
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');

const hasActiveFilters = computed(
    () =>
        !!search.value ||
        (roleType.value && roleType.value !== 'all') ||
        sortBy.value !== null,
);

let filterTimer: number | null = null;

function applyFilters() {
    router.get(
        index().url,
        {
            search: search.value || undefined,
            type: roleType.value === 'all' ? undefined : roleType.value,
            sort_by: sortBy.value ?? undefined,
            sort_dir: sortBy.value ? sortDir.value : undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['roles', 'filters', 'flash'],
        },
    );
}

watch(search, () => {
    if (filterTimer) window.clearTimeout(filterTimer);
    filterTimer = window.setTimeout(() => applyFilters(), 350);
});

watch(roleType, () => {
    applyFilters();
});

function toggleSort(field: SortField) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function clearFilters() {
    search.value = '';
    roleType.value = 'all';
    sortBy.value = null;
    sortDir.value = 'asc';
    applyFilters();
}

function sortIcon(field: SortField) {
    if (sortBy.value !== field) return ArrowUpDown;
    return sortDir.value === 'asc' ? ArrowUp : ArrowDown;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field
        ? 'text-blue-600'
        : 'text-muted-foreground/40';
}

/* ── Type badge ──────────────────────────────────────────────────── */
function typeClass(type: Role['type']): string {
    return type === 'internal'
        ? 'bg-blue-100 text-blue-700 border-blue-200'
        : 'bg-violet-100 text-violet-700 border-violet-200';
}

/* ── Delete dialog ───────────────────────────────────────────────── */
const deleteOpen = ref(false);
const selectedRole = ref<Role | null>(null);
const processing = ref(false);

function openDelete(role: Role) {
    selectedRole.value = role;
    deleteOpen.value = true;
}

function deleteRole() {
    if (processing.value || !selectedRole.value) return;
    processing.value = true;
    router.delete(destroy({ role: selectedRole.value.id }).url, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            deleteOpen.value = false;
            selectedRole.value = null;
        },
    });
}
</script>

<template>
    <Head title="Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-5">
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <ShieldCheck class="h-5 w-5 text-blue-700" />
                            Roles
                        </CardTitle>
                        <CardDescription class="mt-1">
                            Manage the roles and their permissions.
                        </CardDescription>
                    </div>

                    <CardAction>
                        <div class="flex items-center gap-2">
                            <Button as-child size="sm" variant="outline">
                                <Link :href="trash().url">
                                    <Archive class="mr-2 h-4 w-4" />
                                    View Archived
                                </Link>
                            </Button>

                            <Button
                                v-if="canCreate"
                                as-child
                                size="sm"
                                variant="blue"
                            >
                                <Link :href="create().url">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Create Role
                                </Link>
                            </Button>
                        </div>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- Search -->
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-full max-w-sm">
                            <Input
                                v-model="search"
                                placeholder="Search roles..."
                                class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Filter + Sort -->
                    <div class="flex flex-wrap items-center gap-2">
                        <div
                            class="flex items-center gap-1.5 text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            <Filter class="h-3.5 w-3.5" />
                            Filter
                        </div>

                        <Select v-model="roleType">
                            <SelectTrigger
                                class="h-8 w-36 rounded-lg border-slate-200 text-xs"
                            >
                                <SelectValue placeholder="All Types" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="all" class="text-xs"
                                    >All Types</SelectItem
                                >
                                <SelectItem value="internal" class="text-xs"
                                    >Internal</SelectItem
                                >
                                <SelectItem value="external" class="text-xs"
                                    >External</SelectItem
                                >
                            </SelectContent>
                        </Select>

                        <div
                            class="ml-2 flex items-center gap-1.5 text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            <ArrowUpDown class="h-3.5 w-3.5" />
                            Sort
                        </div>

                        <Select
                            :model-value="sortBy ?? 'none'"
                            @update:model-value="
                                (val) => {
                                    sortBy =
                                        val === 'none'
                                            ? null
                                            : (val as SortField);
                                    applyFilters();
                                }
                            "
                        >
                            <SelectTrigger
                                class="h-8 w-44 rounded-lg border-slate-200 text-xs"
                            >
                                <SelectValue placeholder="Sort by..." />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="none" class="text-xs"
                                    >No Sort</SelectItem
                                >
                                <SelectItem value="name" class="text-xs"
                                    >Name</SelectItem
                                >
                                <SelectItem value="type" class="text-xs"
                                    >Type</SelectItem
                                >
                                <SelectItem
                                    value="permissions_count"
                                    class="text-xs"
                                    >Permissions Count</SelectItem
                                >
                                <SelectItem value="created_at" class="text-xs"
                                    >Created Date</SelectItem
                                >
                            </SelectContent>
                        </Select>

                        <Button
                            v-if="sortBy"
                            size="sm"
                            variant="outline"
                            class="h-8 rounded-lg border-slate-200 px-3 text-xs text-slate-600 hover:bg-slate-100"
                            @click="
                                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                                applyFilters();
                            "
                        >
                            <ArrowUp
                                v-if="sortDir === 'asc'"
                                class="mr-1.5 h-3.5 w-3.5 text-blue-600"
                            />
                            <ArrowDown
                                v-else
                                class="mr-1.5 h-3.5 w-3.5 text-blue-600"
                            />
                            {{ sortDir === 'asc' ? 'Ascending' : 'Descending' }}
                        </Button>

                        <div
                            v-if="hasActiveFilters"
                            class="ml-auto flex items-center gap-2"
                        >
                            <Badge
                                class="gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 hover:bg-blue-50"
                            >
                                <Filter class="h-3 w-3" />
                                Filters active
                            </Badge>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-7 rounded-lg px-2 text-xs text-muted-foreground hover:text-rose-600"
                                @click="clearFilters"
                            >
                                <X class="mr-1 h-3.5 w-3.5" />
                                Clear
                            </Button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('name')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Name
                                            <component
                                                :is="sortIcon('name')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('name')"
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('type')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Type
                                            <component
                                                :is="sortIcon('type')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('type')"
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('permissions_count')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Permissions
                                            <component
                                                :is="
                                                    sortIcon(
                                                        'permissions_count',
                                                    )
                                                "
                                                class="h-3.5 w-3.5"
                                                :class="
                                                    sortIconClass(
                                                        'permissions_count',
                                                    )
                                                "
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                    >
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <!-- Empty state -->
                                <TableRow
                                    v-if="!props.roles.data.length"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="4"
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted"
                                            >
                                                <ShieldCheck
                                                    class="h-6 w-6 text-muted-foreground/40"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-foreground"
                                                >
                                                    No roles found
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        hasActiveFilters
                                                            ? 'Try adjusting your filters or search.'
                                                            : 'Try adjusting your search or create a new role.'
                                                    }}
                                                </p>
                                            </div>
                                            <Button
                                                v-if="hasActiveFilters"
                                                size="sm"
                                                variant="outline"
                                                class="mt-1 h-8 rounded-lg text-xs"
                                                @click="clearFilters"
                                            >
                                                <X class="mr-1.5 h-3.5 w-3.5" />
                                                Clear filters
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="role in props.roles.data"
                                    :key="role.id"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <!-- Name -->
                                    <TableCell
                                        class="text-sm font-semibold capitalize"
                                    >
                                        {{ role.name }}
                                    </TableCell>

                                    <!-- Type -->
                                    <TableCell>
                                        <Badge :class="typeClass(role.type)">
                                            {{
                                                role.type === 'internal'
                                                    ? 'Internal'
                                                    : 'External'
                                            }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Permissions -->
                                    <TableCell>
                                        <span
                                            v-if="!role.permissions?.length"
                                            class="text-sm text-muted-foreground"
                                        >
                                            No permissions
                                        </span>

                                        <Popover v-else>
                                            <PopoverTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-7 rounded-lg border-slate-200 text-xs text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                                >
                                                    <Key
                                                        class="mr-1.5 h-3 w-3"
                                                    />
                                                    {{
                                                        role.permissions.length
                                                    }}
                                                    permission{{
                                                        role.permissions
                                                            .length !== 1
                                                            ? 's'
                                                            : ''
                                                    }}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent
                                                class="max-h-60 w-80 overflow-y-auto rounded-xl p-0"
                                            >
                                                <div
                                                    class="border-b border-slate-100 px-4 py-3"
                                                >
                                                    <p
                                                        class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                    >
                                                        Permissions
                                                    </p>
                                                    <p
                                                        class="text-sm font-semibold capitalize"
                                                    >
                                                        {{ role.name }}
                                                    </p>
                                                </div>
                                                <div
                                                    class="flex flex-wrap gap-1.5 p-3"
                                                >
                                                    <span
                                                        v-for="p in role.permissions"
                                                        :key="p.id"
                                                        class="rounded-md bg-muted px-2 py-0.5 font-mono text-xs text-muted-foreground"
                                                    >
                                                        {{ p.name }}
                                                    </span>
                                                </div>
                                            </PopoverContent>
                                        </Popover>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <DropdownMenu
                                            v-if="canUpdate || canDelete"
                                        >
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                                                >
                                                    <MoreHorizontal
                                                        class="h-4 w-4"
                                                    />
                                                    <span class="sr-only"
                                                        >Open actions</span
                                                    >
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                class="w-48 rounded-xl border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    {{ role.name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    v-if="canUpdate"
                                                    as-child
                                                    class="rounded-lg text-amber-600 focus:bg-amber-50 focus:text-amber-700"
                                                >
                                                    <Link
                                                        :href="
                                                            edit({
                                                                role: role.id,
                                                            }).url
                                                        "
                                                        class="flex items-center"
                                                    >
                                                        <Pencil
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        Edit
                                                        <ChevronRight
                                                            class="ml-auto h-3.5 w-3.5 text-amber-400"
                                                        />
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="canDelete"
                                                    class="rounded-lg text-rose-600 focus:bg-rose-50 focus:text-rose-700"
                                                    @click="openDelete(role)"
                                                >
                                                    <ArchiveX
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    Archive
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- ── Delete dialog ──────────────────────────────────────── -->
        <AlertDialog v-if="canDelete" v-model:open="deleteOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Archive Role</AlertDialogTitle>
                    <AlertDialogDescription class="space-y-3">
                        <p>
                            This action will archive
                            <span class="font-semibold text-foreground">{{
                                selectedRole?.name
                            }}</span>
                            and hide it from the active roles list.
                        </p>
                        <p class="text-sm text-muted-foreground">
                            You can restore this role later from Archived Roles.
                        </p>
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        :disabled="processing"
                        class="rounded-lg border-0 bg-rose-600 font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
                        @click="deleteRole"
                    >
                        <ArchiveX class="mr-2 h-4 w-4" />
                        {{ processing ? 'Archiving…' : 'Archive Role' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
