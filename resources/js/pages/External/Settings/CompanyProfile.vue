<script setup lang="ts">
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
        canSubmitChanges.value &&
        !businessTypeRequiresRegistrationUpdate.value &&
        hasComplianceDocumentInputsComplete.value,
);
const requiredComplianceDocType = computed(() =>
    form.business_type === 'corporate' ? 'SEC_CERT' : 'DTI_CERT',
);
const verifiedDocsCount = computed(
    () =>
        props.company.documents.filter((doc) => doc.status === 'verified')
            .length,
);
const pendingDocsCount = computed(
    () =>
        props.company.documents.filter((doc) => doc.status === 'pending')
            .length,
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
    if (file) {
        form.remove_logo = false;
    }
}

function submitProfile() {
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
    form.logo = null;
    form.remove_logo = true;
    submitProfile();
}

function statusClass(status: string): string {
    if (status === 'verified')
        return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'for_verification')
        return 'bg-amber-100 text-amber-700 border-amber-200';
    if (status === 'needs_revision')
        return 'bg-rose-100 text-rose-700 border-rose-200';
    if (status === 'draft')
        return 'bg-slate-100 text-slate-700 border-slate-200';
    return 'bg-slate-100 text-slate-700 border-slate-200';
}

function humanize(value: string | null | undefined): string {
    if (!value) return '—';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function requestStatusClass(status: string): string {
    if (status === 'approved')
        return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'pending')
        return 'bg-amber-100 text-amber-700 border-amber-200';
    if (status === 'rejected')
        return 'bg-rose-100 text-rose-700 border-rose-200';
    return 'bg-slate-100 text-slate-700 border-slate-200';
}

