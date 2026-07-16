<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';

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
import {
    RiAddLine as Plus,
    RiEyeLine as Eye,
    RiFilter2Line,
    RiMore2Line as MoreHorizontal,
} from 'vue-remix-icons';


type Company = {
    id: number;
    company_name: string;
    company_code?: string | null;
    status: string;
    logo_url?: string | null;
};

type AuthUser = {
    id: number;
    name: string;
    username: string;
    email: string;
};

type Role = {
    id: number;
    name: string;
};

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

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

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
    };
    roles: string[];
    statuses: string[];
}>();

const canCreateEmployee = can('external_users.create');
const roleFilter = ref<string>(props.filters?.role ?? 'all');
const statusFilter = ref<string>(props.filters?.status ?? 'all');
const filterOpen = ref(false);


function humanize(value?: string | null) {
    if (!value) return '—';
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatDate(value?: string | null) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function roleName(employee: EmployeeUser) {
    return employee.roles?.[0]?.name ?? '—';
}

function roleClass(employee: EmployeeUser) {
    const role = roleName(employee).toLowerCase();
    if (role === 'driver') return 'bg-sky-100 text-sky-700 border-sky-200';
    if (role === 'dispatcher')
        return 'bg-violet-100 text-violet-700 border-violet-200';
    return 'bg-slate-100 text-slate-500 border-0';
}

function roleIconBg(employee: EmployeeUser) {
    const role = roleName(employee).toLowerCase();
    if (role === 'driver') return 'bg-sky-100';
    if (role === 'dispatcher') return 'bg-violet-100';
    return 'bg-slate-100';
}

function roleIconColor(employee: EmployeeUser) {
    const role = roleName(employee).toLowerCase();
    if (role === 'driver') return 'text-sky-700';
    if (role === 'dispatcher') return 'text-violet-700';
    return 'text-slate-600';
}

function statusClass(status?: string | null) {
    if (status === 'active')
        return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'pending')
        return 'bg-amber-100 text-amber-700 border-amber-200';
    if (status === 'suspended')
        return 'bg-rose-100 text-rose-600 border-rose-200';
    if (status === 'inactive') return 'bg-slate-100 text-slate-500 border-0';
    return 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(status?: string | null) {
    if (status === 'active') return 'bg-emerald-500';
    if (status === 'pending') return 'bg-amber-500';
    if (status === 'suspended') return 'bg-rose-500';
    return 'bg-slate-400';
}

const activeFilterCount = computed(
    () => Number(roleFilter.value !== 'all') + Number(statusFilter.value !== 'all'),
);

const hasActiveFilters = computed(() => activeFilterCount.value > 0);

const employeeRoles = computed(() =>
    props.roles.filter(
        (role) => !['commuter', 'commuters'].includes(role.toLowerCase()),
    ),
);


const dialogState = reactive({
    statusDialog: null as EmployeeUser | null,
    resetPasswordDialog: null as EmployeeUser | null,
    deleteDialog: null as EmployeeUser | null,
});


function toggleStatusLabel(status?: string | null) {
    return status === 'active' ? 'Deactivate' : 'Activate';
}

function applyFilters() {
    filterOpen.value = false;
    router.get(
        '/employee-users',
        {
            search: props.filters?.search || undefined,
            role: roleFilter.value === 'all' ? undefined : roleFilter.value,
            status:
                statusFilter.value === 'all' ? undefined : statusFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters'],
        },
    );
}

function clearFilters() {
    roleFilter.value = 'all';
    statusFilter.value = 'all';
    applyFilters();
}

function openStatusDialog(employee: EmployeeUser) {
    const newStatus = employee.status === 'active' ? 'deactivate' : 'activate';
    const confirmAction = window.confirm(
        `Are you sure you want to ${newStatus} ${employee.name}?`,
    );
    if (confirmAction) {
        router.patch(
            `/employee-users/${employee.id}/toggle-status`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    dialogState.statusDialog = null;
                },
            },
        );
    }
}

