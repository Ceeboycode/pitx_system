<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';
import { create, destroy, edit, index, resetPassword, show, toggleStatus } from '@/routes/employee-users';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    RiAddLine,
    RiArchive2Line,
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiCloseLine,
    RiEditLine,
    RiExternalLinkLine,
    RiFilter2Line,
    RiKey2Line,
    RiMore2Line,
    RiShutDownLine,
} from 'vue-remix-icons';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

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
const canUpdateEmployee = can('external_users.update');
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
    return 'border-slate-200 bg-slate-100 text-slate-600';
}

function statusClass(status?: string | null) {
    if (status === 'active') return 'border-emerald-200 bg-emerald-100 text-emerald-700';
    if (status === 'suspended') return 'border-rose-200 bg-rose-100 text-rose-600';
    return 'border-slate-200 bg-slate-100 text-slate-500';
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

function confirmToggle() {
    if (!togglingEmployee.value) return;
    router.patch(toggleStatus(togglingEmployee.value.id).url, {}, {
        preserveScroll: true,
        onSuccess: () => { togglingEmployee.value = null; toggleOpen.value = false; },
        onError: () => toast.error('Failed to update employee status.'),
    });
}

function openResetDialog(employee: EmployeeUser) {
    resettingEmployee.value = employee;
    resetOpen.value = true;
}

function confirmReset() {
    if (!resettingEmployee.value) return;
    router.patch(resetPassword(resettingEmployee.value.id).url, {}, {
        preserveScroll: true,
        onSuccess: () => { resettingEmployee.value = null; resetOpen.value = false; },
        onError: () => toast.error('Failed to reset employee password.'),
    });
}

function openArchiveDialog(employee: EmployeeUser) {
    archivingEmployee.value = employee;
    archiveOpen.value = true;
}

function confirmArchive() {
    if (!archivingEmployee.value) return;
    router.delete(destroy(archivingEmployee.value.id).url, {
        preserveScroll: true,
        onSuccess: () => { archivingEmployee.value = null; archiveOpen.value = false; },
        onError: () => toast.error('Failed to archive employee.'),
    });
}
</script>

<template>
    <Head title="Employees" />

    <ExternalLayout :company="company" :user="user">
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4 lg:flex-row lg:items-stretch">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2"><span class="font-semibold">Employees</span></CardTitle>
                        <CardDescription>Manage company employees.</CardDescription>
                    </div>
                    <div class="flex flex-1 items-center justify-end gap-2">
                        <Button v-if="canCreateEmployee" as-child variant="float-primary">
                            <Link :href="create().url"><RiAddLine class="h-4 w-4 shrink-0" /><span>Add Employee</span></Link>
                        </Button>
                    </div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
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
                                <Button variant="header-actions" size="icon-text" class="rounded-full" :class="activeFilterCount > 0 ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''">
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

                    <Card :class="['flex min-h-0 max-h-fit flex-1 flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none', users.data.length ? 'border-solid' : 'border-dashed']">
                        <div v-if="users.data.length" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-[minmax(0,1.7fr)_minmax(0,1.7fr)_minmax(0,0.8fr)_minmax(0,0.75fr)_minmax(0,0.8fr)_3rem] gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <button type="button" class="flex h-10 cursor-pointer select-none items-center gap-1.5 pl-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow" @click="toggleSort('name')">
                                        Name & Username
                                        <component :is="sortIcon('name')" class="h-3.5 w-3.5" :class="sortIconClass('name')" />
                                    </button>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Contact Details</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Role</div>
                                    <button type="button" class="flex h-10 cursor-pointer select-none items-center gap-1.5 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow" @click="toggleSort('status')">
                                        Status
                                        <component :is="sortIcon('status')" class="h-3.5 w-3.5" :class="sortIconClass('status')" />
                                    </button>
                                    <button type="button" class="flex h-10 cursor-pointer select-none items-center gap-1.5 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow" @click="toggleSort('created_at')">
                                        Created
                                        <component :is="sortIcon('created_at')" class="h-3.5 w-3.5" :class="sortIconClass('created_at')" />
                                    </button>
                                    <div class="flex h-10 items-center justify-end pr-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                            </div>
                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div v-for="(employee, rowIndex) in users.data" :key="employee.id" :class="['grid cursor-pointer grid-cols-[minmax(0,1.7fr)_minmax(0,1.7fr)_minmax(0,0.8fr)_minmax(0,0.75fr)_minmax(0,0.8fr)_3rem] items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light', rowIndex === users.data.length - 1 ? 'rounded-b-md border-b-0' : '', previewedEmployee?.id === employee.id ? 'bg-custom-secondary/10 text-custom-shadow' : '']" @click="previewedEmployee = employee">
                                    <div class="flex min-w-0 items-center gap-2 py-1.5 pl-3">
                                        <img v-if="employee.avatar" :src="employee.avatar" :alt="`${employee.name} avatar`" class="h-12 w-12 shrink-0 rounded-full object-cover" />
                                        <div v-else class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-custom-secondary/20 text-xs font-semibold">{{ initials(employee.name) }}</div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold capitalize">{{ employee.name }}</p>
                                            <p class="truncate font-mono text-xs text-custom-shadow/70">{{ employee.username }}</p>
                                        </div>
                                    </div>
                                    <div class="flex min-w-0 flex-col gap-1 py-1.5 text-sm text-custom-shadow/80">
                                        <span class="truncate">{{ employee.email || '—' }}</span>
                                        <span class="truncate">{{ employee.phone_number || '—' }}</span>
                                    </div>
                                    <div class="py-1.5"><Badge :class="roleClass(employee)" class="border capitalize">{{ humanize(roleName(employee)) }}</Badge></div>
                                    <div class="py-1.5"><Badge :class="statusClass(employee.status)" class="border capitalize">{{ humanize(employee.status) }}</Badge></div>
                                    <div class="py-1.5 text-sm">{{ formatDate(employee.created_at) }}</div>
                                    <div class="flex justify-end py-1.5 pr-3" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child><Button variant="table-more" size="icon-more"><RiMore2Line class="h-4 w-4" /></Button></DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>{{ employee.username }}</DropdownMenuLabel>
                                                <DropdownMenuItem v-if="canViewEmployee" as-child class="group"><Link :href="show(employee.id).url"><RiExternalLinkLine class="h-4 w-4" />View Profile</Link></DropdownMenuItem>
                                                <DropdownMenuItem v-if="canUpdateEmployee && !isOwnAccount(employee)" as-child class="group"><Link :href="edit(employee.id).url"><RiEditLine class="h-4 w-4" />Edit Details</Link></DropdownMenuItem>
                                                <DropdownMenuItem v-if="canToggleEmployee && !isOwnAccount(employee)" class="group" @click="openToggleDialog(employee)"><RiShutDownLine class="h-4 w-4" />{{ employee.status === 'active' ? 'Set Inactive' : 'Set Active' }}</DropdownMenuItem>
                                                <DropdownMenuItem v-if="canResetEmployee && !isOwnAccount(employee)" class="group" @click="openResetDialog(employee)"><RiKey2Line class="h-4 w-4" />Reset Password</DropdownMenuItem>
                                                <DropdownMenuItem v-if="canArchiveEmployee && !isOwnAccount(employee)" class="group" @click="openArchiveDialog(employee)"><RiArchive2Line class="h-4 w-4" />Archive Employee</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                            <div class="flex w-full max-w-md flex-col items-center gap-2"><img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" /><div class="space-y-1"><p class="text-base font-semibold text-custom-shadow">No employees found</p><p class="text-sm text-custom-shadow/80">{{ activeFilterCount ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search or add a new employee.' }}</p></div></div>
                        </div>
                    </Card>
                    <InertiaPagination :links="users.links" :meta="{ from: users.from, to: users.to, total: users.total }" />
                </CardContent>
            </Card>

            <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-100">
                <CardHeader v-if="previewedEmployee" class="flex flex-row items-start justify-between gap-3">
                    <div class="min-w-0"><CardTitle class="truncate capitalize">{{ previewedEmployee.name }}</CardTitle><CardDescription>Preview</CardDescription></div>
                    <Button variant="header-actions" size="icon" class="h-8 w-8 shrink-0 rounded-full" @click="previewedEmployee = null"><RiCloseLine class="h-4 w-4" /></Button>
                </CardHeader>
                <CardContent v-if="previewedEmployee" class="no-scrollbar min-h-0 flex-1 space-y-4 overflow-y-auto py-2">
                    <div class="flex flex-col items-center gap-2">
                        <img v-if="previewedEmployee.avatar" :src="previewedEmployee.avatar" :alt="`${previewedEmployee.name} avatar`" class="h-24 w-24 rounded-full object-cover" />
                        <div v-else class="flex h-24 w-24 items-center justify-center rounded-full bg-custom-secondary/20 text-2xl font-semibold">{{ initials(previewedEmployee.name) }}</div>
                        <div class="text-center"><p class="font-semibold">{{ previewedEmployee.name }}</p><p class="text-sm text-custom-shadow/70">{{ previewedEmployee.email || '—' }}</p></div>
                    </div>
                    <Separator />
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><span class="font-semibold">Username</span><span>{{ previewedEmployee.username }}</span></div>
                        <div class="flex justify-between gap-3"><span class="font-semibold">Role</span><Badge :class="roleClass(previewedEmployee)" class="border capitalize">{{ humanize(roleName(previewedEmployee)) }}</Badge></div>
                        <div class="flex justify-between gap-3"><span class="font-semibold">Status</span><Badge :class="statusClass(previewedEmployee.status)" class="border capitalize">{{ humanize(previewedEmployee.status) }}</Badge></div>
                        <div class="flex justify-between gap-3"><span class="font-semibold">Phone</span><span>{{ previewedEmployee.phone_number || '—' }}</span></div>
                        <div class="flex justify-between gap-3"><span class="font-semibold">Created</span><span>{{ formatDate(previewedEmployee.created_at) }}</span></div>
                    </div>
                    <Button v-if="canViewEmployee" as-child variant="float-primary" class="w-full"><Link :href="show(previewedEmployee.id).url">View Profile</Link></Button>
                </CardContent>
                <CardContent v-else class="flex min-h-0 flex-1 items-center justify-center"><div class="max-w-60 space-y-1 text-center"><p class="text-base font-semibold text-custom-shadow">No employee selected</p><p class="text-sm text-custom-shadow/80">Click on an employee to preview.</p></div></CardContent>
            </Card>
        </div>

        <Dialog v-model:open="toggleOpen"><DialogContent class="px-6"><DialogHeader class="px-0"><DialogTitle>{{ togglingEmployee?.status === 'active' ? 'Set Employee Inactive' : 'Set Employee Active' }}</DialogTitle><DialogDescription class="mt-4">Are you sure you want to set <span class="font-semibold text-custom-accent-3">{{ togglingEmployee?.name ?? 'this employee' }}</span> as {{ togglingEmployee?.status === 'active' ? 'inactive' : 'active' }}?</DialogDescription></DialogHeader><Separator class="mb-4" /><DialogFooter class="gap-2 sm:justify-end"><Button variant="ghost-outline" @click="toggleOpen = false; togglingEmployee = null">Cancel</Button><Button :variant="togglingEmployee?.status === 'active' ? 'destructive' : 'float-primary'" @click="confirmToggle"><RiShutDownLine class="h-4 w-4" />{{ togglingEmployee?.status === 'active' ? 'Set Inactive' : 'Set Active' }}</Button></DialogFooter></DialogContent></Dialog>
        <Dialog v-model:open="resetOpen"><DialogContent class="px-6"><DialogHeader class="px-0"><DialogTitle>Reset Password</DialogTitle><DialogDescription class="mt-4">Are you sure you want to reset the password for <span class="font-semibold text-custom-accent-3">{{ resettingEmployee?.name ?? 'this employee' }}</span>?</DialogDescription></DialogHeader><Separator class="mb-4" /><DialogFooter class="gap-2 sm:justify-end"><Button variant="ghost-outline" @click="resetOpen = false; resettingEmployee = null">Cancel</Button><Button variant="float-primary" @click="confirmReset"><RiKey2Line class="h-4 w-4" />Reset Password</Button></DialogFooter></DialogContent></Dialog>
        <Dialog v-model:open="archiveOpen"><DialogContent class="px-6"><DialogHeader class="px-0"><DialogTitle>Archive Employee</DialogTitle><DialogDescription class="mt-4">Are you sure you want to archive <span class="font-semibold text-custom-accent-3">{{ archivingEmployee?.name ?? 'this employee' }}</span>?</DialogDescription></DialogHeader><Separator class="mb-4" /><DialogFooter class="gap-2 sm:justify-end"><Button variant="ghost-outline" @click="archiveOpen = false; archivingEmployee = null">Cancel</Button><Button variant="destructive" @click="confirmArchive"><RiArchive2Line class="h-4 w-4" />Archive Employee</Button></DialogFooter></DialogContent></Dialog>
    </ExternalLayout>
</template>
