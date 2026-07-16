<script setup lang="ts">
import { can } from '@/lib/can';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Building2,
    CalendarClock,
    Eye,
    FileCheck2,
    FileWarning,
    ImageIcon,
    Lock,
    Mail,
    MapPin,
    Phone,
    ShieldCheck,
    User,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { Textarea } from '@/components/ui/textarea';
import ExternalLayout from '@/layouts/ExternalLayout.vue';

const props = defineProps<{
    company: {
        id: number;
        company_name: string;
        company_code?: string | null;
        company_email?: string | null;
        company_phone?: string | null;
        company_address?: string | null;
        status: string;
        business_type?: string | null;
        registration_number?: string | null;
        authorized_representative_name?: string | null;
        authorized_representative_position?: string | null;
        authorized_representative_contact?: string | null;
        logo_url?: string | null;
        documents: Array<{
            id: number;
            doc_type: string;
            status: string;
            remarks?: string | null;
            expires_at?: string | null;
            updated_at?: string | null;
        }>;
    };
    latest_change_request: {
        id: number;
        status: 'pending' | 'approved' | 'rejected';
        rejection_reason?: string | null;
        created_at?: string | null;
        approved_at?: string | null;
        requested_values: Record<string, unknown>;
    } | null;
    profile_change_requests?: Array<{
        id: number;
        status: 'pending' | 'approved' | 'rejected';
        rejection_reason?: string | null;
        created_at?: string | null;
        approved_at?: string | null;
        requested_values: Record<string, unknown>;
    }>;
    user: {
        id: number;
        name: string;
        username: string;
        email: string;
    };
}>();

const canUpdateCompanyProfile = can('external_companies_settings.update');

const form = useForm({
    company_name: props.company.company_name ?? '',
    company_email: props.company.company_email ?? '',
    company_phone: props.company.company_phone ?? '',
    company_address: props.company.company_address ?? '',
    business_type: props.company.business_type ?? '',
    registration_number: props.company.registration_number ?? '',
    authorized_representative_name:
        props.company.authorized_representative_name ?? '',
    authorized_representative_position:
        props.company.authorized_representative_position ?? '',
    authorized_representative_contact:
        props.company.authorized_representative_contact ?? '',
    logo: null as File | null,
    remove_logo: false,
    compliance_document_file: null as File | null,
    compliance_document_issued_at: '',
    compliance_document_expires_at: '',
});

const canSubmitChanges = computed(
    () => props.latest_change_request?.status !== 'pending',
);
const canResubmitDocuments = computed(
    () => props.company.status === 'needs_revision',
);
const hasExpiredDocument = computed(() =>
    props.company.documents.some((doc) => doc.status === 'expired'),
);
const hasBusinessTypeChange = computed(
    () =>
        (form.business_type || null) !== (props.company.business_type || null),
);
const hasRegistrationNumberChange = computed(
    () =>
        (form.registration_number || '').trim() !==
        (props.company.registration_number || '').trim(),
);
const businessTypeRequiresRegistrationUpdate = computed(
    () => hasBusinessTypeChange.value && !hasRegistrationNumberChange.value,
);
const requiresComplianceDocument = computed(
    () => hasBusinessTypeChange.value || hasRegistrationNumberChange.value,
);
const hasComplianceDocumentInputsComplete = computed(() => {
    if (!requiresComplianceDocument.value) return true;
    return (
        !!form.compliance_document_file &&
        !!form.compliance_document_issued_at &&
        !!form.compliance_document_expires_at
    );
});
const canSubmitProfileForm = computed(
    () =>
        canUpdateCompanyProfile &&
        canSubmitChanges.value &&
        !businessTypeRequiresRegistrationUpdate.value &&
        hasComplianceDocumentInputsComplete.value,
);
const requiredComplianceDocType = computed(() =>
    form.business_type === 'corporate' ? 'SEC_CERT' : 'DTI_CERT',
);
const verifiedDocsCount = computed(
    () => props.company.documents.filter((doc) => doc.status === 'verified').length,
);
const pendingDocsCount = computed(
    () => props.company.documents.filter((doc) => doc.status === 'pending').length,
);
const actionRequiredDocs = computed(() =>
    props.company.documents.filter((doc) =>
        ['invalid', 'expired'].includes(doc.status),
    ),
);
const flaggedDocsCount = computed(() => actionRequiredDocs.value.length);
const canResubmitNow = computed(
    () => canResubmitDocuments.value && actionRequiredDocs.value.length > 0,
);
const profileError = computed(
    () => (form.errors as Record<string, string | undefined>).profile,
);
const requestDialogOpen = ref(false);
const requestDetailsDialogOpen = ref(false);
const selectedRequestId = ref<number | null>(
    props.latest_change_request?.id ?? null,
);
const profileRequestHistory = computed(
    () => props.profile_change_requests ?? [],
);
const selectedRequest = computed(() => {
    const current = profileRequestHistory.value.find(
        (item) => item.id === selectedRequestId.value,
    );
    return current ?? profileRequestHistory.value[0] ?? null;
});

