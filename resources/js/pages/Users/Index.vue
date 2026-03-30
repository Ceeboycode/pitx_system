<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import ListFilters from '@/components/ListFilters.vue';
import SortDirectionControl from '@/components/filters/SortDirectionControl.vue';
import { useSortableIndex } from '@/composables/useSortableIndex';

/* shadcn-vue */
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardAction,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import AppLayout from '@/layouts/AppLayout.vue';
import {
    create,
    edit,
    index,
    resetPassword,
    show,
    toggleStatus,
} from '@/routes/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import {
    Download,
    Eye,
    KeyRound,
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    Upload,
    UserSearch,
} from 'lucide-vue-next';

import { computed } from 'vue';

import { can } from '@/lib/can';

const canCreate    = can('users.create');
const canUpdate    = can('users.update');
const canToggle    = can('users.toggleStatus');
const canResetPass = can('users.resetPassword');

interface Role {
    id: number;
    name: string;
    type: string;
}

interface Company {
    id: number;
    company_name: string;
    company_code: string;
}

interface User {
    id: number;
    username: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    phone_number: string | null;
    company_id: number | null;
    company: Company | null;
    roles: Role[];
    status: 'active' | 'inactive' | string;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: index().url }];

const props = defineProps<{
    users: {
        data: User[];
        links: any;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search?: string | null;
        type?: string | 'internal';
        status?: string | 'active';
        sort?: string | null;
        direction?: string | null;
    };
    statuses?: string[];
    canSeeSuperAdmin?: boolean;
}>();

function formatFilterLabel(value: string) {
    return value
        .split('_')
        .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
        .join(' ');
}

const sortOptions = [
    { label: 'Name', value: 'name' },
    { label: 'Username', value: 'username' },
    { label: 'Email', value: 'email' },
] as const;

const userFilters = computed(() => [
    {
        key: 'type',
        value: props.filters.type ?? 'internal',
        placeholder: 'User Type',
        allLabel: 'All Types',
        allValue: 'all',
        desktopWidthClass: 'w-36',
        desktopMaxWidth: '9rem',
        options: [
            { label: 'Internal', value: 'internal' },
            { label: 'External', value: 'external' },
        ],
    },
    {
        key: 'status',
        value: props.filters.status ?? 'active',
        placeholder: 'Status',
        allLabel: 'All Status',
        allValue: 'all',
        desktopWidthClass: 'w-36',
        desktopMaxWidth: '9rem',
        options: (props.statuses ?? ['active', 'inactive']).map((status) => ({
            label: formatFilterLabel(status),
            value: status,
        })),
    },
]);

const showCompanyColumn = computed(() => props.filters.type !== 'internal');
const baseQuery = computed(() => ({
    search: props.filters.search ?? '',
    type: props.filters.type ?? 'internal',
    status: props.filters.status ?? 'active',
}));
const {
    currentSort,
    currentDirection,
    applySort,
    toggleDirection,
} = useSortableIndex({
    route: index().url,
    baseQuery,
    sort: computed(() => props.filters.sort ?? 'name'),
    direction: computed(() => props.filters.direction ?? 'asc'),
    only: ['users', 'filters', 'statuses', 'flash'],
});

function roleBadgeVariant(role: Role) {
    switch (role.type) {
        case 'internal':
            return 'info';
        case 'external':
            return 'success';
        default:
            return 'secondary';
    }
}

function roleBadgeClass(role: Role) {
    switch (role.type) {
        case 'internal':
            return 'border-sky-200 bg-sky-100 text-sky-700';
        case 'external':
            return 'border-emerald-200 bg-emerald-100 text-emerald-700';
        default:
            return '';
    }
}

function statusBadgeVariant(status: string) {
    switch (status) {
        case 'active':
            return 'success';
        case 'inactive':
            return 'destructive';
        default:
            return 'secondary';
    }
}

function statusBadgeClass(status: string) {
    switch (status) {
        case 'active':
            return 'border-emerald-200 bg-emerald-100 text-emerald-700';
        case 'inactive':
            return 'border-rose-200 bg-rose-100 text-rose-700';
        default:
            return '';
    }
}

function emailVerificationBadgeVariant(emailVerifiedAt: string | null) {
    return emailVerifiedAt ? 'info' : 'warning';
}

function emailVerificationBadgeClass(emailVerifiedAt: string | null) {
    return emailVerifiedAt
        ? 'border-sky-200 bg-sky-100 text-sky-700'
        : 'border-amber-200 bg-amber-100 text-amber-700';
}

function emailVerificationLabel(emailVerifiedAt: string | null) {
    return emailVerifiedAt ? 'Verified' : 'Not Verified';
}

function visibleRoles(user: User) {
    if (props.canSeeSuperAdmin) return user.roles;
    return user.roles.filter((role) => role.name !== 'super-admin');
}

function isActive(user: User) {
    return user.status === 'active';
}

function handleToggleStatus(user: User) {
    const actionLabel = isActive(user) ? 'set inactive' : 'set active';
    if (!confirm(`Are you sure you want to ${actionLabel} ${user.name}?`)) return;

    router.put(toggleStatus(user.id).url, {}, { preserveScroll: true });
}

function handleResetPassword(user: User) {
    if (!confirm(`Reset password for ${user.name}?`)) return;

    router.post(resetPassword(user.id).url, {}, { preserveScroll: true });
}

