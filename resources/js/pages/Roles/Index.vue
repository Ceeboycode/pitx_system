<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create, destroy, edit, index, trash } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import { Separator } from '@/components/ui/separator/index';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
    RiMore2Line,
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
};

type SortField = 'name' | 'type' | 'permissions_count' | 'created_at' | null;
type SortDir = 'asc' | 'desc';


const props = defineProps<{
    roles: { data: Role[]; links: any[]; from: number | null; to: number | null; total: number };
    filters: {
        search?: string | null;
        type?: string | null;
        sort_by?: SortField;
        sort_dir?: SortDir;
    };
}>();


const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: index().url }];


const search = ref(props.filters.search ?? '');
const roleType = ref(props.filters.type ?? 'all');
const pendingRoleType = ref(roleType.value);
const filterOpen = ref(false);
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');

const hasActiveFilters = computed(
    () =>
        !!search.value ||
        (roleType.value && roleType.value !== 'all') ||
        sortBy.value !== null,
);

const activeFilterCount = computed(() =>
    roleType.value && roleType.value !== 'all' ? 1 : 0,
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
    pendingRoleType.value = 'all';
    sortBy.value = null;
    sortDir.value = 'asc';
    applyFilters();
    filterOpen.value = false;
}

function applyFilterPopover() {
    roleType.value = pendingRoleType.value;
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
const processing = ref(false);

function openPreview(role: Role) {
    previewedRole.value = role;
}

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
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4 lg:flex-row lg:items-stretch">
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
                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters.search"
                                placeholder="Search roles"
                                :only="['roles', 'filters', 'flash']"
                                :debounce="350"
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
                                            ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light dark:hover:text-custom-shadow'
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




                    <Card
                        :class="[
                            'flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            props.roles.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                    <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                        <div v-if="props.roles.data.length" class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                            <div class="grid grid-cols-4 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <div
                                        class="col-span-1 flex h-10 cursor-pointer items-center pl-3 text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase select-none transition-colors hover:text-custom-shadow"
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
                                    </div>

                                    <div
                                        class="col-span-1 flex h-10 cursor-pointer items-center text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase select-none transition-colors hover:text-custom-shadow"
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
                                    </div>

                                    <div
                                        class="col-span-1 flex h-10 cursor-pointer items-center text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase select-none transition-colors hover:text-custom-shadow"
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
                                    </div>

                                    <div
                                        class="col-span-1 flex h-10 items-center justify-end pr-3 text-right text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                    >
                                        Actions
                                    </div>
                            </div>
                        </div>

                        <div v-if="!props.roles.data.length" class="flex flex-1 items-center justify-center p-6 text-center">
                                        <div
                                            class="flex flex-col items-center gap-2"
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-full bg-custom-bg dark:bg-custom-bg-dark"
                                            >
                                                <RiShieldCheckLine
                                                    class="h-6 w-6 text-muted-foreground/40"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-base font-semibold text-custom-shadow"
                                                >
                                                    No roles found
                                                </p>
                                                <p
                                                    class="mt-1 text-sm text-custom-shadow/80"
                                                >
                                                    {{
                                                        hasActiveFilters
                                                            ? 'Try adjusting your filters or search.'
                                                            : 'Try adjusting your search or create a new role.'
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                        </div>

                        <div v-else class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(role, roleIndex) in props.roles.data"
                                    :key="role.id"
                                    :class="[
                                        'group grid cursor-pointer grid-cols-4 items-center border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        roleIndex === props.roles.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                        previewedRole?.id === role.id ? 'bg-custom-secondary/10' : '',
                                    ]"
                                    @click="openPreview(role)"
                                >
                                    
                                    <div
                                        class="col-span-1 py-1.5 pl-3 text-sm font-semibold capitalize"
                                    >
                                        {{ role.name }}
                                    </div>

                                    
                                    <div class="col-span-1 py-1.5">
                                        <Badge :class="typeClass(role.type)">
                                            {{
                                                role.type === 'internal'
                                                    ? 'Internal'
                                                    : 'External'
                                            }}
                                        </Badge>
                                    </div>

                                    
                                    <div class="col-span-1 py-1.5" @click.stop>
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
                                    </div>

                                    
                                    <div class="col-span-1 py-1.5 pr-3 text-right" @click.stop>
                                        <DropdownMenu
                                            v-if="canUpdate || canDelete"
                                        >
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <RiMore2Line
                                                        class="h-4 w-4"
                                                    />
                                                    
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="">
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
                                                            class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow"
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
                                                        class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow"
                                                    />
                                                    <span class="text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow">
                                                        Archive
                                                    </span>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                        </div>
                    </div>
                    </Card>

                    <InertiaPagination
                        v-if="roles.links?.length"
                        :links="roles.links"
                        :meta="{ from: roles.from, to: roles.to, total: roles.total }"
                    />
                </CardContent>
            </Card>

            <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-100">
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
        </div>

        <Dialog v-if="canDelete" v-model:open="deleteOpen">
            <DialogContent class="px-6">
                <DialogHeader class="px-0">
                    <DialogTitle>Archive role</DialogTitle>
                    <DialogDescription class="mt-4">
                        Are you sure you want to archive
                        <span class="font-semibold text-custom-accent-3">{{ selectedRole?.name }}</span>?
                        It can be restored later.
                    </DialogDescription>
                </DialogHeader>
                <Separator class="mb-4" />
                <DialogFooter class="gap-2 sm:justify-end">
                    <Button variant="ghost-outline" @click="deleteOpen = false">Cancel</Button>
                    <Button
                        :variant="processing ? 'disabled' : 'float-primary'"
                        @click="deleteRole"
                    >
                        <RiArchive2Line class="h-4 w-4" />
                        {{ processing ? 'Archiving...' : 'Archive' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
