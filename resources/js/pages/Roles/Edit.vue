<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { RolesEdit, RolesIndex, RolesUpdate } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

type PermissionItem = { id: number; name: string };

type RoleItem = { id: number; name: string };

const props = defineProps<{
    role: RoleItem;
    permissions: PermissionItem[];
    selectedPermissions: number[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: RolesIndex().url },
    { title: 'Edit', href: RolesEdit(props.role.id).url },
];

const form = useForm<{ name: string; permissions: number[] }>({
    name: props.role?.name ?? '',
    permissions: [...(props.selectedPermissions ?? [])],
});

const submit = () => {
    form.put(RolesUpdate(props.role.id).url);
};
</script>

<template>
    <Head title="Edit Role" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Edit Role</h1>
                <Link
                    :href="RolesIndex().url"
                    class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-100"
                >
                    Back to Roles
                </Link>
            </div>

            <form @submit.prevent="submit" class="flex max-w-xl flex-col gap-6">
                <div>
                    <label
                        for="name"
                        class="mb-1 block text-sm font-medium text-gray-700"
                        >Role Name</label
                    >
                    <input
                        id="name"
                        type="text"
                        v-model="form.name"
                        class="w-full rounded border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none"
                        placeholder="e.g., Admin"
                        required
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <span class="mb-2 block text-sm font-medium text-gray-700"
                        >Permissions</span
                    >
                    <div
                        class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3"
                    >
                        <label
                            v-for="permission in props.permissions"
                            :key="permission.id"
                            class="flex items-center gap-2 rounded border border-gray-200 p-2 hover:bg-gray-50"
                        >
                            <input
                                type="checkbox"
                                :value="permission.id"
                                v-model="form.permissions"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="text-sm">{{ permission.name }}</span>
                        </label>
                    </div>
                    <p
                        v-if="form.errors.permissions"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.permissions }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        Update Role
                    </button>
                    <button
                        type="button"
                        @click="form.reset('permissions')"
                        class="rounded border border-gray-300 px-4 py-2 hover:bg-gray-100"
                    >
                        Reset permissions
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
