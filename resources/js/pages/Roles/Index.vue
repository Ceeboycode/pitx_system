<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create, destroy, edit, index } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
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
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
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
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import { can } from '@/lib/can';

import {
    ChevronRight,
    Key,
    MoreHorizontal,
    Pencil,
    Plus,
    ShieldCheck,
    Trash2,
} from 'lucide-vue-next';

/* ── Permissions ─────────────────────────────────────────────────── */
const canCreate = can('roles.create');
const canUpdate = can('roles.update');
const canDelete = can('roles.delete');

/* ── Types ──────────────────────────────────────────────────────── */
type Permission = { id: number; name: string };

type Role = {
    id: number;
    name: string;
    type: 'internal' | 'external';
    permissions: Permission[];
};

/* ── Props ───────────────────────────────────────────────────────── */
const props = defineProps<{
    roles: { data: Role[]; links: any[] };
    filters: { search?: string | null; type?: string | null };
}>();

/* ── Breadcrumbs ─────────────────────────────────────────────────── */
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: index().url }];

/* ── Filters ─────────────────────────────────────────────────────── */
const search   = ref(props.filters.search ?? '');
const roleType = ref(props.filters.type ?? 'all');

let filterTimer: number | null = null;
function applyFilters() {
    router.get(
        index().url,
        {
            search: search.value || undefined,
            type: roleType.value === 'all' ? undefined : roleType.value,
        },
        { preserveScroll: true, preserveState: true, replace: true, only: ['roles', 'filters', 'flash'] },
    );
}

watch([search, roleType], () => {
    if (filterTimer) window.clearTimeout(filterTimer);
    filterTimer = window.setTimeout(() => applyFilters(), 350);
});

/* ── Type badge ──────────────────────────────────────────────────── */
function typeClass(type: Role['type']): string {
    return type === 'internal'
        ? 'bg-blue-100 text-blue-700 border-blue-200'
        : 'bg-violet-100 text-violet-700 border-violet-200';
}

/* ── Delete dialog ───────────────────────────────────────────────── */
const deleteOpen    = ref(false);
const selectedRole  = ref<Role | null>(null);
const confirmation  = ref('');
const processing    = ref(false);

const canConfirmDelete = computed(() => confirmation.value.trim() === 'DELETE');

watch(deleteOpen, (val) => { if (val) confirmation.value = ''; });

function openDelete(role: Role) {
    selectedRole.value = role;
    deleteOpen.value   = true;
}

function deleteRole() {
    if (!canConfirmDelete.value || processing.value || !selectedRole.value) return;
    processing.value = true;
    router.delete(destroy({ role: selectedRole.value.id }).url, {
        preserveScroll: true,
        onFinish: () => {
            processing.value   = false;
            deleteOpen.value   = false;
            selectedRole.value = null;
        },
    });
}
</script>

