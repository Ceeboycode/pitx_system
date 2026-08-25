<script setup lang="ts">
import { update } from '@/routes/companies';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { Save } from 'lucide-vue-next';

import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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



type CompanyStatus =
    | 'draft'
    | 'docs_completed'
    | 'for_verification'
    | 'verified'
    | 'needs_revision'
    | 'rejected';

type ActiveStatusValue = '1' | '0';

const ACTIVE_STATUS_OPTIONS: { value: ActiveStatusValue; label: string }[] = [
    { value: '1', label: 'Active' },
    { value: '0', label: 'Inactive' },
];



const open = defineModel<boolean>('open');

const props = defineProps<{
    company: {
        id: number;
        company_name: string;
        status?: CompanyStatus | null;
        is_active?: boolean | number | null;
    };
}>();



const form = useForm({
    is_active: (props.company.is_active === false || props.company.is_active === 0
        ? '0'
        : '1') as ActiveStatusValue,
});

watch(
    () => props.company,
    (company) => {
        form.is_active = (company.is_active === false || company.is_active === 0
            ? '0'
            : '1') as ActiveStatusValue;
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

function humanizeStatus(status?: CompanyStatus | null): string {
    if (!status) return '-';
    const map: Record<CompanyStatus, string> = {
        draft: 'Draft',
        docs_completed: 'Docs Completed',
        for_verification: 'For Verification',
        verified: 'Verified',
        needs_revision: 'Needs Revision',
        rejected: 'Rejected',
    };

    return map[status];
}

function verificationStatusClass(status?: CompanyStatus | null): string {
    switch (status) {
        case 'verified':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'docs_completed':
            return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'for_verification':
            return 'bg-violet-100 text-violet-700 border-violet-200';
        case 'needs_revision':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'rejected':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

function verificationStatusDot(status?: CompanyStatus | null): string {
    switch (status) {
        case 'verified':
            return 'bg-emerald-500';
        case 'docs_completed':
            return 'bg-blue-500';
        case 'for_verification':
            return 'bg-violet-500';
        case 'needs_revision':
            return 'bg-amber-500';
        case 'rejected':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Edit Company</DialogTitle>
                <DialogDescription>
                    Update the company's active status.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-5 py-1" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label>Company Name</Label>
                    <Input
                        :model-value="props.company.company_name"
                        readonly
                        class="bg-custom-bg text-custom-shadow/80 dark:bg-custom-bg-dark"
                    />
                </div>

                <div class="space-y-2">
                    <Label>Verification Status</Label>
                    <div class="flex h-10 items-center rounded-md border border-custom-bg-dark bg-custom-bg px-3 dark:border-custom-bg-light dark:bg-custom-bg-dark">
                        <Badge :class="['gap-1.5', verificationStatusClass(props.company.status ?? null)]">
                            <span :class="['h-1.5 w-1.5 rounded-full', verificationStatusDot(props.company.status ?? null)]" />
                            {{ humanizeStatus(props.company.status ?? null) }}
                        </Badge>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="edit_is_active">Active Status</Label>
                    <Select v-model="form.is_active" :disabled="form.processing">
                        <SelectTrigger id="edit_is_active">
                            <SelectValue placeholder="Select active status" />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem
                                v-for="option in ACTIVE_STATUS_OPTIONS"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.is_active" />
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
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
