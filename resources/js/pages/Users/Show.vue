<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/can';
import { destroy, edit, index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Archive,
    ArchiveX,
    ArrowLeft,
    CheckCircle2,
    Mail,
    Pencil,
    Phone,
    XCircle,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

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
    currentUserId: number | null;
    user: {
        id: number;
        username: string;
        name: string;
        email: string;
        email_verified_at: string | null;
        avatar: string | null;
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

const isOwnAccount = props.currentUserId === props.user.id;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: index().url },
    { title: props.user.name, href: '#' },
];

const canArchiveUser = can('users.archive');

const archiveOpen = ref(false);

function confirmArchive() {
    router.delete(destroy(props.user.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            archiveOpen.value = false;
            toast.success('User archived successfully.');
        },
        onError: () => toast.error('Failed to archive user.'),
    });
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

function initials(name: string) {
    const parts = name.trim().split(/\s+/).filter(Boolean).slice(0, 2);
    return parts.map((part) => part.charAt(0).toUpperCase()).join('') || 'U';
}
</script>

<template>
    <Head :title="user.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

            <!-- Header card -->
            <Card>
                <CardHeader class="py-0">
                    <div class="flex items-center gap-4">
                        <!-- Avatar / initials box -->
                        <div
                            class="relative h-32 w-32 shrink-0 overflow-hidden rounded-lg border-2 shadow-sm"
                        >
                            <img
                                v-if="user.avatar"
                                :src="user.avatar"
                                :alt="`${user.name} profile photo`"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center bg-primary"
                            >
                                <span class="text-2xl font-bold text-primary-foreground">
                                    {{ initials(user.name) }}
                                </span>
                            </div>
                        </div>

                        <div class="gap-2 w-full">
                            <div class="flex flex-row gap-2 pb-2 w-full items-center">
                                <h1 class="text-2xl leading-tight font-bold tracking-tight">
                                    {{ user.name }}
                                </h1>
                                <div class="ml-2 flex flex-1 items-center">
                                    <hr class="h-px w-full border border-rose-500" />
                                    <div class="border-7 border-rose-500 rounded-xs">
                                        <div class="border-3 border-white rounded-xs"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge :class="['gap-1.5 border', statusBadgeClass(user.status)]">
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 rounded-full',
                                                user.status === 'active' ? 'bg-emerald-500' : 'bg-red-500',
                                            ]"
                                        />
                                        {{ user.status === 'active' ? 'Active' : 'Inactive' }}
                                    </Badge>
                                    <Badge :class="['border capitalize', typeBadgeClass(user.type)]">
                                        {{ user.type ?? 'No Role Type' }}
                                    </Badge>
                                    <Badge :class="['gap-1 border', verificationBadgeClass(user.email_verified_at)]">
                                        <CheckCircle2 v-if="user.email_verified_at" class="h-3 w-3" />
                                        <XCircle v-else class="h-3 w-3" />
                                        {{ user.email_verified_at ? 'Email Verified' : 'Unverified' }}
                                    </Badge>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <Button
                                        as-child
                                        variant="outline"
                                        class="rounded-lg bg-card border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer"
                                    >
                                        <Link :href="index().url">
                                            <ArrowLeft class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="!isOwnAccount"
                                        as-child
                                        variant="outline"
                                        class="group/segment rounded-lg bg-card border-slate-200 text-slate-600 hover:bg-slate-100 gap-0 cursor-pointer"
                                    >
                                        <Link :href="edit(user.id).url">
                                            <Pencil class="h-4 w-4 shrink-0" />
                                            <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-24 group-hover/segment:opacity-100">
                                                Edit User
                                            </span>
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="canArchiveUser && !isOwnAccount"
                                        variant="outline"
                                        class="group/segment rounded-lg bg-card border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700 gap-0 cursor-pointer"
                                        @click="archiveOpen = true"
                                    >
                                        <Archive class="h-4 w-4 shrink-0" />
                                        <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-32 group-hover/segment:opacity-100">
                                            Archive User
                                        </span>
                                    </Button>
                                    <span
                                        v-if="isOwnAccount"
                                        class="text-xs text-muted-foreground px-2"
                                    >
                                        You cannot manage your own account here.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- User Details card -->
            <Card class="py-6">
                <CardHeader>
                    <CardTitle>User Details</CardTitle>
                </CardHeader>
                <CardContent class="px-6 grid divide-y gap-y-2 pt-2 border-t border-slate-100">
                    <div class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Username
                        </span>
                        <span class="rounded bg-muted px-2 py-0.5 font-mono text-sm font-semibold">
                            {{ user.username }}
                        </span>
                    </div>
                    <div class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Full Name
                        </span>
                        <span class="text-sm font-semibold">{{ user.name }}</span>
                    </div>
                    <div class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Status
                        </span>
                        <Badge :class="['mt-1 gap-1.5 border', statusBadgeClass(user.status)]">
                            <span
                                :class="[
                                    'h-1.5 w-1.5 rounded-full',
                                    user.status === 'active' ? 'bg-emerald-500' : 'bg-red-500',
                                ]"
                            />
                            {{ user.status === 'active' ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                    <div class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Role Type
                        </span>
                        <Badge :class="['mt-1 border capitalize', typeBadgeClass(user.type)]">
                            {{ user.type ?? 'None' }}
                        </Badge>
                    </div>
                    <div v-if="user.type === 'external'" class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            External Roles
                        </span>
                        <div v-if="user.external_roles.length > 0" class="mt-1 flex flex-wrap gap-2">
                            <Badge
                                v-for="role in user.external_roles"
                                :key="role.id"
                                :class="roleBadgeClass(role)"
                                class="border capitalize"
                            >
                                {{ role.name }}
                            </Badge>
                        </div>
                        <span v-else class="text-sm text-muted-foreground">No external roles assigned.</span>
                    </div>
                    <div class="grid gap-y-2 pt-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Contacts
                        </span>
                        <div class="items-center flex">
                            <div class="h-full mr-4">
                                <Mail class="h-4 w-4 inline-block text-primary" />
                            </div>
                            <span class="text-sm">{{ user.email }}</span>
                        </div>
                        <div class="items-center flex">
                            <div class="h-full mr-4">
                                <Phone class="h-4 w-4 inline-block text-primary" />
                            </div>
                            <span class="text-sm">{{ user.phone_number || '—' }}</span>
                        </div>
                    </div>
                    <div class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Created
                        </span>
                        <span class="text-sm">{{ formatDateTime(user.created_at) }}</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Internal type section -->
            <Card v-if="user.type === 'internal'" class="py-6">
                <CardHeader>
                    <CardTitle>Internal Roles</CardTitle>
                </CardHeader>
                <CardContent class="px-6 pt-4 border-t border-slate-100">
                    <div v-if="user.internal_roles.length > 0" class="flex flex-wrap gap-2">
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

            <!-- External type section -->
            <Card v-else-if="user.type === 'external' && user.company" class="py-6">
                <CardHeader>
                    <CardTitle>Company</CardTitle>
                </CardHeader>
                <CardContent class="px-6 grid divide-y gap-y-2 pt-2 border-t border-slate-100">
                    <div class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Company Name
                        </span>
                        <span class="text-sm font-semibold">{{ user.company.company_name }}</span>
                    </div>
                    <div class="py-2">
                        <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                            Company Code
                        </span>
                        <span class="rounded bg-muted px-2 py-0.5 font-mono text-sm font-semibold">
                            {{ user.company.company_code ?? '—' }}
                        </span>
                    </div>
                </CardContent>
            </Card>

            <!-- No type fallback -->
            <!-- <Card v-else class="py-6">
                <CardHeader>
                    <CardTitle>User Details</CardTitle>
                </CardHeader>
                <CardContent class="px-6 pt-4 border-t border-slate-100">
                    <div class="rounded-xl border border-dashed p-4 text-sm text-muted-foreground">
                        This user does not currently have a valid internal or external role assigned.
                    </div>
                </CardContent>
            </Card> -->

            <!-- Archive dialog -->
            <Dialog :open="archiveOpen" @update:open="archiveOpen = $event">
                <DialogContent class="sm:max-w-md p-4">
                    <DialogHeader>
                        <DialogTitle>Archive User</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to archive
                            <span class="font-semibold text-foreground">{{ user.name }}</span>?
                            You can restore this account from Archived Users.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2 sm:justify-end">
                        <Button
                            variant="outline"
                            class="cursor-pointer hover:bg-slate-100"
                            @click="archiveOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            class="bg-destructive text-destructive-foreground cursor-pointer hover:bg-destructive/90"
                            @click="confirmArchive"
                        >
                            <ArchiveX class="h-4 w-4" />
                            Archive
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
