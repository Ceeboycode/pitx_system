<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import { create, destroy, edit, index } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { can } from '@/lib/can';
import { AlertTriangle } from 'lucide-vue-next'
import { Plus, Edit, Trash2 } from 'lucide-vue-next';


/* -------------------------------------------------------------------------- */
/* Types                                                                       */
/* -------------------------------------------------------------------------- */
interface Permission {
    id: number;
    name: string;
}

interface RawRole {
    id: number;
    name: string;
    permissions?: Permission[] | { data?: Permission[] };
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

/* -------------------------------------------------------------------------- */
/* Props                                                                       */
/* -------------------------------------------------------------------------- */
const props = defineProps<{
    roles: {
        data: RawRole[];
        meta: {
            current_page: number;
            last_page: number;
        };
    };
}>();

/* -------------------------------------------------------------------------- */
/* Normalize roles (🔥 FIX)                                                    */
/* -------------------------------------------------------------------------- */
const roles = computed<Role[]>(() =>
    props.roles.data.map((role) => ({
        id: role.id,
        name: role.name,
        permissions: Array.isArray(role.permissions)
            ? role.permissions
            : (role.permissions?.data ?? []),
    })),
);

/* -------------------------------------------------------------------------- */
/* Breadcrumbs                                                                 */
/* -------------------------------------------------------------------------- */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: index().url,
    },
];

/* -------------------------------------------------------------------------- */
/* Delete dialog state                                                        */
/* -------------------------------------------------------------------------- */
const dialogOpen = ref(false);
const deleteInput = ref('');
const deletingRoleId = ref<number | null>(null);

const openDeleteDialog = (id: number) => {
    deletingRoleId.value = id;
    deleteInput.value = '';
    dialogOpen.value = true;
};

const confirmDelete = () => {
    if (deleteInput.value !== 'delete' || deletingRoleId.value === null) {
        return;
    }

    router.delete(destroy(deletingRoleId.value).url, {
        preserveScroll: true,
        onFinish: () => {
            dialogOpen.value = false;
            deleteInput.value = '';
            deletingRoleId.value = null;
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <Card class="w-full">
                <!-- Header -->
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Roles</CardTitle>
                        <CardDescription>
                            A list of all roles and their permissions
                        </CardDescription>
                    </div>

                    <Link :href="create().url" v-if="can('roles.create')">
                        <Button size="sm"> <Plus /> Create New Role </Button>
                    </Link>
                </CardHeader>

                <!-- Table -->
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Permissions</TableHead>
                                <TableHead class="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody v-if="roles.length">
                            <TableRow v-for="role in roles" :key="role.id">
                                <TableCell>{{ role.id }}</TableCell>

                                <TableCell class="font-medium">
                                    {{ role.name }}
                                </TableCell>

                                <!-- ✅ SAFE PERMISSIONS -->
                                <TableCell>
                                    <span
                                        v-if="role.permissions.length"
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{
                                            role.permissions
                                                .map((p) => p.name)
                                                .join(', ')
                                        }}
                                    </span>

                                    <span v-else class="text-muted-foreground">
                                        —
                                    </span>
                                </TableCell>

                                <TableCell class="space-x-2 text-right">

                                        <Button
                                            as-child
                                            variant="default"
                                            size="sm"
                                            v-if="can('roles.edit')"
                                        >
                                        <Link :href="edit(role.id).url"> <Edit/>
                                            Edit
                                        </Link>
                                        </Button>

                                    <Button
                                        v-if="can('roles.delete')"
                                        variant="destructive"
                                        size="sm"
                                        @click="openDeleteDialog(role.id)"
                                    >
                                    <Trash2/>
                                        Delete
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>

                        <TableBody v-else>
                            <TableRow>
                                <TableCell
                                    colspan="4"
                                    class="h-24 text-center text-muted-foreground"
                                >
                                    No roles found
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <Dialog v-model:open="dialogOpen">
                        <DialogContent class="sm:max-w-md" :disableOutsidePointerEvents="true">
                            <DialogHeader class="space-y-3">
                            <div class="flex items-center gap-2 text-destructive">
                                <AlertTriangle class="h-5 w-5" />
                                <DialogTitle>Delete Role</DialogTitle>
                            </div>

                            <DialogDescription>
                                This action is
                                <span class="font-semibold text-destructive"> permanent</span>.
                                Type <strong>delete</strong> to confirm.
                            </DialogDescription>
                            </DialogHeader>

                            <Input
                            v-model="deleteInput"
                            placeholder="Type delete to confirm"
                            class="focus-visible:ring-destructive"
                            />

                            <DialogFooter class="gap-2">
                            <Button
                                variant="outline"
                                @click="dialogOpen = false"
                            >
                                Cancel
                            </Button>

                            <Button
                                variant="destructive"
                                :disabled="deleteInput.trim().toLowerCase() !== 'delete'"
                                @click="confirmDelete"
                            >
                                Permanently Delete
                            </Button>
                            </DialogFooter>
                        </DialogContent>
                        </Dialog>
                </CardContent>

                <!-- Pagination -->
                <CardFooter class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">
                        Page {{ props.roles.meta.current_page }} of
                        {{ props.roles.meta.last_page }}
                    </span>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
