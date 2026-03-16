<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'

import ExternalLayout from '@/layouts/ExternalLayout.vue'

import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'

import {
    ArrowLeft,
    Building2,
    KeyRound,
    Loader2,
    Phone,
    ShieldCheck,
    UserPlus,
    Users,
} from 'lucide-vue-next'

type Company = {
    id: number
    company_name: string
    company_code?: string | null
    status: string
    logo_url?: string | null
}

type AuthUser = {
    id: number
    name: string
    username: string
    email: string
}

const props = defineProps<{
    company: Company
    user: AuthUser
    roles: string[]
    statuses: string[]
    defaultStatus: string
    nextUsernamePreview: string
}>()

const form = useForm({
    name: '',
    email: '',
    phone_number: '',
    role: '',
})

function humanize(value?: string | null) {
    if (!value) return '—'
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

function statusClass(status?: string | null) {
    if (status === 'active')    return 'bg-emerald-100 text-emerald-700 border-emerald-200'
    if (status === 'pending')   return 'bg-amber-100 text-amber-700 border-amber-200'
    if (status === 'suspended') return 'bg-rose-100 text-rose-600 border-rose-200'
    if (status === 'inactive')  return 'bg-slate-100 text-slate-500 border-0'
    return 'bg-amber-100 text-amber-700 border-amber-200'
}

function statusDot(status?: string | null) {
    if (status === 'active')    return 'bg-emerald-500'
    if (status === 'pending')   return 'bg-amber-500'
    if (status === 'suspended') return 'bg-rose-500'
    return 'bg-slate-400'
}

function roleClass(role?: string | null) {
    const r = (role ?? '').toLowerCase()
    if (r === 'driver')     return 'bg-sky-100 text-sky-700 border-sky-200'
    if (r === 'dispatcher') return 'bg-violet-100 text-violet-700 border-violet-200'
    return 'bg-slate-100 text-slate-500 border-0'
}

function submit() {
    form.post('/employee-users', {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Create Employee" />

    <ExternalLayout :company="company" :user="user">
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">

                <!-- ── Page header ─────────────────────────────────────── -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                            <Building2 class="h-3.5 w-3.5" />
                            {{ company.company_code ?? company.company_name }}
                            <span class="text-slate-300">·</span>
                            <span>Employees</span>
                            <span class="text-slate-300">·</span>
                            <span>Create</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700">
                                <UserPlus class="h-4 w-4 text-white" />
                            </div>
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Add Employee</h1>
                        </div>
                        <p class="text-sm text-slate-500">
                            Create a new driver or dispatcher account for your company.
                        </p>
                    </div>

                    <Button
                        as-child
                        variant="outline"
                        class="shrink-0 rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-800 self-start"
                    >
                        <Link href="/employee-users">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Employees
                        </Link>
                    </Button>
                </div>

                <!-- ── Stat cards ──────────────────────────────────────── -->
                <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700">
                            <UserPlus class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Account Type</p>
                        <p class="mt-0.5 text-sm font-bold text-slate-900">Employee</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500">
                            <ShieldCheck class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Default Status</p>
                        <Badge :class="['mt-1 gap-1.5', statusClass(defaultStatus)]">
                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(defaultStatus)]" />
                            {{ humanize(defaultStatus) }}
                        </Badge>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-600">
                            <KeyRound class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Default Password</p>
                        <p class="mt-0.5 rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700 inline-block">
                            pitx@123
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600">
                            <Users class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Username Preview</p>
                        <p class="mt-0.5 rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700 inline-block truncate max-w-full">
                            {{ nextUsernamePreview }}
                        </p>
                    </div>

                </div>

                <!-- ── Main form + sidebar ─────────────────────────────── -->
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid items-start gap-6 xl:grid-cols-[1fr_320px]">

                        <!-- Left column: form -->
                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-100 px-6 py-4">
                                <h2 class="text-base font-semibold text-slate-800">Employee Information</h2>
                                <p class="text-xs text-slate-400 mt-0.5">Fill in the basic details below. Username is generated automatically.</p>
                            </div>

                            <div class="space-y-6 p-6">

                                <!-- Full Name -->
                                <div class="space-y-1.5">
                                    <Label for="name" class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                        Full Name
                                    </Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="Enter full name"
                                        class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <!-- Email + Phone -->
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <Label for="email" class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                            Email Address
                                        </Label>
                                        <Input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            placeholder="Enter email address"
                                            class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                                        />
                                        <InputError :message="form.errors.email" />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label for="phone_number" class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                            Phone Number
                                        </Label>
                                        <Input
                                            id="phone_number"
                                            v-model="form.phone_number"
                                            placeholder="Enter phone number"
                                            class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                                        />
                                        <InputError :message="form.errors.phone_number" />
                                    </div>
                                </div>

                                <!-- Role + Status -->
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <Label for="role" class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                            Role
                                        </Label>
                                        <Select v-model="form.role">
                                            <SelectTrigger id="role" class="w-full rounded-lg border-slate-200 focus:ring-blue-500">
                                                <SelectValue placeholder="Select role" />
                                            </SelectTrigger>
                                            <SelectContent class="rounded-xl">
                                                <SelectItem
                                                    v-for="role in roles"
                                                    :key="role"
                                                    :value="role"
                                                    class="rounded-lg"
                                                >
                                                    {{ humanize(role) }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="form.errors.role" />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                            Status
                                        </Label>
                                        <div class="flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3">
                                            <Badge :class="['gap-1.5', statusClass(defaultStatus)]">
                                                <span :class="['h-1.5 w-1.5 rounded-full', statusDot(defaultStatus)]" />
                                                {{ humanize(defaultStatus) }}
                                            </Badge>
                                        </div>
                                        <p class="text-xs text-slate-400">New accounts start with pending status.</p>
                                    </div>
                                </div>

                                <Separator class="bg-slate-100" />

                                <!-- Auto-generated fields -->
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                            Generated Username
                                        </Label>
                                        <div class="flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3">
                                            <span class="font-mono text-sm font-semibold text-slate-500">{{ nextUsernamePreview }}</span>
                                        </div>
                                        <p class="text-xs text-slate-400">Auto-generated when the account is saved.</p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                            Default Password
                                        </Label>
                                        <div class="flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3">
                                            <span class="font-mono text-sm font-semibold text-slate-500">pitx@123</span>
                                        </div>
                                        <p class="text-xs text-slate-400">Employee uses this on first login.</p>
                                    </div>
                                </div>

                                <Separator class="bg-slate-100" />

                                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        as-child
                                        class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                                    >
                                        <Link href="/employee-users">Cancel</Link>
                                    </Button>
                                    <Button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="rounded-lg bg-blue-700 text-white hover:bg-blue-800 border-0 shadow-sm font-semibold disabled:opacity-60"
                                    >
                                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                        <UserPlus v-else class="mr-2 h-4 w-4" />
                                        {{ form.processing ? 'Creating…' : 'Create Employee' }}
                                    </Button>
                                </div>

                            </div>
                        </div>

                        <!-- Right sidebar -->
                        <div class="space-y-4">

                            <!-- Company -->
                            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-100 px-5 py-4">
                                    <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                        <Building2 class="h-4 w-4 text-blue-700" />
                                        Company
                                    </h3>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    <div class="px-5 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-0.5">Company Name</p>
                                        <p class="text-sm font-semibold text-slate-800">{{ company.company_name }}</p>
                                    </div>
                                    <div class="px-5 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400 mb-0.5">Company Code</p>
                                        <p class="font-mono text-sm font-semibold text-slate-700">{{ company.company_code || '—' }}</p>
                                    </div>
                                    <div class="flex items-center justify-between px-5 py-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Status</p>
                                        <Badge :class="['gap-1.5', statusClass(company.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(company.status)]" />
                                            {{ humanize(company.status) }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <!-- Available Roles -->
                            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-100 px-5 py-4">
                                    <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                        <Users class="h-4 w-4 text-blue-700" />
                                        Available Roles
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">Assign one role per employee account.</p>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    <div
                                        v-for="role in roles"
                                        :key="role"
                                        class="flex gap-3 px-5 py-4"
                                    >
                                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md"
                                            :class="role === 'driver' ? 'bg-sky-100' : 'bg-violet-100'"
                                        >
                                            <Users class="h-3.5 w-3.5"
                                                :class="role === 'driver' ? 'text-sky-600' : 'text-violet-600'"
                                            />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 mb-0.5">
                                                <p class="text-xs font-semibold uppercase tracking-widest"
                                                    :class="role === 'driver' ? 'text-sky-700' : 'text-violet-700'"
                                                >
                                                    {{ humanize(role) }}
                                                </p>
                                                <Badge :class="roleClass(role)">
                                                    {{ humanize(role) }}
                                                </Badge>
                                            </div>
                                            <p class="text-xs text-slate-500">
                                                {{ role === 'driver'
                                                    ? 'For vehicle operators and assigned drivers.'
                                                    : 'For dispatch and trip coordination accounts.' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Notes -->
                            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-100 px-5 py-4">
                                    <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                        <KeyRound class="h-4 w-4 text-blue-700" />
                                        Account Notes
                                    </h3>
                                </div>
                                <div class="divide-y divide-slate-100">

                                    <div class="flex gap-3 px-5 py-4">
                                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-amber-100">
                                            <ShieldCheck class="h-3.5 w-3.5 text-amber-600" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-widest text-amber-700">Status</p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                New employee accounts start with pending status.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 px-5 py-4">
                                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-blue-100">
                                            <Phone class="h-3.5 w-3.5 text-blue-700" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-widest text-blue-700">Phone</p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                Phone number is optional but helpful for contact.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between px-5 py-4">
                                        <div class="flex gap-3">
                                            <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-slate-100">
                                                <KeyRound class="h-3.5 w-3.5 text-slate-600" />
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Password</p>
                                                <p class="mt-0.5 text-xs text-slate-500">Default on first login.</p>
                                            </div>
                                        </div>
                                        <code class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700">
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