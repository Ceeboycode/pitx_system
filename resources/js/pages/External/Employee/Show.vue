<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'

import ExternalLayout from '@/layouts/ExternalLayout.vue'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'

import {
    Archive,
    ArrowLeft,
    Building2,
    CalendarDays,
    Edit,
    KeyRound,
    Mail,
    Phone,
    Power,
    Shield,
    UserCog,
    UserSquare2,
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

type RoleItem = {
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
    created_at?: string | null
    roles?: RoleItem[]
    company?: Company | null
}

const props = defineProps<{
    company: Company
    user: AuthUser
    employee: Employee
}>()

function humanize(value?: string | null) {
    if (!value) return '-'
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

function formatDate(value?: string | null) {
    if (!value) return '-'
    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

function statusClass(status?: string | null) {
    if (status === 'active') return 'bg-emerald-100 text-emerald-700 border-emerald-200 font-medium'
    if (status === 'pending') return 'bg-amber-100 text-amber-700 border-amber-200 font-medium'
    if (status === 'suspended') return 'bg-rose-100 text-rose-600 border-rose-200 font-medium'
    if (status === 'inactive') return 'bg-slate-100 text-slate-500 border-0 font-medium'
    return 'bg-slate-100 text-slate-500 border-0 font-medium'
}

function statusDot(status?: string | null) {
    if (status === 'active') return 'bg-emerald-500'
    if (status === 'pending') return 'bg-amber-500'
    if (status === 'suspended') return 'bg-rose-500'
    return 'bg-slate-400'
}

function roleName() {
    return props.employee.roles?.[0]?.name ?? '-'
}

function roleClass(role?: string | null) {
    const value = String(role ?? '').toLowerCase()
    if (value === 'driver') return 'bg-sky-100 text-sky-700 border-sky-200 font-medium'
    if (value === 'dispatcher') return 'bg-violet-100 text-violet-700 border-violet-200 font-medium'
    if (value === 'conductor') return 'bg-teal-100 text-teal-700 border-teal-200 font-medium'
    if (value === 'inspector') return 'bg-orange-100 text-orange-700 border-orange-200 font-medium'
    return 'bg-slate-100 text-slate-500 border-0 font-medium'
}

const initials = computed(() =>
    props.employee.name
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join(''),
)

function toggleStatusLabel(status?: string | null) {
    if (status === 'active') return 'Set Inactive'
    if (status === 'inactive') return 'Set Active'
    if (status === 'pending') return 'Activate Account'
    if (status === 'suspended') return 'Set Active'
    return 'Update Status'
}

const statusDialog = reactive({ open: false })
const resetPasswordDialog = reactive({ open: false })
const archiveDialog = reactive({ open: false })

function confirmToggleStatus() {
    router.patch(`/employee-users/${props.employee.id}/toggle-status`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            statusDialog.open = false
        },
    })
}

function confirmResetPassword() {
    router.patch(`/employee-users/${props.employee.id}/reset-password`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            resetPasswordDialog.open = false
        },
    })
}

