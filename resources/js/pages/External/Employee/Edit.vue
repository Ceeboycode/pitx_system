<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';

import {
    ArrowLeft,
    Building2,
    Phone,
    UserCog,
    UserSquare2,
    Users,
} from 'lucide-vue-next';

// ── Types ─────────────────────────────────────────────────────────────────────

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

// FIX: RoleItem matches the custom Role model (name, guard_name, type)
// Previously typed as string[] — caused v-for iteration bugs.
type RoleItem = {
    name: string;
    guard_name: string;
    type: string;
};

type EmployeeRole = {
    id: number;
    name: string;
};

type Employee = {
    id: number;
    username: string;
    name: string;
    email?: string | null;
    phone_number?: string | null;
    avatar?: string | null;
    status: string;
    roles?: EmployeeRole[];
    company?: Company | null;
};

// ── Props ─────────────────────────────────────────────────────────────────────

const props = defineProps<{
    company: Company;
    user: AuthUser;
    employee: Employee;
    roles: RoleItem[];
    // FIX: type as string | null | undefined — controller sends first role name or null
    selectedRole?: string | null;
}>();

const canUpdateEmployee = can('external_users.update');

// ── Form ──────────────────────────────────────────────────────────────────────

const form = useForm({
    name: props.employee.name ?? '',
    email: props.employee.email ?? '',
    phone_number: props.employee.phone_number ?? '',
    // FIX: fallback to empty string — selectedRole can be undefined/null
    role: props.selectedRole ?? '',
});

// FIX: sidebar role should reflect the *live* form.role value,
// not the static selectedRole prop (which doesn't update as user picks a new role).
const currentRoleName = computed(() => form.role || props.selectedRole || null);

// ── Helpers ───────────────────────────────────────────────────────────────────

