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
    CardAction,
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
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
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
    edit,
    index,
    resetPassword,
    show,
    toggleStatus,
    trash,
} from '@/routes/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

/* ======================================================
   Icons
====================================================== */
import {
    Archive,
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronRight,
    Eye,
    Filter,
    KeyRound,
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    Users,
    X,
} from 'lucide-vue-next';

/* ======================================================
   Vue Core
====================================================== */
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

/* ======================================================
   Permissions
====================================================== */
import { can } from '@/lib/can';

const canCreate = can('users.create');
const canUpdate = can('users.update');
const canToggle = can('users.toggleStatus');
const canResetPass = can('users.resetPassword');
const canViewTrash = can('users.viewTrash');

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
    avatar: string | null;
    phone_number: string | null;
    company_id: number | null;
    company: Company | null;
    roles: Role[];
    status: 'active' | 'inactive' | string;
}

type SortField = 'username' | 'name' | 'email' | 'status' | null;
type SortDir = 'asc' | 'desc';

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
        sort_by?: SortField;
        sort_dir?: SortDir;
    };
    statuses?: string[];
    canSeeSuperAdmin?: boolean;
    currentUserId: number;
}>();

/* ======================================================
   Filter & Sort state
====================================================== */
const typeFilter = ref<string>(props.filters.type ?? 'all');
const statusFilter = ref<string>(props.filters.status ?? 'all');
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');

const hasActiveFilters = computed(
    () =>
        (typeFilter.value && typeFilter.value !== 'all') ||
        (statusFilter.value && statusFilter.value !== 'all') ||
        sortBy.value !== null,
);

function applyFilters(
    overrides: Record<string, string | null | undefined> = {},
) {
    router.get(
        index().url,
        {
            search: props.filters.search ?? undefined,
            type: typeFilter.value !== 'all' ? typeFilter.value : undefined,
            status:
                statusFilter.value !== 'all' ? statusFilter.value : undefined,
            sort_by: sortBy.value ?? undefined,
            sort_dir: sortBy.value ? sortDir.value : undefined,
            ...overrides,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['users', 'filters', 'statuses', 'flash'],
        },
    );
}

function onTypeChange(val: string) {
    typeFilter.value = val;
    applyFilters();
}

function onStatusChange(val: string) {
    statusFilter.value = val;
    applyFilters();
}

function toggleSort(field: SortField) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function clearFilters() {
    typeFilter.value = 'all';
    statusFilter.value = 'all';
    sortBy.value = null;
    sortDir.value = 'asc';

    applyFilters({
        type: undefined,
        status: undefined,
        sort_by: undefined,
        sort_dir: undefined,
    });
}

/* ======================================================
   Computed
====================================================== */
const showCompanyColumn = computed(() => typeFilter.value !== 'internal');

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
   Sort icon helpers
