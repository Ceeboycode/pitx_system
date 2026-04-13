<script setup lang="ts">
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ExternalSettingsLayout from '@/layouts/external/SettingsLayout.vue';
import ExternalLayout from '@/layouts/ExternalLayout.vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <ExternalLayout>
        <Head title="Profile settings" />
        <h1 class="sr-only">Profile Settings</h1>

        <ExternalSettingsLayout>
            <Card>
                <CardHeader>
                    <CardTitle>Profile information</CardTitle>
                    <CardDescription>Update your name and email address</CardDescription>
                </CardHeader>

                <CardContent>
                    <Form
                        method="post"
                        action="/company/settings/profile"
                        class="space-y-6"
                        enctype="multipart/form-data"
                        :options="{ preserveScroll: true }"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <input type="hidden" name="_method" value="PATCH" />

                        <!-- Avatar field -->
                        <div class="grid gap-2">
                            <Label for="avatar">Profile picture</Label>

                            <div v-if="user.avatar" class="flex items-center gap-3 rounded-md border bg-muted/40 p-3">
                                <img
                                    :src="user.avatar"
                                    :alt="`${user.name} avatar`"
                                    class="h-12 w-12 rounded-full object-cover"
                                />
                                <div>
                                    <p class="text-sm font-medium">{{ user.name }}</p>
                                    <p class="text-xs text-muted-foreground">Current profile picture</p>
                                </div>
                            </div>

                            <!-- Fallback initials avatar -->
                            <div v-else class="flex items-center gap-3 rounded-md border bg-muted/40 p-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground">
                                    {{ user.name?.charAt(0).toUpperCase() }}
                                </div>
                                <p class="text-sm text-muted-foreground">No profile picture set</p>
                            </div>

                            <!-- Styled file upload trigger -->
                            <Label
                                for="avatar"
                                class="mt-1 inline-flex w-fit cursor-pointer items-center gap-2 rounded-md border bg-muted/40 px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                Upload new photo
                                <Input
                                    id="avatar"
                                    type="file"
                                    class="sr-only"
                                    name="avatar"
                                    accept="image/png,image/jpeg,image/jpg,image/webp"
                                />
                            </Label>
                            <p class="text-xs text-muted-foreground">PNG, JPG, or WEBP — max 2MB</p>
                            <InputError :message="errors.avatar" />
                        </div>

                        <!-- Name field -->
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                :default-value="user.name"
                                required
                                autocomplete="name"
                                placeholder="Full name"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <!-- Email field -->
                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                :default-value="user.email"
                                required
                                autocomplete="username"
                                placeholder="Email address"
                            />
                            <InputError :message="errors.email" />

                            <!-- Unverified email alert -->
                            <div v-if="mustVerifyEmail && !user.email_verified_at" class="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0">
                                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                                <span>
                                    Your email address is unverified.
                                    <Link :href="send()" as="button" class="font-medium underline underline-offset-4 hover:text-amber-900">
                                        Resend verification email.
                                    </Link>
                                </span>
                            </div>

                            <div v-if="status === 'verification-link-sent'" class="text-sm font-medium text-green-600">
                                A new verification link has been sent to your email address.
                            </div>
                        </div>

                        <!-- Footer actions -->
                        <div class="flex items-center gap-4 border-t pt-5">
                            <Button :disabled="processing">Save changes</Button>
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-show="recentlySuccessful" class="text-sm text-muted-foreground">
                                    Changes saved.
                                </p>
                            </Transition>
                        </div>
                    </Form>
                </CardContent>
            </Card>
        </ExternalSettingsLayout>
    </ExternalLayout>
</template>