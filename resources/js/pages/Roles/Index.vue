<script setup lang="ts">
/* ======================================================
   Layout, Routing & Inertia
====================================================== */
import AppLayout from '@/layouts/AppLayout.vue';
import { create, destroy, edit, index } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Permission = {
    id: number;
    name: string;
};

type Role = {
    id: number;
    name: string;
    type: 'internal' | 'external';
    permissions: Permission[];
};

const props = defineProps<{
    roles: {
        data: Role[];
        links: any[];
    };
    filters: {
        search?: string | null;
        type?: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: index().url }];

// Filters (bind to inputs)
const search = ref(props.filters.search ?? '');
const roleType = ref(props.filters.type ?? 'all'); // all | internal | external

let filterTimer: number | null = null;

function applyFilters() {
    router.get(
        index().url,
        {
            search: search.value || undefined,
            type: roleType.value === 'all' ? undefined : roleType.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['roles', 'filters', 'flash'],
        },
    );
}

watch([search, roleType], () => {
    if (filterTimer) window.clearTimeout(filterTimer);
    filterTimer = window.setTimeout(() => applyFilters(), 350);
});

/* Delete dialog */
const open = ref(false);
const selectedRole = ref<Role | null>(null);
const confirmation = ref('');
const processing = ref(false);

const canConfirmDelete = computed(() => confirmation.value.trim() === 'DELETE');

watch(deleteOpen, (val) => { if (val) confirmation.value = ''; });

function openDelete(role: Role) {
    selectedRole.value = role;
    open.value = true;
}

function deleteRole() {
    if (!canDelete.value || processing.value || !selectedRole.value) return;

    processing.value = true;
    router.delete(destroy({ role: selectedRole.value.id }).url, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            open.value = false;
            selectedRole.value = null;
        },
    });
}
</script>

<template>
    <Head title="Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-10 mt-3">
                <CardHeader>
                    <CardTitle>Roles</CardTitle>
                    <CardDescription
                        >Manage the roles of your application.</CardDescription
                    >

                    <CardAction>
                        <Button
                            variant="default"
                            size="sm"
                            as-child
                            class="mr-2"
                        >
                            <Link :href="create().url">Create Role</Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">

                    <!-- Filters -->
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-full sm:max-w-sm">
                            <Input
                                v-model="search"
                                placeholder="Search roles..."
                            />
                        </div>

                        <div class="w-full sm:w-48">
                            <Select v-model="roleType">
                                <SelectTrigger class="rounded-lg border-slate-200 focus:ring-blue-500">
                                    <SelectValue placeholder="Filter by type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all"
                                        >All types</SelectItem
                                    >
                                    <SelectItem value="internal"
                                        >Internal</SelectItem
                                    >
                                    <SelectItem value="external"
                                        >External</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <Table>
                        <TableCaption
                            >List of roles in the system.</TableCaption
                        >

                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Permissions</TableHead>
                                <TableHead class="text-right"
                                    >Actions</TableHead
                                >
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="role in props.roles.data"
                                :key="role.id"
                            >
                                <TableCell class="font-medium capitalize">
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
                                            <Button variant="outline" size="sm">
                                                {{ role.permissions.length }}
                                                permissions
                                            </Button>
                                        </PopoverTrigger>

                                        <PopoverContent
                                            class="max-h-60 w-80 overflow-y-auto"
                                        >
                                            <div class="flex flex-wrap gap-2">
                                                <span
                                                    v-for="p in role.permissions"
                                                    :key="p.id"
                                                    class="rounded-md bg-muted px-2 py-1 text-xs"
                                                >
                                                    {{ p.name }}
                                                </span>
                                            </div>
                                        </PopoverContent>
                                    </Popover>
                                </TableCell>

                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            variant="default"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    edit({ role: role.id }).url
                                                "
                                            >
                                                <Pencil class="mr-2 h-4 w-4" />
                                                Edit
                                            </Link>
                                        </Button>

                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            @click="openDelete(role)"
                                            class="cursor-pointer"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Delete
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="!props.roles.data.length">
                                <TableCell
                                    colspan="4"
                                    class="py-6 text-center text-muted-foreground"
                                >
                                    No roles found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

            <AlertDialog v-model:open="open">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle
                            >Delete Role Permanently</AlertDialogTitle
                        >

                        <AlertDialogDescription class="space-y-3">
                            <p>
                                This action cannot be undone. It will
                                permanently delete
                                <span class="font-medium">
                                    {{ selectedRole?.name }}
                                </span>
                                and remove it from the system.
                            </p>

                            <p class="text-sm text-muted-foreground">
                                To confirm, please type
                                <span
                                    class="mx-1 font-mono font-semibold text-destructive/80"
                                >
                                    DELETE
                                </span>
                                below.
                            </p>

                            <Input
                                v-model="confirmation"
                                placeholder="Type DELETE to confirm"
                                class="mt-2"
                            />
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>

                        <AlertDialogAction
                            :disabled="!canDelete || processing"
                            @click="deleteRole"
                            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            <Trash2 class="mr-2 h-4 w-4" />
                            {{
                                processing
                                    ? 'Deleting...'
                                    : 'Delete Permanently'
                            }}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AppLayout>
</template>
