<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

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

function handleRestore(user: UserArchive) {
    if (!confirm(`Restore ${user.name}?`)) {
        toast.info('Restore cancelled.');
        return;
    }

    router.patch(
        restore({ user: user.id }).url,
        {},
        {
            preserveScroll: true,
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
                            <hr class="h-px w-full border border-rose-500 " />
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

                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Username</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Name</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Email</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Archived At</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Archived By</TableHead
                                    >
                                    <TableHead
                                        class="text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Actions</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody>
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
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <TableCell class="font-medium">{{
                                        user.username
                                    }}</TableCell>
                                    <TableCell>{{ user.name }}</TableCell>
                                    <TableCell
                                        class="text-sm text-muted-foreground"
                                        >{{ user.email }}</TableCell
                                    >
                                    <TableCell
                                        class="text-sm text-muted-foreground"
                                        >{{
                                            user.deleted_at_human ?? '—'
                                        }}</TableCell
                                    >
                                    <TableCell
                                        class="text-sm text-muted-foreground"
                                        >{{
                                            user.deleter?.name ?? '—'
                                        }}</TableCell
                                    >

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
                                                    class="rounded-lg text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700"
                                                    @click="handleRestore(user)"
                                                >
                                                    <RotateCcw
                                                        class="mr-2 h-4 w-4"
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
    </AppLayout>
</template>
