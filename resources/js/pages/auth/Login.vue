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
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import PitxLogo from '@/components/assets/PITX.png'
import { home } from '@/routes';
import { ref } from 'vue';
import { RiEyeLine, RiEyeOffLine } from 'vue-remix-icons';

const showPassword = ref(false);

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>


<template>
    <AuthBase
        title="Log in to your account"
        description="Access the PITX centralized dispatch system"
    >
        <Head title="Log in" />

        <Card class="mx-auto w-full max-w-sm">
            <CardHeader class="py-3 flex flex-col items-center justify-center relative gap-y-2">
                <Link :href="home()" class="flex flex-col items-center">
                    <img :src="PitxLogo" alt="PITX Logo" class="w-30 object-contain"/>
                </Link>
                <CardTitle class="text-base font-bold">Welcome back, friend!</CardTitle>
                <CardDescription class="text-sm">Access the PITX Terminal Management System</CardDescription>
            </CardHeader>

            <CardContent class="py-3 gap-y-2">
                <!-- TODO: this element stays unstyled, i cant see it... -->
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
                    class="flex flex-col gap-y-2"
                >
                    <div class="flex flex-col gap-y-2">
                        <div class="flex flex-col gap-y-1">
                            <Label class="text-sm" for="login">Username or Email</Label>
                            <Input
                                id="login"
                                type="text"
                                name="login"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="username or email"
                            />
                            <!-- TODO: this might need some redesigning kasi i dont like it without the error title -->
                            <InputError :message="errors.login" />
                        </div>

                        <div class="flex flex-col gap-y-1">
                            <div class="flex items-center justify-between">
                                <Label class="text-sm" for="password">Password</Label>
                                <TextLink
                                    v-if="canResetPassword"
                                    :href="request()"
                                    class="text-xs font-semibold text-custom-accent-3"
                                >
                                    Forgot password?
                                </TextLink>
                            </div>
                            <div class="relative">
                                <Input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="pr-10"
                                />
                                <button
                                    type="button"
                                    aria-label="Hold to show password"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-custom-shadow/60"
                                    @pointerdown.prevent="showPassword = true"
                                    @pointerup="showPassword = false"
                                    @pointerleave="showPassword = false"
                                    @pointercancel="showPassword = false"
                                >
                                    <RiEyeOffLine v-if="showPassword" class="h-4 w-4" />
                                    <RiEyeLine v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError :message="errors.password" />
                        </div>

                        <Label
                            for="remember"
                            class="flex flex-row items-center gap-x-1 text-sm py-2"
                        >
                            <Checkbox id="remember" name="remember" class="rounded-full" />
                            <span class="text-xs text-custom-shadow/80">Remember this device</span>
                        </Label>

                        <div class="py-3 flex flex-col gap-y-2">
                            <Button
                                type="submit"
                                variant="float-primary"
                                :disabled="processing"
                                class="w-full rounded-md px-4 py-2"
                            >
                                <Spinner v-if="processing" />
                                <span v-else>Log in</span>
                            </Button>
                        </div>

                        
                    </div>
                </Form>
            </CardContent>

            <CardFooter class="flex flex-row gap-x-1 justify-center text-xs py-3">
                <span>
                    Need help?
                </span>
                <TextLink href="/contact" class="text-xs text-custom-accent-3 font-semibold">
                    Contact support here.
                </TextLink>
            </CardFooter>
        </Card>
    </AuthBase>
</template>
