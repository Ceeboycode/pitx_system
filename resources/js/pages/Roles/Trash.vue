<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index, restore, trash } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import SearchInput from '@/components/SearchInput.vue';

import { can } from '@/lib/can';

import {
    Archive,
    ArrowLeft,
    MoreHorizontal,
    RotateCcw,
    ShieldCheck,
    X,
} from 'lucide-vue-next';
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

const props = defineProps<{
    roles: { data: Role[]; links: any[] };
    filters: { search: string | null };
}>();

const canRestore = can('roles.restore');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: index().url },
    { title: 'Archived Roles', href: trash().url },
];

const search = ref(props.filters.search ?? '');
const hasActiveFilters = computed(() => !!search.value);

let filterTimer: number | null = null;

function applyFilters() {
    router.get(
        trash().url,
        {
            search: search.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['roles', 'filters', 'flash'],
        },
    );
}

watch(search, () => {
    if (filterTimer) window.clearTimeout(filterTimer);
    filterTimer = window.setTimeout(() => applyFilters(), 350);
});

function typeClass(type: Role['type']): string {
    return type === 'internal'
        ? 'bg-blue-100 text-blue-700 border-blue-200'
        : 'bg-violet-100 text-violet-700 border-violet-200';
}

function handleRestore(role: Role) {
    if (!confirm(`Restore role ${role.name}?`)) {
        toast.info('Restore cancelled.');
        return;
    }

    router.patch(
        restore({ role: role.id }).url,
        {},
        {
            preserveScroll: true,
            onError: () => toast.error('Failed to restore role.'),
        },
    );
}
</script>

<template>
    <Head title="Archived Roles" />

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
                        Archived roles can be restored back to the active roles list.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="trash().url"
                                :initial-value="filters.search"
                                placeholder="Search archived roles…"
                                :only="['roles', 'filters', 'flash']"
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
                                        >Name</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Type</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Permissions</TableHead
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
                                    v-if="!props.roles.data.length"
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
                                                <ShieldCheck
                                                    class="h-6 w-6 text-muted-foreground/40"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-foreground"
                                                >
                                                    No archived roles
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
                                    v-for="role in props.roles.data"
                                    :key="role.id"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <TableCell
                                        class="text-sm font-semibold capitalize"
                                        >{{ role.name }}</TableCell
                                    >

                                    <TableCell>
                                        <Badge :class="typeClass(role.type)">
                                            {{
                                                role.type === 'internal'
                                                    ? 'Internal'
                                                    : 'External'
                                            }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell>
                                        <span
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{
                                                role.permissions?.length ?? 0
                                            }}
                                            permission{{
                                                (role.permissions?.length ??
                                                    0) !== 1
                                                    ? 's'
                                                    : ''
                                            }}
                                        </span>
                                    </TableCell>

                                    <TableCell
                                        class="text-sm text-muted-foreground"
                                        >{{
                                            role.deleted_at_human ?? '—'
                                        }}</TableCell
                                    >
                                    <TableCell
                                        class="text-sm text-muted-foreground"
                                        >{{
                                            role.deleter?.name ?? '—'
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
                                                    {{ role.name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    v-if="canRestore"
                                                    class="rounded-lg focus:bg-emerald-50 focus:text-emerald-700"
                                                    @click="handleRestore(role)"
                                                >
                                                    <RotateCcw
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    Restore
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
    </AppLayout>
</template>
