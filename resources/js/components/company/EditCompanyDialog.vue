<script setup lang="ts">
import { update } from '@/routes/companies';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { Save } from 'lucide-vue-next';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

/* ── Types ─────────────────────────────────────────────────────────────── */

type CompanyStatus =
    | 'draft'
    | 'docs_completed'
    | 'for_verification'
    | 'verified'
    | 'needs_revision'
    | 'rejected';

const STATUS_OPTIONS: { value: CompanyStatus; label: string }[] = [
    { value: 'draft', label: 'Draft' },
    { value: 'docs_completed', label: 'Documents Completed' },
    { value: 'for_verification', label: 'For Verification' },
    { value: 'verified', label: 'Verified' },
    { value: 'needs_revision', label: 'Needs Revision' },
    { value: 'rejected', label: 'Rejected' },
];

/* ── Props & model ─────────────────────────────────────────────────────── */

const open = defineModel<boolean>('open');

const props = defineProps<{
    company: {
        id: number;
        company_name: string;
        status?: CompanyStatus | null;
    };
}>();

/* ── Form ──────────────────────────────────────────────────────────────── */

const form = useForm({
    company_name: props.company.company_name,
    status: (props.company.status ?? 'draft') as CompanyStatus,
});

watch(
    () => props.company,
    (company) => {
        form.company_name = company.company_name;
        form.status = (company.status ?? 'draft') as CompanyStatus;
        form.clearErrors();
    },
    { deep: true },
);

watch(
    () => open.value,
    (isOpen) => {
        if (!isOpen) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit() {
    form.put(update({ company: props.company.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Edit Company</DialogTitle>
                <DialogDescription>
                    Update the company name and verification status.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-5 py-1" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="edit_company_name">Company Name</Label>
                    <Input
                        id="edit_company_name"
                        v-model="form.company_name"
                        placeholder="Enter company name"
                        :disabled="form.processing"
                    />
                    <InputError :message="form.errors.company_name" />
                </div>

                <div class="space-y-2">
                    <Label for="edit_status">Verification Status</Label>
                    <Select v-model="form.status" :disabled="form.processing">
                        <SelectTrigger id="edit_status">
                            <SelectValue placeholder="Select a status" />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem
                                v-for="option in STATUS_OPTIONS"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status" />
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="open = false"
                    >
                        Cancel
                    </Button>

                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
