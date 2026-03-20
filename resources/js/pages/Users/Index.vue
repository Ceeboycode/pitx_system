<script setup lang="ts">
/* ======================================================
   Shared UI
====================================================== */
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

/* shadcn-vue */
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

/* ======================================================
   Layout, Routing & Inertia
====================================================== */
import AppLayout from '@/layouts/AppLayout.vue';
import {
    create,
    destroy,
    edit,
    index,
    resetPassword,
    show,
    toggleStatus,
} from '@/routes/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

/* ======================================================
   Icons
====================================================== */
import {
    Download,
    Eye,
    KeyRound,
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    Trash2,
    Upload,
} from 'lucide-vue-next';

/* ======================================================
   Vue Core
====================================================== */
import { computed } from 'vue';

/* ======================================================
   Permissions
====================================================== */
import { can } from '@/lib/can';

const canCreate    = can('users.create');
const canUpdate    = can('users.update');
const canDelete    = can('users.delete');
const canToggle    = can('users.toggleStatus');
const canResetPass = can('users.resetPassword');

/* ======================================================
   Types
====================================================== */
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

/* ======================================================
   Breadcrumbs
====================================================== */
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: index().url }];

/* ======================================================
   Props
====================================================== */
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
        type?: string | null;
        status?: string | null;
    };
    statuses?: string[];
    canSeeSuperAdmin?: boolean;
}>();

/* ======================================================
   Filters
====================================================== */
function applyFilters(type: string | null, status: string | null) {
    router.get(
        index().url,
        {
            search: props.filters.search ?? '',
            type,
            status,
        },
        {
            preserveScroll: true,
            only: ['users', 'filters', 'statuses', 'flash'],
        },
    );
}

/* ======================================================
   Computed
====================================================== */
const showCompanyColumn = computed(() => props.filters.type !== 'internal');

/* ======================================================
   Badge helpers
====================================================== */
function roleBadgeClass(role: Role) {
    switch (role.type) {
        case 'internal':
            return 'border-blue-200 bg-blue-100 text-blue-700';
        case 'external':
            return 'border-emerald-200 bg-emerald-100 text-emerald-700';
        default:
            return 'bg-muted text-muted-foreground';
    }
}

function statusBadgeClass(status: string) {
    switch (status) {
        case 'active':
            return 'border-emerald-200 bg-emerald-100 text-emerald-700';
        case 'inactive':
            return 'border-red-200 bg-red-100 text-red-700';
        default:
            return 'bg-muted text-muted-foreground';
    }
}

function emailVerificationBadgeClass(emailVerifiedAt: string | null) {
    return emailVerifiedAt
        ? 'border-blue-200 bg-blue-100 text-blue-700'
        : 'border-amber-200 bg-amber-100 text-amber-700';
}

function emailVerificationLabel(emailVerifiedAt: string | null) {
    return emailVerifiedAt ? 'Verified' : 'Not Verified';
}

/* ======================================================
   Helpers
====================================================== */
function visibleRoles(user: User) {
    if (props.canSeeSuperAdmin) return user.roles;
    return user.roles.filter((role) => role.name !== 'super-admin');
}

function isActive(user: User) {
    return user.status === 'active';
}

/* ======================================================
   Actions
====================================================== */
function handleToggleStatus(user: User) {
    const actionLabel = isActive(user) ? 'set inactive' : 'set active';
    if (!confirm(`Are you sure you want to ${actionLabel} ${user.name}?`)) return;

    router.put(toggleStatus(user.id).url, {}, { preserveScroll: true });
}

function handleResetPassword(user: User) {
    if (!confirm(`Reset password for ${user.name}?`)) return;

    router.post(resetPassword(user.id).url, {}, { preserveScroll: true });
}

