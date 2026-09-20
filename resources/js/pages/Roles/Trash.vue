<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenuItem,
    DropdownMenuLabel,
} from '@/components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/can';
import { edit, index, trash } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { RiArrowLeftLine, RiFilter2Line, RiRestartLine } from 'vue-remix-icons';
import { computed, ref, watch } from 'vue';
import { PanelLayout, MainPanel, SidePanel } from '@/components/ui/_panels';
import { RestoreRoleDialog } from '@/components/internal/roles';
import { RolePreviewCard } from '@/components/internal/preview-cards';
import { Table, TableCard, TableColumn, TableContent, TableData, TableHeader, TableMoreButton, TableRow } from '@/components/ui/_table';

type Permission = { id: number; name: string };
type Role = {
    id: number;
    name: string;
    type: 'internal' | 'external';
    permissions: Permission[];
    created_at_human?: string | null;
    deleted_at_human?: string | null;
    creator?: { id: number; name: string } | null;
    deleter?: { id: number; name: string } | null;
};

type PaginatedRoles = {
    data: Role[];
    links: any[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    roles: PaginatedRoles;
    filters: { search: string | null; type: string | null };
}>();

const canRestore = can('roles.restore');
const filterType = ref(props.filters.type ?? 'all');
const filterOpen = ref(false);
const activeFilterCount = computed(() => (filterType.value === 'all' ? 0 : 1));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: index().url },
    { title: 'Archived Roles', href: trash().url },
];

function applyFilters() {
    router.get(
        trash().url,
        {
            search: props.filters.search || undefined,
            type: filterType.value === 'all' ? undefined : filterType.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['roles', 'filters'],
        },
    );
    filterOpen.value = false;
}

function clearFilters() {
    filterType.value = 'all';
    applyFilters();
}

function typeClass(type: Role['type']) {
    return type === 'internal'
        ? 'border-blue-200 bg-blue-100 text-blue-700'
        : 'border-violet-200 bg-violet-100 text-violet-700';
}

const previewedRole = ref<Role | null>(null);
const openMenuId = ref<number | null>(null);

// Drop the preview once its row leaves the list (e.g. after restoring it).
watch(() => props.roles.data, (rows) => {
    if (previewedRole.value && !rows.some((row) => row.id === previewedRole.value?.id)) {
        previewedRole.value = null;
    }
});

const restoringRole = ref<Role | null>(null);
const restoreOpen = ref(false);

function openRestoreDialog(role: Role) {
    restoringRole.value = role;
    restoreOpen.value = true;
}

</script>

<template>
    <Head title="Archived Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row items-start gap-3">
                        <Button as-child variant="header-actions" size="icon">
                            <Link :href="index().url" aria-label="Back to roles">
                                <RiArrowLeftLine class="h-4 w-4" />
                            </Link>
                        </Button>
                        <div class="flex min-w-0 flex-col">
                            <CardTitle class="font-semibold">Archived Roles</CardTitle>
                            <CardDescription>Restore archived roles to the active roles list.</CardDescription>
                        </div>
                    </CardHeader>

                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                        <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                            <div class="w-full">
                                <SearchInput
                                    :route="`${trash().url}?type=${filterType === 'all' ? '' : filterType}`"
                                    :initial-value="filters.search"
                                    placeholder="Search archived roles..."
                                    :only="['roles', 'filters', 'flash']"
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
                                        <span class="hidden lg:flex">{{ activeFilterCount ? '1 filter active' : 'Filter' }}</span>
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent align="end">
                                    <div class="grid gap-y-2">
                                        <div class="flex flex-col gap-y-1">
                                            <p class="text-sm text-custom-shadow/80">Type</p>
                                            <Select v-model="filterType">
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Any type" class="flex justify-start" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">Any type</SelectItem>
                                                    <SelectItem value="internal">Internal</SelectItem>
                                                    <SelectItem value="external">External</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light" />
                                        <div class="flex w-full flex-row items-center justify-between">
                                            <Button v-if="activeFilterCount" size="sm" variant="destructive" @click="clearFilters">Clear</Button>
                                            <div class="ml-auto flex items-center gap-2">
                                                <Button variant="ghost-outline" size="sm" @click="filterOpen = false">Cancel</Button>
                                                <Button size="sm" variant="float-primary" @click="applyFilters">Apply</Button>
                                            </div>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>

                        <TableCard :table-data-length="props.roles.data.length">
                            <Table v-if="props.roles.data.length > 0">
                                <TableHeader>
                                    <TableColumn>Name</TableColumn>
                                    <TableColumn>Type</TableColumn>
                                    <TableColumn>Permissions</TableColumn>
                                    <TableColumn>Created By</TableColumn>
                                    <TableColumn>Archived At</TableColumn>
                                    <TableColumn>Archived By</TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(role, rowIndex) in props.roles.data"
                                        :key="role.id"
                                        :class="[
                                            rowIndex === props.roles.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedRole?.id === role.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        @click.left="previewedRole = role"
                                        @dblclick="router.visit(edit({ role: role.id }).url)"
                                    >
                                        <TableData class="font-semibold capitalize"><span class="block truncate">{{ role.name }}</span></TableData>
                                        <TableData><Badge :class="typeClass(role.type)" class="border capitalize">{{ role.type }}</Badge></TableData>
                                        <TableData>{{ role.permissions?.length ?? 0 }} permission{{ (role.permissions?.length ?? 0) === 1 ? '' : 's' }}</TableData>
                                        <TableData><span class="block truncate">{{ role.creator?.name ?? '—' }}</span></TableData>
                                        <TableData><span class="block truncate">{{ role.deleted_at_human ?? '—' }}</span></TableData>
                                        <TableData><span class="block truncate">{{ role.deleter?.name ?? '—' }}</span></TableData>

                                        <TableMoreButton
                                            :open="openMenuId === role.id"
                                            @update:open="(value) => (openMenuId = value ? role.id : null)"
                                        >
                                            <DropdownMenuLabel>{{ role.name }}</DropdownMenuLabel>
                                            <DropdownMenuItem v-if="canRestore" class="group cursor-pointer" @click="openRestoreDialog(role)">
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
                                        <p class="text-base font-semibold text-custom-shadow">No archived roles found</p>
                                        <p class="text-sm text-custom-shadow/80">{{ filters.search || activeFilterCount ? 'Try adjusting your search or filters.' : 'Nothing has been archived yet.' }}</p>
                                    </div>
                                </div>
                            </div>
                        </TableCard>

                        <InertiaPagination :links="roles.links" :meta="{ from: roles.from, to: roles.to, total: roles.total }" />
                    </CardContent>
                </Card>
            </MainPanel>
            <SidePanel v-if="previewedRole" class="hidden lg:flex">
                <RolePreviewCard :role="previewedRole" archived @close="previewedRole = null" />
            </SidePanel>
        </PanelLayout>

        <RestoreRoleDialog v-model:open="restoreOpen" :role="restoringRole" />
    </AppLayout>
</template>
