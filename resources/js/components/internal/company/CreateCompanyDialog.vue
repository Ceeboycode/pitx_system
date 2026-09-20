<script setup lang="ts">
import { AppDialog } from '@/components/ui/_app-dialog';
import { InputMessage } from '@/components/ui/_input-message'
import { Button } from '@/components/ui/button'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import { store } from '@/routes/companies'
import { useForm } from '@inertiajs/vue3'
import { RiSaveLine } from 'vue-remix-icons'
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
    <AppDialog
        v-model:open="open"
        title="Add New Company"
        description="Add a new company to your system."
        size="md"
        form
        @submit="submit"
    >
        <div class="space-y-4">
            <div class="space-y-2">
                <Label for="company_name">Company name</Label>
                <Input
                    id="company_name"
                    v-model="form.company_name"
                    placeholder="Company name"
                />
                <InputMessage variant="destructive" :message="form.errors.company_name" />
            </div>
        </div>

        <template #footer>
            <Button
                variant="float"
                type="button"
                @click="open = false"
            >
                Cancel
            </Button>

            <Button type="submit" variant="float-primary" :disabled="form.processing">
                <RiSaveLine class="shrink-0" />
                Save
            </Button>
        </template>
    </AppDialog>
</template>
