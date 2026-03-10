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

function submit() {
    form.put(`/employee-users/${props.employee.id}`, {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Edit Employee" />

    <ExternalLayout :company="company" :user="user">
        <div class="space-y-6 p-4 md:p-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <UserCog class="h-5 w-5 text-primary" />
                        <h1 class="text-2xl font-semibold tracking-tight">
                            Edit Employee
                        </h1>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Update employee personal details and assigned role.
                    </p>
                </div>

                <Button as-child variant="outline">
                    <Link href="/employee-users">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Employees
                    </Link>
                </Button>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="xl:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Employee Information</CardTitle>
                            <CardDescription>
                                Edit the employee details below.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <form class="space-y-6" @submit.prevent="submit">
                                <div class="grid gap-6 md:grid-cols-2">
                                    <div class="space-y-2 md:col-span-2">
                                        <Label>Username</Label>
                                        <div
                                            class="flex h-10 items-center rounded-md border bg-muted/40 px-3 text-sm font-medium"
                                        >
                                            {{ employee.username }}
                                        </div>
                                        <p class="text-xs text-muted-foreground">
                                            Username is auto-generated and cannot be changed.
                                        </p>
                                    </div>

                                    <div class="space-y-2 md:col-span-2">
                                        <Label for="name">Full Name</Label>
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            placeholder="Enter full name"
                                        />
                                        <InputError :message="form.errors.name" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="email">Email Address</Label>
                                        <Input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            placeholder="Enter email address"
                                        />
                                        <InputError :message="form.errors.email" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="phone_number">Phone Number</Label>
                                        <Input
                                            id="phone_number"
                                            v-model="form.phone_number"
                                            placeholder="Enter phone number"
                                        />
                                        <InputError :message="form.errors.phone_number" />
                                    </div>

                                    <div class="space-y-2 md:col-span-2">
                                        <Label for="role">Role</Label>
                                        <Select v-model="form.role">
                                            <SelectTrigger id="role" class="w-full">
                                                <SelectValue placeholder="Select role" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="role in roles"
                                                    :key="role"
                                                    :value="role"
                                                >
                                                    {{ humanize(role) }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="form.errors.role" />
                                    </div>
                                </div>

                                <Separator />

                                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                    <Button type="button" variant="outline" as-child>
                                        <Link href="/employee-users">
                                            Cancel
                                        </Link>
                                    </Button>

                                    <Button type="submit" :disabled="form.processing">
                                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <UserSquare2 class="h-4 w-4" />
                                Account Summary
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div>
                                <p class="text-muted-foreground">Employee</p>
                                <p class="font-medium">{{ employee.name }}</p>
                            </div>

                            <div>
                                <p class="text-muted-foreground">Username</p>
                                <p class="font-medium">{{ employee.username }}</p>
                            </div>

                            <div>
                                <p class="text-muted-foreground">Current Status</p>
                                <Badge variant="outline">
                                    {{ humanize(employee.status) }}
                                </Badge>
                            </div>

                            <div>
                                <p class="text-muted-foreground">Current Role</p>
                                <Badge variant="outline">
                                    {{ humanize(selectedRole) }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Building2 class="h-4 w-4" />
                                Company
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div>
                                <p class="text-muted-foreground">Company Name</p>
                                <p class="font-medium">{{ company.company_name }}</p>
                            </div>

                            <div>
                                <p class="text-muted-foreground">Company Code</p>
                                <p class="font-medium">{{ company.company_code || '—' }}</p>
                            </div>

                            <div>
                                <p class="text-muted-foreground">Company Status</p>
                                <Badge variant="outline">
                                    {{ humanize(company.status) }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </ExternalLayout>
</template>
