<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';


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


const props = defineProps<{
    company: Company;
    user: AuthUser;
    employee: Employee;
}>();


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
    if (status === 'active') return 'Set as Inactive';
    if (status === 'inactive') return 'Set as Active';
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
const canUpdateEmployee = can('external_users.update');
const canToggleEmployee = can('external_users.toggleStatus');
const canResetEmployee = can('external_users.resetPassword');
const canArchiveEmployee = can('external_users.archive');
const hasManageActions = computed(
    () =>
        canUpdateEmployee ||
        canToggleEmployee ||
        canResetEmployee ||
        canArchiveEmployee,
);


const statusDialog = reactive({ open: false });
const resetPasswordDialog = reactive({ open: false });
const archiveDialog = reactive({ open: false });


function confirmToggleStatus() {
    if (!canToggleEmployee) return;

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
    if (!canResetEmployee) return;

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
    if (!canArchiveEmployee) return;

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
                
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="space-y-1">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold tracking-widest text-slate-400 uppercase"
                        >
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

                    
                    <div class="flex shrink-0 items-center gap-2 self-start">
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-800"
                        >
                            <Link href="/employee-users">
                                Back
                            </Link>
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    v-if="!isOwnAccount && hasManageActions"
                                    class="gap-2 rounded-lg border-0 bg-blue-700 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
                                >
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

                                
                                <DropdownMenuItem
                                    v-if="!isOwnAccount && canUpdateEmployee"
                                    as-child
                                    class="rounded-lg text-slate-700 focus:bg-amber-50 focus:text-amber-700"
                                >
                                    <Link
                                        :href="`/employee-users/${employee.id}/edit`"
                                    >
                                        Edit Details
                                    </Link>
                                </DropdownMenuItem>

                                
                                <DropdownMenuItem
                                    v-if="!isOwnAccount && canToggleEmployee"
                                    :class="[
                                        'rounded-lg',
                                        toggleStatusClass(employee.status),
                                    ]"
                                    @click="statusDialog.open = true"
                                >
                                    {{ toggleStatusLabel(employee.status) }}
                                </DropdownMenuItem>

                                
                                <DropdownMenuItem
                                    v-if="!isOwnAccount && canResetEmployee"
                                    class="rounded-lg text-slate-700 focus:bg-blue-50 focus:text-blue-700"
                                    @click="resetPasswordDialog.open = true"
                                >
                                    Reset Password
                                </DropdownMenuItem>

                                <DropdownMenuSeparator
                                    v-if="
                                        !isOwnAccount &&
                                        (canArchiveEmployee ||
                                            canUpdateEmployee ||
                                            canToggleEmployee ||
                                            canResetEmployee)
                                    "
                                    class="bg-slate-100"
                                />

                                
                                <DropdownMenuItem
                                    v-if="!isOwnAccount && canArchiveEmployee"
                                    class="rounded-lg text-rose-600 focus:bg-rose-50 focus:text-rose-600"
                                    @click="archiveDialog.open = true"
                                >
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

                
                <div class="grid gap-6 xl:grid-cols-3">
                    
                    <div class="space-y-6 xl:col-span-2">
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            
                            <div
                                class="flex items-center gap-2 border-b border-slate-100 px-5 py-4"
                            >
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

                            
                            <div class="grid gap-4 px-5 pb-5 md:grid-cols-2">
                                <div
                                    class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"
                                >
                                    <div
                                        class="mb-2 flex items-center gap-2 text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
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

                    
                    <div class="space-y-6">
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div
                                class="flex items-center gap-2 border-b border-slate-100 px-5 py-4"
                            >
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

        
        <Dialog
            v-if="!isOwnAccount && canToggleEmployee"
            v-model:open="statusDialog.open"
        >
            <DialogContent class="max-w-md px-6" :show-close-button="false">
                <DialogHeader class="px-0">
                    <DialogTitle>
                        {{ toggleStatusLabel(employee.status) }}
                    </DialogTitle>
                    <DialogDescription>
                        This will update the account status of
                        <span class="font-semibold text-custom-accent-3">{{
                            employee.name
                        }}</span>. Are you sure you want to continue?
                    </DialogDescription>
                </DialogHeader>
                <Separator />
                <DialogFooter class="pt-3 gap-2 sm:justify-end">
                    <Button variant="ghost-outline" @click="statusDialog.open = false">
                        Cancel
                    </Button>
                    <Button variant="float-primary" @click="confirmToggleStatus">
                        Confirm
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        
        <Dialog
            v-if="!isOwnAccount && canResetEmployee"
            v-model:open="resetPasswordDialog.open"
        >
            <DialogContent class="max-w-md px-6" :show-close-button="false">
                <DialogHeader class="px-0">
                    <DialogTitle>Reset employee password?</DialogTitle>
                    <DialogDescription>
                        The password for
                        <span class="font-semibold text-custom-accent-3">{{
                            employee.name
                        }}</span>
                        will be reset to
                        <code
                            class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-slate-700"
                            >pitx@123</code
                        >. They should change it on next login.
                    </DialogDescription>
                </DialogHeader>
                <Separator />
                <DialogFooter class="pt-3 gap-2 sm:justify-end">
                    <Button variant="ghost-outline" @click="resetPasswordDialog.open = false">
                        Cancel
                    </Button>
                    <Button variant="float-primary" @click="confirmResetPassword">
                        Reset Password
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        
        <Dialog
            v-if="!isOwnAccount && canArchiveEmployee"
            v-model:open="archiveDialog.open"
        >
            <DialogContent class="max-w-md px-6" :show-close-button="false">
                <DialogHeader class="px-0">
                    <DialogTitle>Archive employee account?</DialogTitle>
                    <DialogDescription>
                        <span class="font-semibold text-custom-accent-3">{{
                            employee.name
                        }}</span>
                        will be archived and removed from the active employee
                        list. This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <Separator />
                <DialogFooter class="pt-3 gap-2 sm:justify-end">
                    <Button variant="ghost-outline" @click="archiveDialog.open = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="confirmArchive">
                        Archive Account
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </ExternalLayout>
</template>
