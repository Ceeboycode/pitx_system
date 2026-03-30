<script setup lang="ts">
import ListFilters from '@/components/ListFilters.vue';
import SortDirectionControl from '@/components/filters/SortDirectionControl.vue';
import { useSortableIndex } from '@/composables/useSortableIndex';

import AppLayout from '@/layouts/AppLayout.vue';
import { create, destroy, edit, index } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import { Pencil, Plus } from 'lucide-vue-next';

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
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import { can } from '@/lib/can';

const canCreate = can('roles.create');
const canUpdate = can('roles.update');
const canDelete = can('roles.delete');

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
        sort?: string | null;
        direction?: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: index().url }];

const roleFilters = computed(() => [
    {
        key: 'type',
        value: props.filters.type ?? null,
        placeholder: 'Filter by type',
        allLabel: 'All types',
        desktopWidthClass: 'w-44',
        desktopMaxWidth: '11rem',
        options: [
            { label: 'Internal', value: 'internal' },
            { label: 'External', value: 'external' },
        ],
    },
]);

const sortOptions = [
    { label: 'Newest', value: 'created_at' },
    { label: 'Name', value: 'name' },
    { label: 'Type', value: 'type' },
] as const;

const baseQuery = computed(() => ({
    search: props.filters.search ?? '',
    type: props.filters.type ?? null,
}));
const {
    currentSort,
    currentDirection,
    applySort,
    toggleDirection,
} = useSortableIndex({
    route: index().url,
    baseQuery,
    sort: computed(() => props.filters.sort ?? 'created_at'),
    direction: computed(() => props.filters.direction ?? 'desc'),
    only: ['roles', 'filters', 'flash'],
});

const open         = ref(false);
const selectedRole = ref<Role | null>(null);
const confirmation = ref('');
const processing   = ref(false);

const canConfirmDelete = computed(() => confirmation.value.trim() === 'DELETE');

watch(open, (value) => {
    if (value) confirmation.value = '';
});

function openDelete(role: Role) {
    selectedRole.value = role;
    open.value         = true;
}

function deleteRole() {
    if (!canConfirmDelete.value || processing.value || !selectedRole.value) return;

    processing.value = true;

    router.delete(destroy({ role: selectedRole.value.id }).url, {
        preserveScroll: true,
        onFinish: () => {
            processing.value   = false;
            open.value         = false;
            selectedRole.value = null;
        },
    });
}
</script>

<template>
    <Head title="Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-10 mt-3">
                <CardHeader>
                    <CardTitle>Roles</CardTitle>
                    <CardDescription>Manage the roles of your application.</CardDescription>

                    <CardAction>
                        <!-- <Button v-if="canCreate" variant="default" size="sm" as-child>
                            <Link :href="create().url">Create Role</Link>
                        </Button> -->
                        <Button v-if="canCreate" variant="default" size="sm" as-child>
                            <Link :href="create().url">
                                <Plus class="mr-1 h-4 w-4" />
                                Create Role
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- Filters -->
                    <ListFilters
                        :route="index().url"
                        :search="props.filters.search ?? ''"
                        search-placeholder="Search roles..."
                        :only="['roles', 'filters', 'flash']"
                        :filters="roleFilters"
                        :query="{
                            sort: currentSort,
                            direction: currentDirection,
                        }"
                        mobile-inline-actions
                    >
                        <template #panel-actions>
                            <SortDirectionControl
                                :options="sortOptions"
                                :value="currentSort"
                                :direction="currentDirection"
                                label="Sort roles"
                                @select="applySort"
                                @toggle-direction="toggleDirection"
                            />
                        </template>
                    </ListFilters>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Permissions</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="role in props.roles.data" :key="role.id">
                                <TableCell class="font-medium capitalize">
                                    {{ role.name }}
                                </TableCell>

                                <TableCell class="capitalize">
                                    {{ role.type }}
                                </TableCell>

                                <TableCell>
                                    <span
                                        v-if="!role.permissions?.length"
                                        class="text-muted-foreground"
                                    >
                                        No permissions
                                    </span>

                                    <Popover v-else>
                                        <PopoverTrigger as-child>
                                            <Button variant="outline" size="sm">
                                                {{ role.permissions.length }} permissions
                                            </Button>
                                        </PopoverTrigger>

                                        <PopoverContent class="max-h-60 w-80 overflow-y-auto">
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
                                            v-if="canUpdate"
                                            variant="default"
                                            size="sm"
                                            as-child
                                        >
                                            <Link :href="edit({ role: role.id }).url">
                                                <Pencil class="mr-2 h-4 w-4" />
                                                Edit
                                            </Link>
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
    </AppLayout>
</template>
