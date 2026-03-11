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
    KeyRound,
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

function submit() {
    form.post('/employee-users', {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Create Employee" />

    <ExternalLayout :company="company" :user="user">
        <div class="space-y-6 p-4 md:p-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <UserPlus class="h-5 w-5 text-primary" />
                        <h1 class="text-2xl font-semibold tracking-tight">
                            Add Employee
                        </h1>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Create a new driver or dispatcher account for your company.
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
                                Fill in the basic details below. Username is generated automatically.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <form class="space-y-6" @submit.prevent="submit">
                                <div class="grid gap-6 md:grid-cols-2">
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

                                    <div class="space-y-2">
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

                                    <!-- <div class="space-y-2 md:col-span-2">
                                        <Label>Default Password</Label>
                                        <div
                                            class="flex h-10 items-center rounded-md border bg-muted/40 px-3 text-sm"
                                        >
                                            pitx@123
                                        </div>
                                        <p class="text-xs text-muted-foreground">
                                            The employee can use this password on first login.
                                        </p>
                                    </div> -->

                                    <div class="space-y-2 md:col-span-2">
                                        <Label>Generated Username</Label>
                                        <div
                                            class="flex h-10 items-center rounded-md border bg-muted/40 px-3 text-sm font-medium"
                                        >
                                            {{ nextUsernamePreview }}
                                        </div>
                                        <p class="text-xs text-muted-foreground">
                                            Username is generated automatically when the account is saved.
                                        </p>
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
                                        <UserPlus class="mr-2 h-4 w-4" />
                                        {{ form.processing ? 'Creating...' : 'Create Employee' }}
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
                                <p class="font-medium">
                                    {{ company.company_code || '—' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-muted-foreground">Company Status</p>
                                <Badge variant="outline">
                                    {{ humanize(company.status) }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Users class="h-4 w-4" />
                                Available Roles
                            </CardTitle>
                            <CardDescription>
                                Assign one role per employee account.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-for="role in roles"
                                :key="role"
                                class="rounded-lg border p-3"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-medium">
                                        {{ humanize(role) }}
                                    </p>
                                    <Badge variant="outline">Role</Badge>
                                </div>

                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        role === 'driver'
                                            ? 'Use this for vehicle operators and assigned drivers.'
                                            : 'Use this for dispatch and trip coordination accounts.'
                                    }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <KeyRound class="h-4 w-4" />
                                Account Notes
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm text-muted-foreground">
                            <div class="flex items-start gap-2">
                                <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0" />
                                <p>New employee accounts start with pending status.</p>
                            </div>

                            <div class="flex items-start gap-2">
                                <Phone class="mt-0.5 h-4 w-4 shrink-0" />
                                <p>Phone number is optional, but helpful for contact details.</p>
                            </div>

                            <div class="flex items-start gap-2">
                                <KeyRound class="mt-0.5 h-4 w-4 shrink-0" />
                                <p>
                                    Default password is set to
                                    <span class="font-medium text-foreground">pitx@123</span>.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </ExternalLayout>
</template>
