<script setup lang="ts">
import { update } from '@/routes/vehicle-types';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { Save } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogDescription,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';

const open = defineModel<boolean>('open');

const props = defineProps<{
    vehicle_type: {
        id: number;
        type_name: string;
        is_active: boolean;
    };
}>();

const form = useForm({
    type_name: props.vehicle_type.type_name,
    is_active: props.vehicle_type.is_active ? 1 : 0,
});

watch(
    () => props.vehicle_type,
    (vehicle_type) => {
        form.type_name = vehicle_type.type_name;
        form.is_active = vehicle_type.is_active ? 1 : 0;
        form.clearErrors();
    },
    { immediate: true },
);

function submit() {
    form.put(update({ vehicle_type: props.vehicle_type.id }).url, {
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Vehicle Type</DialogTitle>
                <DialogDescription>
                    Update the details of the vehicle type.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label>Vehicle type name</Label>
                    <Input v-model="form.type_name" />
                    <InputError :message="form.errors.type_name" />
                </div>

                <!-- CODE: <div class="space-y-2">
                    <Label>Status</Label>
                    <select
                        v-model.number="form.is_active"
                        class="w-full rounded-md border px-3 py-2 text-sm"
                    >
                        <option :value="1">Active</option>
                        <option :value="0">Inactive</option>
                </select>
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
                        <Save class="mr-2 h-4 w-4" />
                        Save
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
