<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Profile settings', href: edit().url },
];

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />
        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">

                <Card class="overflow-hidden">
                    <CardHeader class="relative overflow-hidden bg-[#1B3F7A] pb-5 pt-5">
                        <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-red-600 opacity-[0.15]" />
                        <CardTitle class="relative z-10 text-base text-white">Profile information</CardTitle>
                        <CardDescription class="relative z-10 text-white/60 text-sm">
                            Update your name and email address
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="pt-6">
                        <Form
                            v-bind="ProfileController.update.form()"
                            class="space-y-5"
                            enctype="multipart/form-data"
                            v-slot="{ errors, processing, recentlySuccessful }"
                        >
                            <!-- Avatar -->
                            <div class="grid gap-2">
                                <Label for="avatar">Profile picture</Label>
                                <div
                                    v-if="user.avatar"
                                    class="flex items-center gap-3 rounded-md border p-3 bg-muted/40"
                                >
                                    <img
                                        :src="user.avatar"
                                        :alt="`${user.name} avatar`"
                                        class="h-12 w-12 rounded-full object-cover ring-2 ring-blue-800/20"
                                    />
                                    <p class="text-sm text-muted-foreground">Current profile picture</p>
                                </div>
                                <Input
                                    id="avatar"
                                    type="file"
                                    class="mt-1 block w-full"
                                    name="avatar"
                                    accept="image/png,image/jpeg,image/jpg,image/webp"
                                />
                                <p class="text-xs text-muted-foreground">PNG, JPG, or WEBP up to 2MB.</p>
                                <InputError class="mt-1" :message="errors.avatar" />
                            </div>

                            <Separator />

                            <!-- Name -->
                            <div class="grid gap-2">
                                <Label for="name">Name</Label>
                                <Input
                                    id="name"
                                    class="mt-1 block w-full focus-visible:ring-blue-800 focus-visible:border-blue-800"
                                    name="name"
                                    :default-value="user.name"
                                    required
                                    autocomplete="name"
                                    placeholder="Full name"
                                />
                                <InputError class="mt-1" :message="errors.name" />
                            </div>

                            <!-- Email -->
                            <div class="grid gap-2">
                                <Label for="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full focus-visible:ring-blue-800 focus-visible:border-blue-800"
                                    name="email"
                                    :default-value="user.email"
                                    required
                                    autocomplete="username"
                                    placeholder="Email address"
                                />
                                <InputError class="mt-1" :message="errors.email" />
                            </div>

                            <!-- Email verification -->
                            <div v-if="mustVerifyEmail && !user.email_verified_at">
                                <p class="text-sm text-muted-foreground">
                                    Your email address is unverified.
                                    <Link
                                        :href="send()"
                                        as="button"
                                        class="text-blue-800 underline underline-offset-4 hover:text-blue-900 transition-colors"
                                    >
                                        Click here to resend the verification email.
                                    </Link>
                                </p>
                                <div
                                    v-if="status === 'verification-link-sent'"
                                    class="mt-2 text-sm font-medium text-green-600"
                                >
                                    A new verification link has been sent to your email address.
                                </div>
                            </div>

                            <Separator />

                            <div class="flex items-center gap-4">
                                <Button
                                    :disabled="processing"
                                    data-test="update-profile-button"
                                    class="bg-blue-800 text-white hover:bg-blue-900 border-0"
                                >
                                    Save changes
                                </Button>
                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-show="recentlySuccessful" class="text-sm text-green-600 font-medium">
                                        Saved successfully.
                                    </p>
                                </Transition>
                            </div>
                        </Form>
                    </CardContent>
                </Card>

            </div>
        </SettingsLayout>
    </AppLayout>
</template>