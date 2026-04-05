<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

import ExternalLayout from '@/layouts/ExternalLayout.vue';

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
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';

import {
    Archive,
    ArrowLeft,
    Building2,
    CalendarDays,
    Edit,
    KeyRound,
    Mail,
    MoreHorizontal,
    Phone,
    Power,
    Shield,
    UserCog,
    UserSquare2,
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

type RoleItem = {
    id: number;
    name: string;
};

type Employee = {
    id: number;
    username: string;
    name: string;
    avatar?: string | null;
    email?: string | null;
    phone_number?: string | null;
    status: string;
    created_at?: string | null;
    roles?: RoleItem[];
    company?: Company | null;
};

/* ======================================================
   Props
====================================================== */
const props = defineProps<{
    company: Company;
    user: AuthUser;
    employee: Employee;
}>();

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

function roleName() {
    return props.employee.roles?.[0]?.name ?? '—';
}

function roleClass(role?: string | null) {
    const value = String(role ?? '').toLowerCase();
    if (value === 'driver') return 'bg-sky-100 text-sky-700 border-sky-200';
    if (value === 'dispatcher')
        return 'bg-violet-100 text-violet-700 border-violet-200';
    if (value === 'conductor')
        return 'bg-teal-100 text-teal-700 border-teal-200';
    if (value === 'inspector')
        return 'bg-orange-100 text-orange-700 border-orange-200';
    return 'bg-slate-100 text-slate-500 border-0';
}

function toggleStatusLabel(status?: string | null) {
    if (status === 'active') return 'Set Inactive';
    if (status === 'inactive') return 'Set Active';
    if (status === 'pending') return 'Activate Account';
    if (status === 'suspended') return 'Set Active';
    return 'Update Status';
}

function toggleStatusClass(status?: string | null) {
    if (status === 'active')
        return 'text-rose-600 focus:text-rose-600 focus:bg-rose-50';
    return 'text-emerald-700 focus:text-emerald-700 focus:bg-emerald-50';
}

const initials = computed(() =>
    props.employee.name
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join(''),
);

const isOwnAccount = computed(() => props.user.id === props.employee.id);

/* ======================================================
   Dialog state
====================================================== */
const statusDialog = reactive({ open: false });
const resetPasswordDialog = reactive({ open: false });
const archiveDialog = reactive({ open: false });

/* ======================================================
   Actions (backend untouched)
====================================================== */
function confirmToggleStatus() {
    router.patch(
        `/employee-users/${props.employee.id}/toggle-status`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                statusDialog.open = false;
            },
        },
    );
}

function confirmResetPassword() {
    router.patch(
        `/employee-users/${props.employee.id}/reset-password`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                resetPasswordDialog.open = false;
            },
        },
    );
}

