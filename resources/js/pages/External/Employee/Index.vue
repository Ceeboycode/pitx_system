<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { ArchiveEmployeeDialog, EmployeePreviewCard, ResetEmployeePasswordDialog, ToggleEmployeeStatusDialog } from '@/components/external/employee';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';
import { create, edit, index } from '@/routes/employee-users';
import { Head, Link, router } from '@inertiajs/vue3';
import { MainPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import { Table, TableCard, TableColumn, TableContent, TableData, TableHeader, TableMoreButton, TableRow } from '@/components/ui/_table';
import {
    RiAddLine,
    RiArchive2Line,
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiExternalLinkLine,
    RiFilter2Line,
    RiMore2Line,
    RiShieldKeyholeLine,
    RiShutDownLine,
} from 'vue-remix-icons';
import { computed, ref } from 'vue';

type Company = {
    id: number;
    company_name: string;
    company_code?: string | null;
    status: string;
    logo_url?: string | null;
};

type AuthUser = { id: number; name: string; username: string; email: string };
type Role = { id: number; name: string };

type EmployeeUser = {
    id: number;
    username: string;
    name: string;
    avatar?: string | null;
    email?: string | null;
    phone_number?: string | null;
    status: string;
    created_at?: string | null;
    roles?: Role[];
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type SortField = 'name' | 'username' | 'status' | 'phone_number' | 'created_at' | null;
type SortDir = 'asc' | 'desc';
type PaginatedUsers = {
    data: EmployeeUser[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

const props = defineProps<{
    company: Company;
    user: AuthUser;
    users: PaginatedUsers;
    filters: {
        search?: string | null;
        role?: string | null;
        status?: string | null;
        sort_by?: SortField;
        sort_dir?: SortDir;
    };
    roles: string[];
    statuses: string[];
}>();

const canCreateEmployee = can('external_users.create');
const canViewEmployee = can('external_users.view');
const canToggleEmployee = can('external_users.toggleStatus');
const canResetEmployee = can('external_users.resetPassword');
const canArchiveEmployee = can('external_users.archive');

const roleFilter = ref(props.filters.role ?? 'all');
const statusFilter = ref(props.filters.status ?? 'all');
const pendingRoleFilter = ref(roleFilter.value);
const pendingStatusFilter = ref(statusFilter.value);
const filterOpen = ref(false);
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');

const activeFilterCount = computed(
    () => Number(roleFilter.value !== 'all') + Number(statusFilter.value !== 'all'),
);
const employeeRoles = computed(() =>
    props.roles.filter((role) => !['commuter', 'commuters'].includes(role.toLowerCase())),
);

function humanize(value?: string | null) {
    if (!value) return '—';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatDate(value?: string | null) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function roleName(employee: EmployeeUser) {
    return employee.roles?.[0]?.name ?? '—';
}

function roleClass(employee: EmployeeUser) {
    const role = roleName(employee).toLowerCase();
    if (role === 'driver') return 'border-sky-200 bg-sky-100 text-sky-700';
    if (role === 'dispatcher') return 'border-violet-200 bg-violet-100 text-violet-700';
    if (role === 'conductor') return 'border-teal-200 bg-teal-100 text-teal-700';
    if (role === 'inspector') return 'border-orange-200 bg-orange-100 text-orange-700';
    return 'border-custom-bg-dark bg-custom-bg text-custom-shadow dark:border-custom-bg-light dark:bg-custom-bg-light';
}

function statusVariant(status?: string | null) {
    if (status === 'active') return 'success';
    if (status === 'pending') return 'warning';
    if (status === 'suspended') return 'orange';
    return 'muted';
}

function statusDotClass(status?: string | null) {
    if (status === 'active') return 'bg-emerald-500';
    if (status === 'pending') return 'bg-amber-500';
    if (status === 'suspended') return 'bg-orange-500';
    return 'bg-slate-400';
}

function initials(name: string) {
    return name.trim().split(/\s+/).slice(0, 2).map((part) => part[0]?.toUpperCase()).join('') || 'E';
}

function isOwnAccount(employee: EmployeeUser) {
    return employee.id === props.user.id;
}

function applyFilters() {
    roleFilter.value = pendingRoleFilter.value;
    statusFilter.value = pendingStatusFilter.value;
    router.get(
        index().url,
        {
            search: props.filters.search || undefined,
            role: roleFilter.value === 'all' ? undefined : roleFilter.value,
            status: statusFilter.value === 'all' ? undefined : statusFilter.value,
            sort_by: sortBy.value ?? undefined,
            sort_dir: sortBy.value ? sortDir.value : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true, only: ['users', 'filters'] },
    );
    filterOpen.value = false;
}

function toggleSort(field: Exclude<SortField, null>) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function sortIcon(field: Exclude<SortField, null>) {
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: Exclude<SortField, null>) {
    return sortBy.value === field ? 'text-custom-primary' : 'text-custom-shadow/40';
}

function cancelFilters() {
    pendingRoleFilter.value = roleFilter.value;
    pendingStatusFilter.value = statusFilter.value;
    filterOpen.value = false;
}

function clearFilters() {
    roleFilter.value = 'all';
    statusFilter.value = 'all';
    pendingRoleFilter.value = 'all';
    pendingStatusFilter.value = 'all';
    applyFilters();
}

const previewedEmployee = ref<EmployeeUser | null>(null);
const openMenus = ref<Record<number, boolean>>({});
const togglingEmployee = ref<EmployeeUser | null>(null);
const resettingEmployee = ref<EmployeeUser | null>(null);
const archivingEmployee = ref<EmployeeUser | null>(null);
const toggleOpen = ref(false);
const resetOpen = ref(false);
const archiveOpen = ref(false);

function openToggleDialog(employee: EmployeeUser) {
    togglingEmployee.value = employee;
    toggleOpen.value = true;
}

function openResetDialog(employee: EmployeeUser) {
    resettingEmployee.value = employee;
    resetOpen.value = true;
}

function openArchiveDialog(employee: EmployeeUser) {
    archivingEmployee.value = employee;
    archiveOpen.value = true;
}
</script>

<template>
    <Head title="Employees" />

    <ExternalLayout :company="company" :user="user">
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row gap-2">
                        <div class="flex flex-col">
                            <CardTitle class="flex items-center gap-2"><span class="font-semibold">Employees</span></CardTitle>
                            <CardDescription>Manage company employees.</CardDescription>
                        </div>
                        <div v-if="canCreateEmployee" class="flex flex-1 items-center justify-end gap-2">
                            <Button as-child variant="float-primary" class="hidden lg:flex">
                                <Link :href="create().url"><RiAddLine class="h-4 w-4 shrink-0" /><span>Add Employee</span></Link>
                            </Button>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="header-actions" size="icon" class="lg:hidden" aria-label="Employee actions">
                                        <RiMore2Line class="h-4 w-4 shrink-0" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-fit">
                                    <DropdownMenuItem as-child class="group">
                                        <Link :href="create().url" class="flex items-center gap-2">
                                            <RiAddLine class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                            Add Employee
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
                                    :route="`${index().url}?role=${roleFilter === 'all' ? '' : roleFilter}&status=${statusFilter === 'all' ? '' : statusFilter}&sort_by=${sortBy ?? ''}&sort_dir=${sortBy ? sortDir : ''}`"
                                    :initial-value="filters.search"
                                    placeholder="Search employees..."
                                    :only="['users', 'filters']"
                                    :debounce="350"
                                />
                            </div>
                            <Popover v-model:open="filterOpen">
                                <PopoverTrigger as-child>
                                    <Button variant="header-actions" size="icon-text" class="rounded-full" :class="activeFilterCount > 0 ? 'bg-custom-secondary/20 transition-all duration-200 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''">
                                        <RiFilter2Line class="h-3.5 w-3.5" />
                                        <span class="hidden lg:flex">{{ activeFilterCount ? `${activeFilterCount} filter${activeFilterCount === 1 ? '' : 's'} active` : 'Filter' }}</span>
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent align="end">
                                    <div class="grid gap-y-2">
                                        <div class="flex flex-col gap-y-1">
                                            <p class="text-sm text-custom-shadow/80">Role</p>
                                            <Select v-model="pendingRoleFilter">
                                                <SelectTrigger class="w-full"><SelectValue placeholder="All roles" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">All Roles</SelectItem>
                                                    <SelectItem v-for="role in employeeRoles" :key="role" :value="role">{{ humanize(role) }}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div class="flex flex-col gap-y-1">
                                            <p class="text-sm text-custom-shadow/80">Status</p>
                                            <Select v-model="pendingStatusFilter">
                                                <SelectTrigger class="w-full"><SelectValue placeholder="All statuses" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">All Statuses</SelectItem>
                                                    <SelectItem v-for="status in statuses" :key="status" :value="status">{{ humanize(status) }}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <Separator class="my-1" />
                                        <div class="flex items-center justify-between">
                                            <Button v-if="activeFilterCount" size="sm" variant="destructive" @click="clearFilters">Clear</Button>
                                            <div class="ml-auto flex gap-2">
                                                <Button size="sm" variant="ghost-outline" @click="cancelFilters">Cancel</Button>
                                                <Button size="sm" variant="float-primary" @click="applyFilters">Apply</Button>
                                            </div>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>

                        <TableCard :table-data-length="users.data.length">
                            <Table v-if="users.data.length > 0">
                                <TableHeader>
                                    <TableColumn class="p-0">
                                        <button
                                            type="button"
                                            class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                            @click="toggleSort('name')"
                                        >
                                            Name & Username
                                            <component :is="sortIcon('name')" class="h-3.5 w-3.5" :class="sortIconClass('name')" />
                                        </button>
                                    </TableColumn>

                                    <TableColumn>Contact Details</TableColumn>
                                    <TableColumn>Role</TableColumn>

                                    <TableColumn class="p-0">
                                        <button
                                            type="button"
                                            class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                            @click="toggleSort('status')"
                                        >
                                            Status
                                            <component :is="sortIcon('status')" class="h-3.5 w-3.5" :class="sortIconClass('status')" />
                                        </button>
                                    </TableColumn>

                                    <TableColumn class="p-0">
                                        <button
                                            type="button"
                                            class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                            @click="toggleSort('created_at')"
                                        >
                                            Created
                                            <component :is="sortIcon('created_at')" class="h-3.5 w-3.5" :class="sortIconClass('created_at')" />
                                        </button>
                                    </TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(employee, rowIndex) in users.data"
                                        :key="employee.id"
                                        :class="[
                                            rowIndex === users.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedEmployee?.id === employee.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        :status="employee.status === 'inactive' ? 'inactive' : 'default'"
                                        @click.left="previewedEmployee = employee"
                                        @dblclick="router.visit(edit(employee.id).url)"
                                    >
                                        <TableData class="pl-3">
                                            <div class="flex min-w-0 items-center gap-2">
                                                <img v-if="employee.avatar" :src="employee.avatar" :alt="`${employee.name} avatar`" class="h-12 w-12 shrink-0 rounded-full object-cover" />
                                                <div v-else class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-custom-secondary/20 text-xs font-semibold">{{ initials(employee.name) }}</div>
                                                <div class="min-w-0">
                                                    <p class="truncate font-semibold capitalize">{{ employee.name }}</p>
                                                    <p class="truncate font-mono text-xs text-custom-shadow/70">{{ employee.username }}</p>
                                                </div>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <div class="flex min-w-0 flex-col gap-1 text-sm text-custom-shadow/80">
                                                <span class="truncate">{{ employee.email || '—' }}</span>
                                                <span class="truncate">{{ employee.phone_number || '—' }}</span>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <Badge :class="roleClass(employee)" class="border capitalize">{{ humanize(roleName(employee)) }}</Badge>
                                        </TableData>

                                        <TableData>
                                            <Badge :variant="statusVariant(employee.status)" class="gap-1.5">
                                                <span :class="['h-1.5 w-1.5 rounded-full', statusDotClass(employee.status)]" />
                                                {{ humanize(employee.status) }}
                                            </Badge>
                                        </TableData>

                                        <TableData>{{ formatDate(employee.created_at) }}</TableData>

                                        <TableMoreButton
                                            :open="openMenus[employee.id] ?? false"
                                            @update:open="(value) => (openMenus[employee.id] = value)"
                                        >
                                            <DropdownMenuLabel>{{ employee.username }}</DropdownMenuLabel>
                                            <DropdownMenuItem v-if="canViewEmployee" as-child class="group"><Link :href="edit(employee.id).url"><RiExternalLinkLine class="h-4 w-4" />View Profile</Link></DropdownMenuItem>
                                            <DropdownMenuItem v-if="canToggleEmployee && !isOwnAccount(employee)" class="group" @click="openToggleDialog(employee)"><RiShutDownLine class="h-4 w-4" />{{ employee.status === 'active' ? 'Inactivate' : 'Activate' }}</DropdownMenuItem>
                                            <DropdownMenuItem v-if="canResetEmployee && !isOwnAccount(employee)" class="group" @click="openResetDialog(employee)"><RiShieldKeyholeLine class="h-4 w-4 shrink-0" />Reset Password</DropdownMenuItem>
                                            <DropdownMenuItem v-if="canArchiveEmployee && !isOwnAccount(employee)" class="group" @click="openArchiveDialog(employee)"><RiArchive2Line class="h-4 w-4" />Archive Employee</DropdownMenuItem>
                                        </TableMoreButton>
                                    </TableRow>
                                </TableContent>
                            </Table>

                            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                                <div class="flex w-full max-w-md flex-col items-center gap-2"><img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" /><div class="space-y-1"><p class="text-base font-semibold text-custom-shadow">No employees found</p><p class="text-sm text-custom-shadow/80">{{ activeFilterCount ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search or add a new employee.' }}</p></div></div>
                            </div>
                        </TableCard>
                        <InertiaPagination :links="users.links" :meta="{ from: users.from, to: users.to, total: users.total }" />
                    </CardContent>
                </Card>
            </MainPanel>

            <SidePanel v-if="previewedEmployee" class="hidden lg:flex">
                <EmployeePreviewCard :employee="previewedEmployee" @close="previewedEmployee = null" />
            </SidePanel>
        </PanelLayout>

        <ToggleEmployeeStatusDialog v-model:open="toggleOpen" :employee="togglingEmployee" />
        <ResetEmployeePasswordDialog v-model:open="resetOpen" :employee="resettingEmployee" />
        <ArchiveEmployeeDialog v-model:open="archiveOpen" :employee="archivingEmployee" />
    </ExternalLayout>
</template>
