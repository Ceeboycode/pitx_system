<script setup lang="ts">
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/user-password';
import { Form, Head } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Password settings', href: edit().url },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Password settings" />
        <h1 class="sr-only">Password Settings</h1>

        <SettingsLayout>
            <Card class="overflow-hidden">
                <CardHeader class="relative overflow-hidden bg-[#1B3F7A] pb-5 pt-5">
                    <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-red-600 opacity-[0.15]" />
                    <CardTitle class="relative z-10 text-base text-white">Update password</CardTitle>
                    <CardDescription class="relative z-10 text-white/60 text-sm">
                        Ensure your account is using a long, random password to stay secure
                    </CardDescription>
                </CardHeader>

                <CardContent class="pt-6">
                    <Form
                        v-bind="PasswordController.update.form()"
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        :reset-on-error="['password', 'password_confirmation', 'current_password']"
                        class="space-y-5"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="current_password">Current password</Label>
                            <Input
                                id="current_password"
                                name="current_password"
                                type="password"
                                class="mt-1 block w-full focus-visible:ring-blue-800 focus-visible:border-blue-800"
                                autocomplete="current-password"
                                placeholder="Current password"
                            />
                            <InputError :message="errors.current_password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">New password</Label>
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                class="mt-1 block w-full focus-visible:ring-blue-800 focus-visible:border-blue-800"
                                autocomplete="new-password"
                                placeholder="New password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirm password</Label>
                            <Input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                class="mt-1 block w-full focus-visible:ring-blue-800 focus-visible:border-blue-800"
                                autocomplete="new-password"
                                placeholder="Confirm password"
                            />
                            <InputError :message="errors.password_confirmation" />
                        </div>

                        <Separator />

                        <div class="flex items-center gap-4">
                            <Button
                                :disabled="processing"
                                data-test="update-password-button"
                                class="bg-blue-800 text-white hover:bg-blue-900 border-0"
                            >
                                Save password
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
        </SettingsLayout>
    </AppLayout>
</template>