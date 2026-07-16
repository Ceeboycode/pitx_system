<script setup lang="ts">
import { ref } from 'vue';
import { Form, Head } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ExternalSettingsLayout from '@/layouts/external/SettingsLayout.vue';
import ExternalLayout from '@/layouts/ExternalLayout.vue';

const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);
</script>

<template>
    <ExternalLayout>
        <Head title="Password settings" />
        <h1 class="sr-only">Password Settings</h1>

        <ExternalSettingsLayout>
            <Card>
                <CardHeader>
                    <CardTitle>Update password</CardTitle>
                    <CardDescription>
                        Ensure your account is using a long, random password to stay secure
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <Form
                        method="put"
                        action="/company/settings/password"
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        :reset-on-error="['password', 'password_confirmation', 'current_password']"
                        class="space-y-6"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        
                        <div class="grid gap-2">
                            <Label for="current_password">Current password</Label>
                            <div class="relative">
                                <Input
                                    id="current_password"
                                    name="current_password"
                                    :type="showCurrent ? 'text' : 'password'"
                                    class="mt-1 block w-full pr-10"
                                    autocomplete="current-password"
                                    placeholder="Current password"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground mt-1"
                                    @click="showCurrent = !showCurrent"
                                >
                                    <svg v-if="!showCurrent" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                            <InputError :message="errors.current_password" />
                        </div>

                        
                        <div class="grid gap-2">
                            <Label for="password">New password</Label>
                            <div class="relative">
                                <Input
                                    id="password"
                                    name="password"
                                    :type="showNew ? 'text' : 'password'"
                                    class="mt-1 block w-full pr-10"
                                    autocomplete="new-password"
                                    placeholder="New password"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground mt-1"
                                    @click="showNew = !showNew"
                                >
                                    <svg v-if="!showNew" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                            <InputError :message="errors.password" />
                        </div>

                        
                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirm password</Label>
                            <div class="relative">
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    :type="showConfirm ? 'text' : 'password'"
                                    class="mt-1 block w-full pr-10"
                                    autocomplete="new-password"
                                    placeholder="Confirm password"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground mt-1"
                                    @click="showConfirm = !showConfirm"
                                >
                                    <svg v-if="!showConfirm" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                            <InputError :message="errors.password_confirmation" />
                        </div>

                        
                        <div class="flex items-center gap-4 border-t pt-5">
                            <Button :disabled="processing">Save password</Button>
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-show="recentlySuccessful" class="text-sm text-muted-foreground">
                                    Password updated.
                                </p>
                            </Transition>
                        </div>
                    </Form>
                </CardContent>
            </Card>
        </ExternalSettingsLayout>
    </ExternalLayout>
</template>