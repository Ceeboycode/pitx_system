<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/can';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import { Button } from '@/components/ui/button';

/* =======================
   Types
======================= */
interface UserResource {
    id: number;
    name: string;
    email: string;
    roles?: string[];
    created_at?: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

/* =======================
   Props (MATCH LARAVEL)
======================= */
const props = defineProps<{
    users: {
        data: UserResource[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            links: Array<PaginationLink | null>;
        };
    };
}>();

/* =======================
   Computed
======================= */
const paginationLinks = computed(() =>
    props.users.meta.links.filter(
        (link): link is PaginationLink => link !== null,
    ),
);

/* =======================
   Breadcrumbs
======================= */

const dialogOpen = ref(false);
const deleteInput = ref('');
const deletingUserId = ref<number | null>(null);

const openDeleteDialog = (id: number) => {
    deletingUserId.value = id;
    deleteInput.value = '';
    dialogOpen.value = true;
};

const confirmDelete = () => {
    if (deleteInput.value !== 'delete' || deletingUserId.value === null) {
        return;
    }

    router.delete(destroy(deletingUserId.value).url, {
        preserveScroll: true,
        onFinish: () => {
            dialogOpen.value = false;
            deleteInput.value = '';
            deletingUserId.value = null;
        },
    });
};

import {
    create,
    destroy,
    edit,
    index,
    show,
} from '@/routes/users';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users Table',
        href: index().url,
    },
];
</script>

<template>
    <Head title="Users Table" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <Card class="w-full">
                <!-- Header -->
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Users</CardTitle>
                        <CardDescription>
                            A list of all registered users
                        </CardDescription>
                    </div>

                    <Button v-if="can('users.create')" as-child size="sm">
                        <Link :href="create().url"> Create New User </Link>
                    </Button>

                </CardHeader>
                <!-- Table -->
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Roles</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <!-- Table body -->
                        <TableBody v-if="props.users.data.length">
                            <TableRow
                                v-for="user in props.users.data"
                                :key="user.id"
                            >
                                <TableCell>{{ user.id }}</TableCell>
                                <TableCell class="font-medium">
                                    {{ user.name }}
                                </TableCell>
                                <TableCell>{{ user.email }}</TableCell>
                                <TableCell>
                                    {{ user.roles?.join(', ') ?? '—' }}
                                </TableCell>
                                <TableCell>
                                    {{ user.created_at ?? '—' }}
                                </TableCell>
                                <TableCell class="space-x-2">
                                    <Button
                                        as-child
                                        variant="outline"
                                        size="sm"
                                        v-if="can('users.edit')"
                                    >
                                        <Link :href="edit(user.id).url">
                                            Edit
                                        </Link>
                                    </Button>

                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="openDeleteDialog(user.id)"
                                        v-if="can('users.delete')"
                                    >
                                        Delete
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>

                        <!-- Empty state -->
                        <TableBody v-else>
                            <TableRow>
                                <TableCell
                                    colspan="5"
                                    class="h-24 text-center text-muted-foreground"
                                >
                                    No users found
                                </TableCell>
                            </TableRow>
                        </TableBody>
                        <Dialog v-model:open="dialogOpen">
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Delete user</DialogTitle>
                                    <DialogDescription>
                                        This action cannot be undone.
                                        <br />
                                        Please type
                                        <span
                                            class="font-semibold text-foreground"
                                            >delete</span
                                        >
                                        to confirm.
                                    </DialogDescription>
                                </DialogHeader>

                                <Input
                                    v-model="deleteInput"
                                    placeholder="Type delete to confirm"
                                />

                                <DialogFooter>
                                    <Button
                                        variant="outline"
                                        @click="dialogOpen = false"
                                    >
                                        Cancel
                                    </Button>

                                    <Button
                                        variant="destructive"
                                        :disabled="deleteInput !== 'delete'"
                                        @click="confirmDelete"
                                    >
                                        Confirm delete
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </Table>
                </CardContent>

                <!-- Pagination Footer -->
                <CardFooter class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">
                        Page {{ props.users.meta.current_page }} of
                        {{ props.users.meta.last_page }}
                    </span>

                    <div class="flex items-center gap-1">
                        <Button
                            v-for="link in paginationLinks"
                            :key="link.label"
                            as-child
                            variant="outline"
                            size="sm"
                            :disabled="!link.url"
                            :class="{
                                'bg-primary text-primary-foreground':
                                    link.active,
                            }"
                        >
                            <Link
                                :href="link.url ?? ''"
                                preserve-scroll
                                v-html="link.label"
                            />
                        </Button>
                    </div>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
