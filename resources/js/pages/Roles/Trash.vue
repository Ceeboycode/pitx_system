<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator/index';
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
import { can } from '@/lib/can';
import { index, restore, trash } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { RiArrowLeftSLine, RiFilter2Line, RiMore2Line, RiRestartLine } from 'vue-remix-icons';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

type Permission = { id: number; name: string };
type Role = {
    id: number;
    name: string;
    type: 'internal' | 'external';
    permissions: Permission[];
    deleted_at_human?: string | null;
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

const restoringRole = ref<Role | null>(null);
const restoreOpen = ref(false);

function openRestoreDialog(role: Role) {
    restoringRole.value = role;
    restoreOpen.value = true;
}

function closeRestoreDialog() {
    restoreOpen.value = false;
    restoringRole.value = null;
}

function confirmRestore() {
    if (!restoringRole.value) return;
    router.patch(
        restore({ role: restoringRole.value.id }).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => closeRestoreDialog(),
            onError: () => toast.error('Failed to restore role.'),
        },
    );
}
</script>

<template>
    <Head title="Archived Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row items-start gap-3">
                    <Button as-child variant="header-actions" size="icon">
                        <Link :href="index().url" aria-label="Back to roles">
                            <RiArrowLeftSLine class="h-4 w-4" />
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
                                    :class="activeFilterCount > 0 ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''"
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

                    <Card
                        :class="[
                            'flex min-h-0 max-h-fit flex-1 flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            roles.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div v-if="roles.data.length" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-[minmax(0,1.3fr)_minmax(0,0.8fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_3rem] gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <div class="flex h-10 items-center pl-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Name</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Type</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Permissions</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Archived At</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Archived By</div>
                                    <div class="flex h-10 items-center justify-end pr-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(role, rowIndex) in roles.data"
                                    :key="role.id"
                                    :class="[
                                        'grid grid-cols-[minmax(0,1.3fr)_minmax(0,0.8fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_3rem] items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        rowIndex === roles.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    ]"
                                >
                                    <div class="min-w-0 py-1.5 pl-3"><span class="block truncate font-semibold capitalize">{{ role.name }}</span></div>
                                    <div class="py-1.5"><Badge :class="typeClass(role.type)" class="border capitalize">{{ role.type }}</Badge></div>
                                    <div class="py-1.5 text-sm">{{ role.permissions?.length ?? 0 }} permission{{ (role.permissions?.length ?? 0) === 1 ? '' : 's' }}</div>
                                    <div class="min-w-0 py-1.5 text-sm"><span class="block truncate">{{ role.deleted_at_human ?? '—' }}</span></div>
                                    <div class="min-w-0 py-1.5 text-sm"><span class="block truncate">{{ role.deleter?.name ?? '—' }}</span></div>
                                    <div class="flex justify-end py-1.5 pr-3 text-right">
                                        <DropdownMenu v-if="canRestore">
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="table-more" size="icon-more"><RiMore2Line class="h-4 w-4" /></Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>{{ role.name }}</DropdownMenuLabel>
                                                <DropdownMenuItem class="group" @click="openRestoreDialog(role)">
                                                    <RiRestartLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
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
                                    <p class="text-base font-semibold text-custom-shadow">No archived roles found</p>
                                    <p class="text-sm text-custom-shadow/80">{{ filters.search || activeFilterCount ? 'Try adjusting your search or filters.' : 'Nothing has been archived yet.' }}</p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <InertiaPagination :links="roles.links" :meta="{ from: roles.from, to: roles.to, total: roles.total }" />
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="restoreOpen">
            <DialogContent class="px-6">
                <DialogHeader class="px-0">
                    <DialogTitle>Restore Role</DialogTitle>
                    <DialogDescription class="mt-4">
                        Are you sure you want to restore
                        <span class="font-semibold text-custom-accent-3">{{ restoringRole?.name ?? 'this role' }}</span>?
                        It will be moved back to the active roles list.
                    </DialogDescription>
                </DialogHeader>
                <Separator class="mb-4" />
                <DialogFooter class="gap-2 sm:justify-end">
                    <Button variant="ghost-outline" @click="closeRestoreDialog">Cancel</Button>
                    <Button variant="float-primary" @click="confirmRestore">
                        <RiRestartLine class="h-4 w-4" />
                        Restore
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
