<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import {
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenuItem,
    DropdownMenuLabel,
} from '@/components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, trash } from '@/routes/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { RiArrowLeftLine, RiFilter2Line, RiRestartLine } from 'vue-remix-icons';
import { computed, ref, watch } from 'vue';
import { PanelLayout, MainPanel, SidePanel } from '@/components/ui/_panels';
import { RestoreUserDialog } from '@/components/internal/users';
import { UserPreviewCard } from '@/components/internal/preview-cards';
import { Table, TableCard, TableColumn, TableContent, TableData, TableHeader, TableMoreButton, TableRow } from '@/components/ui/_table';

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

const previewedUser = ref<UserArchive | null>(null);
const openMenuId = ref<number | null>(null);

// Drop the preview once its row leaves the list (e.g. after restoring it).
watch(() => props.users.data, (rows) => {
    if (previewedUser.value && !rows.some((row) => row.id === previewedUser.value?.id)) {
        previewedUser.value = null;
    }
});

const restoringUser = ref<UserArchive | null>(null);
const restoreOpen = ref(false);

function openRestoreDialog(user: UserArchive) {
    restoringUser.value = user;
    restoreOpen.value = true;
}

</script>

<template>
    <Head title="Archived Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row items-start gap-3">
                        <Button as-child variant="header-actions" size="icon">
                            <Link :href="index().url" aria-label="Back to users">
                                <RiArrowLeftLine class="h-4 w-4" />
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
                                        :class="activeFilterCount > 0 ? 'bg-custom-secondary/20 transition-all duration-200 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''"
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

                        <TableCard :table-data-length="props.users.data.length">
                            <Table v-if="props.users.data.length > 0">
                                <TableHeader>
                                    <TableColumn>Username</TableColumn>
                                    <TableColumn>Name</TableColumn>
                                    <TableColumn>Email</TableColumn>
                                    <TableColumn>Archived At</TableColumn>
                                    <TableColumn>Archived By</TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(user, rowIndex) in props.users.data"
                                        :key="user.id"
                                        :class="[
                                            rowIndex === props.users.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedUser?.id === user.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        @click.left="previewedUser = user"
                                        @dblclick="router.visit(show(user.id).url)"
                                    >
                                        <TableData class="font-semibold"><span class="block truncate">{{ user.username }}</span></TableData>
                                        <TableData><span class="block truncate">{{ user.name }}</span></TableData>
                                        <TableData><span class="block truncate">{{ user.email }}</span></TableData>
                                        <TableData><span class="block truncate">{{ user.deleted_at_human ?? '—' }}</span></TableData>
                                        <TableData><span class="block truncate">{{ user.deleter?.name ?? '—' }}</span></TableData>

                                        <TableMoreButton
                                            :open="openMenuId === user.id"
                                            @update:open="(value) => (openMenuId = value ? user.id : null)"
                                        >
                                            <DropdownMenuLabel>{{ user.name }}</DropdownMenuLabel>
                                            <DropdownMenuItem class="group cursor-pointer" @click="openRestoreDialog(user)">
                                                <RiRestartLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                Restore
                                            </DropdownMenuItem>
                                        </TableMoreButton>
                                    </TableRow>
                                </TableContent>
                            </Table>

                            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                                <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                    <img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" />
                                    <div class="space-y-1">
                                        <p class="text-base font-semibold text-custom-shadow">No archived users found</p>
                                        <p class="text-sm text-custom-shadow/80">{{ filters.search || activeFilterCount > 0 ? 'Try adjusting your search or filters.' : 'Nothing has been archived yet.' }}</p>
                                    </div>
                                </div>
                            </div>
                        </TableCard>

                        <InertiaPagination :links="users.links" :meta="{ from: users.from, to: users.to, total: users.total }" />
                    </CardContent>
                </Card>
            </MainPanel>

            <SidePanel v-if="previewedUser" class="hidden lg:flex">
                <UserPreviewCard :user="previewedUser" archived @close="previewedUser = null" />
            </SidePanel>
            
        </PanelLayout>

        <RestoreUserDialog v-model:open="restoreOpen" :user="restoringUser" />
    </AppLayout>
</template>
