<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import { store } from '@/routes/companies'
import { useForm } from '@inertiajs/vue3'
import { Save } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
const open = defineModel<boolean>('open')

const form = useForm({
    company_name: '',
})

function submit() {
    form.post(store().url, {
        onSuccess: () => {
            open.value = false
            form.reset()
            form.clearErrors()
        },
        onError: () => {
            toast.error('Failed to create company. Please check the form for errors.');
        },
    })
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Create Company</DialogTitle>
                <DialogDescription>
                    Add a new company to your system.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="company_name">Company name</Label>
                    <Input
                        id="company_name"
                        v-model="form.company_name"
                        placeholder="Company name"
                    />
                    <InputError :message="form.errors.company_name" />
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        type="button"
                        @click="open = false"
                    >
                        Cancel
                    </Button>

                    <Button type="submit" :disabled="form.processing">
                        <Save />
                        Save
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
