<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { edit as editRoute, index, update } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';

type Permission = {
    id: number;
    name: string;
};

type RolePayload = {
    id: number;
    name: string;
    permissions?: Permission[] | { data?: Permission[] };
};

const props = defineProps<{
    role: RolePayload | { data: RolePayload };
    permissions: { data: Permission[] };
    selectedPermissions?: number[];
}>();

const resolvedRole = computed<RolePayload | null>(() => {
    if (!props.role) {
        return null;
    }

    if ('data' in props.role && props.role.data) {
        return props.role.data;
    }

    return props.role;
});

const roleId = computed(() => resolvedRole.value?.id ?? null);

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    const base: BreadcrumbItem[] = [
        {
            title: 'Roles Table',
            href: index().url,
        },
    ];

    if (roleId.value) {
        base.push({
            title: 'Edit Role',
            href: editRoute(roleId.value).url,
        });
    }

    return base;
});

const form = useForm({
    name: '',
    permissions: [] as number[],
});

const extractPermissionIds = (role: RolePayload | null): number[] => {
    if (!role) {
        return [];
    }

    const permissions = Array.isArray(role.permissions)
        ? role.permissions
        : (role.permissions?.data ?? []);

    return permissions.map((permission) => permission.id);
};

const hydrateForm = () => {
    const role = resolvedRole.value;

    if (!role) {
        form.name = '';
        form.permissions = [];
        form.defaults({ name: '', permissions: [] });
        return;
    }

    const selectedIds = (
        props.selectedPermissions?.length
            ? props.selectedPermissions
            : extractPermissionIds(role)
    ) as number[];

    form.name = role.name ?? '';
    form.permissions = [...selectedIds];
    form.defaults({
        name: role.name ?? '',
        permissions: [...selectedIds],
    });
};

watch(
    () => ({
        role: resolvedRole.value,
        selected: props.selectedPermissions,
    }),
    hydrateForm,
    { immediate: true },
);

const togglePermission = (checked: boolean | 'indeterminate', id: number) => {
    const isChecked = checked === true;

    form.permissions = isChecked
        ? form.permissions.includes(id)
            ? form.permissions
            : [...form.permissions, id]
        : form.permissions.filter((permissionId) => permissionId !== id);
};

const submit = () => {
    if (!roleId.value) {
        toast.error('Unable to determine which role to update.');
        return;
    }

    form.put(update(roleId.value).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Role updated successfully.');
        },
        onError: () => {
            toast.error('Please review the highlighted errors.');
        },
    });
};
</script>

<template>
    <Head title="Edit Role" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 justify-center p-4">
            <Card class="w-full max-w-2xl">
                <CardHeader>
                    <CardTitle>Edit Role</CardTitle>
                    <CardDescription>
                        Update the role name and assigned permissions
                    </CardDescription>
                </CardHeader>

                <form @submit.prevent="submit">
                    <CardContent class="space-y-6">
                        <div class="space-y-2">
                            <Label for="name">Role Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g. Admin"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-3">
                            <Label>Permissions</Label>

                            <div class="grid grid-cols-2 gap-3">
                                <div
                                    v-for="permission in props.permissions.data"
                                    :key="permission.id"
                                    class="flex items-center gap-2"
                                >
                                    <Checkbox
                                        :id="`perm-${permission.id}`"
                                        :model-value="
                                            form.permissions.includes(
                                                permission.id,
                                            )
                                        "
                                        :disabled="form.processing"
                                        @update:modelValue="
                                            (checked) =>
                                                togglePermission(
                                                    checked,
                                                    permission.id,
                                                )
                                        "
                                    />

                                    <Label
                                        :for="`perm-${permission.id}`"
                                        class="font-normal"
                                    >
                                        {{ permission.name }}
                                    </Label>
                                </div>
                            </div>

                            <InputError :message="form.errors.permissions" />
                        </div>
                    </CardContent>

                    <CardFooter class="flex justify-end gap-2">
                        <Link :href="index().url">
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="form.processing"
                            >
                                Cancel
                            </Button>
                        </Link>

                        <Button type="submit" :disabled="form.processing">
                            Update Role
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
