<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';

import { Button } from '@/components/ui/button';
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

import {
    Building2,
    Eye,
    MoreHorizontal,
    Plus,
    Power,
    Radio,
    TruckIcon,
    Users,
} from 'lucide-vue-next';

/* ======================================================
   Types
====================================================== */
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

/* ======================================================
   Props
====================================================== */
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

/* ======================================================
   Helpers
====================================================== */
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
    return 'bg-blue-100';
}

function roleIconColor(employee: EmployeeUser) {
    const role = roleName(employee).toLowerCase();
    if (role === 'driver') return 'text-sky-700';
    if (role === 'dispatcher') return 'text-violet-700';
    return 'text-blue-700';
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

/* ======================================================
   Computed stats
====================================================== */
const totalEmployees = computed(() => props.users.total ?? 0);

const totalDrivers = computed(
    () =>
        props.users.data.filter((e) => roleName(e).toLowerCase() === 'driver')
            .length,
);

const totalDispatchers = computed(
    () =>
        props.users.data.filter(
            (e) => roleName(e).toLowerCase() === 'dispatcher',
        ).length,
);

const activeCount = computed(
    () => props.users.data.filter((e) => e.status === 'active').length,
);

/* ======================================================
   State
====================================================== */
const dialogState = reactive({
    statusDialog: null as EmployeeUser | null,
    resetPasswordDialog: null as EmployeeUser | null,
    deleteDialog: null as EmployeeUser | null,
});

/* ======================================================
   Methods
====================================================== */
function toggleStatusClass(status?: string | null) {
    if (status === 'active') {
        return 'text-amber-700 focus:bg-amber-50 focus:text-amber-700';
    }
    return 'text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700';
}

function toggleStatusLabel(status?: string | null) {
    return status === 'active' ? 'Deactivate' : 'Activate';
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
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
                <!-- ── Page header ───────────────────────────── -->
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="space-y-1">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            <Building2 class="h-3.5 w-3.5" />
                            {{ company.company_code ?? company.company_name }}
                        </div>
                        <h1
                            class="text-2xl font-bold tracking-tight text-slate-900"
                        >
                            Employee Accounts
                        </h1>
                        <p class="text-sm text-slate-500">
                            Manage drivers and dispatchers for your company.
                        </p>
                    </div>

                    <Button
                        v-if="canCreateEmployee"
                        as-child
                        class="shrink-0 gap-2 self-start rounded-lg border-0 bg-blue-700 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
                    >
                        <Link href="/employee-users/create">
                            <Plus class="h-4 w-4" />
                            Add Employee
                        </Link>
                    </Button>
                </div>

                <!-- ── Stats ─────────────────────────────────── -->
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <!-- Total -->
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <p
                                class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                            >
                                Total
                            </p>
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-700"
                            >
                                <Users class="h-4 w-4 text-white" />
                            </div>
                        </div>

                        <p
                            class="mt-3 text-3xl font-bold text-slate-900 tabular-nums"
                        >
                            {{ totalEmployees }}
                        </p>
                    </div>

                    <!-- Drivers -->
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <p
                                class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                            >
                                Drivers
                            </p>
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-600"
                            >
                                <TruckIcon class="h-4 w-4 text-white" />
                            </div>
                        </div>

                        <p
                            class="mt-3 text-3xl font-bold text-slate-900 tabular-nums"
                        >
                            {{ totalDrivers }}
                        </p>
                    </div>

                    <!-- Dispatchers -->
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <p
                                class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                            >
                                Dispatchers
                            </p>
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-600"
                            >
                                <Radio class="h-4 w-4 text-white" />
                            </div>
                        </div>

                        <p
                            class="mt-3 text-3xl font-bold text-slate-900 tabular-nums"
                        >
                            {{ totalDispatchers }}
                        </p>
                    </div>

                    <!-- Active -->
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <p
                                class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                            >
                                Active
                            </p>
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600"
                            >
                                <Power class="h-4 w-4 text-white" />
                            </div>
                        </div>

                        <p
                            class="mt-3 text-3xl font-bold text-slate-900 tabular-nums"
                        >
                            {{ activeCount }}
                        </p>
                    </div>
                </div>

                <!-- ── Table card ─────────────────────────────── -->
                <div
                    class="rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div
                        class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">
                                Employee List
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-400">
                                Search by name, username, email, or phone.
                            </p>
                        </div>
                        <div class="sm:w-72">
                            <SearchInput
                                route="/employee-users"
                                :initial-value="filters.search"
                                placeholder="Search employees…"
                                :only="['users', 'filters']"
                            />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow
                                    class="border-slate-100 bg-slate-50/70 hover:bg-slate-50/70"
                                >
                                    <TableHead
                                        class="pl-5 text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                    >
                                        Username
                                    </TableHead>
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                    >
                                        Employee
                                    </TableHead>
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                    >
                                        Role
                                    </TableHead>
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                    >
                                        Status
                                    </TableHead>
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                    >
                                        Phone
                                    </TableHead>
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                    >
                                        Created
                                    </TableHead>
                                    <TableHead
                                        class="pr-5 text-right text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                    >
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <!-- Empty state -->
                                <TableRow
                                    v-if="users.data.length === 0"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="7"
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100"
                                            >
                                                <Users
                                                    class="h-6 w-6 text-slate-400"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-slate-600"
                                                >
                                                    No employees found
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-slate-400"
                                                >
                                                    Try adjusting your search.
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="employee in users.data"
                                    :key="employee.id"
                                    class="border-slate-100 transition-colors hover:bg-slate-50/80"
                                >
                                    <!-- Username -->
                                    <TableCell class="pl-5">
                                        <span
                                            class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold tracking-wide text-slate-700"
                                        >
                                            {{ employee.username }}
                                        </span>
                                    </TableCell>

                                    <!-- Employee info -->
                                    <TableCell>
                                        <div class="flex items-center gap-2.5">
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
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-slate-800"
                                                >
                                                    {{ employee.name }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-400"
                                                >
                                                    {{ employee.email || '—' }}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Role -->
                                    <TableCell>
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                roleClass(employee),
                                            ]"
                                        >
                                            {{ humanize(roleName(employee)) }}
                                        </span>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
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
                                    </TableCell>

                                    <!-- Phone -->
                                    <TableCell
                                        class="text-sm text-slate-500 tabular-nums"
                                    >
                                        {{ employee.phone_number || '—' }}
                                    </TableCell>

                                    <!-- Created -->
                                    <TableCell class="text-sm text-slate-400">
                                        {{ formatDate(employee.created_at) }}
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="pr-5 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                                >
                                                    <MoreHorizontal
                                                        class="h-4 w-4"
                                                    />
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                class="w-56 rounded-xl border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                                >
                                                    Actions
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator
                                                    class="bg-slate-100"
                                                />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-slate-700 focus:bg-blue-50 focus:text-blue-700"
                                                >
                                                    <Link
                                                        :href="`/employee-users/${employee.id}`"
                                                    >
                                                        <Eye
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        View Profile
                                                    </Link>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="users.last_page > 1"
                        class="border-t border-slate-100 px-5 py-3"
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
                </div>
            </div>
        </div>
    </ExternalLayout>
</template>
