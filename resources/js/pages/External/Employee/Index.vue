<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'

import InertiaPagination from '@/components/InertiaPagination.vue'
import SearchInput from '@/components/SearchInput.vue'
import ExternalLayout from '@/layouts/ExternalLayout.vue'

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
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

import {
    Eye,
    KeyRound,
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    Trash2,
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

type Role = {
    id: number
    name: string
}

type EmployeeUser = {
    id: number
    username: string
    name: string
    email?: string | null
    phone_number?: string | null
    status: string
    created_at?: string | null
    roles?: Role[]
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type PaginatedUsers = {
    data: EmployeeUser[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: PaginationLink[]
}

const props = defineProps<{
    company: Company
    user: AuthUser
    users: PaginatedUsers
    filters: {
        search?: string | null
        role?: string | null
        status?: string | null
    }
    roles: string[]
    statuses: string[]
}>()

function humanize(value?: string | null) {
    if (!value) return '—'

    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

function formatDate(value?: string | null) {
    if (!value) return '—'

    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

function roleName(employee: EmployeeUser) {
    return employee.roles?.[0]?.name ?? '—'
}

function statusVariant(status?: string | null) {
    if (status === 'active') return 'default'
    if (status === 'pending') return 'secondary'
    if (status === 'suspended') return 'destructive'
    return 'outline'
}

function toggleStatusLabel(status?: string | null) {
    if (status === 'active') return 'Set Inactive'
    if (status === 'inactive') return 'Set Active'
    if (status === 'pending') return 'Activate Account'
    if (status === 'suspended') return 'Set Active'
    return 'Update Status'
}

const totalEmployees = computed(() => props.users.total ?? 0)

const totalDrivers = computed(() =>
    props.users.data.filter((employee) => roleName(employee) === 'driver').length,
)

const totalDispatchers = computed(() =>
    props.users.data.filter((employee) => roleName(employee) === 'dispatcher').length,
)

const deleteDialog = reactive({
    open: false,
    employee: null as EmployeeUser | null,
})

const statusDialog = reactive({
    open: false,
    employee: null as EmployeeUser | null,
})

const resetPasswordDialog = reactive({
    open: false,
    employee: null as EmployeeUser | null,
})

function openDeleteDialog(employee: EmployeeUser) {
    deleteDialog.employee = employee
    deleteDialog.open = true
}

function openStatusDialog(employee: EmployeeUser) {
    statusDialog.employee = employee
    statusDialog.open = true
}

function openResetPasswordDialog(employee: EmployeeUser) {
    resetPasswordDialog.employee = employee
    resetPasswordDialog.open = true
}

function confirmDelete() {
    if (!deleteDialog.employee) return

    router.delete(`/employee-users/${deleteDialog.employee.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialog.open = false
            deleteDialog.employee = null
        },
    })
}

function confirmToggleStatus() {
    if (!statusDialog.employee) return

    router.patch(
        `/employee-users/${statusDialog.employee.id}/toggle-status`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                statusDialog.open = false
                statusDialog.employee = null
            },
        },
    )
}

function confirmResetPassword() {
    if (!resetPasswordDialog.employee) return

    router.patch(
        `/employee-users/${resetPasswordDialog.employee.id}/reset-password`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                resetPasswordDialog.open = false
                resetPasswordDialog.employee = null
            },
        },
    )
}
</script>

<template>
    <Head title="Employee Accounts" />

    <ExternalLayout :company="company" :user="user">
        <div class="space-y-6 p-4 md:p-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Employee Accounts
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Manage your company drivers and dispatchers.
                    </p>
                </div>

                <Button as-child>
                    <Link href="/employee-users/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Employee
                    </Link>
                </Button>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Total Employees</CardDescription>
                        <CardTitle class="text-2xl">
                            {{ totalEmployees }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Drivers</CardDescription>
                        <CardTitle class="text-2xl">
                            {{ totalDrivers }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Dispatchers</CardDescription>
                        <CardTitle class="text-2xl">
                            {{ totalDispatchers }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Company</CardDescription>
                        <CardTitle class="text-base">
                            {{ company.company_code ?? company.company_name }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <Card>
                <CardHeader class="space-y-4">
                    <div>
                        <CardTitle>Employee List</CardTitle>
                        <CardDescription>
                            Search employees by name, username, email, or phone.
                        </CardDescription>
                    </div>

                    <SearchInput
                        route="/employee-users"
                        :initial-value="filters.search"
                        placeholder="Search employees..."
                        :only="['users', 'filters']"
                    />
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="overflow-x-auto rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Username</TableHead>
                                    <TableHead>Employee</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Phone</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead class="text-right">
                                        Action
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-if="users.data.length === 0">
                                    <TableCell
                                        colspan="7"
                                        class="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        No employees found.
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="employee in users.data"
                                    :key="employee.id"
                                >
                                    <TableCell class="font-medium">
                                        {{ employee.username }}
                                    </TableCell>

                                    <TableCell>
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <UserSquare2
                                                    class="h-4 w-4 text-muted-foreground"
                                                />
                                                <span>{{ employee.name }}</span>
                                            </div>

                                            <p class="text-xs text-muted-foreground">
                                                {{ employee.email || '—' }}
                                            </p>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <Badge variant="outline">
                                            {{ humanize(roleName(employee)) }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell>
                                        <Badge :variant="statusVariant(employee.status)">
                                            {{ humanize(employee.status) }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ employee.phone_number || '—' }}
                                    </TableCell>

                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDate(employee.created_at) }}
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon">
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="w-52">
                                                <DropdownMenuLabel>
                                                    Actions
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem as-child>
                                                    <Link :href="`/employee-users/${employee.id}`">
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem as-child>
                                                    <Link :href="`/employee-users/${employee.id}/edit`">
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    @click="openStatusDialog(employee)"
                                                >
                                                    <Power class="mr-2 h-4 w-4" />
                                                    {{ toggleStatusLabel(employee.status) }}
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    @click="openResetPasswordDialog(employee)"
                                                >
                                                    <KeyRound class="mr-2 h-4 w-4" />
                                                    Reset Password
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    class="text-destructive focus:text-destructive"
                                                    @click="openDeleteDialog(employee)"
                                                >
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <InertiaPagination
                        v-if="users.last_page > 1"
                        :links="users.links"
                        :meta="{
                            from: users.from,
                            to: users.to,
                            total: users.total,
                        }"
                    />
                </CardContent>
            </Card>

            <AlertDialog v-model:open="statusDialog.open">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {{ toggleStatusLabel(statusDialog.employee?.status) }}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            This will update the status of
                            <span class="font-medium text-foreground">
                                {{ statusDialog.employee?.name || 'this employee' }}
                            </span>.
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <AlertDialogFooter>
                        <AlertDialogCancel @click="statusDialog.employee = null">
                            Cancel
                        </AlertDialogCancel>
                        <AlertDialogAction @click="confirmToggleStatus">
                            Confirm
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <AlertDialog v-model:open="resetPasswordDialog.open">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Reset employee password?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            This will reset the password of
                            <span class="font-medium text-foreground">
                                {{ resetPasswordDialog.employee?.name || 'this employee' }}
                            </span>
                            to
                            <span class="font-medium text-foreground">pitx@123</span>.
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <AlertDialogFooter>
                        <AlertDialogCancel @click="resetPasswordDialog.employee = null">
                            Cancel
                        </AlertDialogCancel>
                        <AlertDialogAction @click="confirmResetPassword">
                            Reset Password
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <AlertDialog v-model:open="deleteDialog.open">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Delete employee account?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            This will permanently remove
                            <span class="font-medium text-foreground">
                                {{ deleteDialog.employee?.name || 'this employee' }}
                            </span>
                            from your employee list.
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <AlertDialogFooter>
                        <AlertDialogCancel @click="deleteDialog.employee = null">
                            Cancel
                        </AlertDialogCancel>
                        <AlertDialogAction @click="confirmDelete">
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </ExternalLayout>
</template>
