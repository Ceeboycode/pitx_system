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

import { create, index, store } from '@/routes/users';
import { toast } from 'vue-sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users Table',
        href: index().url,
    },
    {
        title: 'Create User',
        href: create().url,
    },
];

const props = defineProps<{
    roles: string[];
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
});

const updateRoleSelection = (
    role: string,
    checked: boolean | 'indeterminate',
) => {
    if (checked === true && !form.roles.includes(role)) {
        form.roles.push(role);
        return;
    }

    if (checked === false) {
        form.roles = form.roles.filter((assignedRole) => assignedRole !== role);
    }
};

const submit = () => {
    form.post(store().url, {
        onSuccess: () => {
            toast.success('User created successfully!');
            form.reset();
            form.clearErrors();
        },
        onError: () => {
            toast.error('Please check the form for errors.');
            form.reset('password', 'password_confirmation');
        },
        preserveScroll: true,
    });
};

</script>

<template>
    <Head title="Create User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 items-start justify-center p-8">
            <Card
                class="w-full max-w-xl rounded-xl border bg-background shadow-sm"
            >
                <!-- Header -->
                <CardHeader class="space-y-1">
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-lg font-medium">
                            Create user
                        </CardTitle>

                        <!-- <Link
                :href="index().url"
                class="text-sm text-muted-foreground hover:text-foreground"
            >
                Back
            </Link> -->
                    </div>

                    <CardDescription class="text-sm">
                        Add a new user to your system.
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
                                />
                                <InputError
                                    :message="form.errors.password"
                                />
                            </div>

                            <!-- Roles (now inside grid) -->
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
                                            :model-value="
                                                form.roles.includes(role)
                                            "
                                            :disabled="form.processing"
                                            @update:model-value="
                                                (checked) =>
                                                    updateRoleSelection(
                                                        role,
                                                        checked,
                                                    )
                                            "
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

                    <!-- Footer -->
                    <CardFooter class="flex justify-end gap-3 pt-6">
                        <Link :href="index().url">
                            <Button variant="outline" class="px-5">
                                Cancel
                            </Button>
                        </Link>

                        <Button
                            type="submit"
                            class="px-6"
                            :disabled="form.processing"
                        >
                            Create user
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
