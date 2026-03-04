<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed, watch } from 'vue';

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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';

type Role = {
    id: number;
    name: string;
    type: 'internal' | 'external';
};

const props = defineProps<{
    user: {
        id: number;
        username: string | null;
        name: string;
        email: string;
        phone_number: string | null;
    };
    roles: Role[];
    selectedRole: string | null;
    roleTypes: string[]; // ['internal','external']
    initialRoleType: 'internal' | 'external' | 'mixed' | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: index().url },
    { title: 'Edit', href: '#' },
];

// UI-only filter: prefer user's derived type, otherwise default internal
const initialType =
    props.initialRoleType === 'internal' || props.initialRoleType === 'external'
        ? props.initialRoleType
        : 'internal';

const roleTypeForm = useForm({
    role_type: initialType as 'internal' | 'external',
});

const filteredRoles = computed(() =>
    props.roles.filter((r) => r.type === roleTypeForm.role_type),
);

const form = useForm({
    username: props.user.username ?? '',
    name: props.user.name ?? '',
    email: props.user.email ?? '',
    phone_number: props.user.phone_number ?? '',
    password: '',

    // single role
    role: props.selectedRole ?? '',

    // optional: send to backend to validate role matches type
    role_type: roleTypeForm.role_type,
});

// keep form.role_type synced with UI selector
watch(
    () => roleTypeForm.role_type,
    (t) => {
        form.role_type = t;

        // if current selected role not in filtered list, clear it
        const allowed = new Set(filteredRoles.value.map((r) => r.name));
        if (form.role && !allowed.has(form.role)) {
            form.role = '';
        }
    },
);

// if user has a selected role but initialRoleType is mixed/null,
// auto-detect the roleType from selectedRole
if (
    (!props.initialRoleType || props.initialRoleType === 'mixed') &&
    props.selectedRole
) {
    const found = props.roles.find((r) => r.name === props.selectedRole);
    if (found) {
        roleTypeForm.role_type = found.type;
        form.role_type = found.type;
    }
}

function submit() {
    form.put(update({ user: props.user.id }).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Edit User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <Card class="mx-5">
                <CardHeader>
                    <CardTitle>Edit User</CardTitle>
                    <CardDescription>
                        Update the user details and assign exactly one role.
                    </CardDescription>
                </CardHeader>

                <Separator />

                <CardContent class="space-y-6 pt-6">
                    <div class="space-y-2">
                        <Label for="username">Username</Label>
                        <Input
                            id="username"
                            v-model="form.username"
                            placeholder="e.g. johndoe"
                        />
                        <InputError :message="form.errors.username" />
                    </div>

                    <div class="space-y-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="e.g. John Doe"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            placeholder="e.g. john@email.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="space-y-2">
                        <Label for="phone_number">Phone Number</Label>
                        <Input
                            id="phone_number"
                            v-model="form.phone_number"
                            placeholder="e.g. 09123456789"
                        />
                        <InputError :message="form.errors.phone_number" />
                    </div>

                    <div class="space-y-2">
                        <Label for="password">New Password (optional)</Label>
                        <Input
                            id="password"
                            type="password"
                            v-model="form.password"
                            placeholder="Leave blank to keep current password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <!-- Role type filter (UI-only) -->
                    <div class="space-y-2">
                        <Label>Role Type</Label>
                        <Select v-model="roleTypeForm.role_type">
                            <SelectTrigger>
                                <SelectValue placeholder="Select role type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="t in props.roleTypes"
                                    :key="t"
                                    :value="t"
                                >
                                    {{ t }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.role_type" />
                    </div>

                    <!-- Single role select -->
                    <div class="space-y-2">
                        <Label>Role</Label>
                        <Select v-model="form.role">
                            <SelectTrigger>
                                <SelectValue placeholder="Select one role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="r in filteredRoles"
                                    :key="r.id"
                                    :value="r.name"
                                >
                                    {{ r.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <InputError :message="form.errors.role" />
                    </div>
                </CardContent>

                <Separator />

                <CardFooter class="flex justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="index().url">Cancel</Link>
                    </Button>

                    <Button :disabled="form.processing" @click="submit">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save changes' }}
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
