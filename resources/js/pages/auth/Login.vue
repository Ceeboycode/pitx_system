<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
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
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase
        title="Log in to your account"
        description="Access the PITX centralized dispatch system"
    >
        <Head title="Log in" />

        <Card class="mx-auto w-full max-w-md">
            <!-- Card Header -->
            <CardHeader class="text-center">
                <CardTitle>Welcome back</CardTitle>
                <CardDescription>
                    Sign in to continue to PITX Dispatch
                </CardDescription>
            </CardHeader>

            <!-- Card Content -->
            <CardContent>
                <!-- Status Message -->
                <div
                    v-if="status"
                    class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-center text-sm font-medium text-green-700"
                >
                    {{ status }}
                </div>

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-6"
                >
                    <div class="grid gap-6">
                        <!-- Email -->
                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="dispatcher@pitx.ph"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <!-- Password -->
                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <Label for="password">Password</Label>
                                <TextLink
                                    v-if="canResetPassword"
                                    :href="request()"
                                    class="text-xs font-medium"
                                >
                                    Forgot password?
                                </TextLink>
                            </div>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <!-- Remember me -->
                        <Label
                            for="remember"
                            class="flex items-center space-x-3 text-sm"
                        >
                            <Checkbox id="remember" name="remember" />
                            <span>Remember this device</span>
                        </Label>

                        <!-- Submit -->
                        <Button
                            type="submit"
                            class="w-full bg-red-600 hover:bg-red-700"
                            :disabled="processing"
                        >
                            <Spinner v-if="processing" />
                            <span v-else>Log in</span>
                        </Button>
                    </div>
                </Form>
            </CardContent>

            <!-- Card Footer -->
            <CardFooter v-if="canRegister" class="flex justify-center text-sm">
                Don’t have an account?
                <TextLink :href="register()" class="ml-1 font-medium">
                    Contact Admin
                </TextLink>
            </CardFooter>
        </Card>
    </AuthBase>
</template>
