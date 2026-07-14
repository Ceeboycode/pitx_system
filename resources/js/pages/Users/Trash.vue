<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import AppLayout from '@/layouts/AppLayout.vue';
import { index, restore, trash } from '@/routes/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import {
    Archive,
    ArrowLeft,
    MoreHorizontal,
    RotateCcw,
    Users,
} from 'lucide-vue-next';

import { ref } from 'vue';
import { toast } from 'vue-sonner';

type UserArchive = {
    id: number;
    username: string;
    name: string;
    email: string;
    deleted_at_human?: string;
    deleter?: { id: number; name: string } | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: index().url },
    { title: 'Archived Users', href: trash().url },
];

const props = defineProps<{
    users: any;
    filters: { search: string | null };
}>();

const restoringUser = ref<UserArchive | null>(null);
const restoreOpen = ref(false);

function openRestoreDialog(user: UserArchive) {
    restoringUser.value = user;
    restoreOpen.value = true;
}

function confirmRestore() {
    if (!restoringUser.value) return;
    router.patch(
        restore({ user: restoringUser.value.id }).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                restoringUser.value = null;
                restoreOpen.value = false;
            },
            onError: () => toast.error('Failed to restore user.'),
        },
    );
}
</script>

<template>
    <Head title="Archived Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <!-- <span>Change Requests</span> -->
                         <!-- TODO: make the text straight, not wrapped -->
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 mr-2"
                        >
                            <Link :href="index().url">
                                <ArrowLeft class="h-4 w-4" />
                            </Link>
                        </Button>
                        Archives
                        <span class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-rose-500" />
                            <div class="border-7 border-rose-500 rounded-xs">
                                <div class="border-3 border-white rounded-xs"></div>
                            </div>
                        </span>
                    </CardTitle>
                    <CardDescription class="mt-1">
                        Archived users can be restored back to the active user list.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center"
                    >
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="trash().url"
                                :initial-value="filters.search"
                                placeholder="Search archived users..."
                                :only="['users', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="border-y border-slate-200">
                                <TableRow class="gap-2">
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Username</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Name</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Email</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Archived At</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Archived By</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Actions</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody class="border-y border-slate-200">
                                <TableRow
                                    v-if="users.data.length === 0"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="6"
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
                                                    No archived users
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-muted-foreground"
                                                >
                                                    Nothing has been archived
                                                    yet.
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="group transition-colors hover:bg-muted/30"
                                >
                                    <TableCell class="px-0 font-medium">{{
                                        user.username
                                    }}</TableCell>
                                    <TableCell class="px-0">{{ user.name }}</TableCell>
                                    <TableCell
                                        class="px-0 text-sm text-muted-foreground"
                                        >{{ user.email }}</TableCell
                                    >
                                    <TableCell
                                        class="px-0 text-sm text-muted-foreground"
                                        >{{
                                            user.deleted_at_human ?? '—'
                                        }}</TableCell
                                    >
                                    <TableCell
                                        class="px-0 text-sm text-muted-foreground"
                                        >{{
                                            user.deleter?.name ?? '—'
                                        }}</TableCell
                                    >

                                    <TableCell class="text-right px-0">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    class="rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground cursor-pointer"
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
                                                class="w-fit rounded-lg border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    {{ user.username }}
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    class="rounded-lg cursor-pointer hover:bg-slate-100"
                                                    @click="openRestoreDialog(user)"
                                                >
                                                    <RotateCcw
                                                        class="h-4 w-4"
                                                    />
                                                    Restore User
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <InertiaPagination
                        :links="users.links"
                        :meta="{
                            from: users.from,
                            to: users.to,
                            total: users.total,
                        }"
                    />
                </CardContent>
            </Card>
        </div>

        <AlertDialog v-model:open="restoreOpen">
            <AlertDialogContent class="rounded-lg p-4">
                <AlertDialogHeader>
                    <AlertDialogTitle>Restore User</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to restore
                        <span class="font-semibold text-foreground">{{ restoringUser?.name ?? 'this user' }}</span>?
                        They will be moved back to the active users list.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg cursor-pointer hover:bg-slate-100" @click="restoringUser = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        class="rounded-lg border-0 text-white cursor-pointer bg-primary hover:bg-primary/90"
                        @click="confirmRestore"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Restore User
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
