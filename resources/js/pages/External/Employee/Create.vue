<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import ExternalLayout from '@/layouts/ExternalLayout.vue';

import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
    KeyRound,
    Loader2,
    Phone,
    ShieldCheck,
    UserPlus,
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

// Matches the custom Role model columns: name, guard_name, type
type RoleItem = {
    name: string;
    guard_name: string;
    type: string;
};

// ── Props ─────────────────────────────────────────────────────────────────────

const props = defineProps<{
    company: Company;
    user: AuthUser;
    roles: RoleItem[];
    // FIX: defaultStatus is now 'active' from the controller
    defaultStatus: string;
    nextUsernamePreview: string;
}>();

// ── Form ──────────────────────────────────────────────────────────────────────

const form = useForm({
    name: '',
    email: '',
    phone_number: '',
    role: '',
});

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

// Consistent accent color per role name — extend as you add external roles
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

function roleColor(roleName: string) {
    return roleColorMap[roleName] ?? fallbackColor;
}

// ── Submit ────────────────────────────────────────────────────────────────────

function submit() {
    form.post('/employee-users', { preserveScroll: true });
}
</script>

<template>
    <Head title="Create Employee" />

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
                            <span>Create</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700"
                            >
                                <UserPlus class="h-4 w-4 text-white" />
                            </div>
                            <h1
                                class="text-2xl font-bold tracking-tight text-slate-900"
                            >
                                Add Employee
                            </h1>
                        </div>
                        <p class="text-sm text-slate-500">
                            Create a new employee account. Select from available
                            external roles.
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

                <!-- ── Stat cards ── -->
                <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700"
                        >
                            <UserPlus class="h-4 w-4 text-white" />
                        </div>
                        <p
                            class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            Account Type
                        </p>
                        <p class="mt-0.5 text-sm font-bold text-slate-900">
                            Employee
                        </p>
                    </div>

                    <!-- FIX: Shows 'Active' since defaultStatus is now 'active' -->
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500"
                        >
                            <ShieldCheck class="h-4 w-4 text-white" />
                        </div>
                        <p
                            class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            Default Status
                        </p>
                        <Badge
                            :class="[
                                'mt-1 gap-1.5 border',
                                statusClass(defaultStatus),
                            ]"
                        >
                            <span
                                :class="[
                                    'h-1.5 w-1.5 rounded-full',
                                    statusDot(defaultStatus),
                                ]"
                            />
                            {{ humanize(defaultStatus) }}
                        </Badge>
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-600"
                        >
                            <KeyRound class="h-4 w-4 text-white" />
                        </div>
                        <p
                            class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            Default Password
                        </p>
                        <p
                            class="mt-0.5 inline-block rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700"
                        >
                            pitx@123
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600"
                        >
                            <Users class="h-4 w-4 text-white" />
                        </div>
                        <p
                            class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            Username Preview
                        </p>
                        <p
                            class="mt-0.5 inline-block max-w-full truncate rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700"
                        >
                            {{ nextUsernamePreview }}
                        </p>
                    </div>
                </div>

                <!-- ── Main form + sidebar ── -->
                <form class="space-y-6" @submit.prevent="submit">
                    <div
                        class="grid items-start gap-6 xl:grid-cols-[1fr_320px]"
                    >
                        <!-- Left: form card -->
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
                                    Fill in the details. Username is
                                    auto-generated on save.
                                </p>
                            </div>

                            <div class="space-y-6 p-6">
                                <!-- Full Name -->
                                <div class="space-y-1.5">
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
                                    <InputError :message="form.errors.name" />
                                </div>

                                <!-- Email + Phone -->
                                <div class="grid gap-5 sm:grid-cols-2">
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
                                </div>

                                <!-- Role — dynamic from Spatie, filtered by type = 'external' -->
                                <div class="space-y-1.5">
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
                                            <!-- FIX: iterate role.name (RoleItem objects, not plain strings) -->
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
                                                    {{ humanize(role.name) }}
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
                                    <InputError :message="form.errors.role" />
                                </div>

                                <!-- Read-only info: Status + Password -->
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <Label
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Default Status
                                        </Label>
                                        <div
                                            class="flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3"
                                        >
                                            <!-- FIX: shows Active with green styling -->
                                            <Badge
                                                :class="[
                                                    'gap-1.5 border',
                                                    statusClass(defaultStatus),
                                                ]"
                                            >
                                                <span
                                                    :class="[
                                                        'h-1.5 w-1.5 rounded-full',
                                                        statusDot(
                                                            defaultStatus,
                                                        ),
                                                    ]"
                                                />
                                                {{ humanize(defaultStatus) }}
                                            </Badge>
                                        </div>
                                        <p class="text-xs text-slate-400">
                                            New accounts are created as active.
                                        </p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Default Password
                                        </Label>
                                        <div
                                            class="flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3"
                                        >
                                            <span
                                                class="font-mono text-sm font-semibold text-slate-500"
                                                >pitx@123</span
                                            >
                                        </div>
                                        <p class="text-xs text-slate-400">
                                            Employee uses this on first login.
                                        </p>
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
                                        type="submit"
                                        :disabled="form.processing"
                                        class="rounded-lg border-0 bg-blue-700 font-semibold text-white shadow-sm hover:bg-blue-800 disabled:opacity-60"
                                    >
                                        <Loader2
                                            v-if="form.processing"
                                            class="mr-2 h-4 w-4 animate-spin"
                                        />
                                        <UserPlus v-else class="mr-2 h-4 w-4" />
                                        {{
                                            form.processing
                                                ? 'Creating…'
                                                : 'Create Employee'
                                        }}
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Right sidebar -->
                        <div class="space-y-4">
                            <!-- Company card -->
                            <div
                                class="rounded-xl border border-slate-200 bg-white shadow-sm"
                            >
                                <div
                                    class="border-b border-slate-100 px-5 py-4"
                                >
                                    <h3
                                        class="flex items-center gap-2 text-sm font-semibold text-slate-800"
                                    >
                                        <Building2
                                            class="h-4 w-4 text-blue-700"
                                        />
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
                                        <Badge
                                            :class="[
                                                'gap-1.5 border',
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
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <!-- Available Roles -->
                            <div
                                class="rounded-xl border border-slate-200 bg-white shadow-sm"
                            >
                                <div
                                    class="border-b border-slate-100 px-5 py-4"
                                >
                                    <h3
                                        class="flex items-center gap-2 text-sm font-semibold text-slate-800"
                                    >
                                        <Users class="h-4 w-4 text-blue-700" />
                                        Available Roles
                                    </h3>
                                    <p class="mt-0.5 text-xs text-slate-400">
                                        {{ roles.length }} external role{{
                                            roles.length === 1 ? '' : 's'
                                        }}. Assign one per employee.
                                    </p>
                                </div>

                                <div
                                    v-if="roles.length > 0"
                                    class="divide-y divide-slate-100"
                                >
                                    <div
                                        v-for="role in roles"
                                        :key="role.name"
                                        class="flex cursor-pointer gap-3 px-5 py-4 transition-colors hover:bg-slate-50"
                                        :class="
                                            form.role === role.name
                                                ? 'bg-blue-50/70'
                                                : ''
                                        "
                                        @click="form.role = role.name"
                                    >
                                        <div
                                            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md"
                                            :class="roleColor(role.name).icon"
                                        >
                                            <Users
                                                class="h-3.5 w-3.5"
                                                :class="
                                                    roleColor(role.name).text
                                                "
                                            />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="mb-0.5 flex items-center gap-2"
                                            >
                                                <p
                                                    class="text-xs font-semibold tracking-widest uppercase"
                                                    :class="
                                                        roleColor(role.name)
                                                            .text
                                                    "
                                                >
                                                    {{ humanize(role.name) }}
                                                </p>
                                                <span
                                                    v-if="
                                                        form.role === role.name
                                                    "
                                                    class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white"
                                                    >✓</span
                                                >
                                            </div>
                                            <p class="text-xs text-slate-400">
                                                type:
                                                <code
                                                    class="rounded bg-slate-100 px-1 font-mono text-[10px]"
                                                    >{{ role.type }}</code
                                                >
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="px-5 py-6 text-center">
                                    <p class="text-xs text-slate-400">
                                        No external roles found.
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Ensure roles have
                                        <code
                                            class="rounded bg-slate-100 px-1 font-mono text-[10px]"
                                            >type = 'external'</code
                                        >.
                                    </p>
                                </div>
                            </div>

                            <!-- Account Notes -->
                            <div
                                class="rounded-xl border border-slate-200 bg-white shadow-sm"
                            >
                                <div
                                    class="border-b border-slate-100 px-5 py-4"
                                >
                                    <h3
                                        class="flex items-center gap-2 text-sm font-semibold text-slate-800"
                                    >
                                        <KeyRound
                                            class="h-4 w-4 text-blue-700"
                                        />
                                        Account Notes
                                    </h3>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    <div class="flex gap-3 px-5 py-4">
                                        <div
                                            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-emerald-100"
                                        >
                                            <ShieldCheck
                                                class="h-3.5 w-3.5 text-emerald-600"
                                            />
                                        </div>
                                        <div>
                                            <p
                                                class="text-xs font-semibold tracking-widest text-emerald-700 uppercase"
                                            >
                                                Status
                                            </p>
                                            <p
                                                class="mt-0.5 text-xs text-slate-500"
                                            >
                                                New accounts are created as
                                                <strong>active</strong>
                                                immediately.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 px-5 py-4">
                                        <div
                                            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-blue-100"
                                        >
                                            <Phone
                                                class="h-3.5 w-3.5 text-blue-700"
                                            />
                                        </div>
                                        <div>
                                            <p
                                                class="text-xs font-semibold tracking-widest text-blue-700 uppercase"
                                            >
                                                Phone
                                            </p>
                                            <p
                                                class="mt-0.5 text-xs text-slate-500"
                                            >
                                                Optional but helpful for
                                                contact.
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-center justify-between px-5 py-4"
                                    >
                                        <div class="flex gap-3">
                                            <div
                                                class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-slate-100"
                                            >
                                                <KeyRound
                                                    class="h-3.5 w-3.5 text-slate-600"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-xs font-semibold tracking-widest text-slate-500 uppercase"
                                                >
                                                    Password
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-slate-500"
                                                >
                                                    Changed on first login.
                                                </p>
                                            </div>
                                        </div>
                                        <code
                                            class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700"
                                        >
                                            pitx@123
                                        </code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </ExternalLayout>
</template>
