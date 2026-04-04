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
import { ArrowLeft } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
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
            <CardHeader className="flex items-center justify-center relative">
                <Link
                    href="/"
                    className="absolute left-4 text-gray-500 hover:text-gray-700"
                >
                    <ArrowLeft className="h-5 w-5" />
                </Link>

                <CardTitle>Welcome back</CardTitle>
            </CardHeader>

            <CardContent>
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
                        <div class="grid gap-2">
                            <Label for="login">Username or Email</Label>
                            <Input
                                id="login"
                                type="text"
                                name="login"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="Enter your username or email"
                            />
                            <InputError :message="errors.login" />
                        </div>

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

                        <Label
                            for="remember"
                            class="flex items-center space-x-3 text-sm"
                        >
                            <Checkbox id="remember" name="remember" />
                            <span>Remember this device</span>
                        </Label>

                        <Button
                            type="submit"
                            variant="blue"
                            :disabled="processing"
                        >
                            <Spinner v-if="processing" />
                            <span v-else>Log in</span>
                        </Button>
                    </div>
                </Form>
            </CardContent>

            <CardFooter v-if="canRegister" class="flex justify-center text-sm">
                Don’t have an account?
                <TextLink :href="register()" class="ml-1 font-medium">
                    Contact Admin
                </TextLink>
            </CardFooter>
        </Card>
    </AuthBase>
</template>