function humanize(value?: string | null) {
    if (!value) return '—';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
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

// FIX: dynamic role colors — no longer hardcoded to driver/dispatcher only.
const roleColorMap: Record<
    string,
    { icon: string; text: string; badge: string }
> = {
    driver: {
        icon: 'bg-sky-100',
        text: 'text-sky-700',
        badge: 'bg-sky-100 text-sky-700 border-sky-200',
    },
    dispatcher: {
        icon: 'bg-violet-100',
        text: 'text-violet-700',
        badge: 'bg-violet-100 text-violet-700 border-violet-200',
    },
    conductor: {
        icon: 'bg-teal-100',
        text: 'text-teal-700',
        badge: 'bg-teal-100 text-teal-700 border-teal-200',
    },
    inspector: {
        icon: 'bg-orange-100',
        text: 'text-orange-700',
        badge: 'bg-orange-100 text-orange-700 border-orange-200',
    },
};
const fallbackColor = {
    icon: 'bg-slate-100',
    text: 'text-slate-600',
    badge: 'bg-slate-100 text-slate-600 border-0',
};

function roleColor(roleName?: string | null) {
    return roleColorMap[roleName ?? ''] ?? fallbackColor;
}

// Employee initials for the avatar
const initials = computed(() =>
    props.employee.name
        .split(' ')
        .slice(0, 2)
        .map((n) => n.charAt(0).toUpperCase())
        .join(''),
);

// ── Submit ────────────────────────────────────────────────────────────────────

function submit() {
    if (!canUpdateEmployee) return;

    form.put(`/employee-users/${props.employee.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Edit Employee" />

    <ExternalLayout :company="company" :user="user">
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
                <!-- ── Page header ── -->
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
                            <span>Edit</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700"
                            >
                                <UserCog class="h-4 w-4 text-white" />
                            </div>
                            <h1
                                class="text-2xl font-bold tracking-tight text-slate-900"
                            >
                                Edit Employee
                            </h1>
                        </div>
                        <p class="text-sm text-slate-500">
                            Update employee details and assigned role.
                        </p>
                    </div>

                    <Button
                        as-child
                        variant="outline"
                        class="shrink-0 self-start rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-800"
                    >
                        <Link href="/employee-users">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Employees
                        </Link>
                    </Button>
                </div>

                <!-- ── Main grid ── -->
                <div class="grid gap-6 xl:grid-cols-3">
                    <!-- Form card -->
                    <div class="xl:col-span-2">
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-100 px-6 py-4">
                                <h2
                                    class="text-base font-semibold text-slate-800"
                                >
                                    Employee Information
                                </h2>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Edit the employee details below.
                                </p>
                            </div>

                            <form
                                class="space-y-6 p-6"
                                @submit.prevent="submit"
                            >
                                <div class="grid gap-5 md:grid-cols-2">
                                    <!-- Username (read-only) -->
                                    <div class="space-y-1.5 md:col-span-2">
                                        <Label
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Username
                                        </Label>
                                        <div
                                            class="flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3"
                                        >
                                            <code
                                                class="rounded bg-slate-200 px-1.5 py-0.5 font-mono text-xs font-semibold text-slate-600"
                                            >
                                                {{ employee.username }}
                                            </code>
                                        </div>
                                        <p class="text-xs text-slate-400">
                                            Auto-generated — cannot be changed.
                                        </p>
                                    </div>

                                    <!-- Full Name -->
                                    <div class="space-y-1.5 md:col-span-2">
                                        <Label
                                            for="name"
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Full Name
                                            <span class="text-rose-500">*</span>
                                        </Label>
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            placeholder="Enter full name"
                                            autocomplete="off"
                                            class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                                        />
                                        <InputError
                                            :message="form.errors.name"
                                        />
                                    </div>

                                    <!-- Email -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="email"
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Email Address
                                        </Label>
                                        <Input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            placeholder="Enter email address"
                                            autocomplete="off"
                                            class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                                        />
                                        <InputError
                                            :message="form.errors.email"
                                        />
                                    </div>

                                    <!-- Phone -->
                                    <div class="space-y-1.5">
                                        <Label
                                            for="phone_number"
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Phone Number
                                        </Label>
                                        <div class="relative">
                                            <Phone
                                                class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                                            />
                                            <Input
                                                id="phone_number"
                                                v-model="form.phone_number"
                                                placeholder="Enter phone number"
                                                class="rounded-lg border-slate-200 pl-9 focus-visible:ring-blue-500"
                                            />
                                        </div>
                                        <InputError
                                            :message="form.errors.phone_number"
                                        />
                                    </div>

                                    <!-- Role — FIX: iterates role.name, not role as string -->
                                    <div class="space-y-1.5 md:col-span-2">
                                        <Label
                                            for="role"
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Role
                                            <span class="text-rose-500">*</span>
                                        </Label>
                                        <Select v-model="form.role">
                                            <SelectTrigger
                                                id="role"
                                                class="w-full rounded-lg border-slate-200 focus:ring-blue-500"
                                            >
                                                <SelectValue
                                                    placeholder="Select a role"
                                                />
                                            </SelectTrigger>
                                            <SelectContent class="rounded-xl">
                                                <SelectItem
                                                    v-for="role in roles"
                                                    :key="role.name"
                                                    :value="role.name"
                                                    class="rounded-lg"
                                                >
                                                    <span
                                                        class="flex items-center gap-2"
                                                    >
                                                        <span
                                                            class="inline-block h-2 w-2 rounded-full"
                                                            :class="
                                                                roleColor(
                                                                    role.name,
                                                                ).icon.replace(
                                                                    '-100',
                                                                    '-400',
                                                                )
                                                            "
                                                        />
                                                        {{
                                                            humanize(role.name)
                                                        }}
                                                    </span>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-xs text-slate-400">
                                            {{ roles.length }} external role{{
                                                roles.length === 1 ? '' : 's'
                                            }}
                                            available.
                                        </p>
                                        <InputError
                                            :message="form.errors.role"
                                        />
                                    </div>
                                </div>

                                <Separator class="bg-slate-100" />

                                <div
                                    class="flex flex-col gap-3 sm:flex-row sm:justify-end"
                                >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        as-child
                                        class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                                    >
                                        <Link href="/employee-users"
                                            >Cancel</Link
                                        >
                                    </Button>
                                    <Button
                                        v-if="canUpdateEmployee"
                                        type="submit"
                                        :disabled="form.processing"
                                        class="rounded-lg border-0 bg-blue-700 font-semibold text-white shadow-sm hover:bg-blue-800 disabled:opacity-60"
                                    >
                                        {{
                                            form.processing
                                                ? 'Saving…'
                                                : 'Save Changes'
                                        }}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-4">
                        <!-- Account Summary -->
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-100 px-5 py-4">
                                <h3
                                    class="flex items-center gap-2 text-sm font-semibold text-slate-800"
                                >
                                    <UserSquare2
                                        class="h-4 w-4 text-blue-700"
                                    />
                                    Account Summary
                                </h3>
                            </div>
                            <div class="divide-y divide-slate-100">
                                <!-- Avatar + name -->
                                <div class="flex items-center gap-3 px-5 py-4">
                                    <img
                                        v-if="employee.avatar"
                                        :src="employee.avatar"
                                        :alt="`${employee.name} avatar`"
                                        class="h-10 w-10 shrink-0 rounded-full border border-slate-200 object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700 uppercase"
                                    >
                                        {{ initials }}
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-semibold text-slate-800"
                                        >
                                            {{ employee.name }}
                                        </p>
                                        <code
                                            class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-500"
                                        >
                                            {{ employee.username }}
                                        </code>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center justify-between px-5 py-3"
                                >
                                    <p
                                        class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        Status
                                    </p>
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

                                <!-- FIX: uses computed currentRoleName so it updates live as user changes the select -->
                                <div
                                    class="flex items-center justify-between px-5 py-3"
                                >
                                    <p
                                        class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        Role
                                    </p>
                                    <span
                                        v-if="currentRoleName"
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                            roleColor(currentRoleName).badge,
                                        ]"
                                    >
                                        <Users class="h-3 w-3" />
                                        {{ humanize(currentRoleName) }}
                                    </span>
                                    <span
                                        v-else
                                        class="text-xs text-slate-400 italic"
                                        >None selected</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Company -->
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-100 px-5 py-4">
                                <h3
                                    class="flex items-center gap-2 text-sm font-semibold text-slate-800"
                                >
                                    <Building2 class="h-4 w-4 text-blue-700" />
                                    Company
                                </h3>
                            </div>
                            <div class="divide-y divide-slate-100">
                                <div class="px-5 py-3">
                                    <p
                                        class="mb-0.5 text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        Company Name
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-slate-800"
                                    >
                                        {{ company.company_name }}
                                    </p>
                                </div>
                                <div class="px-5 py-3">
                                    <p
                                        class="mb-0.5 text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        Company Code
                                    </p>
                                    <p
                                        class="font-mono text-sm font-semibold text-slate-700"
                                    >
                                        {{ company.company_code || '—' }}
                                    </p>
                                </div>
                                <div
                                    class="flex items-center justify-between px-5 py-3"
                                >
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

                        <!-- Available Roles reference -->
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-100 px-5 py-4">
                                <h3
                                    class="flex items-center gap-2 text-sm font-semibold text-slate-800"
                                >
                                    <Users class="h-4 w-4 text-blue-700" />
                                    Available Roles
                                </h3>
                            </div>
                            <div
                                v-if="roles.length > 0"
                                class="divide-y divide-slate-100"
                            >
                                <div
                                    v-for="role in roles"
                                    :key="role.name"
                                    class="flex cursor-pointer items-center gap-3 px-5 py-3 transition-colors hover:bg-slate-50"
                                    :class="
                                        form.role === role.name
                                            ? 'bg-blue-50/60'
                                            : ''
                                    "
                                    @click="form.role = role.name"
                                >
                                    <div
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md"
                                        :class="roleColor(role.name).icon"
                                    >
                                        <Users
                                            class="h-3.5 w-3.5"
                                            :class="roleColor(role.name).text"
                                        />
                                    </div>
                                    <p
                                        class="flex-1 text-xs font-semibold"
                                        :class="roleColor(role.name).text"
                                    >
                                        {{ humanize(role.name) }}
                                    </p>
                                    <span
                                        v-if="form.role === role.name"
                                        class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white"
                                        >✓</span
                                    >
                                </div>
                            </div>
                            <div
                                v-else
                                class="px-5 py-4 text-center text-xs text-slate-400"
                            >
                                No external roles available.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ExternalLayout>
</template>
