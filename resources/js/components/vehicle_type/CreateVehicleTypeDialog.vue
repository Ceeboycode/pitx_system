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
import { store } from '@/routes/vehicle-types'
import { useForm } from '@inertiajs/vue3'
import { Save } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
const open = defineModel<boolean>('open')

const form = useForm({
    type_name: '',
})

function submit() {
    form.post(store().url, {
        onSuccess: () => {
            open.value = false
            form.reset()
            form.clearErrors()
        },
        onError: () => {
            toast.error('Failed to create vehicle type. Please check the form for errors.');
        },
    })
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Create Vehicle Type</DialogTitle>
                <DialogDescription>
                    Add a new vehicle type to your system.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="type_name">Vehicle type name</Label>
                    <Input
                        id="type_name"
                        v-model="form.type_name"
                        placeholder="Vehicle type name"
                    />
                    <InputError :message="form.errors.type_name" />
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