function confirmArchive() {
    router.delete(`/employee-users/${props.employee.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            archiveDialog.open = false;
        },
    });
}
</script>

<template>
    <Head title="Employee Profile" />

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
                            <span class="text-slate-300">·</span>
                            <span>Employees</span>
                            <span class="text-slate-300">·</span>
                            <span>Profile</span>
                        </div>
                        <h1
                            class="text-2xl font-bold tracking-tight text-slate-900"
                        >
                            Employee Profile
                        </h1>
                        <p class="text-sm text-slate-500">
                            View employee details and manage account actions.
                        </p>
                    </div>

                    <!-- Back + Actions dropdown -->
                    <div class="flex shrink-0 items-center gap-2 self-start">
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-800"
                        >
                            <Link href="/employee-users">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back
                            </Link>
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    class="gap-2 rounded-lg border-0 bg-blue-700 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
                                >
                                    <MoreHorizontal class="h-4 w-4" />
                                    Actions
                                </Button>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent
                                align="end"
                                class="w-56 rounded-xl border-slate-200 shadow-lg"
                            >
                                <DropdownMenuLabel
                                    class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                >
                                    Manage Employee
                                </DropdownMenuLabel>

                                <DropdownMenuSeparator class="bg-slate-100" />

                                <!-- Edit -->
                                <DropdownMenuItem
                                    v-if="!isOwnAccount"
                                    as-child
                                    class="rounded-lg text-slate-700 focus:bg-amber-50 focus:text-amber-700"
                                >
                                    <Link
                                        :href="`/employee-users/${employee.id}/edit`"
                                    >
                                        <Edit class="mr-2 h-4 w-4" />
                                        Edit Details
                                    </Link>
                                </DropdownMenuItem>

                                <!-- Toggle status -->
                                <DropdownMenuItem
                                    v-if="!isOwnAccount"
                                    :class="[
                                        'rounded-lg',
                                        toggleStatusClass(employee.status),
                                    ]"
                                    @click="statusDialog.open = true"
                                >
                                    <Power class="mr-2 h-4 w-4" />
                                    {{ toggleStatusLabel(employee.status) }}
                                </DropdownMenuItem>

                                <!-- Reset password -->
                                <DropdownMenuItem
                                    v-if="!isOwnAccount"
                                    class="rounded-lg text-slate-700 focus:bg-blue-50 focus:text-blue-700"
                                    @click="resetPasswordDialog.open = true"
                                >
                                    <KeyRound class="mr-2 h-4 w-4" />
                                    Reset Password
                                </DropdownMenuItem>

                                <DropdownMenuSeparator
                                    v-if="!isOwnAccount"
                                    class="bg-slate-100"
                                />

                                <!-- Archive -->
                                <DropdownMenuItem
                                    v-if="!isOwnAccount"
                                    class="rounded-lg text-rose-600 focus:bg-rose-50 focus:text-rose-600"
                                    @click="archiveDialog.open = true"
                                >
                                    <Archive class="mr-2 h-4 w-4" />
                                    Archive Account
                                </DropdownMenuItem>

                                <DropdownMenuItem
                                    v-if="isOwnAccount"
                                    disabled
                                    class="pointer-events-none rounded-lg text-slate-400"
                                >
                                    You cannot manage your own account here
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <!-- ── Main content grid ──────────────────────── -->
                <div class="grid gap-6 xl:grid-cols-3">
                    <!-- Left: employee info card -->
                    <div class="space-y-6 xl:col-span-2">
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <!-- Card header -->
                            <div
                                class="flex items-center gap-2 border-b border-slate-100 px-5 py-4"
                            >
                                <UserSquare2 class="h-4 w-4 text-blue-700" />
                                <div>
                                    <h2
                                        class="text-base font-semibold text-slate-800"
                                    >
                                        Employee Information
                                    </h2>
                                    <p class="text-xs text-slate-400">
                                        Basic employee account details.
                                    </p>
                                </div>
                            </div>

                            <!-- Avatar + badges -->
                            <div class="px-5 pt-5">
                                <div
                                    class="flex flex-col gap-4 sm:flex-row sm:items-center"
                                >
                                    <img
                                        v-if="employee.avatar"
                                        :src="employee.avatar"
                                        :alt="`${employee.name} avatar`"
                                        class="h-16 w-16 shrink-0 rounded-full border border-slate-200 object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700 uppercase"
                                    >
                                        {{ initials }}
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-lg font-semibold text-slate-900"
                                        >
                                            {{ employee.name }}
                                        </p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span
                                                :class="[
                                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                    statusClass(
                                                        employee.status,
                                                    ),
                                                ]"
                                            >
                                                <span
                                                    :class="[
                                                        'h-1.5 w-1.5 rounded-full',
                                                        statusDot(
                                                            employee.status,
                                                        ),
                                                    ]"
                                                />
                                                {{ humanize(employee.status) }}
                                            </span>

                                            <span
                                                :class="[
                                                    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                    roleClass(roleName()),
                                                ]"
                                            >
                                                {{ humanize(roleName()) }}
                                            </span>

                                            <span
                                                class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 font-mono text-xs font-semibold text-slate-600"
                                            >
                                                {{ employee.username }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="px-5 py-4">
                                <Separator class="bg-slate-100" />
                            </div>

                            <!-- Detail fields grid -->
                            <div class="grid gap-4 px-5 pb-5 md:grid-cols-2">
                                <div
                                    class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"
                                >
                                    <div
                                        class="mb-2 flex items-center gap-2 text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        <Users class="h-3.5 w-3.5" />
                                        Full Name
                                    </div>
                                    <p
                                        class="text-sm font-semibold text-slate-800"
                                    >
                                        {{ employee.name }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"
                                >
                                    <div
                                        class="mb-2 flex items-center gap-2 text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        <Shield class="h-3.5 w-3.5" />
                                        Username
                                    </div>
                                    <p
                                        class="font-mono text-sm font-semibold text-slate-800"
                                    >
                                        {{ employee.username }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"
                                >
                                    <div
                                        class="mb-2 flex items-center gap-2 text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        <Mail class="h-3.5 w-3.5" />
                                        Email Address
                                    </div>
                                    <p
                                        class="text-sm font-semibold break-all text-slate-800"
                                    >
                                        {{ employee.email || '—' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"
                                >
                                    <div
                                        class="mb-2 flex items-center gap-2 text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        <Phone class="h-3.5 w-3.5" />
                                        Phone Number
                                    </div>
                                    <p
                                        class="text-sm font-semibold text-slate-800"
                                    >
                                        {{ employee.phone_number || '—' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"
                                >
                                    <div
                                        class="mb-2 flex items-center gap-2 text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        <UserCog class="h-3.5 w-3.5" />
                                        Assigned Role
                                    </div>
                                    <p
                                        class="text-sm font-semibold text-slate-800"
                                    >
                                        {{ humanize(roleName()) }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"
                                >
                                    <div
                                        class="mb-2 flex items-center gap-2 text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        <CalendarDays class="h-3.5 w-3.5" />
                                        Created At
                                    </div>
                                    <p
                                        class="text-sm font-semibold text-slate-800"
                                    >
                                        {{ formatDate(employee.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: company card -->
                    <div class="space-y-6">
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div
                                class="flex items-center gap-2 border-b border-slate-100 px-5 py-4"
                            >
                                <Building2 class="h-4 w-4 text-blue-700" />
                                <h2
                                    class="text-sm font-semibold text-slate-800"
                                >
                                    Company
                                </h2>
                            </div>

                            <div class="space-y-4 p-5">
                                <div>
                                    <p
                                        class="mb-1 text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        Company Name
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-slate-800"
                                    >
                                        {{ company.company_name }}
                                    </p>
                                </div>

                                <div>
                                    <p
                                        class="mb-1 text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        Company Code
                                    </p>
                                    <p
                                        class="font-mono text-sm font-semibold text-slate-700"
                                    >
                                        {{ company.company_code || '—' }}
                                    </p>
                                </div>

                                <div class="flex items-center justify-between">
                                    <p
                                        class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        Status
                                    </p>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                            statusClass(company.status),
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 rounded-full',
                                                statusDot(company.status),
                                            ]"
                                        />
                                        {{ humanize(company.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Toggle status dialog ──────────────────── -->
        <AlertDialog v-if="!isOwnAccount" v-model:open="statusDialog.open">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ toggleStatusLabel(employee.status) }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        This will update the account status of
                        <span class="font-semibold text-slate-800">{{
                            employee.name
                        }}</span
                        >. Are you sure you want to continue?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                        @click="confirmToggleStatus"
                    >
                        Confirm
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- ── Reset password dialog ─────────────────── -->
        <AlertDialog
            v-if="!isOwnAccount"
            v-model:open="resetPasswordDialog.open"
        >
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle
                        >Reset employee password?</AlertDialogTitle
                    >
                    <AlertDialogDescription>
                        The password for
                        <span class="font-semibold text-slate-800">{{
                            employee.name
                        }}</span>
                        will be reset to
                        <code
                            class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-slate-700"
                            >pitx@123</code
                        >. They should change it on next login.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                        @click="confirmResetPassword"
                    >
                        Reset Password
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- ── Archive dialog ────────────────────────── -->
        <AlertDialog v-if="!isOwnAccount" v-model:open="archiveDialog.open">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle
                        >Archive employee account?</AlertDialogTitle
                    >
                    <AlertDialogDescription>
                        <span class="font-semibold text-slate-800">{{
                            employee.name
                        }}</span>
                        will be archived and removed from the active employee
                        list. This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-rose-600 text-white hover:bg-rose-700"
                        @click="confirmArchive"
                    >
                        Archive Account
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </ExternalLayout>
</template>
