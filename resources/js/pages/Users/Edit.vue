<script setup lang="ts">
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

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

import { edit, index, update } from '@/routes/users';
import { toast } from 'vue-sonner';
import { Save, ArrowLeft } from 'lucide-vue-next';

type UserPayload = {
    id: number;
    name: string;
    email: string;
    roles?: string[];
};

const props = defineProps<{
    user: UserPayload | { data: UserPayload };
    roles: string[];
}>();

const resolvedUser = computed<UserPayload | null>(() => {
    const u: any = props.user;

    if (!u) return null;

    if (typeof u === 'object' && 'data' in u && u.data) {
        return {
            ...u.data,
            roles: [...(u.data.roles ?? [])],
        };
    }

    return {
        ...(u as UserPayload),
        roles: [...((u as UserPayload).roles ?? [])],
    };
});

const userId = computed(() => resolvedUser.value?.id ?? null);

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    const items: BreadcrumbItem[] = [
        { title: 'Users Table', href: index().url },
    ];

    if (userId.value) {
        items.push({
            title: 'Update User',
            href: edit(userId.value).url,
        });
    }

    return items;
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
});

watch(
    resolvedUser,
    (value, previousValue) => {
        const changedUser = value?.id !== previousValue?.id;

        if (!value) {
            form.defaults({
                name: '',
                email: '',
                roles: [],
                password: '',
                password_confirmation: '',
            });
            form.reset();
            return;
        }

        if (changedUser || !previousValue) {
            form.defaults({
                name: value.name,
                email: value.email,
                roles: [...(value.roles ?? [])],
                password: '',
                password_confirmation: '',
            });

            form.name = value.name;
            form.email = value.email;
            form.roles = [...(value.roles ?? [])];

            // keep passwords blank when editing
            form.reset('password', 'password_confirmation');
        }
    },
    { immediate: true },
);

const updateRoleSelection = (role: string, checked: boolean | 'indeterminate') => {
    if (checked === true) {
        if (!form.roles.includes(role)) form.roles.push(role);
        return;
    }

    if (checked === false) {
        form.roles = form.roles.filter((r) => r !== role);
    }
};

const submit = () => {
    if (!userId.value) {
        toast.error('Unable to determine which user to update.');
        return;
    }

    form.put(update(userId.value).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('User updated successfully!');
            form.reset('password', 'password_confirmation');
        },
        onError: () => {
            toast.error('Please check the form for errors.');
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Update User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 items-start justify-center p-8">
            <Card class="w-full max-w-xl rounded-xl border bg-background shadow-sm">
                <CardHeader class="space-y-1">
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-lg font-medium">
                            Update user
                        </CardTitle>

                        <Button as-child variant="link" size="sm">
                            <Link :href="index().url" class="flex items-center gap-1">
                                <ArrowLeft class="h-4 w-4" />
                                Back to Users
                            </Link>
                        </Button>
                    </div>

                    <CardDescription class="text-sm">
                        Update this user’s profile information.
                    </CardDescription>
                </CardHeader>
                <form @submit.prevent="submit">
                    <CardContent class="pt-6">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <!-- Full name -->
                            <div class="space-y-1.5 md:col-span-2">
                                <Label for="fullname">Full name</Label>
                                <Input
                                    id="fullname"
                                    v-model="form.name"
                                    placeholder="John Doe"
                                    :disabled="form.processing"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <!-- Email -->
                            <div class="space-y-1.5 md:col-span-2">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="john@example.com"
                                    :disabled="form.processing"
                                />
                                <InputError :message="form.errors.email" />
                            </div>

                            <!-- Password -->
                            <div class="space-y-1.5">
                                <Label for="password">Password</Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    :disabled="form.processing"
                                />
                                <InputError :message="form.errors.password" />
                            </div>

                            <!-- Password confirmation -->
                            <div class="space-y-1.5">
                                <Label for="password_confirmation">
                                    Confirm password
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    :disabled="form.processing"
                                />
                                <InputError :message="form.errors.password_confirmation" />
                            </div>

                            <!-- Roles -->
                            <div class="space-y-2 md:col-span-2">
                                <Label>Roles</Label>

                                <div class="grid grid-cols-2 gap-3">
                                    <div
                                        v-for="role in roles"
                                        :key="role"
                                        class="flex items-center gap-2"
                                    >
                                        <Checkbox
                                            :id="`role-${role}`"
                                            :model-value="form.roles.includes(role)"
                                            :disabled="form.processing"
                                            @update:model-value="(checked) =>
                                                updateRoleSelection(role, checked)"
                                        />

                                        <Label
                                            :for="`role-${role}`"
                                            class="cursor-pointer font-normal"
                                        >
                                            {{ role }}
                                        </Label>
                                    </div>
                                </div>

                                <InputError :message="form.errors.roles" />
                            </div>
                        </div>
                    </CardContent>

                    <CardFooter class="flex justify-end gap-3 pt-6">
                        <Button as-child variant="outline" class="px-5">
                            <Link :href="index().url">Cancel</Link>
                        </Button>

                        <Button
                            type="submit"
                            class="px-6"
                            :disabled="form.processing"
                        >
                            <Save class="mr-2 h-4 w-4" />
                            Update user
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