function onLogoSelected(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.logo = file;
    if (file) form.remove_logo = false;
}

function submitProfile() {
    if (!canUpdateCompanyProfile) return;
    form.post('/profile/logo', {
        forceFormData: true,
        preserveScroll: true,
    });
}

function onComplianceDocumentSelected(event: Event) {
    form.compliance_document_file =
        (event.target as HTMLInputElement).files?.[0] ?? null;
}

function requestLogoRemoval() {
    if (!canUpdateCompanyProfile) return;
    form.logo = null;
    form.remove_logo = true;
    submitProfile();
}

function statusClass(status: string): string {
    if (status === 'verified') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'for_verification') return 'bg-amber-100 text-amber-700 border-amber-200';
    if (status === 'needs_revision') return 'bg-rose-100 text-rose-700 border-rose-200';
    if (status === 'draft') return 'bg-slate-100 text-slate-700 border-slate-200';
    return 'bg-slate-100 text-slate-700 border-slate-200';
}

function humanize(value: string | null | undefined): string {
    if (!value) return '—';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function requestStatusClass(status: string): string {
    if (status === 'approved') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'pending') return 'bg-amber-100 text-amber-700 border-amber-200';
    if (status === 'rejected') return 'bg-rose-100 text-rose-700 border-rose-200';
    return 'bg-slate-100 text-slate-700 border-slate-200';
}

