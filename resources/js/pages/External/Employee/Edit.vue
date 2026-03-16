<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'

import ExternalLayout from '@/layouts/ExternalLayout.vue'

import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
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
    UserCog,
    UserSquare2,
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

type Role = {
    id: number
    name: string
}

type Employee = {
    id: number
    username: string
    name: string
    email?: string | null
    phone_number?: string | null
    status: string
    roles?: Role[]
    company?: Company | null
}

const props = defineProps<{
    company: Company
    user: AuthUser
    employee: Employee
    roles: string[]
    selectedRole?: string | null
}>()

const form = useForm({
    name: props.employee.name ?? '',
    email: props.employee.email ?? '',
    phone_number: props.employee.phone_number ?? '',
    role: props.selectedRole ?? '',
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
    return 'bg-slate-100 text-slate-500 border-0'
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

function companyStatusClass(status?: string | null) {
    if (status === 'active') return 'bg-emerald-100 text-emerald-700 border-emerald-200'
    return 'bg-slate-100 text-slate-500 border-0'
}

function submit() {
    form.put(`/employee-users/${props.employee.id}`, {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Edit Employee" />

    <ExternalLayout :company="company" :user="user">
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">

                <!-- ── Page header ─────────────────────────────────── -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                            <Building2 class="h-3.5 w-3.5" />
                            {{ company.company_code ?? company.company_name }}
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700">
                                <UserCog class="h-4.5 w-4.5 text-white" />
                            </div>
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Employee</h1>
                        </div>
                        <p class="text-sm text-slate-500">
                            Update employee personal details and assigned role.
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

                <!-- ── Main grid ───────────────────────────────────── -->
                <div class="grid gap-6 xl:grid-cols-3">

                    <!-- Form card -->
                    <div class="xl:col-span-2">
                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-100 px-6 py-4">
                                <h2 class="text-base font-semibold text-slate-800">Employee Information</h2>
                                <p class="text-xs text-slate-400 mt-0.5">Edit the employee details below.</p>
                            </div>

                            <form class="p-6 space-y-6" @submit.prevent="submit">
                                <div class="grid gap-5 md:grid-cols-2">

                                    <!-- Username (read-only) -->
                                    <div class="space-y-1.5 md:col-span-2">
                                        <Label class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                            Username
                                        </Label>
                                        <div class="flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3">
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-slate-600">
                                                {{ employee.username }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400">
                                            Username is auto-generated and cannot be changed.
                                        </p>
                                    </div>

                                    <!-- Full Name -->
                                    <div class="space-y-1.5 md:col-span-2">
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

                                    <!-- Email -->
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

                                    <!-- Phone -->
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

                                    <!-- Role -->
                                    <div class="space-y-1.5 md:col-span-2">
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
                                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-4">

                        <!-- Account Summary -->
                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-100 px-5 py-4">
                                <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    <UserSquare2 class="h-4 w-4 text-blue-700" />
                                    Account Summary
                                </h3>
                            </div>
                            <div class="divide-y divide-slate-100">

                                <!-- Avatar + name row -->
                                <div class="flex items-center gap-3 px-5 py-4">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-sm font-bold uppercase">
                                        {{ employee.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">{{ employee.name }}</p>
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-500">
                                            {{ employee.username }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between px-5 py-3">
                                    <p class="text-xs text-slate-400 font-medium uppercase tracking-widest">Status</p>
                                    <span :class="['inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium', statusClass(employee.status)]">
                                        <span :class="['h-1.5 w-1.5 rounded-full', statusDot(employee.status)]" />
                                        {{ humanize(employee.status) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between px-5 py-3">
                                    <p class="text-xs text-slate-400 font-medium uppercase tracking-widest">Role</p>
                                    <span :class="['rounded-full border px-2.5 py-0.5 text-xs font-medium', roleClass(selectedRole)]">
                                        {{ humanize(selectedRole) }}
                                    </span>
                                </div>

                            </div>
                        </div>

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
                                    <p class="text-xs text-slate-400 font-medium uppercase tracking-widest mb-0.5">Company Name</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ company.company_name }}</p>
                                </div>

                                <div class="px-5 py-3">
                                    <p class="text-xs text-slate-400 font-medium uppercase tracking-widest mb-0.5">Company Code</p>
                                    <p class="font-mono text-sm font-semibold text-slate-700">{{ company.company_code || '—' }}</p>
                                </div>

                                <div class="flex items-center justify-between px-5 py-3">
                                    <p class="text-xs text-slate-400 font-medium uppercase tracking-widest">Status</p>
                                    <span :class="['rounded-full border px-2.5 py-0.5 text-xs font-medium', companyStatusClass(company.status)]">
                                        {{ humanize(company.status) }}
                                    </span>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </ExternalLayout>
</template>