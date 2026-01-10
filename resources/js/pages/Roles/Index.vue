<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { RolesCreate, RolesDestroy, RolesEdit, RolesIndex } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { toRefs } from 'vue';

type RoleItem = {
    id: number;
    name: string;
    guard_name?: string;
    created_at?: string;
    permissions?: { id: number; name: string }[];
};

const props = withDefaults(defineProps<{ roles: RoleItem[] }>(), {
    roles: () => [],
});
const { roles } = toRefs(props);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles Table',
        href: RolesIndex().url,
    },
];

// Actions handled via Inertia <Link> components in the template
</script>

<template>
    <Head title="Roles Table" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Roles</h1>
                <Link
                    :href="RolesCreate().url"
                    class="rounded bg-green-600 px-3 py-1 text-sm text-white hover:bg-green-700"
                >
                    Create Role
                </Link>
            </div>
            <table
                class="w-full table-auto border-collapse border border-gray-200"
            >
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-4 py-2 text-left">
                            Name
                        </th>
                        <th class="border border-gray-300 px-4 py-2 text-left">
                            Permissions
                        </th>
                        <th class="border border-gray-300 px-4 py-2 text-left">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!roles.length">
                        <td
                            class="border border-gray-300 px-4 py-2"
                            colspan="3"
                        >
                            No roles found.
                        </td>
                    </tr>
                    <tr
                        v-for="role in roles"
                        :key="role.id"
                        class="hover:bg-gray-50"
                    >
                        <td class="border border-gray-300 px-4 py-2">
                            {{ role.name }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            <template
                                v-if="
                                    role.permissions && role.permissions.length
                                "
                            >
                                {{
                                    role.permissions
                                        .map((p) => p.name)
                                        .join(', ')
                                }}
                            </template>
                            <template v-else> None </template>
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="RolesEdit(role.id).url"
                                    as="button"
                                    class="rounded bg-blue-600 px-3 py-1 text-sm text-white hover:bg-blue-700"
                                    aria-label="Edit role"
                                >
                                    Edit
                                </Link>
                                <Link
                                    :href="RolesDestroy(role.id).url"
                                    method="delete"
                                    as="button"
                                    class="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700"
                                    aria-label="Delete role"
                                >
                                    Delete
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
