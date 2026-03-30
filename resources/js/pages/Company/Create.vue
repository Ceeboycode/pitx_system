<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index, store } from '@/routes/companies';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';

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
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Create Company', href: create().url },
];

type BusinessType = 'corporate' | 'sole_proprietorship';

type CompanyForm = {
    company_name: string;
    company_email: string;
    company_phone: string;
    company_address: string;

    business_type: BusinessType;
    registration_number: string;

    authorized_representative_name: string;
    authorized_representative_position: string;
    authorized_representative_contact: string;

    sec_cert: File | null;
    dti_cert: File | null;
    mayors_permit: File | null;
    bir_2303: File | null;
    authorization_letter: File | null;
};

const form = useForm<CompanyForm>({
    company_name: '',
    company_email: '',
    company_phone: '',
    company_address: '',

    business_type: 'corporate',
    registration_number: '',

    authorized_representative_name: '',
    authorized_representative_position: '',
    authorized_representative_contact: '',

    sec_cert: null,
    dti_cert: null,
    mayors_permit: null,
    bir_2303: null,
    authorization_letter: null,
});

const submit = () => {
    form.post(store().url, {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create Company" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl leading-tight font-semibold">
                        Create Company
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Encode company details and submit required documents
                        (single submit).
                    </p>
                </div>

                <Button as-child variant="outline">
                    <Link :href="index().url">Back</Link>
                </Button>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle>Company Details</CardTitle>
                        <CardDescription>
                            Basic company information used for onboarding and
                            contact.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="company_name">Company Name *</Label>
                                <Input
                                    id="company_name"
                                    v-model="form.company_name"
                                    placeholder="ABC Transport Corp."
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.company_name"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label>Business Type *</Label>
                                <Select v-model="form.business_type">
                                    <SelectTrigger>
                                        <SelectValue
                                            placeholder="Select business type"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="corporate"
                                            >Corporate</SelectItem
                                        >
                                        <SelectItem value="sole_proprietorship"
                                            >Sole Proprietorship</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.business_type"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="company_email"
                                    >Company Email *</Label
                                >
                                <Input
                                    id="company_email"
                                    v-model="form.company_email"
                                    placeholder="company@email.com"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.company_email"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="company_phone"
                                    >Company Number *</Label
                                >
                                <Input
                                    id="company_phone"
                                    v-model="form.company_phone"
                                    placeholder="0917xxxxxxx"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.company_phone"
                                />
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <Label for="company_address"
                                    >Company Address *</Label
                                >
                                <Textarea
                                    id="company_address"
                                    v-model="form.company_address"
                                    placeholder="Full office address..."
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.company_address"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="registration_number"
                                    >Registration Number (SEC/DTI No.)</Label
                                >
                                <Input
                                    id="registration_number"
                                    v-model="form.registration_number"
                                    placeholder="Optional"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.registration_number"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Authorized Representative</CardTitle>
                        <CardDescription>
                            Optional but recommended. Helps track who is
                            responsible for terminal coordination.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="space-y-2">
                                <Label for="rep_name">Name</Label>
                                <Input
                                    id="rep_name"
                                    v-model="
                                        form.authorized_representative_name
                                    "
                                    placeholder="Juan Dela Cruz"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="
                                        form.errors
                                            .authorized_representative_name
                                    "
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="rep_position">Position</Label>
                                <Input
                                    id="rep_position"
                                    v-model="
                                        form.authorized_representative_position
                                    "
                                    placeholder="Operations Manager"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="
                                        form.errors
                                            .authorized_representative_position
                                    "
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="rep_contact">Contact</Label>
                                <Input
                                    id="rep_contact"
                                    v-model="
                                        form.authorized_representative_contact
                                    "
                                    placeholder="0917xxxxxxx / email"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="
                                        form.errors
                                            .authorized_representative_contact
                                    "
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Company Documents</CardTitle>
                        <CardDescription>
                            Upload required documents. Allowed: PDF/JPG/PNG.
                            Max: 10MB each.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <Separator />

                        <div class="grid gap-4 md:grid-cols-2">
                            <div
                                v-if="form.business_type === 'corporate'"
                                class="space-y-2"
                            >
                                <Label>SEC Certificate *</Label>
                                <Input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    @change="
                                        form.sec_cert =
                                            ($event.target as HTMLInputElement)
                                                .files?.[0] ?? null
                                    "
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.sec_cert"
                                />
                            </div>

                            <div v-else class="space-y-2">
                                <Label>DTI Certificate *</Label>
                                <Input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    @change="
                                        form.dti_cert =
                                            ($event.target as HTMLInputElement)
                                                .files?.[0] ?? null
                                    "
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.dti_cert"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label>Mayor’s Permit *</Label>
                                <Input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    @change="
                                        form.mayors_permit =
                                            ($event.target as HTMLInputElement)
                                                .files?.[0] ?? null
                                    "
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.mayors_permit"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label>BIR Form 2303 *</Label>
                                <Input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    @change="
                                        form.bir_2303 =
                                            ($event.target as HTMLInputElement)
                                                .files?.[0] ?? null
                                    "
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.bir_2303"
                                />
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <Label
                                    >Authorization Letter / Board Resolution (if
                                    someone else is transacting)</Label
                                >
                                <Input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    @change="
                                        form.authorization_letter =
                                            ($event.target as HTMLInputElement)
                                                .files?.[0] ?? null
                                    "
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.authorization_letter"
                                />
                            </div>
                        </div>

                        <div v-if="form.progress" class="space-y-2">
                            <div class="text-sm text-muted-foreground">
                                Uploading… {{ form.progress.percentage }}%
                            </div>
                            <progress
                                class="h-2 w-full"
                                :value="form.progress.percentage"
                                max="100"
                            />
                        </div>
                    </CardContent>
                </Card>
                
                <div class="flex items-center gap-2">
                    <Button type="submit" :disabled="form.processing">
                        {{
                            form.processing ? 'Submitting...' : 'Submit Company'
                        }}
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