====================================================== */
function sortIcon(field: SortField) {
    if (sortBy.value !== field) return ArrowUpDown;
    return sortDir.value === 'asc' ? ArrowUp : ArrowDown;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field
        ? 'text-blue-600'
        : 'text-muted-foreground/40';
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

function initials(name: string) {
    const parts = name.trim().split(/\s+/).filter(Boolean).slice(0, 2);

    return parts.map((part) => part.charAt(0).toUpperCase()).join('') || 'U';
}

function isOwnAccount(user: User) {
    return user.id === props.currentUserId;
}

/* ======================================================
   Actions
====================================================== */
function handleToggleStatus(user: User) {
    const actionLabel = isActive(user) ? 'set inactive' : 'set active';
    if (!confirm(`Are you sure you want to ${actionLabel} ${user.name}?`)) {
        toast.info('Status update cancelled.');
        return;
    }

    router.put(
        toggleStatus(user.id).url,
        {},
        {
            preserveScroll: true,
            onError: () => toast.error('Failed to update user status.'),
        },
    );
}

function handleResetPassword(user: User) {
    if (!confirm(`Reset password for ${user.name}?`)) {
        toast.info('Password reset cancelled.');
        return;
    }

    router.post(
        resetPassword(user.id).url,
        {},
        {
            preserveScroll: true,
            onError: () => toast.error('Failed to reset password.'),
        },
    );
}
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Card>
                <CardHeader
                    class="flex flex-col gap-4 pb-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-2">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5 text-blue-700" />
                                User List
                            </CardTitle>
                            <CardDescription
                                >Manage users, assign roles, and control
                                access.</CardDescription
                            >
                        </div>
                    </div>

                    <CardAction class="flex gap-2">
                        <Button size="sm" variant="outline" as-child>
                            <Link
                                :href="trash().url"
                                class="flex items-center gap-1.5"
                            >
                                <Archive class="h-4 w-4" />
                                View Archived
                            </Link>
                        </Button>

                        <Button
                            v-if="canCreate"
                            size="sm"
                            variant="blue"
                            as-child
                        >
                            <Link
                                :href="create().url"
                                class="flex items-center gap-1.5"
                            >
                                <Plus class="h-4 w-4" />
                                New User
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <Separator />

                <CardContent class="space-y-4 pt-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="`${index().url}?type=${typeFilter !== 'all' ? typeFilter : ''}&status=${statusFilter !== 'all' ? statusFilter : ''}&sort_by=${sortBy ?? ''}&sort_dir=${sortBy ? sortDir : ''}`"
                                :initial-value="props.filters.search"
                                placeholder="Search users..."
                                :only="[
                                    'users',
                                    'filters',
                                    'statuses',
                                    'flash',
                                ]"
                                :debounce="350"
                            />
                        </div>
                    </div>

                    <!-- Row 2: Filters + Sort -->
                    <div class="flex flex-wrap items-center gap-2">
                        <div
                            class="flex items-center gap-1.5 text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            <Filter class="h-3.5 w-3.5" />
                            Filter
                        </div>

                        <Select
                            :model-value="typeFilter"
                            @update:model-value="onTypeChange"
                        >
                            <SelectTrigger
                                class="h-8 w-36 rounded-lg border-slate-200 text-xs"
                            >
                                <SelectValue placeholder="All Types" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="all" class="text-xs"
                                    >All Types</SelectItem
                                >
                                <SelectItem value="internal" class="text-xs"
                                    >Internal</SelectItem
                                >
                                <SelectItem value="external" class="text-xs"
                                    >External</SelectItem
                                >
                            </SelectContent>
                        </Select>

                        <Select
                            :model-value="statusFilter"
                            @update:model-value="onStatusChange"
                        >
                            <SelectTrigger
                                class="h-8 w-36 rounded-lg border-slate-200 text-xs"
                            >
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="all" class="text-xs"
                                    >All Statuses</SelectItem
                                >
                                <SelectItem value="active" class="text-xs"
                                    >Active</SelectItem
                                >
                                <SelectItem value="inactive" class="text-xs"
                                    >Inactive</SelectItem
                                >
                            </SelectContent>
                        </Select>

                        <div
                            class="ml-2 flex items-center gap-1.5 text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            <ArrowUpDown class="h-3.5 w-3.5" />
                            Sort
                        </div>

                        <Select
                            :model-value="sortBy ?? 'none'"
                            @update:model-value="
                                (val) => {
                                    sortBy =
                                        val === 'none'
                                            ? null
                                            : (val as SortField);
                                    applyFilters();
                                }
                            "
                        >
                            <SelectTrigger
                                class="h-8 w-36 rounded-lg border-slate-200 text-xs"
                            >
                                <SelectValue placeholder="Sort by…" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="none" class="text-xs"
                                    >No Sort</SelectItem
                                >
                                <SelectItem value="username" class="text-xs"
                                    >Username</SelectItem
                                >
                                <SelectItem value="name" class="text-xs"
                                    >Name</SelectItem
                                >
                                <SelectItem value="email" class="text-xs"
                                    >Email</SelectItem
                                >
                                <SelectItem value="status" class="text-xs"
                                    >Status</SelectItem
                                >
                            </SelectContent>
                        </Select>

                        <Button
                            v-if="sortBy"
                            size="sm"
                            variant="outline"
                            class="h-8 rounded-lg border-slate-200 px-3 text-xs text-slate-600 hover:bg-slate-100"
                            @click="
                                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                                applyFilters();
                            "
                        >
                            <ArrowUp
                                v-if="sortDir === 'asc'"
                                class="mr-1.5 h-3.5 w-3.5 text-blue-600"
                            />
                            <ArrowDown
                                v-else
                                class="mr-1.5 h-3.5 w-3.5 text-blue-600"
                            />
                            {{ sortDir === 'asc' ? 'Ascending' : 'Descending' }}
                        </Button>

                        <div
                            v-if="hasActiveFilters"
                            class="ml-auto flex items-center gap-2"
                        >
                            <Badge
                                class="gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 hover:bg-blue-50"
                            >
                                <Filter class="h-3 w-3" />
                                Filters active
                            </Badge>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-7 rounded-lg px-2 text-xs text-muted-foreground hover:text-rose-600"
                                @click="clearFilters"
                            >
                                <X class="mr-1 h-3.5 w-3.5" />
                                Clear
                            </Button>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('username')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Username
                                            <component
                                                :is="sortIcon('username')"
                                                class="h-3.5 w-3.5"
                                                :class="
                                                    sortIconClass('username')
                                                "
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('name')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Name
                                            <component
                                                :is="sortIcon('name')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('name')"
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('email')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Email
                                            <component
                                                :is="sortIcon('email')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('email')"
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                    >
                                        Verification
                                    </TableHead>

                                    <TableHead
                                        class="cursor-pointer text-[11px] font-bold tracking-widest text-muted-foreground uppercase select-none hover:text-foreground"
                                        @click="toggleSort('status')"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            Status
                                            <component
                                                :is="sortIcon('status')"
                                                class="h-3.5 w-3.5"
                                                :class="sortIconClass('status')"
                                            />
                                        </div>
                                    </TableHead>

                                    <TableHead
                                        v-if="showCompanyColumn"
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                    >
                                        Company
                                    </TableHead>

                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                    >
                                        Roles
                                    </TableHead>

                                    <TableHead
                                        class="text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                    >
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow
                                    v-if="props.users.data.length === 0"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell
                                        :colspan="showCompanyColumn ? 8 : 7"
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted"
                                            >
                                                <Users
                                                    class="h-6 w-6 text-muted-foreground/40"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-foreground"
                                                >
                                                    No users found
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        hasActiveFilters
                                                            ? 'Try adjusting your filters or search.'
                                                            : 'Try adjusting your search.'
                                                    }}
                                                </p>
                                            </div>
                                            <Button
                                                v-if="hasActiveFilters"
                                                size="sm"
                                                variant="outline"
                                                class="mt-1 h-8 rounded-lg text-xs"
                                                @click="clearFilters"
                                            >
                                                <X class="mr-1.5 h-3.5 w-3.5" />
                                                Clear filters
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="user in props.users.data"
                                    :key="user.id"
                                    class="group transition-colors hover:bg-muted/30"
                                >
                                    <TableCell class="font-medium">
                                        {{ user.username }}
                                    </TableCell>

                                    <TableCell>
                                        <div class="flex items-center gap-2.5">
                                            <img
                                                v-if="user.avatar"
                                                :src="user.avatar"
                                                :alt="`${user.name} profile photo`"
                                                class="h-8 w-8 rounded-full border border-slate-200 object-cover"
                                            />
                                            <div
                                                v-else
                                                class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-[11px] font-semibold text-slate-600"
                                            >
                                                {{ initials(user.name) }}
                                            </div>

                                            <span>{{ user.name }}</span>
                                        </div>
                                    </TableCell>

                                    <TableCell
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ user.email }}
                                    </TableCell>

                                    <TableCell>
                                        <Badge
                                            :class="
                                                emailVerificationBadgeClass(
                                                    user.email_verified_at,
                                                )
                                            "
                                            class="border"
                                        >
                                            {{
                                                emailVerificationLabel(
                                                    user.email_verified_at,
                                                )
                                            }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell>
                                        <Badge
                                            :class="
                                                statusBadgeClass(user.status)
                                            "
                                            class="border capitalize"
                                        >
                                            {{ user.status }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell
                                        v-if="showCompanyColumn"
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{
                                            visibleRoles(user).some(
                                                (r) => r.type === 'external',
                                            )
                                                ? (user.company?.company_name ??
                                                  '-')
                                                : '-'
                                        }}
                                    </TableCell>

                                    <TableCell>
                                        <div
                                            class="flex flex-wrap gap-1 capitalize"
                                        >
                                            <Badge
                                                v-for="role in visibleRoles(
                                                    user,
                                                )"
                                                :key="role.id"
                                                :class="roleBadgeClass(role)"
                                                class="border"
                                            >
                                                {{ role.name }}
                                            </Badge>
                                            <span
                                                v-if="
                                                    visibleRoles(user)
                                                        .length === 0
                                                "
                                                class="text-sm text-muted-foreground"
                                            >
                                                -
                                            </span>
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                                                >
                                                    <MoreHorizontal
                                                        class="h-4 w-4"
                                                    />
                                                    <span class="sr-only"
                                                        >Open actions</span
                                                    >
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                class="w-52 rounded-xl border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    {{ user.username }}
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-blue-700 focus:bg-blue-50 focus:text-blue-700"
                                                >
                                                    <Link
                                                        :href="
                                                            show(user.id).url
                                                        "
                                                        class="flex items-center"
                                                    >
                                                        <Eye
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        View Profile
                                                        <ChevronRight
                                                            class="ml-auto h-3.5 w-3.5 text-blue-400"
                                                        />
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="
                                                        canUpdate &&
                                                        !isOwnAccount(user)
                                                    "
                                                    as-child
                                                >
                                                    <Link
                                                        :href="
                                                            edit(user.id).url
                                                        "
                                                        class="rounded-lg text-amber-600 focus:bg-amber-50 focus:text-amber-700"
                                                    >
                                                        <Pencil
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        Edit Details
                                                        <ChevronRight
                                                            class="ml-auto h-3.5 w-3.5 text-amber-400"
                                                        />
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="
                                                        canToggle &&
                                                        !isOwnAccount(user)
                                                    "
                                                    class="rounded-lg text-rose-700 focus:bg-rose-50 focus:text-rose-700"
                                                    @click="
                                                        handleToggleStatus(user)
                                                    "
                                                >
                                                    <Power
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    {{
                                                        isActive(user)
                                                            ? 'Set Inactive'
                                                            : 'Set Active'
                                                    }}
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="
                                                        canResetPass &&
                                                        !isOwnAccount(user)
                                                    "
                                                    class="rounded-lg text-blue-700 focus:bg-blue-50 focus:text-blue-700"
                                                    @click="
                                                        handleResetPassword(
                                                            user,
                                                        )
                                                    "
                                                >
                                                    <KeyRound
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    Reset Password
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="isOwnAccount(user)"
                                                    disabled
                                                    class="pointer-events-none rounded-lg text-muted-foreground"
                                                >
                                                    You cannot manage your own
                                                    account here
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

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