function formatDateTime(value: string | null | undefined): string {
    if (!value) return 'Not available';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function formatDate(value: string | null | undefined): string {
    if (!value) return 'Not set';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
}

function businessTypeLabel(value: string | null | undefined): string {
    if (!value) return 'No value';
    const map: Record<string, string> = {
        corporate: 'Corporate',
        sole_proprietorship: 'Sole Proprietorship',
    };
    return map[value] ?? humanize(value);
}

function documentTypeLabel(value: string | null | undefined): string {
    if (!value) return 'Document';
    const map: Record<string, string> = {
        SEC_CERT: 'SEC Certificate',
        DTI_CERT: 'DTI Certificate',
        MAYORS_PERMIT: "Mayor's Permit",
        BIR_2303: 'BIR Form 2303',
        AUTHORIZATION_LETTER: 'Authorization Letter',
    };
    return map[value] ?? humanize(value);
}

function prettifyPrimitive(value: string): string {
    if (/^[a-z0-9_]+$/.test(value) && value.includes('_')) return humanize(value);
    return value;
}

function formatRequestedValue(key: string, value: unknown): string {
    if (value === null || value === undefined || value === '') return 'No value';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (key === 'business_type' && typeof value === 'string') return businessTypeLabel(value);
    if (Array.isArray(value))
        return value.length ? value.map((v) => prettifyPrimitive(String(v))).join(', ') : 'No value';
    if (typeof value === 'object') return 'Updated details available';
    return prettifyPrimitive(String(value));
}

const readableRequestedValues = computed(() => {
    const values = selectedRequest.value?.requested_values ?? {};
    const entries: Array<{ label: string; value: string }> = [];

    for (const [key, value] of Object.entries(values)) {
        if (['logo_path', 'logo_url'].includes(key)) continue;

        if (key === '_supporting_documents' && value && typeof value === 'object' && !Array.isArray(value)) {
            const docs = Object.values(value as Record<string, any>);
            for (const doc of docs) {
                entries.push({
                    label: `${documentTypeLabel(doc?.doc_type)} (Supporting Document)`,
                    value: `Issued ${formatDate(doc?.issued_at)} • Expires ${formatDate(doc?.expires_at)}`,
                });
            }
            continue;
        }

        if (key === 'logo' && value === null) {
            entries.push({ label: 'Logo', value: 'Request to remove logo' });
            continue;
        }

        entries.push({ label: humanize(key), value: formatRequestedValue(key, value) });
    }

    return entries;
});

function requestedFieldCount(request: { requested_values: Record<string, unknown> }): number {
    return Object.keys(request.requested_values ?? {}).filter((k) => k !== '_supporting_documents').length;
}

function selectRequest(requestId: number | string): void {
    const id = Number(requestId);
    selectedRequestId.value = Number.isNaN(id) ? null : id;
}

function openRequestDetails(requestId: number | string): void {
    selectRequest(requestId);
    requestDetailsDialogOpen.value = true;
}
</script>

<template>
    <Head :title="`Profile - ${company.company_name}`" />

    <ExternalLayout :company="company" :user="user">
        <div class="mx-auto max-w-6xl space-y-5 p-4 md:p-8">

            
            <Card>
                <CardContent class="px-6 py-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-1.5 rounded-md border bg-muted/50 px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                <Building2 class="h-3 w-3" />
                                {{ company.company_code || 'No company code' }}
                            </div>
                            <h1 class="text-2xl font-semibold tracking-tight">Company Profile</h1>
                            <p class="text-sm text-muted-foreground">
                                Keep your profile accurate. All updates are staged and reviewed by admins before they are applied.
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <ShieldCheck class="h-4 w-4 text-muted-foreground" />
                            <Badge :class="statusClass(company.status)">
                                {{ humanize(company.status) }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            
            <div class="grid gap-3 md:grid-cols-3">
                <Card class="border-emerald-200/70 bg-emerald-50/50">
                    <CardContent class="flex items-center justify-between p-4">
                        <div>
                            <p class="text-xs font-medium text-emerald-700">Verified</p>
                            <p class="mt-1 text-2xl font-semibold text-emerald-800">{{ verifiedDocsCount }}</p>
                        </div>
                        <FileCheck2 class="h-5 w-5 text-emerald-600" />
                    </CardContent>
                </Card>

                <Card class="border-amber-200/70 bg-amber-50/60">
                    <CardContent class="flex items-center justify-between p-4">
                        <div>
                            <p class="text-xs font-medium text-amber-700">Under review</p>
                            <p class="mt-1 text-2xl font-semibold text-amber-800">{{ pendingDocsCount }}</p>
                        </div>
                        <Building2 class="h-5 w-5 text-amber-600" />
                    </CardContent>
                </Card>

                <Card class="border-rose-200/70 bg-rose-50/60">
                    <CardContent class="flex items-center justify-between p-4">
                        <div>
                            <p class="text-xs font-medium text-rose-700">Needs action</p>
                            <p class="mt-1 text-2xl font-semibold text-rose-800">{{ flaggedDocsCount }}</p>
                        </div>
                        <FileWarning class="h-5 w-5 text-rose-600" />
                    </CardContent>
                </Card>
            </div>

            
            <div class="grid gap-5 lg:grid-cols-[1.5fr,1fr]">

                
                <Card>
                    <CardHeader>
                        <CardTitle>Edit company details</CardTitle>
                        <CardDescription>
                            Update your contact and representative details. Locked fields are managed by admin.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submitProfile">

                            
                            <div class="flex items-start gap-3 rounded-md border border-blue-200 bg-blue-50 p-3 md:col-span-2">
                                <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" />
                                <div>
                                    <p class="text-xs font-medium text-blue-800">Before you submit</p>
                                    <p class="mt-0.5 text-xs text-blue-700">
                                        Update editable fields (email, phone, address, representative info, or logo).
                                        Locked fields — company name, registration number, business type — are read-only and managed by admin.
                                    </p>
                                </div>
                            </div>

                            
                            <div class="flex items-center gap-3 md:col-span-2">
                                <div class="flex items-center gap-1.5 whitespace-nowrap text-xs font-medium text-muted-foreground">
                                    <Lock class="h-3 w-3" />
                                    Read-only identity
                                </div>
                                <div class="h-px flex-1 bg-border" />
                                <Badge variant="secondary" class="text-[11px]">Managed by admin</Badge>
                            </div>

                            <div class="space-y-2">
                                <Label for="company_name" class="inline-flex items-center gap-1.5">
                                    <Building2 class="h-3.5 w-3.5" /> Company name
                                </Label>
                                <Input
                                    id="company_name"
                                    v-model="form.company_name"
                                    disabled
                                    class="bg-muted/50 disabled:cursor-not-allowed disabled:opacity-70"
                                />
                                <p v-if="form.errors.company_name" class="text-xs text-destructive">
                                    {{ form.errors.company_name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="company_email" class="inline-flex items-center gap-1.5">
                                    <Mail class="h-3.5 w-3.5" /> Company email
                                </Label>
                                <Input id="company_email" type="email" v-model="form.company_email" />
                                <p v-if="form.errors.company_email" class="text-xs text-destructive">
                                    {{ form.errors.company_email }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="registration_number" class="inline-flex items-center gap-1.5">
                                    <FileCheck2 class="h-3.5 w-3.5" /> Registration number
                                </Label>
                                <Input
                                    id="registration_number"
                                    v-model="form.registration_number"
                                    disabled
                                    class="bg-muted/50 disabled:cursor-not-allowed disabled:opacity-70"
                                />
                                <p v-if="form.errors.registration_number" class="text-xs text-destructive">
                                    {{ form.errors.registration_number }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="business_type" class="inline-flex items-center gap-1.5">
                                    <Building2 class="h-3.5 w-3.5" /> Business type
                                </Label>
                                <select
                                    id="business_type"
                                    v-model="form.business_type"
                                    disabled
                                    class="w-full rounded-md border bg-muted/50 px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    <option value="">Select business type</option>
                                    <option value="corporate">Corporate</option>
                                    <option value="sole_proprietorship">Sole Proprietorship</option>
                                </select>
                            </div>

                            
                            <div class="flex items-center gap-3 md:col-span-2">
                                <div class="flex items-center gap-1.5 whitespace-nowrap text-xs font-medium text-emerald-700">
                                    <ShieldCheck class="h-3 w-3" />
                                    Editable details
                                </div>
                                <div class="h-px flex-1 bg-border" />
                                <Badge class="border-emerald-200 bg-emerald-50 text-[11px] text-emerald-700">
                                    You can update
                                </Badge>
                            </div>

                            <div class="space-y-2">
                                <Label for="company_phone" class="inline-flex items-center gap-1.5">
                                    <Phone class="h-3.5 w-3.5" /> Phone
                                </Label>
                                <Input id="company_phone" v-model="form.company_phone" />
                            </div>

                            <div class="space-y-2">
                                <Label for="authorized_representative_name" class="inline-flex items-center gap-1.5">
                                    <User class="h-3.5 w-3.5" /> Authorized representative
                                </Label>
                                <Input id="authorized_representative_name" v-model="form.authorized_representative_name" />
                            </div>

                            <div class="space-y-2">
                                <Label for="authorized_representative_position" class="inline-flex items-center gap-1.5">
                                    <ShieldCheck class="h-3.5 w-3.5" /> Representative position
                                </Label>
                                <Input id="authorized_representative_position" v-model="form.authorized_representative_position" />
                            </div>

                            <div class="space-y-2">
                                <Label for="authorized_representative_contact" class="inline-flex items-center gap-1.5">
                                    <Phone class="h-3.5 w-3.5" /> Representative contact
                                </Label>
                                <Input id="authorized_representative_contact" v-model="form.authorized_representative_contact" />
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <Label for="company_address" class="inline-flex items-center gap-1.5">
                                    <MapPin class="h-3.5 w-3.5" /> Company address
                                </Label>
                                <Textarea id="company_address" v-model="form.company_address" rows="3" />
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <Label for="logo" class="inline-flex items-center gap-1.5">
                                    <ImageIcon class="h-3.5 w-3.5" /> Company logo
                                </Label>
                                <label
                                    for="logo"
                                    class="flex cursor-pointer items-center justify-center rounded-md border border-dashed px-4 py-3 text-sm text-muted-foreground transition-colors hover:bg-muted/40"
                                >
                                    <ImageIcon class="mr-2 h-4 w-4 shrink-0" />
                                    Click to upload — JPG, PNG, or WEBP up to 2MB
                                    <input
                                        id="logo"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        class="sr-only"
                                        @change="onLogoSelected"
                                    />
                                </label>
                                <p v-if="company.logo_url" class="text-xs text-muted-foreground">
                                    A current logo is set.
                                </p>
                                <p v-if="form.errors.logo" class="text-xs text-destructive">
                                    {{ form.errors.logo }}
                                </p>
                            </div>

                            
                            <div
                                v-if="requiresComplianceDocument"
                                class="space-y-3 rounded-md border border-amber-200 bg-amber-50 p-3 md:col-span-2"
                            >
                                <div class="flex items-start gap-2">
                                    <FileWarning class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" />
                                    <div>
                                        <p class="text-xs font-medium text-amber-800">Major change detected</p>
                                        <p class="text-xs text-amber-700">
                                            Re-upload {{ requiredComplianceDocType }} to continue with this request.
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="compliance_document_file" class="inline-flex items-center gap-1.5 text-amber-800">
                                        <FileCheck2 class="h-3.5 w-3.5" /> {{ requiredComplianceDocType }} file
                                    </Label>
                                    <input
                                        id="compliance_document_file"
                                        type="file"
                                        accept="application/pdf,image/jpeg,image/png"
                                        class="block w-full text-sm text-muted-foreground file:mr-3 file:rounded-md file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-muted/40"
                                        @change="onComplianceDocumentSelected"
                                    />
                                    <p class="text-xs text-amber-700">Upload PDF, JPG, or PNG (max 5MB).</p>
                                    <p v-if="form.errors.compliance_document_file" class="text-xs text-destructive">
                                        {{ form.errors.compliance_document_file }}
                                    </p>
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="compliance_document_issued_at" class="inline-flex items-center gap-1.5 text-amber-800">
                                            <CalendarClock class="h-3.5 w-3.5" /> Issue date
                                        </Label>
                                        <Input id="compliance_document_issued_at" type="date" v-model="form.compliance_document_issued_at" />
                                        <p v-if="form.errors.compliance_document_issued_at" class="text-xs text-destructive">
                                            {{ form.errors.compliance_document_issued_at }}
                                        </p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="compliance_document_expires_at" class="inline-flex items-center gap-1.5 text-amber-800">
                                            <CalendarClock class="h-3.5 w-3.5" /> Expiry date
                                        </Label>
                                        <Input id="compliance_document_expires_at" type="date" v-model="form.compliance_document_expires_at" />
                                        <p v-if="form.errors.compliance_document_expires_at" class="text-xs text-destructive">
                                            {{ form.errors.compliance_document_expires_at }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="flex flex-wrap items-center gap-2 border-t pt-4 md:col-span-2">
                                <Button
                                    variant="default"
                                    type="submit"
                                    :disabled="form.processing || !canSubmitProfileForm"
                                >
                                    {{ form.processing ? 'Submitting...' : 'Submit changes for review' }}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="border-rose-200 text-rose-700 hover:bg-rose-50 hover:text-rose-800"
                                    :disabled="form.processing || !canUpdateCompanyProfile || !canSubmitChanges || !company.logo_url"
                                    @click="requestLogoRemoval"
                                >
                                    Remove current logo
                                </Button>
                            </div>

                            
                            <div
                                v-if="businessTypeRequiresRegistrationUpdate"
                                class="flex items-start gap-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 md:col-span-2"
                            >
                                <FileWarning class="mt-0.5 h-3.5 w-3.5 shrink-0 text-rose-700" />
                                <p class="text-xs text-rose-800">
                                    Update the registration number to match your new business type before submitting.
                                </p>
                            </div>

                            <div
                                v-if="requiresComplianceDocument && !hasComplianceDocumentInputsComplete"
                                class="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 md:col-span-2"
                            >
                                <CalendarClock class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-700" />
                                <p class="text-xs text-amber-800">
                                    Upload {{ requiredComplianceDocType }} and complete the issue and expiry dates.
                                </p>
                            </div>

                            <div
                                v-if="!canSubmitChanges"
                                class="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 md:col-span-2"
                            >
                                <CalendarClock class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-700" />
                                <p class="text-xs text-amber-800">
                                    You already have a pending profile request. Wait for admin action before submitting again.
                                </p>
                            </div>

                            <p v-if="profileError" class="text-xs text-destructive md:col-span-2">
                                {{ profileError }}
                            </p>
                        </form>
                    </CardContent>
                </Card>

                
                <div class="space-y-5">

                    
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <ImageIcon class="h-4 w-4" /> Brand preview
                            </CardTitle>
                            <CardDescription>Current company identity snapshot.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="overflow-hidden rounded-md border bg-muted/30">
                                <img
                                    v-if="company.logo_url"
                                    :src="company.logo_url"
                                    alt="Company logo"
                                    class="h-40 w-full object-contain p-4"
                                />
                                <div
                                    v-else
                                    class="flex h-40 flex-col items-center justify-center gap-2"
                                >
                                    <div class="flex h-14 w-14 items-center justify-center rounded-md bg-muted text-lg font-semibold text-muted-foreground">
                                        {{ (company.company_code ?? company.company_name).slice(0, 2).toUpperCase() }}
                                    </div>
                                    <p class="text-xs text-muted-foreground">No logo uploaded</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    
                    <Card v-if="latest_change_request">
                        <CardHeader>
                            <CardTitle>Latest change request</CardTitle>
                            <CardDescription>
                                Track the latest request before submitting another profile update.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <Badge :class="requestStatusClass(latest_change_request.status)">
                                    {{ humanize(latest_change_request.status) }}
                                </Badge>
                                <Button variant="outline" class="cursor-pointer" size="sm" @click="requestDialogOpen = true">
                                    <Eye class="mr-1.5 h-3.5 w-3.5" /> View details
                                </Button>
                            </div>

                            <div class="rounded-md border bg-muted/30 p-3 text-xs text-muted-foreground">
                                <p><span class="font-medium text-foreground">Request:</span> #{{ latest_change_request.id }}</p>
                                <p class="mt-1"><span class="font-medium text-foreground">Submitted:</span> {{ formatDateTime(latest_change_request.created_at) }}</p>
                            </div>

                            <div v-if="latest_change_request.status === 'pending'" class="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                <CalendarClock class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                Your request is under review. You can submit again once admins finish processing.
                            </div>

                            <div v-if="latest_change_request.status === 'approved'" class="flex items-start gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                                <FileCheck2 class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                Your latest profile change request was approved.
                            </div>

                            <div v-if="latest_change_request.status === 'rejected'" class="flex items-start gap-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800">
                                <FileWarning class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                {{ latest_change_request.rejection_reason || 'No reason provided.' }}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            
            <Card>
                <CardHeader>
                    <CardTitle>Document status</CardTitle>
                    <CardDescription>
                        Required documents can be re-uploaded only when your company needs revision.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="doc in company.documents"
                        :key="doc.id"
                        class="flex items-start justify-between gap-4 rounded-md border p-3 transition-colors hover:bg-muted/20"
                    >
                        <div class="space-y-0.5">
                            <p class="text-sm font-medium">{{ documentTypeLabel(doc.doc_type) }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ humanize(doc.status) }}
                                <span v-if="doc.expires_at"> · Expires {{ formatDate(doc.expires_at) }}</span>
                            </p>
                            <p v-if="doc.remarks" class="text-xs text-rose-700">
                                Remarks: {{ doc.remarks }}
                            </p>
                        </div>
                        <Badge :class="statusClass(doc.status)" class="shrink-0">
                            {{ humanize(doc.status) }}
                        </Badge>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 border-t pt-3">
                        <Button v-if="canResubmitNow" as-child>
                            <Link href="/registration/status">Resubmit documents</Link>
                        </Button>
                        <Button v-else type="button" disabled>Resubmit documents</Button>
                        <p class="text-xs text-muted-foreground">
                            Enabled only when status is Needs Revision and there are expired or invalid documents.
                        </p>
                    </div>

                    <div v-if="hasExpiredDocument" class="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        <FileWarning class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                        One or more documents are expired. Reupload them in the registration status page.
                    </div>
                </CardContent>
            </Card>
        </div>

        
        <Dialog v-model:open="requestDialogOpen">
            <DialogContent class="max-h-[85vh] overflow-hidden sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <CalendarClock class="h-4 w-4" /> Profile change request history
                    </DialogTitle>
                    <DialogDescription>
                        Click "View changes" to open a detailed breakdown of each request.
                    </DialogDescription>
                </DialogHeader>

                <div class="max-h-[60vh] space-y-2 overflow-y-auto rounded-md border p-2">
                    <div
                        v-for="requestItem in profileRequestHistory"
                        :key="requestItem.id"
                        class="cursor-pointer rounded-md border p-3 text-left transition-colors"
                        :class="selectedRequestId === requestItem.id ? 'border-foreground/30 bg-muted/40' : 'border-border hover:bg-muted/20'"
                        @click="selectRequest(requestItem.id)"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-medium">Change request #{{ requestItem.id }}</p>
                            <Badge :class="requestStatusClass(requestItem.status)">
                                {{ humanize(requestItem.status) }}
                            </Badge>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">{{ formatDateTime(requestItem.created_at) }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ requestedFieldCount(requestItem) }} field(s) updated</p>
                        <p v-if="requestItem.status === 'rejected'" class="mt-1 line-clamp-2 text-xs text-rose-700">
                            {{ requestItem.rejection_reason || 'No reason provided.' }}
                        </p>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="mt-2 h-7 px-2 text-xs"
                            @click.stop="openRequestDetails(requestItem.id)"
                        >
                            View changes
                        </Button>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="requestDialogOpen = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        
        <Dialog v-model:open="requestDetailsDialogOpen">
            <DialogContent class="max-h-[85vh] overflow-hidden sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <CalendarClock class="h-4 w-4" /> Request #{{ selectedRequest?.id }} details
                    </DialogTitle>
                    <DialogDescription>
                        Complete summary of requested changes and important details.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedRequest" class="max-h-[60vh] space-y-4 overflow-y-auto pr-1 text-sm">
                    <div class="rounded-md border bg-muted/30 p-3 text-xs">
                        <div class="space-y-1.5 text-muted-foreground">
                            <p><span class="font-medium text-foreground">Request:</span> #{{ selectedRequest.id }}</p>
                            <p><span class="font-medium text-foreground">Status:</span> {{ humanize(selectedRequest.status) }}</p>
                            <p><span class="font-medium text-foreground">Submitted:</span> {{ formatDateTime(selectedRequest.created_at) }}</p>
                            <p v-if="selectedRequest.approved_at">
                                <span class="font-medium text-foreground">Reviewed:</span> {{ formatDateTime(selectedRequest.approved_at) }}
                            </p>
                        </div>
                        <div v-if="selectedRequest.status === 'rejected'" class="mt-2 flex items-start gap-2 rounded-md border border-rose-200 bg-rose-50 px-2 py-1.5 text-xs text-rose-800">
                            <FileWarning class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                            {{ selectedRequest.rejection_reason || 'No reason provided.' }}
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-medium text-muted-foreground">Requested updates</p>
                        <div v-if="readableRequestedValues.length" class="space-y-2">
                            <div
                                v-for="item in readableRequestedValues"
                                :key="`${selectedRequest.id}-${item.label}`"
                                class="rounded-md border p-2.5"
                            >
                                <p class="text-xs text-muted-foreground">{{ item.label }}</p>
                                <p class="mt-0.5 break-words text-sm font-medium">{{ item.value }}</p>
                            </div>
                        </div>
                        <p v-else class="rounded-md border p-3 text-xs text-muted-foreground">
                            No field-level details available for this request.
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="requestDetailsDialogOpen = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </ExternalLayout>
</template>