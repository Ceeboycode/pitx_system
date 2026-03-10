<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

import AuthBase from '@/layouts/AuthLayout.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { logout } from '@/routes'
import { ShieldAlert, LogOut } from 'lucide-vue-next'

const props = defineProps<{
    mustChangePassword: boolean
}>()

const form = useForm({
    password: '',
    password_confirmation: '',
})

const dialogOpen = computed(() => props.mustChangePassword)

function submit() {
    form.post('/force-password-reset', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
        },
    })
}
</script>

<template>
    <AuthBase
        title="Change your password"
        description="You must set your own password before continuing."
    >
        <Head title="Force Password Reset" />

        <Dialog :open="dialogOpen">
            <DialogContent class="sm:max-w-md" :show-close-button="false">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <ShieldAlert class="h-5 w-5 text-primary" />
                        Password change required
                    </DialogTitle>
                    <DialogDescription>
                        Your account is using a default password. Please create your own password to continue.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="password">New Password</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="Enter new password"
                            autocomplete="new-password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="space-y-2">
                        <Label for="password_confirmation">Confirm Password</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Confirm new password"
                            autocomplete="new-password"
                        />
                    </div>

                    <div class="flex flex-col gap-2 pt-2">
                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Saving...' : 'Update Password' }}
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            class="w-full"
                            as-child
                        >
                            <Link
                                :href="logout().url"
                                method="post"
                                as="button"
                            >
                                <LogOut class="mr-2 h-4 w-4" />
                                Log Out
                            </Link>
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AuthBase>
</template>