<template>
    <Head title="Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-5">
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <ShieldCheck class="h-5 w-5 text-blue-700" />
                            Roles
                        </CardTitle>
                        <CardDescription class="mt-1">
                            Manage the roles and their permissions.
                        </CardDescription>
                    </div>

                    <CardAction>
                        <Button
                            v-if="canCreate"
                            as-child
                            size="sm"
                            class="rounded-lg bg-blue-700 text-white hover:bg-blue-800 border-0 shadow-sm"
                        >
                            <Link :href="create().url">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Role
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">

                    <!-- Filters -->
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="w-full max-w-sm">
                            <Input
                                v-model="search"
                                placeholder="Search roles…"
                                class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                            />
                        </div>
                        <div class="w-full sm:w-48">
                            <Select v-model="roleType">
                                <SelectTrigger class="rounded-lg border-slate-200 focus:ring-blue-500">
                                    <SelectValue placeholder="Filter by type" />
                                </SelectTrigger>
                                <SelectContent class="rounded-xl">
                                    <SelectItem value="all" class="rounded-lg">All types</SelectItem>
                                    <SelectItem value="internal" class="rounded-lg">Internal</SelectItem>
                                    <SelectItem value="external" class="rounded-lg">External</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Name</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Type</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Permissions</TableHead>
                                    <TableHead class="text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <!-- Empty state -->
                                <TableRow v-if="!props.roles.data.length" class="hover:bg-transparent">
                                    <TableCell colspan="4" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                                <ShieldCheck class="h-6 w-6 text-muted-foreground/40" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-foreground">No roles found</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">Try adjusting your search or create a new role.</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="role in props.roles.data"
                                    :key="role.id"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <!-- Name -->
                                    <TableCell class="text-sm font-semibold capitalize">
                                        {{ role.name }}
                                    </TableCell>

                                    <!-- Type -->
                                    <TableCell>
                                        <Badge :class="typeClass(role.type)">
                                            {{ role.type === 'internal' ? 'Internal' : 'External' }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Permissions -->
                                    <TableCell>
                                        <span v-if="!role.permissions?.length" class="text-sm text-muted-foreground">
                                            No permissions
                                        </span>

                                        <Popover v-else>
                                            <PopoverTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-7 rounded-lg border-slate-200 text-xs text-slate-600 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-200"
                                                >
                                                    <Key class="mr-1.5 h-3 w-3" />
                                                    {{ role.permissions.length }}
                                                    permission{{ role.permissions.length !== 1 ? 's' : '' }}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent class="w-80 max-h-60 overflow-y-auto rounded-xl p-0">
                                                <div class="border-b border-slate-100 px-4 py-3">
                                                    <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Permissions</p>
                                                    <p class="text-sm font-semibold capitalize">{{ role.name }}</p>
                                                </div>
                                                <div class="flex flex-wrap gap-1.5 p-3">
                                                    <span
                                                        v-for="p in role.permissions"
                                                        :key="p.id"
                                                        class="rounded-md bg-muted px-2 py-0.5 font-mono text-xs text-muted-foreground"
                                                    >
                                                        {{ p.name }}
                                                    </span>
                                                </div>
                                            </PopoverContent>
                                        </Popover>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <!-- Show dropdown only if user has at least one action -->
                                        <DropdownMenu v-if="canUpdate || canDelete">
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                                                >
                                                    <MoreHorizontal class="h-4 w-4" />
                                                    <span class="sr-only">Open actions</span>
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="w-48 rounded-xl border-slate-200 shadow-lg">
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                                    {{ role.name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    v-if="canUpdate"
                                                    as-child
                                                    class="rounded-lg text-amber-600 focus:bg-amber-50 focus:text-amber-700"
                                                >
                                                    <Link :href="edit({ role: role.id }).url" class="flex items-center">
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Edit
                                                        <ChevronRight class="ml-auto h-3.5 w-3.5 text-amber-400" />
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator v-if="canUpdate && canDelete" />

                                                <DropdownMenuItem
                                                    v-if="canDelete"
                                                    class="rounded-lg text-rose-600 focus:bg-rose-50 focus:text-rose-600"
                                                    @click="openDelete(role)"
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
                </CardContent>
            </Card>
        </div>

        <!-- ── Delete dialog ──────────────────────────────────────── -->
        <AlertDialog v-if="canDelete" v-model:open="deleteOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Role Permanently</AlertDialogTitle>
                    <AlertDialogDescription class="space-y-3">
                        <p>
                            This action cannot be undone. It will permanently delete
                            <span class="font-semibold text-foreground">{{ selectedRole?.name }}</span>
                            and remove it from the system.
                        </p>
                        <p class="text-sm text-muted-foreground">
                            To confirm, type
                            <code class="mx-1 rounded bg-muted px-1.5 py-0.5 font-mono text-xs font-semibold text-rose-600">DELETE</code>
                            below.
                        </p>
                        <Input
                            v-model="confirmation"
                            placeholder="Type DELETE to confirm"
                            class="rounded-lg border-slate-200 focus-visible:ring-rose-500"
                        />
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        :disabled="!canConfirmDelete || processing"
                        class="rounded-lg bg-rose-600 text-white hover:bg-rose-700 border-0 font-semibold disabled:opacity-50"
                        @click="deleteRole"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        {{ processing ? 'Deleting…' : 'Delete Permanently' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </AppLayout>
</template>