function formatDateTime(value: string | null | undefined): string {
    if (!value) return 'Not available';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDate(value: string | null | undefined): string {
    if (!value) return 'Not set';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
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
    if (/^[a-z0-9_]+$/.test(value) && value.includes('_')) {
        return humanize(value);
    }

    return value;
}

function formatRequestedValue(key: string, value: unknown): string {
    if (value === null || value === undefined || value === '')
        return 'No value';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';

    if (key === 'business_type' && typeof value === 'string') {
        return businessTypeLabel(value);
    }

    if (Array.isArray(value))
        return value.length
            ? value.map((v) => prettifyPrimitive(String(v))).join(', ')
            : 'No value';
    if (typeof value === 'object') return 'Updated details available';
    return prettifyPrimitive(String(value));
}

const readableRequestedValues = computed(() => {
    const values = selectedRequest.value?.requested_values ?? {};
    const entries: Array<{ label: string; value: string }> = [];

    for (const [key, value] of Object.entries(values)) {
        if (['logo_path', 'logo_url'].includes(key)) {
            continue;
        }

        if (
            key === '_supporting_documents' &&
            value &&
            typeof value === 'object' &&
            !Array.isArray(value)
        ) {
            const docs = Object.values(value as Record<string, any>);
            for (const doc of docs) {
                const docType = documentTypeLabel(doc?.doc_type);
                const issuedAt = formatDate(doc?.issued_at);
                const expiresAt = formatDate(doc?.expires_at);
                entries.push({
                    label: `${docType} (Supporting Document)`,
                    value: `Issued ${issuedAt} • Expires ${expiresAt}`,
                });
            }
            continue;
        }

        if (key === 'logo' && value === null) {
            entries.push({ label: 'Logo', value: 'Request to remove logo' });
            continue;
        }

        entries.push({
            label: humanize(key),
            value: formatRequestedValue(key, value),
        });
    }

    return entries;
});

function requestedFieldCount(request: {
    requested_values: Record<string, unknown>;
}): number {
    const values = request.requested_values ?? {};
    return Object.keys(values).filter((key) => key !== '_supporting_documents')
        .length;
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
        <div class="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
            <Card class="overflow-hidden border-slate-200 shadow-sm">
                <CardContent class="relative px-6 py-7">
                    <div
                        class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(251,191,36,0.16),transparent_55%),radial-gradient(circle_at_bottom_left,rgba(14,165,233,0.12),transparent_55%)]"
                    />
                    <div
                        class="relative flex flex-col gap-5 md:flex-row md:items-start md:justify-between"
                    >
                        <div class="space-y-3">
                            <Badge
                                class="border-slate-300 bg-white/80 text-slate-700"
                            >
                                Company Code:
                                {{ company.company_code || 'Not Assigned' }}
                            </Badge>
                            <div>
                                <h1
                                    class="text-2xl font-semibold tracking-tight md:text-3xl"
                                >
                                    Company Profile
                                </h1>
                                <p
                                    class="mt-1 text-sm text-muted-foreground md:text-base"
                                >
                                    Keep your profile accurate. All updates are
                                    staged and reviewed by admins before they
                                    are applied.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <ShieldCheck class="h-4 w-4 text-slate-500" />
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
                            <p
                                class="text-xs font-medium tracking-widest text-emerald-700 uppercase"
                            >
                                Verified
                            </p>
                            <p
                                class="mt-1 text-2xl font-semibold text-emerald-800"
                            >
                                {{ verifiedDocsCount }}
                            </p>
                        </div>
                        <FileCheck2 class="h-5 w-5 text-emerald-700" />
                    </CardContent>
                </Card>

                <Card class="border-amber-200/70 bg-amber-50/60">
                    <CardContent class="flex items-center justify-between p-4">
                        <div>
                            <p
                                class="text-xs font-medium tracking-widest text-amber-700 uppercase"
                            >
                                Under Review
                            </p>
                            <p
                                class="mt-1 text-2xl font-semibold text-amber-800"
                            >
                                {{ pendingDocsCount }}
                            </p>
                        </div>
                        <Building2 class="h-5 w-5 text-amber-700" />
                    </CardContent>
                </Card>

                <Card class="border-rose-200/70 bg-rose-50/60">
                    <CardContent class="flex items-center justify-between p-4">
                        <div>
                            <p
                                class="text-xs font-medium tracking-widest text-rose-700 uppercase"
                            >
                                Needs Action
                            </p>
                            <p
                                class="mt-1 text-2xl font-semibold text-rose-800"
                            >
                                {{ flaggedDocsCount }}
                            </p>
                        </div>
                        <FileWarning class="h-5 w-5 text-rose-700" />
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.5fr,1fr]">
                <Card>
                    <CardHeader>
                        <CardTitle>Edit Company Details</CardTitle>
                        <CardDescription>
                            Update your contact and representative details.
                            Locked fields are managed by admin.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            class="grid gap-4 md:grid-cols-2"
                            @submit.prevent="submitProfile"
                        >
                            <div
                                class="space-y-2 rounded-lg border border-sky-200 bg-sky-50 p-3 md:col-span-2"
                            >
                                <div class="flex items-center gap-2">
                                    <ShieldCheck class="h-4 w-4 text-sky-700" />
                                    <p
                                        class="text-xs font-semibold tracking-widest text-sky-700 uppercase"
                                    >
                                        Before You Submit
                                    </p>
                                </div>
                                <p class="text-xs text-sky-800">
                                    1) Update editable details (email, phone,
                                    address, representative info, or logo). 2)
                                    Locked fields (Company Name, Registration
                                    Number, Business Type) are read-only. 3)
                                    Submit once your updates are complete.
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <div
                                    class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"
                                >
                                    <div class="inline-flex items-center gap-2">
                                        <Lock class="h-4 w-4 text-slate-500" />
                                        <p
                                            class="text-xs font-semibold tracking-widest text-slate-700 uppercase"
                                        >
                                            Read-only Company Identity
                                        </p>
                                    </div>
                                    <Badge
                                        variant="secondary"
                                        class="text-[11px]"
                                    >
                                        Managed by Admin
                                    </Badge>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label
                                    for="company_name"
                                    class="inline-flex items-center gap-2"
                                >
                                    <Building2 class="h-4 w-4" />
                                    Company Name
                                </Label>
                                <Input
                                    id="company_name"
                                    v-model="form.company_name"
                                    disabled
                                    class="bg-slate-100 disabled:cursor-not-allowed disabled:opacity-80"
                                />
                                <p
                                    v-if="form.errors.company_name"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors.company_name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label
                                    for="company_email"
                                    class="inline-flex items-center gap-2"
                                >
                                    <Mail class="h-4 w-4" />
                                    Company Email
                                </Label>
                                <Input
                                    id="company_email"
                                    type="email"
                                    v-model="form.company_email"
                                />
                                <p
                                    v-if="form.errors.company_email"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors.company_email }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label
                                    for="company_phone"
                                    class="inline-flex items-center gap-2"
                                >
                                    <Phone class="h-4 w-4" />
                                    Phone
                                </Label>
                                <Input
                                    id="company_phone"
                                    v-model="form.company_phone"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label
                                    for="registration_number"
                                    class="inline-flex items-center gap-2"
                                >
                                    <FileCheck2 class="h-4 w-4" />
                                    Registration Number
                                </Label>
                                <Input
                                    id="registration_number"
                                    v-model="form.registration_number"
                                    disabled
                                    class="bg-slate-100 disabled:cursor-not-allowed disabled:opacity-80"
                                />
                                <p
                                    v-if="form.errors.registration_number"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors.registration_number }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label
                                    for="business_type"
                                    class="inline-flex items-center gap-2"
                                >
                                    <Building2 class="h-4 w-4" />
                                    Business Type
                                </Label>
                                <select
                                    id="business_type"
                                    v-model="form.business_type"
                                    disabled
                                    class="w-full rounded-md border bg-slate-100 px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-80"
                                >
                                    <option value="">
                                        Select business type
                                    </option>
                                    <option value="corporate">Corporate</option>
                                    <option value="sole_proprietorship">
                                        Sole Proprietorship
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <div
                                    class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2"
                                >
                                    <div class="inline-flex items-center gap-2">
                                        <ShieldCheck
                                            class="h-4 w-4 text-emerald-700"
                                        />
                                        <p
                                            class="text-xs font-semibold tracking-widest text-emerald-700 uppercase"
                                        >
                                            Editable Profile Details
                                        </p>
                                    </div>
                                    <Badge
                                        class="border-emerald-300 bg-white text-[11px] text-emerald-700"
                                    >
                                        You Can Update
                                    </Badge>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label
                                    for="authorized_representative_name"
                                    class="inline-flex items-center gap-2"
                                >
                                    <User class="h-4 w-4" />
                                    Authorized Representative
                                </Label>
                                <Input
                                    id="authorized_representative_name"
                                    v-model="
                                        form.authorized_representative_name
                                    "
                                />
                            </div>

                            <div class="space-y-2">
                                <Label
                                    for="authorized_representative_position"
                                    class="inline-flex items-center gap-2"
                                >
                                    <ShieldCheck class="h-4 w-4" />
                                    Representative Position
                                </Label>
                                <Input
                                    id="authorized_representative_position"
                                    v-model="
                                        form.authorized_representative_position
                                    "
                                />
                            </div>

                            <div class="space-y-2">
                                <Label
                                    for="authorized_representative_contact"
                                    class="inline-flex items-center gap-2"
                                >
                                    <Phone class="h-4 w-4" />
                                    Representative Contact
                                </Label>
                                <Input
                                    id="authorized_representative_contact"
                                    v-model="
                                        form.authorized_representative_contact
                                    "
                                />
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <Label
                                    for="company_address"
                                    class="inline-flex items-center gap-2"
                                >
                                    <MapPin class="h-4 w-4" />
                                    Company Address
                                </Label>
                                <Textarea
                                    id="company_address"
                                    v-model="form.company_address"
                                    rows="3"
                                />
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <Label
                                    for="logo"
                                    class="inline-flex items-center gap-2"
                                >
                                    <ImageIcon class="h-4 w-4" />
                                    Company Logo
                                </Label>
                                <input
                                    id="logo"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
                                    @change="onLogoSelected"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Optional. Upload JPG, PNG, or WEBP (max
                                    2MB).
                                </p>
                                <p
                                    v-if="company.logo_url"
                                    class="text-xs text-muted-foreground"
                                >
                                    Current logo is set.
                                </p>
                                <p
                                    v-if="form.errors.logo"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors.logo }}
                                </p>
                            </div>

                            <div
                                v-if="requiresComplianceDocument"
                                class="space-y-2 rounded-lg border border-amber-200 bg-amber-50 p-3 md:col-span-2"
                            >
                                <p
                                    class="text-xs font-semibold tracking-widest text-amber-700 uppercase"
                                >
                                    Major change detected
                                </p>
                                <p class="text-xs text-amber-700">
                                    Re-upload {{ requiredComplianceDocType }} to
                                    continue with this request.
                                </p>

                                <div class="space-y-2">
                                    <Label
                                        for="compliance_document_file"
                                        class="inline-flex items-center gap-2"
                                    >
                                        <FileCheck2 class="h-4 w-4" />
                                        {{ requiredComplianceDocType }} File
                                    </Label>
                                    <input
                                        id="compliance_document_file"
                                        type="file"
                                        accept="application/pdf,image/jpeg,image/png"
                                        class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-100"
                                        @change="onComplianceDocumentSelected"
                                    />
                                    <p class="text-xs text-amber-700">
                                        Upload PDF, JPG, or PNG (max 5MB).
                                    </p>
                                    <p
                                        v-if="
                                            form.errors.compliance_document_file
                                        "
                                        class="text-xs text-destructive"
                                    >
                                        {{
                                            form.errors.compliance_document_file
                                        }}
                                    </p>
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label
                                            for="compliance_document_issued_at"
                                            class="inline-flex items-center gap-2"
                                        >
                                            <CalendarClock class="h-4 w-4" />
                                            Issue Date
                                        </Label>
                                        <Input
                                            id="compliance_document_issued_at"
                                            type="date"
                                            v-model="
                                                form.compliance_document_issued_at
                                            "
                                        />
                                        <p
                                            v-if="
                                                form.errors
                                                    .compliance_document_issued_at
                                            "
                                            class="text-xs text-destructive"
                                        >
                                            {{
                                                form.errors
                                                    .compliance_document_issued_at
                                            }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label
                                            for="compliance_document_expires_at"
                                            class="inline-flex items-center gap-2"
                                        >
                                            <CalendarClock class="h-4 w-4" />
                                            Expiry Date
                                        </Label>
                                        <Input
                                            id="compliance_document_expires_at"
                                            type="date"
                                            v-model="
                                                form.compliance_document_expires_at
                                            "
                                        />
                                        <p
                                            v-if="
                                                form.errors
                                                    .compliance_document_expires_at
                                            "
                                            class="text-xs text-destructive"
                                        >
                                            {{
                                                form.errors
                                                    .compliance_document_expires_at
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex flex-wrap items-center gap-2 border-t pt-3 md:col-span-2"
                            >
                                <Button
                                    type="submit"
                                    :disabled="
                                        form.processing || !canSubmitProfileForm
                                    "
                                >
                                    {{
                                        form.processing
                                            ? 'Submitting...'
                                            : 'Submit Changes for Review'
                                    }}
                                </Button>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    :disabled="
                                        form.processing ||
                                        !canSubmitChanges ||
                                        !company.logo_url
                                    "
                                    @click="requestLogoRemoval"
                                >
                                    Remove Current Logo
                                </Button>
                            </div>

                            <p
                                v-if="businessTypeRequiresRegistrationUpdate"
                                class="rounded-md border border-rose-300 bg-rose-50 px-3 py-2 text-xs text-rose-800 md:col-span-2"
                            >
                                <FileWarning
                                    class="mr-1 inline h-3.5 w-3.5 align-text-bottom"
                                />
                                Action required: Update Registration Number to
                                match your new Business Type.
                            </p>

                            <p
                                v-if="
                                    requiresComplianceDocument &&
                                    !hasComplianceDocumentInputsComplete
                                "
                                class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800 md:col-span-2"
                            >
                                <CalendarClock
                                    class="mr-1 inline h-3.5 w-3.5 align-text-bottom"
                                />
                                Action required: Upload
                                {{ requiredComplianceDocType }} and complete the
                                issue and expiry dates.
                            </p>

                            <p
                                v-if="!canSubmitChanges"
                                class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800 md:col-span-2"
                            >
                                You already have a pending profile request. Wait
                                for admin action before submitting again.
                            </p>
                            <p
                                v-if="profileError"
                                class="text-xs text-destructive md:col-span-2"
                            >
                                {{ profileError }}
                            </p>
                        </form>
                    </CardContent>
                </Card>

                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <ImageIcon class="h-4 w-4" />
                                Brand Preview
                            </CardTitle>
                            <CardDescription
                                >Current company identity
                                snapshot.</CardDescription
                            >
                        </CardHeader>
                        <CardContent>
                            <div
                                class="overflow-hidden rounded-lg border bg-slate-50"
                            >
                                <img
                                    v-if="company.logo_url"
                                    :src="company.logo_url"
                                    alt="Company Logo"
                                    class="h-40 w-full object-contain p-4"
                                />
                                <div
                                    v-else
                                    class="flex h-40 items-center justify-center text-sm text-muted-foreground"
                                >
                                    No logo uploaded
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="latest_change_request">
                        <CardHeader>
                            <CardTitle>Latest Profile Change Request</CardTitle>
                            <CardDescription>
                                Track the latest request before submitting
                                another profile update.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <Badge
                                    :class="
                                        requestStatusClass(
                                            latest_change_request.status,
                                        )
                                    "
                                >
                                    {{ humanize(latest_change_request.status) }}
                                </Badge>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="requestDialogOpen = true"
                                >
                                    <Eye class="mr-2 h-3.5 w-3.5" />
                                    View Details
                                </Button>
                            </div>

                            <div
                                class="space-y-1 rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700"
                            >
                                <p>
                                    <span class="font-semibold"
                                        >Change request:</span
                                    >
                                    #{{ latest_change_request.id }}
                                </p>
                                <p>
                                    <span class="font-semibold"
                                        >Submitted:</span
                                    >
                                    {{
                                        formatDateTime(
                                            latest_change_request.created_at,
                                        )
                                    }}
                                </p>
                            </div>

                            <p
                                v-if="
                                    latest_change_request.status === 'pending'
                                "
                                class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-amber-800"
                            >
                                Your request is currently under review. You can
                                submit again once admins finish processing this
                                request.
                            </p>
                            <p
                                v-if="
                                    latest_change_request.status === 'approved'
                                "
                                class="rounded-md border border-emerald-300 bg-emerald-50 px-3 py-2 text-emerald-800"
                            >
                                Great news. Your latest profile change request
                                was approved.
                            </p>
                            <p
                                v-if="
                                    latest_change_request.status === 'rejected'
                                "
                                class="rounded-md border border-rose-300 bg-rose-50 px-3 py-2 text-rose-800"
                            >
                                Rejected:
                                {{
                                    latest_change_request.rejection_reason ||
                                    'No reason provided.'
                                }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Document Status</CardTitle>
                    <CardDescription>
                        Required documents can be re-uploaded only when your
                        company needs revision.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="doc in company.documents"
                        :key="doc.id"
                        class="flex items-start justify-between rounded-lg border border-slate-200 p-3"
                    >
                        <div>
                            <p class="text-sm font-medium">
                                {{ humanize(doc.doc_type) }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                <span class="font-medium">Status:</span>
                                {{ humanize(doc.status) }}
                                <span v-if="doc.expires_at">
                                    • <span class="font-medium">Expires:</span>
                                    {{ formatDate(doc.expires_at) }}</span
                                >
                            </p>
                            <p
                                v-if="doc.remarks"
                                class="mt-1 text-xs text-rose-700"
                            >
                                Remarks: {{ doc.remarks }}
                            </p>
                        </div>
                        <Badge :class="statusClass(doc.status)">
                            {{ humanize(doc.status) }}
                        </Badge>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-2">
                        <Button v-if="canResubmitNow" as-child>
                            <Link href="/registration/status"
                                >Resubmit Documents</Link
                            >
                        </Button>
                        <Button v-else type="button" disabled>
                            Resubmit Documents
                        </Button>
                        <p class="text-xs text-muted-foreground">
                            Enabled only when status is Needs Revision and there
                            are expired or invalid documents.
                        </p>
                    </div>

                    <p
                        v-if="hasExpiredDocument"
                        class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800"
                    >
                        One or more documents are expired. Reupload the expired
                        documents in the registration status page.
                    </p>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="requestDialogOpen">
            <DialogContent class="max-h-[85vh] overflow-hidden sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <CalendarClock class="h-4 w-4" />
                        Profile Change Request History
                    </DialogTitle>
                    <DialogDescription>
                        Click View Changes to open a detailed request dialog.
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="max-h-[60vh] space-y-2 overflow-y-auto rounded-md border border-slate-200 p-2"
                >
                    <div
                        v-for="requestItem in profileRequestHistory"
                        :key="requestItem.id"
                        class="w-full cursor-pointer rounded-md border p-2 text-left transition"
                        :class="
                            selectedRequestId === requestItem.id
                                ? 'border-slate-900 bg-slate-50'
                                : 'border-slate-200 hover:bg-slate-50'
                        "
                        @click="selectRequest(requestItem.id)"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-slate-800">
                                Change request #{{ requestItem.id }}
                            </p>
                            <Badge
                                :class="requestStatusClass(requestItem.status)"
                                >{{ humanize(requestItem.status) }}</Badge
                            >
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ formatDateTime(requestItem.created_at) }}
                        </p>
                        <p class="mt-1 text-xs text-slate-700">
                            {{ requestedFieldCount(requestItem) }} field(s)
                            updated
                        </p>
                        <p
                            v-if="requestItem.status === 'rejected'"
                            class="mt-1 line-clamp-2 text-xs text-rose-700"
                        >
                            {{
                                requestItem.rejection_reason ||
                                'No reason provided.'
                            }}
                        </p>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="mt-2 h-7 px-2 text-xs"
                            @click.stop="openRequestDetails(requestItem.id)"
                        >
                            View Changes
                        </Button>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="requestDialogOpen = false"
                        >Close</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="requestDetailsDialogOpen">
            <DialogContent class="max-h-[85vh] overflow-hidden sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <CalendarClock class="h-4 w-4" />
                        Request #{{ selectedRequest?.id }} Details
                    </DialogTitle>
                    <DialogDescription>
                        Complete summary of requested changes and important
                        details.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="selectedRequest"
                    class="max-h-[60vh] space-y-4 overflow-y-auto pr-1 text-sm"
                >
                    <div
                        class="grid grid-cols-1 gap-2 rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700"
                    >
                        <p>
                            <span class="font-semibold">Change request:</span>
                            #{{ selectedRequest.id }}
                        </p>
                        <p>
                            <span class="font-semibold">Status:</span>
                            {{ humanize(selectedRequest.status) }}
                        </p>
                        <p>
                            <span class="font-semibold">Submitted:</span>
                            {{ formatDateTime(selectedRequest.created_at) }}
                        </p>
                        <p v-if="selectedRequest.approved_at">
                            <span class="font-semibold">Reviewed:</span>
                            {{ formatDateTime(selectedRequest.approved_at) }}
                        </p>
                        <p
                            v-if="selectedRequest.status === 'rejected'"
                            class="rounded-md border border-rose-300 bg-rose-50 px-2 py-1 text-rose-800"
                        >
                            <span class="font-semibold">Rejection reason:</span>
                            {{
                                selectedRequest.rejection_reason ||
                                'No reason provided.'
                            }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Requested updates
                        </p>
                        <div
                            v-if="readableRequestedValues.length"
                            class="space-y-2"
                        >
                            <div
                                v-for="item in readableRequestedValues"
                                :key="`${selectedRequest.id}-${item.label}`"
                                class="rounded-md border border-slate-200 p-2"
                            >
                                <p class="text-xs font-medium text-slate-600">
                                    {{ item.label }}
                                </p>
                                <p
                                    class="text-sm wrap-break-word text-slate-900"
                                >
                                    {{ item.value }}
                                </p>
                            </div>
                        </div>
                        <p
                            v-else
                            class="rounded-md border border-slate-200 p-3 text-xs text-muted-foreground"
                        >
                            No field-level details available for this request.
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="requestDetailsDialogOpen = false"
                        >Close</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </ExternalLayout>
</template>
