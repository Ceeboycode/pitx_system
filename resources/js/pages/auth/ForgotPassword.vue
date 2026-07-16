<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/vue3';
import { RiArrowLeftLine, RiCheckLine } from 'vue-remix-icons';
import securityOnRaifikiUrl from '@/components/assets/Security-on-rafiki.svg';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Forgot password"
        description=""
    >
        <Head title="Forgot password" />

        <!-- TODO: might need to redesign these to make them make sense-->
        <!-- TODO: adjust semantic colors to fit the theme, idk what this component is supposed to be, so adjust it depending on what it should do -->

        <div 
            v-if="status"
            class="mt-2 w-full flex flex-row items-center p-3 gap-x-2 rounded-md border border-2 border-success/50 bg-success/20 text-success"
        >
            
            <RiCheckLine class="shrink-0 h-3.5 w-3.5"/>
                <!-- LABEL: where the title will go -->
                <!-- CODE: <p></p> -->
            
            
            <p class="text-sm">
                {{ status }}
            </p>
        </div>

        <Card class="mx-auto w-full max-w-md">
            <CardHeader class="flex flex-col items-center justify-center text-center">
                <img
                    :src="securityOnRaifikiUrl"
                    alt=""
                    class="w-1/2 object-contain opacity-90"
                    aria-hidden="true"
                />
                <CardTitle class="mb-2">
                    Forgot password?
                </CardTitle>
                <CardDescription>
                    Don't worry! Enter your email address and<br/>we'll send you reset instructions.
                </CardDescription>
            </CardHeader>
            <CardContent class="pt-6">
                <Form v-bind="email.form()" v-slot="{ errors, processing }">
                    <div class="flex flex-col gap-y-1">
                        <Label for="email" class="text-sm">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autocomplete="off"
                            autofocus
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="pt-3 items-center">
                        <Button
                            variant="float-primary"
                            class="w-full rounded-md px-4 py-2"
                            :disabled="processing"
                            data-test="email-password-reset-link-button"
                        >
                            <Spinner v-if="processing" />
                            <!-- TODO:  i dont remember exactly what is being sent over, change the text as necessary-->
                            <span>Receive link</span>
                        </Button>
                    </div>
                </Form>
            </CardContent>
            <CardFooter class="pt-2 px-6 pb-3">
                <Button
                    as-child
                    variant="ghost-outline"
                    class="w-full rounded-md px-4 py-2 items-center flex flex-row"
                >
                    <Link :href="login()">
                        <RiArrowLeftLine class="w-4 h-4"/>
                        <span>Back to login</span>
                    </Link>
                </Button>
            </CardFooter>
        </Card>
    </AuthLayout>
</template>
