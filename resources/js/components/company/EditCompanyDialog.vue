<script setup lang="ts">
import { update } from '@/routes/companies';
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
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';

const open = defineModel<boolean>('open');

const props = defineProps<{
    company: {
        id: number;
        company_name: string;
    };
}>();

const form = useForm({
    company_name: props.company.company_name,
});

watch(
    () => props.company,
    (company) => {
        form.company_name = company.company_name;
        form.clearErrors();
    },
    { immediate: true },
);

function submit() {
    form.put(update({ company: props.company.id }).url, {
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Company</DialogTitle>
                <DialogDescription>
                    Update the details of the company.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label>Company name</Label>
                    <Input v-model="form.company_name" />
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
                        <Save class="mr-2 h-4 w-4" />
                        Save
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
