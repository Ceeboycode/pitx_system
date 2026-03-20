<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { edit, index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    CalendarDays,
    CheckCircle2,
    Mail,
    Phone,
    Pencil,
    ShieldCheck,
    User2,
    XCircle,
} from 'lucide-vue-next';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

type Role = {
    id: number;
    name: string;
    type: 'internal' | 'external';
};

type Company = {
    id: number;
    company_name: string;
    company_code: string | null;
};

const props = defineProps<{
    user: {
        id: number;
        username: string;
        name: string;
        email: string;
        email_verified_at: string | null;
        phone_number: string | null;
        type: 'internal' | 'external' | null;
        status: 'active' | 'inactive' | string;
        created_at: string | null;
        company: Company | null;
        roles: Role[];
        internal_roles: Role[];
        external_roles: Role[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: index().url },
    { title: props.user.name, href: '#' },
];

function formatDate(value: string | null | undefined) {
    if (!value) return '—';

    return new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(value));
}

function formatDateTime(value: string | null | undefined) {
    if (!value) return '—';

    return new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

function statusBadgeClass(status: string) {
    switch (status) {
        case 'active':
            return 'border-emerald-200 bg-emerald-100 text-emerald-700';
        case 'inactive':
            return 'border-red-200 bg-red-100 text-red-700';
        default:
            return 'border-border bg-muted text-muted-foreground';
    }
}

function typeBadgeClass(type: string | null) {
    switch (type) {
        case 'internal':
            return 'border-blue-200 bg-blue-100 text-blue-700';
        case 'external':
            return 'border-amber-200 bg-amber-100 text-amber-700';
        default:
            return 'border-border bg-muted text-muted-foreground';
    }
}

function roleBadgeClass(role: Role) {
    switch (role.type) {
        case 'internal':
            return 'border-blue-200 bg-blue-100 text-blue-700';
        case 'external':
            return 'border-emerald-200 bg-emerald-100 text-emerald-700';
        default:
            return 'border-border bg-muted text-muted-foreground';
    }
}

function verificationBadgeClass(date?: string | null) {
    return date
        ? 'border-blue-200 bg-blue-100 text-blue-700'
        : 'border-amber-200 bg-amber-100 text-amber-700';
}
</script>

<template>
    <Head :title="user.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="mx-5 flex flex-col gap-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <User2 class="h-5 w-5 text-muted-foreground" />
                            <h1 class="text-2xl font-semibold tracking-tight">
                                {{ user.name }}
                            </h1>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            View user profile and role-based account details.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="index().url">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back to Users
                            </Link>
                        </Button>

                        <Button as-child>
                            <Link :href="edit(user.id).url">
                                <Pencil class="mr-2 h-4 w-4" />
                                Edit User
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader class="pb-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-2">
                                <CardTitle class="text-xl">{{ user.username }}</CardTitle>
                                <CardDescription>
                                    Profile details based on assigned role type.
                                </CardDescription>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <Badge :class="statusBadgeClass(user.status)" class="border capitalize">
                                    {{ user.status }}
                                </Badge>

                                <Badge :class="typeBadgeClass(user.type)" class="border capitalize">
                                    {{ user.type ?? 'No Role Type' }}
                                </Badge>

                                <Badge
                                    :class="verificationBadgeClass(user.email_verified_at)"
                                    class="border"
                                >
                                    {{ user.email_verified_at ? 'Email Verified' : 'Email Not Verified' }}
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>

                    <Separator />

                    <CardContent class="grid gap-4 pt-6 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl border bg-muted/30 p-4">
                            <div class="mb-2 flex items-center gap-2 text-sm font-medium">
                                <Mail class="h-4 w-4 text-muted-foreground" />
                                Email Address
                            </div>
                            <p class="break-all text-sm">{{ user.email }}</p>
                            <p class="mt-2 text-xs text-muted-foreground">
                                {{ user.email_verified_at ? `Verified on ${formatDateTime(user.email_verified_at)}` : 'Not yet verified' }}
                            </p>
                        </div>

                        <div class="rounded-xl border bg-muted/30 p-4">
                            <div class="mb-2 flex items-center gap-2 text-sm font-medium">
                                <Phone class="h-4 w-4 text-muted-foreground" />
                                Phone Number
                            </div>
                            <p class="text-sm">{{ user.phone_number || '—' }}</p>
                            <p class="mt-2 text-xs text-muted-foreground">
                                Contact number on record
                            </p>
                        </div>

                        <div class="rounded-xl border bg-muted/30 p-4">
                            <div class="mb-2 flex items-center gap-2 text-sm font-medium">
                                <CalendarDays class="h-4 w-4 text-muted-foreground" />
                                Created At
                            </div>
                            <p class="text-sm">{{ formatDateTime(user.created_at) }}</p>
                            <p class="mt-2 text-xs text-muted-foreground">
                                Account creation date
                            </p>
                        </div>

                        <div class="rounded-xl border bg-muted/30 p-4">
                            <div class="mb-2 flex items-center gap-2 text-sm font-medium">
                                <ShieldCheck class="h-4 w-4 text-muted-foreground" />
                                Role Type
                            </div>
                            <p class="text-sm capitalize">{{ user.type ?? '—' }}</p>
                            <p class="mt-2 text-xs text-muted-foreground">
                                Based on assigned role
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <div v-if="user.type === 'internal'" class="grid gap-4 lg:grid-cols-3">
                    <Card class="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Internal User Details</CardTitle>
                            <CardDescription>
                                Internal account information and internal roles only.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-5">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">Full Name</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm">
                                        {{ user.name }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">Username</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm">
                                        {{ user.username }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">Email</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm break-all">
                                        {{ user.email }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">Phone Number</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm">
                                        {{ user.phone_number || '—' }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">User Type</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm capitalize">
                                        internal
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">Status</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm capitalize">
                                        {{ user.status }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Internal Roles</CardTitle>
                            <CardDescription>
                                Roles assigned under internal access.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <div
                                v-if="user.internal_roles.length > 0"
                                class="flex flex-wrap gap-2"
                            >
                                <Badge
                                    v-for="role in user.internal_roles"
                                    :key="role.id"
                                    :class="roleBadgeClass(role)"
                                    class="border capitalize"
                                >
                                    {{ role.name }}
                                </Badge>
                            </div>

                            <div
                                v-else
                                class="rounded-xl border border-dashed p-4 text-sm text-muted-foreground"
                            >
                                No internal roles assigned.
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-else-if="user.type === 'external'" class="grid gap-4 lg:grid-cols-3">
                    <Card class="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>External User Details</CardTitle>
                            <CardDescription>
                                External account information, assigned company, and external roles only.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-5">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">Full Name</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm">
                                        {{ user.name }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">Username</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm">
                                        {{ user.username }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">Email</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm break-all">
                                        {{ user.email }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">Phone Number</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm">
                                        {{ user.phone_number || '—' }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">Company</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm">
                                        {{ user.company?.company_name ?? '—' }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">Company Code</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm">
                                        {{ user.company?.company_code ?? '—' }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">User Type</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm capitalize">
                                        external
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-muted-foreground">Status</p>
                                    <div class="rounded-lg border px-3 py-2 text-sm capitalize">
                                        {{ user.status }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>External Roles</CardTitle>
                            <CardDescription>
                                Roles assigned under external access.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <div
                                v-if="user.external_roles.length > 0"
                                class="flex flex-wrap gap-2"
                            >
                                <Badge
                                    v-for="role in user.external_roles"
                                    :key="role.id"
                                    :class="roleBadgeClass(role)"
                                    class="border capitalize"
                                >
                                    {{ role.name }}
                                </Badge>
                            </div>

                            <div
                                v-else
                                class="rounded-xl border border-dashed p-4 text-sm text-muted-foreground"
                            >
                                No external roles assigned.
                            </div>

                            <Separator class="my-4" />

                            <div class="space-y-3 text-sm">
                                <div class="flex items-start gap-2">
                                    <Building2 class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="font-medium">Assigned Company</p>
                                        <p class="text-muted-foreground">
                                            {{ user.company?.company_name ?? '—' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2">
                                    <CheckCircle2
                                        v-if="user.email_verified_at"
                                        class="mt-0.5 h-4 w-4 text-muted-foreground"
                                    />
                                    <XCircle
                                        v-else
                                        class="mt-0.5 h-4 w-4 text-muted-foreground"
                                    />
                                    <div>
                                        <p class="font-medium">Email Verification</p>
                                        <p class="text-muted-foreground">
                                            {{ user.email_verified_at ? 'Verified' : 'Not Verified' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2">
                                    <CalendarDays class="mt-0.5 h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="font-medium">Created On</p>
                                        <p class="text-muted-foreground">
                                            {{ formatDate(user.created_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card v-else>
                    <CardHeader>
                        <CardTitle>User Details</CardTitle>
                        <CardDescription>
                            No valid role type was found for this user.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <div class="rounded-xl border border-dashed p-4 text-sm text-muted-foreground">
                            This user does not currently have a valid internal or external role assigned.
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
