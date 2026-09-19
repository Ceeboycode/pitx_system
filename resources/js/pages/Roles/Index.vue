<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create, edit, index, trash } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

import Button from '@/components/ui/button/Button.vue'
import { ArchiveRoleDialog } from '@/components/internal/roles';
import {
    Table,
    TableColumn,
    TableHeader,
    TableContent,
    TableRow,
    TableCard,
    TableData,
    TableMoreButton,
} from '@/components/ui/_table';
import { Badge } from '@/components/ui/badge';
import {
    Card,
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
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { PanelLayout, MainPanel, SidePanel } from '@/components/ui/_panels';

import { can } from '@/lib/can';

import {
    RiAddLine,
    RiArchive2Line,
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiCloseLine,
    RiEditLine,
    RiFilter2Line,
    RiKey2Line,
    RiMoreLine,
    RiShieldCheckLine,
} from 'vue-remix-icons';


const canCreate = computed(() => can('roles.create'));
const canUpdate = computed(() => can('roles.update'));
const canDelete = computed(() => can('roles.archive'));
const canViewTrash = computed(() => can('roles.viewTrash'));


type Permission = { id: number; name: string };

type Role = {
    id: number;
    name: string;
    type: 'internal' | 'external';
    permissions: Permission[];
    created_at_human?: string | null;
    updated_at_human?: string | null;
    creator?: { id: number; name: string } | null;
    updater?: { id: number; name: string } | null;
};

type SortField = 'name' | 'type' | 'permissions_count' | 'created_at' | null;
type SortDir = 'asc' | 'desc';


const props = defineProps<{
    roles: { data: Role[]; links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number };
    filters: {
        search?: string | null;
        type?: string | null;
        sort_by?: SortField;
        sort_dir?: SortDir;
    };
}>();


const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: index().url }];


const roleType = ref(props.filters.type ?? 'all');
const pendingRoleType = ref(roleType.value);
const filterOpen = ref(false);
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');

const hasActiveFilters = computed(
    () =>
        (roleType.value && roleType.value !== 'all') ||
        sortBy.value !== null,
);

const activeFilterCount = computed(() =>
    roleType.value && roleType.value !== 'all' ? 1 : 0,
);

function currentFilterParams(): Record<string, string | undefined> {
    return {
        type: roleType.value === 'all' ? undefined : roleType.value,
        sort_by: sortBy.value ?? undefined,
        sort_dir: sortBy.value ? sortDir.value : undefined,
    };
}

function applyFilters() {
    router.get(
        index().url,
        {
            search: props.filters.search ?? undefined,
            ...currentFilterParams(),
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['roles', 'filters', 'flash'],
        },
    );
}

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
    roleType.value = 'all';
    pendingRoleType.value = 'all';
    sortBy.value = null;
    sortDir.value = 'asc';
    applyFilters();
    filterOpen.value = false;
}

function applyFilterPopover() {
    roleType.value = pendingRoleType.value;
    applyFilters();
    filterOpen.value = false;
}

function cancelFilterPopover() {
    pendingRoleType.value = roleType.value;
    filterOpen.value = false;
}

function sortIcon(field: SortField) {
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field
        ? 'text-custom-primary'
        : 'text-custom-shadow/40';
}


function typeClass(type: Role['type']): string {
    return type === 'internal'
        ? 'bg-blue-100 text-blue-700 border-blue-200'
        : 'bg-violet-100 text-violet-700 border-violet-200';
}


const deleteOpen = ref(false);
const selectedRole = ref<Role | null>(null);
const previewedRole = ref<Role | null>(null);
const openMenus = ref<Record<number, boolean>>({});

function openPreview(role: Role) {
    previewedRole.value = role;
}

function selectAdjacentRole(direction: 1 | -1) {
    if (!previewedRole.value) return;

    const list = props.roles.data;
    const currentIndex = list.findIndex((r) => r.id === previewedRole.value?.id);
    const nextIndex = currentIndex + direction;
    if (currentIndex === -1 || nextIndex < 0 || nextIndex >= list.length) return;

    openPreview(list[nextIndex]);
}

function handleRowNavigationKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectAdjacentRole(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectAdjacentRole(-1);
    }
}

onMounted(() => window.addEventListener('keydown', handleRowNavigationKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleRowNavigationKeydown));

function openDelete(role: Role) {
    selectedRole.value = role;
    deleteOpen.value = true;
}
</script>

<template>
    <Head title="Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row gap-2">
                        <div class="flex flex-col">
                            <CardTitle class="flex items-center gap-2">Roles</CardTitle>
                            <CardDescription>
                                Manage roles and their permissions.
                            </CardDescription>
                        </div>
                        <div class="flex flex-1 items-center justify-end gap-2">
                            <Button
                                v-if="canCreate"
                                as-child
                                variant="float-primary"
                                class="hidden lg:flex"
                            >
                                <Link :href="create().url" class="flex items-center">
                                    <RiAddLine class="h-4 w-4 shrink-0" />
                                    <span>Add Role</span>
                                </Link>
                            </Button>
                            <DropdownMenu v-if="canCreate || canViewTrash">
                                <DropdownMenuTrigger as-child class="m-0">
                                    <div class="inline-flex">
                                        <Button
                                            variant="header-actions"
                                            class="text-custom-shadow"
                                            size="icon"
                                            aria-label="Open role actions"
                                        >
                                            <RiMoreLine class="h-4 w-4 shrink-0" />
                                        </Button>
                                    </div>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-fit">
                                    <DropdownMenuItem
                                        v-if="canCreate"
                                        as-child
                                        class="cursor-pointer lg:hidden"
                                    >
                                        <Link :href="create().url" class="flex items-center">
                                            <RiAddLine class="h-4 w-4" />
                                            Create Role
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="canViewTrash"
                                        as-child
                                        class="cursor-pointer"
                                    >
                                        <Link :href="trash().url" class="flex items-center">
                                            <RiArchive2Line class="h-4 w-4" />
                                            Archives
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardHeader>
                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 pt-2">
                        <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                            <div class="w-full">
                                <SearchInput
                                    :route="index().url"
                                    :initial-value="props.filters.search"
                                    placeholder="Search roles"
                                    :only="['roles', 'filters', 'flash']"
                                    :debounce="350"
                                    :extra-params="currentFilterParams"
                                />
                            </div>
                            <div class="flex w-fit flex-row gap-2 lg:items-center lg:justify-between">
                                <Popover v-model:open="filterOpen">
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="header-actions"
                                            size="icon-text"
                                            class="rounded-full"
                                            :class="activeFilterCount > 0
                                                ? 'bg-custom-secondary/20 transition-all duration-200 hover:bg-custom-secondary/80 hover:text-custom-bg-light dark:hover:text-custom-shadow'
                                                : ''"
                                        >
                                            <RiFilter2Line class="h-3.5 w-3.5" />
                                            <span class="hidden lg:flex">
                                                {{ activeFilterCount > 0 ? '1 filter active' : 'Filter' }}
                                            </span>
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent align="end">
                                        <div class="grid gap-y-2">
                                            <div class="flex flex-col gap-y-1">
                                                <p class="text-sm text-custom-shadow/80">Type</p>
                                                <Select v-model="pendingRoleType">
                                                    <SelectTrigger class="w-full">
                                                        <SelectValue placeholder="All Types" class="flex justify-start" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all" class="cursor-pointer text-sm">All Types</SelectItem>
                                                        <SelectItem value="internal" class="cursor-pointer text-sm">Internal</SelectItem>
                                                        <SelectItem value="external" class="cursor-pointer text-sm">External</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">
                                            <div class="flex w-full items-center justify-between">
                                                <Button
                                                    v-if="activeFilterCount > 0"
                                                    size="sm"
                                                    variant="destructive"
                                                    @click="clearFilters"
                                                >
                                                    Clear
                                                </Button>
                                                <div class="ml-auto flex items-center gap-2">
                                                    <Button
                                                        variant="ghost-outline"
                                                        size="sm"
                                                        @click="cancelFilterPopover"
                                                    >
                                                        Cancel
                                                    </Button>
                                                    <Button
                                                        variant="float-primary"
                                                        size="sm"
                                                        @click="applyFilterPopover"
                                                    >
                                                        Apply
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>
                            </div>
                        </div>




                        <TableCard :table-data-length="props.roles.data.length">
                            <Table v-if="props.roles.data.length > 0">
                                <TableHeader>
                                    <TableColumn class="p-0">
                                        <button
                                            type="button"
                                            class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                            @click="toggleSort('name')"
                                        >
                                            Name
                                            <component
                                                :is="sortIcon('name')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('name')"
                                            />
                                        </button>
                                    </TableColumn>

                                    <TableColumn class="p-0">
                                        <button
                                            type="button"
                                            class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                            @click="toggleSort('type')"
                                        >
                                            Type
                                            <component
                                                :is="sortIcon('type')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('type')"
                                            />
                                        </button>
                                    </TableColumn>

                                    <TableColumn class="p-0">
                                        <button
                                            type="button"
                                            class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                            @click="toggleSort('permissions_count')"
                                        >
                                            Permissions
                                            <component
                                                :is="sortIcon('permissions_count')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('permissions_count')"
                                            />
                                        </button>
                                    </TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(role, roleIndex) in props.roles.data"
                                        :key="role.id"
                                        :class="[
                                            roleIndex === props.roles.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedRole?.id === role.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        @click.left="openPreview(role)"
                                        @dblclick="router.visit(edit({ role: role.id }).url)"
                                    >
                                        <TableData class="pl-3 text-sm font-semibold capitalize">
                                            {{ role.name }}
                                        </TableData>

                                        <TableData>
                                            <Badge :class="typeClass(role.type)">
                                                {{
                                                    role.type === 'internal'
                                                        ? 'Internal'
                                                        : 'External'
                                                }}
                                            </Badge>
                                        </TableData>

                                        <TableData @click.stop>
                                            <span
                                                v-if="!role.permissions?.length"
                                                class="text-sm text-muted-foreground"
                                            >
                                                No permissions
                                            </span>

                                            <Popover v-else>
                                                <PopoverTrigger as-child>
                                                    <Button
                                                        variant="ghost-outline"
                                                        size="sm"
                                                        class="h-7 rounded-md text-xs"
                                                    >
                                                        <RiKey2Line
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
                                        </TableData>

                                        <TableMoreButton
                                            v-if="canUpdate || canDelete"
                                            :open="openMenus[role.id] ?? false"
                                            @update:open="(value) => (openMenus[role.id] = value)"
                                        >
                                            <DropdownMenuLabel>
                                                {{ role.name }}
                                            </DropdownMenuLabel>
                                            <DropdownMenuItem
                                                v-if="canUpdate"
                                                as-child
                                                class="group"
                                            >
                                                <Link
                                                    :href="
                                                        edit({
                                                            role: role.id,
                                                        }).url
                                                    "
                                                    class="flex items-center"
                                                >
                                                    <RiEditLine
                                                        class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow"
                                                    />
                                                    Edit
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="canDelete"
                                                class="group"
                                                @click="openDelete(role)"
                                            >
                                                <RiArchive2Line
                                                    class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow"
                                                />
                                                <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow">
                                                    Archive
                                                </span>
                                            </DropdownMenuItem>
                                        </TableMoreButton>
                                    </TableRow>
                                </TableContent>
                            </Table>

                            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                                <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                    <img
                                        :src="emptyRafikiUrl"
                                        alt=""
                                        class="w-1/3 object-contain opacity-90"
                                        aria-hidden="true"
                                    />
                                    <div class="space-y-1">
                                        <p class="text-custom-shadow text-base font-semibold">No roles found</p>
                                        <p class="text-custom-shadow/80 text-sm">
                                            {{
                                                hasActiveFilters
                                                    ? 'Try adjusting your filters or search.'
                                                    : 'Try adjusting your search or create a new role.'
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TableCard>

                        <InertiaPagination
                            v-if="roles.links?.length"
                            :links="roles.links"
                            :meta="{ from: roles.from, to: roles.to, total: roles.total }"
                        />
                    </CardContent>
                </Card>
            </MainPanel>

            <SidePanel>
                <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-full">
                    <CardHeader
                        v-if="previewedRole"
                        class="flex flex-row items-start justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <CardTitle class="truncate capitalize">
                                {{ previewedRole.name }}
                            </CardTitle>
                            <CardDescription>Preview</CardDescription>
                        </div>
                        <Button
                            variant="header-actions"
                            size="icon"
                            class="h-8 w-8 shrink-0 rounded-full"
                            aria-label="Close role preview"
                            @click="previewedRole = null"
                        >
                            <RiCloseLine class="h-4 w-4" />
                        </Button>
                    </CardHeader>

                    <CardContent
                        v-if="previewedRole"
                        class="no-scrollbar min-h-0 flex-1 space-y-4 overflow-y-auto py-2"
                    >
                        <div class="flex items-center justify-center rounded-md border border-dashed border-custom-bg-dark bg-custom-bg p-6 dark:border-custom-bg-light dark:bg-custom-bg-dark">
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-custom-primary/15 text-custom-primary">
                                <RiShieldCheckLine class="h-9 w-9" />
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-custom-shadow">Name</span>
                                <span class="truncate text-right text-sm font-medium capitalize text-custom-shadow/80">
                                    {{ previewedRole.name }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-custom-shadow">Type</span>
                                <Badge :class="typeClass(previewedRole.type)" class="border capitalize">
                                    {{ previewedRole.type }}
                                </Badge>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-semibold text-custom-shadow">Permissions</span>
                                    <span class="text-sm text-custom-shadow/80">
                                        {{ previewedRole.permissions.length }}
                                    </span>
                                </div>
                                <div v-if="previewedRole.permissions.length" class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="permission in previewedRole.permissions"
                                        :key="permission.id"
                                        class="rounded-md bg-custom-bg px-2 py-1 font-mono text-xs text-custom-shadow/70 dark:bg-custom-bg-dark"
                                    >
                                        {{ permission.name }}
                                    </span>
                                </div>
                                <p v-else class="rounded-md bg-custom-bg px-3 py-2 text-sm text-custom-shadow/70 dark:bg-custom-bg-dark">
                                    No permissions assigned.
                                </p>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-custom-shadow">Created</span>
                                <span class="truncate text-right text-sm text-custom-shadow/80">
                                    {{ previewedRole.created_at_human ?? '—' }}
                                    <span class="text-custom-accent-3"> • </span>
                                    {{ previewedRole.creator?.name ?? '—' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-custom-shadow">Updated</span>
                                <span class="truncate text-right text-sm text-custom-shadow/80">
                                    {{ previewedRole.updated_at_human ?? '—' }}
                                    <span class="text-custom-accent-3"> • </span>
                                    {{ previewedRole.updater?.name ?? '—' }}
                                </span>
                            </div>
                        </div>

                        <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                        <div class="flex items-center justify-between gap-2">
                            <Button
                                v-if="canUpdate"
                                as-child
                                variant="ghost-outline"
                                size="icon-text"
                            >
                                <Link :href="edit({ role: previewedRole.id }).url">
                                    <RiEditLine class="h-4 w-4" />
                                    Edit
                                </Link>
                            </Button>
                            <Button
                                v-if="canDelete"
                                variant="destructive"
                                size="icon-text"
                                class="ml-auto"
                                @click="openDelete(previewedRole)"
                            >
                                <RiArchive2Line class="h-4 w-4" />
                                Archive
                            </Button>
                        </div>
                    </CardContent>

                    <CardContent v-else class="flex min-h-0 flex-1 items-center justify-center">
                        <div class="max-w-60 space-y-1 text-center">
                            <p class="text-base font-semibold text-custom-shadow">No role selected</p>
                            <p class="text-sm text-custom-shadow/80">
                                Click on a role to preview.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </SidePanel>
            

            
        </PanelLayout>

        <ArchiveRoleDialog v-if="canDelete" v-model:open="deleteOpen" :role="selectedRole" />
    </AppLayout>
</template>
