<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit, index, update } from '@/routes/companies';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';

type CompanyStatus =
    | 'draft'
    | 'docs_completed'
    | 'for_verification'
    | 'verified'
    | 'needs_revision'
    | 'rejected';

type ActiveStatusValue = '1' | '0';

type Company = {
    id: number;
    company_name: string;
    status?: CompanyStatus | null;
    is_active?: boolean | number | null;
};

const props = defineProps<{ company: Company }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Edit Company', href: edit(props.company.id).url },
];

const form = useForm({
    is_active: (props.company.is_active === false || props.company.is_active === 0
        ? '0'
        : '1') as ActiveStatusValue,
});

const activeStatusOptions: { value: ActiveStatusValue; label: string }[] = [
    { value: '1', label: 'Active' },
    { value: '0', label: 'Inactive' },
];

function submit(): void {
    form.put(update(props.company.id).url, {
        preserveScroll: true,
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
    <Head title="Edit Company" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl leading-tight font-semibold">
                        Edit Company
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Manage the company's active status.
                    </p>
                </div>

                <Button as-child variant="outline">
                    <Link :href="index().url">Back</Link>
                </Button>
            </div>

            <form class="max-w-2xl space-y-4" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle>Company Status</CardTitle>
                        <CardDescription>
                            Company name and verification status are read-only here.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-5">
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
                            <Label for="is_active">Active Status</Label>
                            <Select v-model="form.is_active" :disabled="form.processing">
                                <SelectTrigger id="is_active">
                                    <SelectValue placeholder="Select active status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in activeStatusOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.is_active" />
                        </div>
                    </CardContent>
                </Card>

                <div class="flex items-center gap-2">
                    <Button type="submit" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="form.reset()"
                    >
                        Reset
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