function confirmArchive() {
    router.delete(`/employee-users/${props.employee.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            archiveDialog.open = false
        },
    })
}
</script>

<template>
    <Head title="Employee Profile" />

    <ExternalLayout :company="company" :user="user">
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                            <Building2 class="h-3.5 w-3.5" />
                            {{ company.company_code ?? company.company_name }}
                            <span class="text-slate-300">.</span>
                            <span>Employees</span>
                            <span class="text-slate-300">.</span>
                            <span>Profile</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-700">
                                <UserCog class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                                    Employee Profile
                                </h1>
                                <p class="text-sm text-slate-500">
                                    View employee details and manage account actions.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-800"
                        >
                            <Link href="/employee-users">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back to Employees
                            </Link>
                        </Button>

                        <Button
                            as-child
                            class="rounded-lg border-0 bg-blue-700 text-white shadow-sm hover:bg-blue-800"
                        >
                            <Link :href="`/employee-users/${employee.id}/edit`">
                                <Edit class="mr-2 h-4 w-4" />
                                Edit Employee
                            </Link>
                        </Button>
                    </div>
                </div>

                <div class="grid gap-6 xl:grid-cols-3">
                    <div class="space-y-6 xl:col-span-2">
                        <Card class="rounded-xl border border-slate-200 shadow-sm">
                            <CardHeader class="border-b border-slate-100 pb-4">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <CardTitle class="flex items-center gap-2 text-base text-slate-800">
                                            <UserSquare2 class="h-4 w-4 text-blue-700" />
                                            Employee Information
                                        </CardTitle>
                                        <CardDescription class="mt-1">
                                            Basic employee account details and actions.
                                        </CardDescription>
                                    </div>

                                    <div class="flex flex-wrap gap-2 lg:justify-end">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="rounded-lg border-slate-200 text-slate-700 hover:bg-slate-50"
                                            @click="statusDialog.open = true"
                                        >
                                            <Power class="mr-2 h-4 w-4" />
                                            {{ toggleStatusLabel(employee.status) }}
                                        </Button>

                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="rounded-lg border-slate-200 text-slate-700 hover:bg-slate-50"
                                            @click="resetPasswordDialog.open = true"
                                        >
                                            <KeyRound class="mr-2 h-4 w-4" />
                                            Reset Password
                                        </Button>

                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="rounded-lg border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                            @click="archiveDialog.open = true"
                                        >
                                            <Archive class="mr-2 h-4 w-4" />
                                            Archive Account
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>

                            <CardContent class="space-y-5 p-6">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-blue-100 text-lg font-bold uppercase text-blue-700">
                                            {{ initials }}
                                        </div>

                                        <div class="min-w-0">
                                            <p class="text-lg font-semibold text-slate-900">
                                                {{ employee.name }}
                                            </p>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <Badge class="rounded-full border px-2.5 py-0.5 text-xs" :class="statusClass(employee.status)">
                                                    <span :class="['mr-1.5 inline-block h-1.5 w-1.5 rounded-full', statusDot(employee.status)]" />
                                                    {{ humanize(employee.status) }}
                                                </Badge>

                                                <Badge class="rounded-full border px-2.5 py-0.5 text-xs" :class="roleClass(roleName())">
                                                    {{ humanize(roleName()) }}
                                                </Badge>

                                                <Badge class="rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-xs text-slate-600">
                                                    {{ employee.username }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <Separator class="bg-slate-100" />

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                        <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                            <Users class="h-4 w-4" />
                                            Full Name
                                        </div>
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ employee.name }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                        <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                            <Shield class="h-4 w-4" />
                                            Username
                                        </div>
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ employee.username }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                        <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                            <Mail class="h-4 w-4" />
                                            Email Address
                                        </div>
                                        <p class="break-all text-sm font-semibold text-slate-800">
                                            {{ employee.email || '-' }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                        <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                            <Phone class="h-4 w-4" />
                                            Phone Number
                                        </div>
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ employee.phone_number || '-' }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                        <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                            <UserCog class="h-4 w-4" />
                                            Assigned Role
                                        </div>
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ humanize(roleName()) }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                        <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                            <CalendarDays class="h-4 w-4" />
                                            Created At
                                        </div>
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ formatDate(employee.created_at) }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div class="space-y-6">
                        <Card class="rounded-xl border border-slate-200 shadow-sm">
                            <CardHeader class="border-b border-slate-100 pb-4">
                                <CardTitle class="flex items-center gap-2 text-sm text-slate-800">
                                    <Building2 class="h-4 w-4 text-blue-700" />
                                    Company
                                </CardTitle>
                            </CardHeader>

                            <CardContent class="space-y-4 p-5">
                                <div>
                                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                        Company Name
                                    </p>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ company.company_name }}
                                    </p>
                                </div>

                                <div>
                                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                        Company Code
                                    </p>
                                    <p class="font-mono text-sm font-semibold text-slate-700">
                                        {{ company.company_code || '-' }}
                                    </p>
                                </div>

                                <div class="flex items-center justify-between">
                                    <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                        Status
                                    </p>
                                    <span :class="['inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium', statusClass(company.status)]">
                                        <span :class="['h-1.5 w-1.5 rounded-full', statusDot(company.status)]" />
                                        {{ humanize(company.status) }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>

        <AlertDialog v-model:open="statusDialog.open">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ toggleStatusLabel(employee.status) }}</AlertDialogTitle>
                    <AlertDialogDescription>
                        This will update the account status of
                        <span class="font-semibold text-slate-800">{{ employee.name }}</span>.
                        Are you sure you want to continue?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                        @click="confirmToggleStatus"
                    >
                        Confirm
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="resetPasswordDialog.open">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Reset employee password?</AlertDialogTitle>
                    <AlertDialogDescription>
                        The password for
                        <span class="font-semibold text-slate-800">{{ employee.name }}</span>
                        will be reset to
                        <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-mono font-semibold text-slate-700">pitx@123</code>.
                        They should change it on next login.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                        @click="confirmResetPassword"
                    >
                        Reset Password
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="archiveDialog.open">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Archive employee account?</AlertDialogTitle>
                    <AlertDialogDescription>
                        <span class="font-semibold text-slate-800">{{ employee.name }}</span>
                        will be archived and removed from the active employee list.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg">Cancel</AlertDialogCancel>
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