function handleDelete(user: User) {
    if (!confirm(`Delete account for ${user.name}? This action cannot be undone.`)) return;

    router.delete(destroy(user.id).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-5">
                <CardHeader>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle>Users</CardTitle>
                            <CardDescription>
                                Manage users, assign roles, and control access.
                            </CardDescription>
                        </div>

                        <div class="flex items-center gap-2">
                            <Select
                                :model-value="props.filters.type ?? 'all'"
                                @update:model-value="
                                    (value) => {
                                        const v = String(value);
                                        applyFilters(v === 'all' ? null : v, props.filters.status ?? null);
                                    }
                                "
                            >
                                <SelectTrigger class="w-32">
                                    <SelectValue placeholder="User Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    <SelectItem value="internal">Internal</SelectItem>
                                    <SelectItem value="external">External</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select
                                :model-value="props.filters.status ?? 'all'"
                                @update:model-value="
                                    (value) => {
                                        const v = String(value);
                                        applyFilters(props.filters.type ?? null, v === 'all' ? null : v);
                                    }
                                "
                            >
                                <SelectTrigger class="w-32">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>

                            <Button v-if="canCreate" size="sm" as-child>
                                <Link :href="create().url">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Create User
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="`${index().url}?type=${props.filters.type ?? ''}&status=${props.filters.status ?? ''}`"
                                :initial-value="props.filters.search"
                                placeholder="Search users..."
                                :only="['users', 'filters', 'statuses', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex gap-2 sm:justify-end">
                            <Button size="sm" variant="outline">
                                <Upload class="mr-2 h-4 w-4" />
                                Import
                            </Button>
                            <Button size="sm" variant="outline">
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </div>
                    </div>

                    <Table>
                        <TableCaption>List of Users</TableCaption>

                        <TableHeader>
                            <TableRow>
                                <TableHead>Username</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Email Verification</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead v-if="showCompanyColumn">Company</TableHead>
                                <TableHead>Roles</TableHead>
                                <TableHead class="w-[80px] text-right">Actions</TableHead>
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
                                        :class="emailVerificationBadgeClass(user.email_verified_at)"
                                        class="border"
                                    >
                                        {{ emailVerificationLabel(user.email_verified_at) }}
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        :class="statusBadgeClass(user.status)"
                                        class="border capitalize"
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
                                            :class="roleBadgeClass(role)"
                                            class="border"
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

                                <TableCell class="text-right">
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

                                            <!-- View — always visible -->
                                            <DropdownMenuItem as-child>
                                                <Link
                                                    :href="show(user.id).url"
                                                    class="flex w-full cursor-pointer items-center"
                                                >
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    <span>View Profile</span>
                                                </Link>
                                            </DropdownMenuItem>

                                            <!-- Edit -->
                                            <DropdownMenuItem v-if="canUpdate" as-child>
                                                <Link
                                                    :href="edit(user.id).url"
                                                    class="flex w-full cursor-pointer items-center"
                                                >
                                                    <Pencil class="mr-2 h-4 w-4" />
                                                    <span>Edit Details</span>
                                                </Link>
                                            </DropdownMenuItem>

                                            <!-- Toggle status -->
                                            <DropdownMenuItem
                                                v-if="canToggle"
                                                class="cursor-pointer"
                                                @click="handleToggleStatus(user)"
                                            >
                                                <Power class="mr-2 h-4 w-4" />
                                                <span>{{ isActive(user) ? 'Set Inactive' : 'Set Active' }}</span>
                                            </DropdownMenuItem>

                                            <!-- Reset password -->
                                            <DropdownMenuItem
                                                v-if="canResetPass"
                                                class="cursor-pointer"
                                                @click="handleResetPassword(user)"
                                            >
                                                <KeyRound class="mr-2 h-4 w-4" />
                                                <span>Reset Password</span>
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator v-if="canDelete" />

                                            <!-- Delete -->
                                            <DropdownMenuItem
                                                v-if="canDelete"
                                                class="cursor-pointer text-red-600 focus:text-red-600"
                                                @click="handleDelete(user)"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                <span>Delete Account</span>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="props.users.data.length === 0">
                                <TableCell
                                    :colspan="showCompanyColumn ? 8 : 7"
                                    class="py-8 text-center text-sm text-muted-foreground"
                                >
                                    No users found.
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
