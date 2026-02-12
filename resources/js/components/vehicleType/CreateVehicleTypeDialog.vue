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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { store } from '@/routes/vehicle-types'
import { useForm } from '@inertiajs/vue3'
import { Save } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
const open = defineModel<boolean>('open')

const form = useForm({
    type_name: '',
    is_active: 1,
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

                <!-- <div class="space-y-2">
                    <Label>Status</Label>
                    <Select
                        v-model="form.is_active"
                        class="w-full rounded-md border px-3 py-2 text-sm"
                    >
                        <option :value="1">Active</option>
                        <option :value="0">Inactive</option>
                    </Select>
                    <InputError :message="form.errors.is_active" />
                </div> -->

                <div class="space-y-2">
                    <Label>Status</Label>

                    <Select v-model="form.is_active">
                        <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select status" />
                        </SelectTrigger>

                        <SelectContent>
                        <SelectItem :value="1">Active</SelectItem>
                        <SelectItem :value="0">Inactive</SelectItem>
                        </SelectContent>
                    </Select>

                    <InputError :message="form.errors.is_active" />
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
