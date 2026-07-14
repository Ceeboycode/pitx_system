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
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

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
    Ellipsis,
} from 'lucide-vue-next';

/* ── Permissions ─────────────────────────────────────────────────── */
const canCreate = computed(() => can('roles.create'));
const canUpdate = computed(() => can('roles.update'));
const canDelete = computed(() => can('roles.archive'));
const canViewTrash = computed(() => can('roles.viewTrash'));

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
    roles: { data: Role[]; links: any[]; from: number | null; to: number | null; total: number };
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
const createOpen = ref(false);
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
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        Roles
                        <div class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-rose-500" />
                            <div class="border-7 border-rose-500 rounded-xs">
                                <div class="border-3 border-white rounded-xs"></div>
                            </div>
                        </div>
                    </CardTitle>
                    <CardDescription class="mt-1">
                        Manage the roles and their permissions.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-50/100">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters.search"
                                placeholder="Search roles"
                                :only="['roles', 'filters', 'flash']"
                                :debounce="350"
                                class="shadow-sm rounded-lg "
                            />
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between min-w-50/100">
                            <div class="flex flex-wrap items-center gap-2">
                                <Select
                                    :model-value="roleType"
                                >
                                    <SelectTrigger
                                        class="cursor-pointer h-8 w-fit rounded-lg border-slate-200 shadow-sm"
                                    >
                                        <Filter class="h-3.5 w-3.5 text-slate-600" />
                                        <SelectValue placeholder="All Types" class="justify-start flex"/>
                                    </SelectTrigger>
                                    <SelectContent class="rounded-lg shadow-lg">
                                        <SelectItem value="all" class="cursor-pointer text-sm"
                                            >All Types</SelectItem
                                        >
                                        <SelectItem value="internal" class="cursor-pointer text-sm"
                                            >Internal</SelectItem
                                        >
                                        <SelectItem
                                            value="external"
                                            class="cursor-pointer text-sm"
                                            >External</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between min-w-auto">
                                <DropdownMenu
                                    v-if="canCreate || canViewTrash"
                                >
                                    <DropdownMenuTrigger as-child class="m-0">
                                        <div
                                            class="inline-flex rounded-lg border border-slate-200 bg-white shadow-sm"
                                        >
                                            <Button
                                                variant="ghost"
                                                class="rounded-lg cursor-pointer group/segment border-0 px-3 text-slate-600 shadow-none transition-all duration-300 hover:bg-slate-100 focus-visible:z-10 gap-0"
                                            >
                                                <Ellipsis
                                                    class="h-4 w-4 shrink-0"
                                                />
                                                <span
                                                    class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-20 group-hover/segment:opacity-100 group-focus-visible/segment:ml-2 group-focus-visible/segment:max-w-20 group-focus-visible/segment:opacity-100"
                                                >
                                                    Actions
                                                </span>
                                            </Button>
                                        </div>
                                    </DropdownMenuTrigger>

                                    <DropdownMenuContent
                                        align="end"
                                        class="w-fit rounded-lg shadow-lg"
                                    >
                                        <DropdownMenuItem
                                            v-if="canCreate"
                                            as-child
                                            class="cursor-pointer rounded-lg text-slate-700 focus:bg-slate-100 focus:text-slate-900"
                                        >
                                            <Link
                                                :href="
                                                    create().url
                                                "
                                                class="flex items-center"
                                            >
                                                <Plus
                                                    class="h-4 w-4"
                                                />
                                                Create Role
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="canViewTrash"
                                            as-child
                                            class="cursor-pointer rounded-lg text-slate-700 focus:bg-slate-100 focus:text-slate-900"
                                        >
                                            <Link
                                                :href="trash().url"
                                                class="flex items-center"
                                            >
                                                <Archive class="h-4 w-4" />
                                                Archives
                                            </Link>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>




                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="border-y border-slate-200">
                                <TableRow class="gap-2">
                                    <TableHead
                                        class="px-0 cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
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
                                        class="px-0 cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
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
                                        class="px-0 cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
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
                                        class="px-0 text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                    >
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody class="border-y border-slate-200">
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
                                    class="group transition-colors hover:bg-muted/30"
                                >
                                    <!-- Name -->
                                    <TableCell
                                        class="text-sm font-semibold capitalize px-0"
                                    >
                                        {{ role.name }}
                                    </TableCell>

                                    <!-- Type -->
                                    <TableCell class="px-0">
                                        <Badge :class="typeClass(role.type)">
                                            {{
                                                role.type === 'internal'
                                                    ? 'Internal'
                                                    : 'External'
                                            }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Permissions -->
                                    <TableCell class="px-0">
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
                                    <TableCell class="text-right px-0">
                                        <DropdownMenu
                                            v-if="canUpdate || canDelete"
                                        >
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    class="rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground cursor-pointer"
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
                                                class="w-fit rounded-lg border-slate-200 shadow-lg"
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
                                                    class="rounded-lg cursor-pointer hover:bg-slate-100"
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
                                                            class="h-4 w-4"
                                                        />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="canDelete"
                                                    class="rounded-lg cursor-pointer hover:bg-slate-100"
                                                    @click="openDelete(role)"
                                                >
                                                    <ArchiveX
                                                        class="h-4 w-4"
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

                    <InertiaPagination
                        v-if="roles.links?.length"
                        :links="roles.links"
                        :meta="{ from: roles.from, to: roles.to, total: roles.total }"
                    />
                </CardContent>
            </Card>
        </div>

        <!-- ── Delete dialog ──────────────────────────────────────── -->
        <AlertDialog v-if="canDelete" v-model:open="deleteOpen">
            <AlertDialogContent class="rounded-lg p-4">
                <AlertDialogHeader>
                    <AlertDialogTitle>Archive Role</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to archive
                        <span class="font-semibold text-foreground">{{ selectedRole?.name }}</span>?
                        It will be hidden from the active roles list and can be restored later from Archived Roles.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg cursor-pointer hover:bg-slate-100">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        :disabled="processing"
                        class="rounded-lg border-0 text-white cursor-pointer bg-rose-600 hover:bg-rose-700 disabled:opacity-50"
                        @click="deleteRole"
                    >
                        <ArchiveX class="h-4 w-4" />
                        {{ processing ? 'Archiving…' : 'Archive Role' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
