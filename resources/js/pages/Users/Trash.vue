<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
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
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, restore, trash } from '@/routes/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { RiArrowLeftSLine, RiFilter2Line, RiMore2Line, RiRestartLine } from 'vue-remix-icons';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

type UserArchive = {
    id: number;
    username: string;
    name: string;
    email: string;
    deleted_at_human?: string;
    deleter?: { id: number; name: string } | null;
};

type PaginatedUsers = {
    data: UserArchive[];
    links: any[];
    from: number | null;
    to: number | null;
    total: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: index().url },
    { title: 'Archived Users', href: trash().url },
];

const props = defineProps<{
    users: PaginatedUsers;
    filters: { search: string | null; archived_within: string | null };
}>();

const archivedWithin = ref(props.filters.archived_within ?? 'all');
const filterOpen = ref(false);
const activeFilterCount = computed(() => (archivedWithin.value === 'all' ? 0 : 1));

function applyFilters() {
    router.get(
        trash().url,
        {
            search: props.filters.search || undefined,
            archived_within: archivedWithin.value === 'all' ? undefined : archivedWithin.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['users', 'filters'],
        },
    );
    filterOpen.value = false;
}

function clearFilters() {
    archivedWithin.value = 'all';
    applyFilters();
}

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
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row items-start gap-3">
                    <Button as-child variant="header-actions" size="icon">
                        <Link :href="index().url" aria-label="Back to users">
                            <RiArrowLeftSLine class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div class="flex min-w-0 flex-col">
                        <CardTitle class="font-semibold">Archived Users</CardTitle>
                        <CardDescription>Restore archived users to the active users list.</CardDescription>
                    </div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="`${trash().url}?archived_within=${archivedWithin === 'all' ? '' : archivedWithin}`"
                                :initial-value="filters.search"
                                placeholder="Search archived users..."
                                :only="['users', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <Popover v-model:open="filterOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    variant="header-actions"
                                    size="icon-text"
                                    class="rounded-full"
                                    :class="activeFilterCount > 0 ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''"
                                >
                                    <RiFilter2Line class="h-3.5 w-3.5" />
                                    <span class="hidden lg:flex">{{ activeFilterCount > 0 ? '1 filter active' : 'Filter' }}</span>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent align="end">
                                <div class="grid gap-y-2">
                                    <div class="flex flex-col gap-y-1">
                                        <p class="text-sm text-custom-shadow/80">Archived within</p>
                                        <Select v-model="archivedWithin">
                                            <SelectTrigger class="w-full">
                                                <SelectValue placeholder="Any time" class="flex justify-start" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Any time</SelectItem>
                                                <SelectItem value="today">Today</SelectItem>
                                                <SelectItem value="7_days">Last 7 days</SelectItem>
                                                <SelectItem value="30_days">Last 30 days</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light" />
                                    <div class="flex w-full flex-row items-center justify-between">
                                        <Button v-if="activeFilterCount > 0" size="sm" variant="destructive" @click="clearFilters">Clear</Button>
                                        <div class="ml-auto flex items-center gap-2">
                                            <Button variant="ghost-outline" size="sm" @click="filterOpen = false">Cancel</Button>
                                            <Button size="sm" variant="float-primary" @click="applyFilters">Apply</Button>
                                        </div>
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <Card
                        :class="[
                            'flex min-h-0 max-h-fit flex-1 flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            users.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div v-if="users.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-6 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <div class="flex h-10 items-center pl-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Username</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Name</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Email</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Archived At</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Archived By</div>
                                    <div class="flex h-10 items-center justify-end pr-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(user, rowIndex) in users.data"
                                    :key="user.id"
                                    :class="[
                                        'grid grid-cols-6 items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        rowIndex === users.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    ]"
                                >
                                    <div class="min-w-0 py-1.5 pl-3 font-semibold"><span class="block truncate">{{ user.username }}</span></div>
                                    <div class="min-w-0 py-1.5"><span class="block truncate">{{ user.name }}</span></div>
                                    <div class="min-w-0 py-1.5 text-sm"><span class="block truncate">{{ user.email }}</span></div>
                                    <div class="min-w-0 py-1.5 text-sm"><span class="block truncate">{{ user.deleted_at_human ?? '—' }}</span></div>
                                    <div class="min-w-0 py-1.5 text-sm"><span class="block truncate">{{ user.deleter?.name ?? '—' }}</span></div>
                                    <div class="flex justify-end py-1.5 pr-3 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="table-more" size="icon-more"><RiMore2Line class="h-4 w-4" /></Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>{{ user.username }}</DropdownMenuLabel>
                                                <DropdownMenuItem class="group" @click="openRestoreDialog(user)">
                                                    <RiRestartLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    Restore
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                            <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                <img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" />
                                <div class="space-y-1">
                                    <p class="text-base font-semibold text-custom-shadow">No archived users found</p>
                                    <p class="text-sm text-custom-shadow/80">{{ filters.search || activeFilterCount > 0 ? 'Try adjusting your search or filters.' : 'Nothing has been archived yet.' }}</p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <InertiaPagination :links="users.links" :meta="{ from: users.from, to: users.to, total: users.total }" />
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
                    <AlertDialogCancel class="cursor-pointer rounded-lg hover:bg-slate-100" @click="restoringUser = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction class="cursor-pointer rounded-lg border-0 bg-primary text-white hover:bg-primary/90" @click="confirmRestore">
                        <RiRestartLine class="h-4 w-4" />
                        Restore
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