function openResetPasswordDialog(employee: EmployeeUser) {
    const confirmAction = window.confirm(
        `Are you sure you want to reset the password for ${employee.name}? A new temporary password will be generated.`,
    );
    if (confirmAction) {
        router.patch(
            `/employee-users/${employee.id}/reset-password`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    dialogState.resetPasswordDialog = null;
                },
            },
        );
    }
}

function openDeleteDialog(employee: EmployeeUser) {
    const confirmAction = window.confirm(
        `Are you sure you want to delete the account for ${employee.name}? This action cannot be undone.`,
    );
    if (confirmAction) {
        router.delete(`/employee-users/${employee.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                dialogState.deleteDialog = null;
            },
        });
    }
}
</script>

<template>
    <Head title="Employee Accounts" />

    <ExternalLayout :company="company" :user="user">
        <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
            <CardHeader class="flex flex-row gap-2">
                <div class="flex flex-col">
                    <CardTitle class="flex items-center gap-2">
                        <span class="font-semibold">Employee Accounts</span>
                    </CardTitle>
                    <CardDescription>
                        Search by name, username, email, or phone number.
                    </CardDescription>
                </div>

                <div class="flex flex-1 items-center justify-end gap-2">
                    <Button
                        v-if="canCreateEmployee"
                        as-child
                        variant="float-primary"
                    >
                        <Link href="/employee-users/create">
                            <Plus class="h-4 w-4 shrink-0" />
                            <span>Add Employee</span>
                        </Link>
                    </Button>
                </div>
            </CardHeader>

                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                        <div
                            class="flex flex-row gap-2 lg:items-center lg:justify-between"
                        >
                            
                            <div class="w-full">
                                <SearchInput
                                    route="/employee-users"
                                    :initial-value="filters.search"
                                    placeholder="Search employees..."
                                    :only="['users', 'filters']"
                                />
                            </div>

                            <div class="flex w-fit flex-row gap-2 lg:items-center lg:justify-between">
                                <Popover v-model:open="filterOpen">
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="header-actions"
                                            size="icon-text"
                                            class="rounded-full"
                                            :class="
                                                activeFilterCount > 0
                                                    ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light'
                                                    : ''
                                            "
                                        >
                                            <RiFilter2Line class="h-3.5 w-3.5" />
                                            <span class="hidden lg:flex">
                                                {{
                                                    activeFilterCount > 0
                                                        ? (activeFilterCount === 1 ? '1 filter active' : `${activeFilterCount} filters active`)
                                                        : 'Filter'
                                                }}
                                            </span>
                                        </Button>
                                    </PopoverTrigger>

                                    <PopoverContent align="end">
                                        <div class="grid gap-y-2">
                                            <div class="flex flex-col gap-y-1">
                                                <p class="text-sm text-custom-shadow/80">Role</p>
                                                <Select
                                                    :model-value="roleFilter"
                                                    @update:model-value="(value) => roleFilter = value != null ? String(value) : 'all'"
                                                >
                                                    <SelectTrigger class="w-full">
                                                        <SelectValue placeholder="All roles" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all" class="cursor-pointer">All Roles</SelectItem>
                                                        <SelectItem
                                                            v-for="role in employeeRoles"
                                                            :key="role"
                                                            :value="role"
                                                            class="cursor-pointer"
                                                        >
                                                            {{ humanize(role) }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="flex flex-col gap-y-1">
                                                <p class="text-sm text-custom-shadow/80">Status</p>
                                                <Select
                                                    :model-value="statusFilter"
                                                    @update:model-value="(value) => statusFilter = value != null ? String(value) : 'all'"
                                                >
                                                    <SelectTrigger class="w-full">
                                                        <SelectValue placeholder="All statuses" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all" class="cursor-pointer">All Statuses</SelectItem>
                                                        <SelectItem
                                                            v-for="status in props.statuses"
                                                            :key="status"
                                                            :value="status"
                                                            class="cursor-pointer"
                                                        >
                                                            {{ humanize(status) }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                                            <div class="flex w-full flex-row items-center justify-between">
                                                <Button
                                                    v-if="hasActiveFilters"
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
                                                        @click="filterOpen = false"
                                                    >
                                                        Cancel
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="float-primary"
                                                        @click="applyFilters"
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
                            users.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                    <div v-if="users.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                        <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                            <div class="grid grid-cols-7 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                <div class="col-span-1 flex h-10 items-center justify-start px-0 pl-5 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Employee</div>
                                <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Username</div>
                                <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Role</div>
                                <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Status</div>
                                <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Phone</div>
                                <div class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Created</div>
                                <div class="col-span-1 flex h-10 items-center justify-end px-0 pr-5 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Actions</div>
                            </div>
                        </div>

                        <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                            <template
                                v-for="(employee, index) in users.data"
                                :key="employee.id"
                            >
                                <div
                                    :class="[
                                        'grid grid-cols-7 items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        index === users.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    ]"
                                >
                                    
                                    <div class="col-span-1 flex items-center justify-start gap-2.5 py-1.5 pl-5">
                                            <img
                                                v-if="employee.avatar"
                                                :src="employee.avatar"
                                                :alt="`${employee.name} avatar`"
                                                class="h-8 w-8 shrink-0 rounded-full border border-slate-200 object-cover"
                                            />
                                            <div
                                                v-else
                                                :class="[
                                                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold uppercase',
                                                    roleIconBg(employee),
                                                    roleIconColor(employee),
                                                ]"
                                            >
                                                {{ employee.name.charAt(0) }}
                                            </div>
                                             <div class="min-w-0">
                                                <p
                                                    class="truncate text-sm font-semibold text-custom-shadow"
                                                >
                                                    {{ employee.name }}
                                                </p>
                                                <p
                                                    class="truncate text-xs text-custom-shadow/60"
                                                >
                                                    {{ employee.email || '—' }}
                                                </p>
                                            </div>
                                    </div>

                                    
                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <span
                                            class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold tracking-wide text-slate-700"
                                        >
                                            {{ employee.username }}
                                        </span>
                                    </div>

                                    
                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                roleClass(employee),
                                            ]"
                                        >
                                            {{ humanize(roleName(employee)) }}
                                        </span>
                                    </div>

                                    
                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                statusClass(employee.status),
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full',
                                                    statusDot(employee.status),
                                                ]"
                                            />
                                            {{ humanize(employee.status) }}
                                        </span>
                                    </div>

                                    
                                    <div class="col-span-1 flex justify-start py-1.5 text-sm tabular-nums text-custom-shadow/70">
                                        {{ employee.phone_number || '—' }}
                                    </div>

                                    
                                    <div class="col-span-1 flex justify-start py-1.5 text-sm text-custom-shadow/60">
                                        {{ formatDate(employee.created_at) }}
                                    </div>

                                    
                                    <div class="col-span-1 flex justify-end py-1.5 pr-5 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <MoreHorizontal
                                                        class="h-4 w-4"
                                                    />
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="">
                                                <DropdownMenuLabel>
                                                    Actions
                                                </DropdownMenuLabel>

                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-slate-700 focus:bg-slate-50 focus:text-slate-900"
                                                >
                                                    <Link
                                                        :href="`/employee-users/${employee.id}`"
                                                        class="flex items-center"
                                                    >
                                                        <Eye
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        View Profile
                                                    </Link>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                        <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                            <img
                                :src="emptyRafikiUrl"
                                alt=""
                                class="w-1/3 object-contain opacity-90"
                                aria-hidden="true"
                            />
                            <div class="space-y-1">
                                <p class="text-custom-shadow text-base font-semibold">No employees found</p>
                                <p class="text-custom-shadow/80 text-sm">
                                    {{ activeFilterCount > 0 ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search or add a new employee.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    </Card>

                    
                    <div
                        v-if="users.last_page > 1 || users.total > 0"
                        class="border-t border-custom-bg-dark px-5 py-3 dark:border-custom-bg-light"
                    >
                        <InertiaPagination
                            :links="users.links"
                            :meta="{
                                from: users.from,
                                to: users.to,
                                total: users.total,
                            }"
                        />
                    </div>
            </CardContent>
        </Card>
    </ExternalLayout>
</template>