</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-10 mt-3">
                <CardHeader>
                    <CardTitle>Users</CardTitle>
                    <CardDescription>Manage users, assign roles, and control access.</CardDescription>

                    <CardAction>
                        <Button v-if="canCreate" variant="default" size="sm" as-child>
                            <Link :href="create().url">
                                <Plus class="mr-1 h-4 w-4" />
                                Create User
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <ListFilters
                        :route="index().url"
                        :search="props.filters.search ?? ''"
                        search-placeholder="Search users..."
                        :only="['users', 'filters', 'statuses', 'flash']"
                        :filters="userFilters"
                        :query="{
                            sort: currentSort,
                            direction: currentDirection,
                        }"
                        mobile-inline-actions
                    >
                        <template #panel-actions>
                            <SortDirectionControl
                                :options="sortOptions"
                                :value="currentSort"
                                :direction="currentDirection"
                                label="Sort users"
                                @select="applySort"
                                @toggle-direction="toggleDirection"
                            />
                        </template>

                        <template #inline-actions>
                            <div class="inline-flex overflow-hidden rounded-md border bg-background shadow-xs">
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    class="group gap-0 rounded-none border-0 shadow-none"
                                >
                                    <Upload class="h-4 w-4 shrink-0" />
                                    <span
                                        class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-500 group-hover:ml-2 group-hover:max-w-20 group-hover:opacity-100"
                                    >
                                        Import
                                    </span>
                                    <span class="sr-only">Import</span>
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    class="group gap-0 rounded-none border-0 border-l shadow-none"
                                >
                                    <Download class="h-4 w-4 shrink-0" />
                                    <span
                                        class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-500 group-hover:ml-2 group-hover:max-w-20 group-hover:opacity-100"
                                    >
                                        Export
                                    </span>
                                    <span class="sr-only">Export</span>
                                </Button>
                            </div>
                        </template>
                    </ListFilters>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="data-table-head">Username</TableHead>
                                <TableHead class="data-table-head">Name</TableHead>
                                <TableHead class="data-table-head">Email</TableHead>
                                <TableHead class="data-table-head">Email Verification</TableHead>
                                <TableHead class="data-table-head">Status</TableHead>
                                <TableHead v-if="showCompanyColumn" class="data-table-head">Company</TableHead>
                                <TableHead class="data-table-head">Roles</TableHead>
                                <TableHead class="data-table-head w-[80px] text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="user in props.users.data" :key="user.id">
                                <TableCell class="font-medium">
                                    {{ user.username }}
                                </TableCell>

                                <TableCell>{{ user.name }}</TableCell>

                                <TableCell>{{ user.email }}</TableCell>

                                <TableCell>
                                    <Badge
                                        :variant="emailVerificationBadgeVariant(user.email_verified_at)"
                                        :class="emailVerificationBadgeClass(user.email_verified_at)"
                                    >
                                        {{ emailVerificationLabel(user.email_verified_at) }}
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        :variant="statusBadgeVariant(user.status)"
                                        :class="['capitalize', statusBadgeClass(user.status)]"
                                    >
                                        {{ user.status }}
                                    </Badge>
                                </TableCell>

                                <TableCell v-if="showCompanyColumn">
                                    {{
                                        visibleRoles(user).some((r) => r.type === 'external')
                                            ? (user.company?.company_name ?? '-')
                                            : '-'
                                    }}
                                </TableCell>

                                <TableCell>
                                    <div class="flex flex-wrap gap-1 capitalize">
                                        <Badge
                                            v-for="role in visibleRoles(user)"
                                            :key="role.id"
                                            :variant="roleBadgeVariant(role)"
                                            :class="roleBadgeClass(role)"
                                        >
                                            {{ role.name }}
                                        </Badge>

                                        <span
                                            v-if="visibleRoles(user).length === 0"
                                            class="text-sm text-muted-foreground"
                                        >
                                            -
                                        </span>
                                    </div>
                                </TableCell>

                                <TableCell class="text-center">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon" class="h-8 w-8">
                                                <MoreHorizontal class="h-4 w-4" />
                                                <span class="sr-only">Open actions</span>
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end" class="w-56">
                                            <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem as-child>
                                                <Link
                                                    :href="show(user.id).url"
                                                    class="flex w-full cursor-pointer items-center"
                                                >
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    <span>View Profile</span>
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem v-if="canUpdate" as-child>
                                                <Link
                                                    :href="edit(user.id).url"
                                                    class="flex w-full cursor-pointer items-center"
                                                >
                                                    <Pencil class="mr-2 h-4 w-4" />
                                                    <span>Edit Details</span>
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="canToggle"
                                                class="cursor-pointer"
                                                @click="handleToggleStatus(user)"
                                            >
                                                <Power class="mr-2 h-4 w-4" />
                                                <span>{{ isActive(user) ? 'Set Inactive' : 'Set Active' }}</span>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="canResetPass"
                                                class="cursor-pointer"
                                                @click="handleResetPassword(user)"
                                            >
                                                <KeyRound class="mr-2 h-4 w-4" />
                                                <span>Reset Password</span>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="props.users.data.length === 0">
                                <TableCell
                                    :colspan="showCompanyColumn ? 8 : 7"
                                    class="py-20 text-center"
                                >
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                            <UserSearch class="h-6 w-6 text-muted-foreground/40" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-foreground">No users found</p>
                                            <p class="mt-0.5 text-xs text-muted-foreground">
                                                Try adjusting your search or filters.
                                            </p>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <InertiaPagination
                        :links="props.users.links"
                        :meta="{
                            from: props.users.from,
                            to: props.users.to,
                            total: props.users.total,
                        }"
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
