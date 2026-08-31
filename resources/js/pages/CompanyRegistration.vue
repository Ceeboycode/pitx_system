<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarDate } from '@internationalized/date';
import { computed, onUnmounted, ref, watch } from 'vue';

import AuthLayout from '@/layouts/AuthLayout.vue';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Calendar as CalendarPicker } from '@/components/ui/calendar';
import { Card, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';

import AddressSelectPH from '@/components/AddressSelectPH.vue';
import ConfirmPasswordRequirements from '@/components/ConfirmPasswordRequirements.vue';
import InputError from '@/components/InputError.vue';
import PasswordRequirements from '@/components/PasswordRequirements.vue';
import RegistrationStatus from '@/pages/RegistrationStatus.vue';

import {
    resendStep1Otp,
    resendStep2Otp,
    storeStep1,
    storeStep2,
    storeStep3,
    verifyStep1Otp,
    verifyStep2Otp,
} from '@/actions/App/Http/Controllers/CompanyRegistration';

import {
    RiAddLine,
    RiArrowLeftSLine,
    RiCalendarLine,
    RiCheckboxCircleFill,
    RiCheckLine,
    RiCloseLine,
    RiDeleteBinLine,
    RiEyeLine,
    RiEyeOffLine,
    RiFileTextLine,
    RiImageAddLine,
    RiLoaderLine,
} from 'vue-remix-icons';

import AuthenticationRaifikiUrl from '@/components/assets/Authentication-rafiki.svg';

type DocRow = {
    id: number;
    doc_type: string;
    status: string;
    original_name?: string | null;
    remarks?: string | null;
    expires_at?: string | null;
};

type UploadRules = {
    extensions: string[];
    accept: string;
    maxKb: number;
    maxMb: number;
    previewableExtensions: string[];
};

type DocumentInput = {
    file: File | null;
    issued_at: string;
    expires_at: string;
};

type SupportingDocumentInput = DocumentInput & {
    id: number;
    title: string;
};

const props = defineProps<{
    company?: {
        id: number;
        company_name: string;
        company_code?: string | null;
        company_email?: string;
        status: string;
        documents?: DocRow[];
    };
    meta?: {
        title: string;
        description: string;
        icon: string;
        color: string;
    };
    uploadRules?: UploadRules;
}>();

const fallbackUploadRules: UploadRules = {
    extensions: ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    accept: '.pdf,.doc,.docx,.jpg,.jpeg,.png',
    maxKb: 5120,
    maxMb: 5,
    previewableExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
};

const uploadRules = computed(() => props.uploadRules ?? fallbackUploadRules);
const allowedFileTypesText = computed(() =>
    uploadRules.value.extensions
        .map((extension) => extension.toUpperCase())
        .join(', '),
);
const maxFileSizeText = computed(() => `${uploadRules.value.maxMb} MB`);

type SubStep = 1 | 1.5 | 2 | 2.5 | 3 | 4;

const currentStep = ref<SubStep>(props.company ? 4 : 1);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

watch(
    () => props.company,
    (company) => {
        if (company) {
            currentStep.value = 4;
        }
    },
    { immediate: true },
);

const visualStep = computed((): 1 | 2 | 3 | 4 => {
    if (currentStep.value < 2) return 1;
    if (currentStep.value < 3) return 2;
    if (currentStep.value < 4) return 3;
    return 4;
});

const stepMeta = [
    {
        number: 1,
        title: 'Operator Profile',
        description: 'Register and verify your operator account details.',
    },
    {
        number: 2,
        title: 'Company Profile',
        description: 'Enter and verify company details.',
    },
    {
        number: 3,
        title: 'Documents',
        description:
            'Provide required registration documentation for verification.',
    },
    {
        number: 4,
        title: 'Status',
        description: 'Track the review status of your company registration.',
    },
];

const step1 = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const isValidEmail = (value: string): boolean =>
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());

const step1EmailValidationMessage = computed(() => {
    if (!step1.email || isValidEmail(step1.email)) {
        return '';
    }

    return 'Please enter a valid email address.';
});

const isPhilippineMobileNumber = (value: string): boolean => {
    const digits = value.replace(/[\s()-]/g, '');

    return (
        /^09\d{9}$/.test(digits) ||
        /^\+639\d{9}$/.test(digits) ||
        /^639\d{9}$/.test(digits)
    );
};

const normalizePhilippineMobileNumber = (value: string): string => {
    const digits = value.replace(/[\s()-]/g, '');

    if (/^09\d{9}$/.test(digits)) {
        return digits;
    }

    if (/^639\d{9}$/.test(digits)) {
        return `0${digits.slice(2)}`;
    }

    return value;
};

function sanitizeStep1Phone(): void {
    const value = step1.phone.replace(/[^\d+]/g, '');

    step1.phone = value.startsWith('+')
        ? `+${value.slice(1).replace(/\+/g, '')}`
        : value.replace(/\+/g, '');
}

const phoneValidationMessage = computed(() => {
    if (!step1.phone || isPhilippineMobileNumber(step1.phone)) {
        return '';
    }

    return 'Enter a valid PH phone number.';
});

const isStep1Valid = computed(() => {
    const password = step1.password;

    return (
        step1.name.trim().length > 0 &&
        isValidEmail(step1.email) &&
        isPhilippineMobileNumber(step1.phone) &&
        password.length >= 12 &&
        /[A-Z]/.test(password) &&
        /[a-z]/.test(password) &&
        /\d/.test(password) &&
        /[^A-Za-z\d]/.test(password) &&
        password === step1.password_confirmation
    );
});

function validateStep1Phone(): boolean {
    if (!step1.phone || !isPhilippineMobileNumber(step1.phone)) {
        step1.setError(
            'phone',
            phoneValidationMessage.value || 'Phone number is required.',
        );

        return false;
    }

    normalizeStep1Phone();
    step1.clearErrors('phone');

    return true;
}

function normalizeStep1Phone(): void {
    if (isPhilippineMobileNumber(step1.phone)) {
        step1.phone = normalizePhilippineMobileNumber(step1.phone);
    }
}

const otpAccount = useForm({ otp: '' });
const resendAccount = useForm({});
const resentAccountMsg = ref('');

const step2 = useForm({
    company_name: '',
    company_email: '',
    company_phone: '',
    company_address: '',
    business_type: '' as 'corporate' | 'sole_proprietorship' | '',
    registration_number: '',
    authorized_representative_name: '',
    authorized_representative_position: '',
    authorized_representative_contact: '',

    logo: null as File | null,
});

const step2EmailValidationMessage = computed(() => {
    if (!step2.company_email || isValidEmail(step2.company_email)) {
        return '';
    }

    return 'Please enter a valid email address.';
});

const isStep2Valid = computed(() => {
    const hasRequiredCompanyDetails = [
        step2.company_name,
        step2.company_email,
        step2.company_phone,
        step2.company_address,
        step2.business_type,
    ].every((value) => value.trim().length > 0);

    if (!hasRequiredCompanyDetails || !isValidEmail(step2.company_email)) {
        return false;
    }

    return Boolean(
        step2.registration_number &&
            step2.authorized_representative_name &&
            step2.authorized_representative_position &&
            step2.authorized_representative_contact,
    );
});

const logoPreview = ref<string | null>(null);
const logoInputRef = ref<HTMLInputElement | null>(null);

function handleLogoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;

    step2.logo = file;
    step2.clearErrors('logo');

    const reader = new FileReader();
    reader.onload = (ev) => {
        logoPreview.value = ev.target?.result as string;
    };
    reader.readAsDataURL(file);
}

function removeLogo() {
    step2.logo = null;
    logoPreview.value = null;
    if (logoInputRef.value) logoInputRef.value.value = '';
}

const addressCodes = ref({
    regionCode: '',
    provinceCode: '',
    cityMunCode: '',
    barangayCode: '',
});

const positionOptions = [
    'Owner',
    'Proprietor',
    'President',
    'CEO',
    'COO',
    'General Manager',
    'Operations Manager',
    'HR Manager',
    'Authorized Representative',
] as const;

const positionChoice = ref('');

watch(positionChoice, (val) => {
    if (!val) return;
    if (val !== 'other') {
        step2.authorized_representative_position = val;
        step2.clearErrors('authorized_representative_position');
    } else if (
        positionOptions.includes(
            step2.authorized_representative_position as any,
        )
    ) {
        step2.authorized_representative_position = '';
    }
});

const otpCompany = useForm({ otp: '' });
const resendCompany = useForm({});
const resentCompanyMsg = ref('');

const step3 = useForm({
    documents: {
        AUTHORIZATION_LETTER: {
            file: null as File | null,
            issued_at: '',
            expires_at: '',
        },
        SEC_CERT: { file: null as File | null, issued_at: '', expires_at: '' },
        DTI_CERT: { file: null as File | null, issued_at: '', expires_at: '' },
        MAYORS_PERMIT: {
            file: null as File | null,
            issued_at: '',
            expires_at: '',
        },
        BIR_2303: { file: null as File | null, issued_at: '', expires_at: '' },
    } as Record<string, DocumentInput>,
    supporting_documents: [] as SupportingDocumentInput[],
});

const isCorporate = computed(() => step2.business_type === 'corporate');
const isSole = computed(() => step2.business_type === 'sole_proprietorship');

const requiredDocs = computed<
    { key: string; label: string; required: boolean }[]
>(() => {
    const base = [
        { key: 'MAYORS_PERMIT', label: "Mayor's Permit", required: true },
        { key: 'BIR_2303', label: 'BIR Form 2303', required: true },
    ];

    if (isCorporate.value)
        return [
            {
                key: 'AUTHORIZATION_LETTER',
                label: 'Authorization Letter',
                required: false,
            },
            { key: 'SEC_CERT', label: 'SEC Certificate', required: true },
            ...base,
        ];

    if (isSole.value)
        return [
            { key: 'DTI_CERT', label: 'DTI Certificate', required: true },
            ...base,
        ];

    return [
        {
            key: 'AUTHORIZATION_LETTER',
            label: 'Authorization Letter (Corporate)',
            required: false,
        },
        {
            key: 'SEC_CERT',
            label: 'SEC Certificate (Corporate)',
            required: false,
        },
        {
            key: 'DTI_CERT',
            label: 'DTI Certificate (Sole Prop.)',
            required: false,
        },
        ...base.map((d) => ({ ...d, required: false })),
    ];
});

const isStep3Valid = computed(() =>
    requiredDocs.value
        .filter((document) => document.required)
        .every((document) => Boolean(step3.documents[document.key]?.file)),
);

const confirmStep3Open = ref(false);
const previewOpen = ref(false);
const previewFile = ref<{
    title: string;
    name: string;
    size: string;
    url: string;
    type: 'image' | 'pdf';
} | null>(null);
const filePreviewUrls = ref<Record<string, string>>({});
const documentInputResetKeys = ref<Record<string, number>>({});
const supportingInputResetKeys = ref<Record<number, number>>({});
let supportingDocumentId = 0;

const selectedSubmissionDocuments = computed(() => [
    ...requiredDocs.value
        .filter((doc) => step3.documents[doc.key]?.file)
        .map((doc) => ({
            label: doc.label,
            file: step3.documents[doc.key].file as File,
        })),
    ...step3.supporting_documents
        .filter((document) => document.file)
        .map((document, index) => ({
            label: document.title || `Supporting Document ${index + 1}`,
            file: document.file as File,
        })),
]);

function submitStep1() {
    if (!step1.email || !isValidEmail(step1.email)) {
        step1.setError('email', 'Please enter a valid email address.');

        return;
    }

    if (!validateStep1Phone()) return;

    step1.submit(storeStep1(), {
        onSuccess: () => {
            resentAccountMsg.value = '';
            currentStep.value = 1.5;
        },
    });
}

function submitAccountOtp() {
    otpAccount.submit(verifyStep1Otp(), {
        onSuccess: () => {
            currentStep.value = 2;
            otpAccount.reset();
        },
    });
}

function submitStep2() {
    if (!step2.company_email || !isValidEmail(step2.company_email)) {
        step2.setError(
            'company_email',
            step2EmailValidationMessage.value ||
                'Please enter a valid email address.',
        );

        return;
    }

    step2.submit(storeStep2(), {
        forceFormData: true,
        onSuccess: () => {
            resentCompanyMsg.value = '';
            currentStep.value = 2.5;
        },
    });
}

function submitCompanyOtp() {
    otpCompany.submit(verifyStep2Otp(), {
        onSuccess: () => {
            currentStep.value = 3;
            otpCompany.reset();
        },
    });
}

function submitStep3() {
    confirmStep3Open.value = false;
    step3.submit(storeStep3(), {
        forceFormData: true,
        onError: (errors) => {
            console.log('[Company Registration] Document submission failed.', {
                validationErrors: errors,
                documents: Object.fromEntries(
                    Object.entries(step3.documents).map(([type, document]) => [
                        type,
                        {
                            fileName: document.file?.name ?? null,
                            fileType: document.file?.type ?? null,
                            fileSize: document.file?.size ?? null,
                            issuedAt: document.issued_at,
                            expiresAt: document.expires_at,
                        },
                    ]),
                ),
            });
        },
    });
}

function doResendAccount() {
    resentAccountMsg.value = '';
    resendAccount.submit(resendStep1Otp(), {
        onSuccess: () => {
            resentAccountMsg.value = 'A new code was sent to your email.';
            otpAccount.reset();
        },
    });
}

function doResendCompany() {
    resentCompanyMsg.value = '';
    resendCompany.submit(resendStep2Otp(), {
        onSuccess: () => {
            resentCompanyMsg.value =
                'A new code was sent to the company email.';
            otpCompany.reset();
        },
    });
}

function goBack() {
    const map: Partial<Record<SubStep, SubStep>> = {
        1.5: 1,
        2: 1.5,
        2.5: 2,
        3: 2.5,
    };

    const prev = map[currentStep.value];

    if (prev !== undefined) {
        currentStep.value = prev;
    } else {
        router.visit('/');
    }
}

function onOtpInput(type: 'account' | 'company') {
    const form = type === 'account' ? otpAccount : otpCompany;
    if (form.otp.replace(/\D/g, '').length === 6) {
        type === 'account' ? submitAccountOtp() : submitCompanyOtp();
    }
}

// UNFINISHED CODE KASI I CANT PROCEED SA NEXT STEPSSSSS!!!

function handleFile(docKey: string, event: Event) {
    const el = event.target as HTMLInputElement;
    const file = el.files?.[0] ?? null;
    step3.documents[docKey].file = file;
    step3.clearErrors(`documents.${docKey}.file` as any);
    setPreviewUrl(`documents.${docKey}`, file);
}

function docError(
    docKey: string,
    field: 'file' | 'issued_at' | 'expires_at',
): string | undefined {
    return (step3.errors as Record<string, string>)[
        `documents.${docKey}.${field}`
    ];
}

function supportingDocError(
    index: number,
    field: 'title' | 'file' | 'issued_at' | 'expires_at',
): string | undefined {
    return (step3.errors as Record<string, string>)[
        `supporting_documents.${index}.${field}`
    ];
}

function addSupportingDocument() {
    supportingDocumentId += 1;
    step3.supporting_documents.push({
        id: supportingDocumentId,
        title: '',
        file: null,
        issued_at: '',
        expires_at: '',
    });
}

function removeSupportingDocument(index: number) {
    const document = step3.supporting_documents[index];
    if (!document) return;

    revokePreviewUrl(`supporting_documents.${document.id}`);
    delete supportingInputResetKeys.value[document.id];
    step3.supporting_documents.splice(index, 1);
}

function handleSupportingFile(index: number, event: Event) {
    const input = event.target as HTMLInputElement;
    const document = step3.supporting_documents[index];
    if (!document) return;

    const file = input.files?.[0] ?? null;
    document.file = file;
    step3.clearErrors(`supporting_documents.${index}.file` as never);
    setPreviewUrl(`supporting_documents.${document.id}`, file);
}

function removeDocumentFile(docKey: string) {
    step3.documents[docKey].file = null;
    revokePreviewUrl(`documents.${docKey}`);
    documentInputResetKeys.value[docKey] =
        (documentInputResetKeys.value[docKey] ?? 0) + 1;
}

function removeSupportingFile(index: number) {
    const document = step3.supporting_documents[index];
    if (!document) return;

    document.file = null;
    revokePreviewUrl(`supporting_documents.${document.id}`);
    supportingInputResetKeys.value[document.id] =
        (supportingInputResetKeys.value[document.id] ?? 0) + 1;
}

function setPreviewUrl(key: string, file: File | null) {
    revokePreviewUrl(key);

    if (file && canPreviewFile(file)) {
        filePreviewUrls.value[key] = URL.createObjectURL(file);
    }
}

function revokePreviewUrl(key: string) {
    if (!filePreviewUrls.value[key]) return;

    URL.revokeObjectURL(filePreviewUrls.value[key]);
    delete filePreviewUrls.value[key];
}

function fileExtension(file?: File | null) {
    return file?.name.split('.').pop()?.toLowerCase() ?? '';
}

function canPreviewFile(file?: File | null) {
    return (
        !!file &&
        uploadRules.value.previewableExtensions.includes(fileExtension(file))
    );
}

function previewType(file: File): 'image' | 'pdf' {
    return fileExtension(file) === 'pdf' ? 'pdf' : 'image';
}

function openSelectedFilePreview(
    key: string,
    title: string,
    file?: File | null,
) {
    const url = filePreviewUrls.value[key];

    if (!file || !url || !canPreviewFile(file)) return;

    previewFile.value = {
        title,
        name: file.name,
        size: formatFileSize(file.size),
        url,
        type: previewType(file),
    };
    previewOpen.value = true;
}

function formatFileSize(bytes?: number | null) {
    if (!bytes || bytes <= 0) return '0 B';

    const units = ['B', 'KB', 'MB', 'GB'];
    let value = bytes;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex++;
    }

    return `${value.toFixed(value >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function requestStep3Submission() {
    confirmStep3Open.value = true;
}

const openDatePicker = ref<string | null>(null);

function parseCalendarDate(value: string): CalendarDate | undefined {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return undefined;
    const [year, month, day] = value.split('-').map(Number);
    try {
        return new CalendarDate(year, month, day);
    } catch {
        return undefined;
    }
}

function selectDocumentDate(
    docKey: string,
    field: 'issued_at' | 'expires_at',
    value: CalendarDate | undefined,
) {
    step3.documents[docKey][field] = value
        ? `${value.year}-${String(value.month).padStart(2, '0')}-${String(value.day).padStart(2, '0')}`
        : '';
    openDatePicker.value = null;
}

onUnmounted(() => {
    Object.keys(filePreviewUrls.value).forEach(revokePreviewUrl);
});
</script>

<template>
    <AuthLayout title="Company Registration" description="">
        <Head
            :title="
                currentStep === 4
                    ? 'Registration Status'
                    : 'Company Registration'
            "
        />
        <Card class="mx-auto flex max-w-3xl flex-row">
            <div class="flex h-full w-1/2 flex-col gap-y-2 px-6 text-sm">
                <div class="pb-2">
                    <CardTitle class="">
                        <span>Registration</span>
                    </CardTitle>
                </div>
                <div
                    v-for="(step, idx) in stepMeta"
                    :key="step.number"
                    class="relative"
                >
                    <button
                        type="button"
                        class="flex flex-col items-start text-left"
                        :disabled="
                            company
                                ? step.number !== 4
                                : step.number > visualStep
                        "
                        @click="
                            !company &&
                            step.number < visualStep &&
                            (currentStep = step.number as SubStep)
                        "
                    >
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-8 w-8 items-center justify-center border-[8px] text-sm transition-all"
                                :class="{
                                    'scale-100 border-custom-primary bg-transparent font-semibold':
                                        visualStep === step.number,
                                    'cursor-pointer border-custom-primary bg-transparent font-semibold':
                                        step.number < visualStep,
                                    'border-custom-primary/40 bg-transparent font-semibold':
                                        step.number > visualStep,
                                }"
                            >
                                <RiCheckLine
                                    v-if="step.number < visualStep"
                                    class="h-4 w-4 text-custom-shadow"
                                />
                                <span
                                    v-else
                                    class="w-full text-center"
                                    :class="{
                                        'font-semibold text-custom-shadow':
                                            visualStep === step.number ||
                                            step.number < visualStep,
                                        'font-normal text-custom-shadow/80':
                                            step.number > visualStep,
                                    }"
                                    >{{ step.number }}</span
                                >
                            </div>
                            <span
                                class="inline"
                                :class="{
                                    'font-semibold text-custom-shadow':
                                        visualStep === step.number ||
                                        step.number < visualStep,
                                    'text-custom-shadow/80':
                                        step.number > visualStep,
                                }"
                            >
                                {{ step.title }}
                            </span>
                        </div>
                        <span
                            v-if="visualStep === step.number"
                            class="mt-2 ml-10 text-xs text-custom-shadow"
                        >
                            {{ step.description }}
                        </span>
                    </button>
                    <div
                        v-if="idx < stepMeta.length - 1"
                        class="absolute top-8 -bottom-2 left-4 w-[2px] -translate-x-1/2 transition-colors"
                        :class="
                            step.number < visualStep
                                ? 'bg-custom-accent-2'
                                : 'bg-custom-bg-dark dark:bg-custom-bg-light'
                        "
                    />
                </div>
            </div>
            <Separator orientation="vertical" class="h-auto! self-stretch" />
            <div class="h-full w-full px-6">
                <div class="pb-2">
                    <CardTitle class="">
                        <template v-if="currentStep === 1.5"
                            >Verify Account Email</template
                        >
                        <template v-else-if="currentStep === 2.5"
                            >Verify Company Email</template
                        >
                        <template v-else>{{
                            stepMeta[visualStep - 1].title
                        }}</template>
                    </CardTitle>
                </div>

                <div class="py-2 text-sm">
                    <!-- LABEL: ══ STEP 1 – Account Details ══════════════════════════ -->
                    <div
                        v-if="currentStep === 1"
                        class="flex flex-col gap-y-2"
                        @keydown.enter.prevent="
                            !step1.processing && isStep1Valid && submitStep1()
                        "
                    >
                        <div class="grid gap-2 sm:grid-cols-2">
                            <!-- TODO: might need to separate the full name into first name, middle name, and last name -->
                            <div class="space-y-1">
                                <Label for="s1_name">Full Name</Label>
                                <Input
                                    id="s1_name"
                                    v-model="step1.name"
                                    placeholder="Juan Dela Cruz"
                                    autocomplete="name"
                                    class="bg-custom-bg dark:bg-custom-bg-dark capitalize"
                                />
                                <InputError :message="step1.errors.name" />
                            </div>
                            <div class="space-y-1">
                                <Label for="s1_email">Email</Label>
                                <Input
                                    id="s1_email"
                                    type="email"
                                    v-model="step1.email"
                                    placeholder="juan.delacruz@gmail.com"
                                    autocomplete="email"
                                    :aria-invalid="!!step1EmailValidationMessage"
                                    class="bg-custom-bg dark:bg-custom-bg-dark"
                                />
                                <p
                                    v-if="step1EmailValidationMessage"
                                    class="text-sm text-destructive"
                                >
                                    {{ step1EmailValidationMessage }}
                                </p>
                                <InputError :message="step1.errors.email" />
                            </div>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="space-y-1">
                                <Label for="s1_phone">Phone</Label>
                                <Input
                                    id="s1_phone"
                                    v-model="step1.phone"
                                    type="tel"
                                    inputmode="tel"
                                    required
                                    pattern="(09[0-9]{9}|\+639[0-9]{9}|639[0-9]{9})"
                                    placeholder="0917 123 4567"
                                    autocomplete="tel"
                                    :aria-invalid="Boolean(phoneValidationMessage)"
                                    @input="sanitizeStep1Phone"
                                    @blur="normalizeStep1Phone"
                                    class="bg-custom-bg dark:bg-custom-bg-dark"
                                />
                                <p
                                    v-if="phoneValidationMessage"
                                    class="text-sm text-destructive"
                                >
                                    {{ phoneValidationMessage }}
                                </p>
                                <InputError :message="step1.errors.phone" />
                            </div>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="s1_pw">Password</Label>
                                <div class="relative">
                                    <Input
                                        id="s1_pw"
                                        v-model="step1.password"
                                        :type="
                                            showPassword ? 'text' : 'password'
                                        "
                                        placeholder="••••••••"
                                        autocomplete="new-password"
                                        class="bg-custom-bg pr-10 dark:bg-custom-bg-dark"
                                    />
                                    <button
                                        type="button"
                                        aria-label="Hold to show password"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-custom-shadow/60"
                                        @pointerdown.prevent="
                                            showPassword = true
                                        "
                                        @pointerup="showPassword = false"
                                        @pointerleave="showPassword = false"
                                        @pointercancel="showPassword = false"
                                    >
                                        <RiEyeOffLine
                                            v-if="showPassword"
                                            class="h-4 w-4"
                                        />
                                        <RiEyeLine v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <InputError :message="step1.errors.password" />
                                <PasswordRequirements
                                    :password="step1.password"
                                    :active="step1.password.length > 0"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="s1_pwc">Confirm Password</Label>
                                <div class="relative">
                                    <Input
                                        id="s1_pwc"
                                        v-model="step1.password_confirmation"
                                        :type="
                                            showPasswordConfirmation
                                                ? 'text'
                                                : 'password'
                                        "
                                        placeholder="••••••••"
                                        autocomplete="new-password"
                                        class="bg-custom-bg pr-10 dark:bg-custom-bg-dark"
                                    />
                                    <button
                                        type="button"
                                        aria-label="Hold to show password confirmation"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-custom-shadow/60"
                                        @pointerdown.prevent="
                                            showPasswordConfirmation = true
                                        "
                                        @pointerup="
                                            showPasswordConfirmation = false
                                        "
                                        @pointerleave="
                                            showPasswordConfirmation = false
                                        "
                                        @pointercancel="
                                            showPasswordConfirmation = false
                                        "
                                    >
                                        <RiEyeOffLine
                                            v-if="showPasswordConfirmation"
                                            class="h-4 w-4"
                                        />
                                        <RiEyeLine v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <InputError
                                    :message="
                                        step1.errors.password_confirmation
                                    "
                                />
                                <ConfirmPasswordRequirements
                                    :password="step1.password"
                                    :confirmation="step1.password_confirmation"
                                    :active="step1.password_confirmation.length > 0"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- LABEL: ══ STEP 1.5 – Account Email OTP ══════════════════════ -->
                    <div
                        v-else-if="currentStep === 1.5"
                        class="space-y-2"
                        @keydown.enter.prevent="
                            !otpAccount.processing &&
                            otpAccount.otp.length === 6 &&
                            submitAccountOtp()
                        "
                    >
                        <div
                            class="flex flex-col items-center rounded-md border border-dashed border-custom-bg-dark p-6 text-center text-custom-shadow dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                        >
                            <img
                                :src="AuthenticationRaifikiUrl"
                                alt=""
                                class="w-1/3 object-contain opacity-90"
                                aria-hidden="true"
                            />
                            <p class="mb-2 text-custom-shadow">
                                Enter the 6-digit code we sent to
                                <span
                                    class="font-semibold text-custom-accent-3"
                                    >{{ step1.email }}</span
                                >. The code expires in 10 minutes.
                            </p>
                        </div>

                        <div class="space-y-0">
                            <Label for="otp_acc" class="sr-only"
                                >Verification Code</Label
                            >
                            <Input
                                id="otp_acc"
                                v-model="otpAccount.otp"
                                inputmode="numeric"
                                maxlength="6"
                                placeholder="000000"
                                class="h-14 text-center font-mono text-2xl tracking-widest placeholder:text-custom-shadow/80"
                                :disabled="otpAccount.processing"
                                @input="onOtpInput('account')"
                            />
                            <InputError
                                :message="otpAccount.errors.otp"
                                class="justify-center"
                            />
                        </div>

                        <div
                            v-if="resentAccountMsg"
                            class="mt-2 flex w-full flex-row items-start justify-center gap-x-2 rounded-md border-2 border-info/30 bg-info/10 p-3 text-info"
                        >
                            <RiCheckboxCircleFill
                                class="mt-0.5 h-4 w-4 shrink-0"
                            />
                            <span class="text-sm">
                                {{ resentAccountMsg }}
                            </span>
                        </div>

                        <div
                            class="flex flex-row justify-center gap-x-1 py-3 text-xs"
                        >
                            <span> Didn't receive it? </span>
                            <button
                                type="button"
                                class="cursor-pointer text-xs font-semibold text-custom-accent-3 transition-colors duration-300 ease-out hover:underline hover:underline-offset-2 disabled:opacity-50"
                                :disabled="resendAccount.processing"
                                @click="doResendAccount"
                            >
                                Resend code
                            </button>
                        </div>
                    </div>

                    <!-- LABEL: ══ STEP 2 – Company Details ══════════════════════════ -->
                    <div
                        v-else-if="currentStep === 2"
                        class="flex flex-col gap-y-2"
                        @keydown.enter.capture.prevent="
                            !step2.processing && isStep2Valid && submitStep2()
                        "
                    >
                        <div class="space-y-2">
                            <Label>
                                Logo
                                <span class="text-custom-shadow/80"
                                    >(optional)</span
                                >
                            </Label>

                            <div
                                class="flex items-center gap-3 rounded-md border border-dashed border-custom-bg-dark p-3 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                            >
                                <div
                                    role="button"
                                    tabindex="0"
                                    :aria-label="
                                        logoPreview
                                            ? 'Change logo'
                                            : 'Upload logo'
                                    "
                                    class="group relative h-24 w-24 shrink-0 cursor-pointer overflow-hidden rounded-md border transition-colors"
                                    :class="
                                        logoPreview
                                            ? 'border-none'
                                            : 'border-dashed border-custom-bg-dark dark:border-custom-bg-light'
                                    "
                                    @click="logoInputRef?.click()"
                                    @keydown.enter.prevent="
                                        logoInputRef?.click()
                                    "
                                    @keydown.space.prevent="
                                        logoInputRef?.click()
                                    "
                                >
                                    <img
                                        v-if="logoPreview"
                                        :src="logoPreview"
                                        alt="Logo preview"
                                        class="h-full w-full object-cover transition duration-200 group-hover:brightness-30"
                                    />
                                    <div
                                        v-if="logoPreview"
                                        class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-200 group-hover:opacity-100 group-focus-visible:opacity-100"
                                    >
                                        <RiImageAddLine
                                            class="h-7 w-7 text-custom-shadow"
                                        />
                                    </div>
                                    <div
                                        v-else
                                        class="flex h-full w-full flex-col items-center justify-center"
                                    >
                                        <RiImageAddLine
                                            class="h-6 w-6 text-custom-shadow/80"
                                        />
                                    </div>

                                    <Button
                                        v-if="logoPreview"
                                        type="button"
                                        aria-label="Remove logo"
                                        @click.stop="removeLogo"
                                        class="absolute top-1 right-1 z-10 flex h-6 w-6 cursor-pointer items-center rounded-full border border-custom-shadow/50 text-custom-shadow transition-all duration-300 hover:border-destructive hover:bg-destructive/20 hover:text-destructive"
                                    >
                                        <RiCloseLine class="h-4 w-4" />
                                    </Button>
                                </div>

                                <div class="space-y-1">
                                    <p class="text-sm text-custom-shadow/80">
                                        <span class="font-semibold"
                                            >File format: </span
                                        >.jpg, .png or .webp<br />
                                        <span class="font-semibold"
                                            >Max. file size: </span
                                        >2 MB<br />
                                        <span class="font-semibold"
                                            >Recommended: </span
                                        >square, 200×200 px+
                                    </p>
                                    <input
                                        id="logo-upload"
                                        ref="logoInputRef"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        class="sr-only"
                                        @change="handleLogoChange"
                                    />
                                </div>
                            </div>

                            <InputError :message="step2.errors.logo" />
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="space-y-1">
                                <Label for="s2_cname">Name</Label>
                                <Input
                                    id="s2_cname"
                                    v-model="step2.company_name"
                                    class="bg-custom-bg dark:bg-custom-bg-dark"
                                />
                                <InputError
                                    :message="step2.errors.company_name"
                                />
                            </div>
                            <div class="space-y-1">
                                <Label for="s2_cemail">Email</Label>
                                <Input
                                    id="s2_cemail"
                                    type="email"
                                    v-model="step2.company_email"
                                    autocomplete="email"
                                    :aria-invalid="Boolean(step2EmailValidationMessage)"
                                    class="bg-custom-bg dark:bg-custom-bg-dark"
                                />
                                <p
                                    v-if="step2EmailValidationMessage"
                                    class="text-sm text-destructive"
                                >
                                    {{ step2EmailValidationMessage }}
                                </p>
                                <InputError
                                    :message="step2.errors.company_email"
                                />
                            </div>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="space-y-1">
                                <Label for="s2_cphone">Phone</Label>
                                <Input
                                    id="s2_cphone"
                                    v-model="step2.company_phone"
                                    autocomplete="tel"
                                    placeholder="+63 9XX XXX XXXX"
                                    class="bg-custom-bg dark:bg-custom-bg-dark"
                                />
                                <InputError
                                    :message="step2.errors.company_phone"
                                />
                            </div>
                            <div class="space-y-1">
                                <Label for="s2_btype">Business Type</Label>
                                <Select v-model="step2.business_type">
                                    <SelectTrigger id="s2_btype" class="w-full">
                                        <SelectValue />
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
                                    :message="step2.errors.business_type"
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <AddressSelectPH
                                v-model:address="step2.company_address"
                                v-model:codes="addressCodes"
                                label="Address"
                                street-label="Street / Building / Unit"
                            />
                            <!-- TODO: see how the sapcing for the input error will look like -->
                            <InputError
                                :message="step2.errors.company_address"
                            />
                        </div>

                        <div v-if="step2.business_type" class="space-y-1">
                            <Label for="s2_regno">
                                {{
                                    isCorporate
                                        ? 'SEC Registration Number'
                                        : 'DTI Registration Number'
                                }}
                            </Label>
                            <Input
                                id="s2_regno"
                                v-model="step2.registration_number"
                                class="bg-custom-bg dark:bg-custom-bg-dark"
                            />
                            <InputError
                                :message="step2.errors.registration_number"
                            />
                        </div>

                        <template v-if="step2.business_type">
                            <div class="flex items-center gap-3 py-2">
                                <p
                                    class="text-base font-semibold text-custom-accent-3"
                                >
                                    Authorized Representative
                                </p>
                                <Separator class="flex-1" />
                            </div>

                            <div class="grid gap-2 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <Label for="s2_rname">Full Name</Label>
                                    <Input
                                        id="s2_rname"
                                        v-model="
                                            step2.authorized_representative_name
                                        "
                                        autocomplete="name"
                                        class="bg-custom-bg dark:bg-custom-bg-dark"
                                    />
                                    <InputError
                                        :message="
                                            step2.errors
                                                .authorized_representative_name
                                        "
                                    />
                                </div>

                                <div class="space-y-1">
                                    <Label for="s2_rpos">Position</Label>
                                    <Select v-model="positionChoice">
                                        <SelectTrigger
                                            id="s2_rpos"
                                            class="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="p in positionOptions"
                                                :key="p"
                                                :value="p"
                                                >{{ p }}</SelectItem
                                            >
                                            <SelectItem value="other"
                                                >Other (type
                                                manually)</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                    <Input
                                        v-if="positionChoice === 'other'"
                                        class="mt-2 bg-custom-bg dark:bg-custom-bg-dark"
                                        v-model="
                                            step2.authorized_representative_position
                                        "
                                    />
                                    <InputError
                                        :message="
                                            step2.errors
                                                .authorized_representative_position
                                        "
                                    />
                                </div>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <Label for="s2_rcont">Phone</Label>
                                    <Input
                                        id="s2_rcont"
                                        v-model="
                                            step2.authorized_representative_contact
                                        "
                                        autocomplete="tel"
                                        class="bg-custom-bg dark:bg-custom-bg-dark"
                                    />
                                    <InputError
                                        :message="
                                            step2.errors
                                                .authorized_representative_contact
                                        "
                                    />
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- LABEL: ══ STEP 2.5 – Company Email OTP ══════════════════════ -->
                    <div
                        v-else-if="currentStep === 2.5"
                        class="space-y-2"
                        @keydown.enter.prevent="
                            !otpCompany.processing &&
                            otpCompany.otp.length === 6 &&
                            submitCompanyOtp()
                        "
                    >
                        <div
                            class="flex flex-col items-center rounded-md border border-dashed border-custom-bg-dark p-6 text-center text-custom-shadow dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                        >
                            <img
                                :src="AuthenticationRaifikiUrl"
                                alt=""
                                class="w-1/3 object-contain opacity-90"
                                aria-hidden="true"
                            />
                            <p class="mb-2 text-custom-shadow">
                                Enter the 6-digit code we sent to
                                <span
                                    class="font-semibold text-custom-accent-3"
                                    >{{ step2.company_email }}</span
                                >. The code expires in 10 minutes.
                            </p>
                        </div>

                        <div class="space-y-0">
                            <Label for="otp_comp" class="sr-only"
                                >Verification Code</Label
                            >
                            <Input
                                id="otp_comp"
                                v-model="otpCompany.otp"
                                inputmode="numeric"
                                maxlength="6"
                                placeholder="000000"
                                class="h-14 text-center font-mono text-2xl tracking-widest placeholder:text-custom-shadow/80"
                                :disabled="otpCompany.processing"
                                @input="onOtpInput('company')"
                            />
                            <InputError
                                :message="otpCompany.errors.otp"
                                class="text-center"
                            />
                        </div>

                        <div
                            v-if="resentCompanyMsg"
                            class="mt-2 flex w-full flex-row items-start gap-x-2 rounded-md border-2 border-info/50 bg-info/20 p-3 text-info"
                        >
                            <RiCheckboxCircleFill
                                class="mt-0.5 h-4 w-4 shrink-0"
                            />
                            <p class="text-sm">
                                {{ resentCompanyMsg }}
                            </p>
                        </div>

                        <div
                            class="flex flex-row justify-center gap-x-1 py-3 text-xs"
                        >
                            <span> Didn't receive it? </span>
                            <button
                                type="button"
                                class="cursor-pointer text-xs font-semibold text-custom-accent-3 transition-colors duration-300 ease-out hover:underline hover:underline-offset-2 disabled:opacity-50"
                                :disabled="resendCompany.processing"
                                @click="doResendCompany"
                            >
                                Resend code
                            </button>
                        </div>
                    </div>

                    <!-- LABEL: ══ STEP 3 – Documents ════════════════════════════════ -->

                    <div
                        v-else-if="currentStep === 3"
                        class="flex flex-col gap-y-2"
                        @keydown.enter.capture.prevent="
                            !step3.processing && isStep3Valid && submitStep3()
                        "
                    >
                        <div class="space-y-2">
                            <template
                                v-for="(doc, index) in requiredDocs"
                                :key="doc.key"
                            >
                                <div
                                    class="flex items-center gap-2"
                                    :class="index === 0 ? 'pb-2' : 'py-2'"
                                >
                                    <p
                                        class="shrink-0 text-base font-semibold text-custom-accent-3"
                                    >
                                        {{ doc.label }}
                                        <span
                                            v-if="doc.required"
                                            class="text-destructive"
                                        >
                                            *
                                        </span>
                                        <span
                                            v-else
                                            class="text-xs text-custom-shadow/80"
                                        >
                                            (optional)
                                        </span>
                                    </p>
                                    <Separator class="flex-1" />
                                </div>

                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div class="space-y-1 sm:col-span-2">
                                        <Label :for="doc.key">Document</Label>
                                        <p
                                            class="text-xs text-custom-shadow/70"
                                        >
                                            Allowed: {{ allowedFileTypesText }}.
                                            Maximum: {{ maxFileSizeText }}.
                                        </p>
                                        <Input
                                            :id="doc.key"
                                            :key="
                                                documentInputResetKeys[
                                                    doc.key
                                                ] ?? 0
                                            "
                                            type="file"
                                            :accept="uploadRules.accept"
                                            class="cursor-pointer p-0 pr-3 file:mr-3 file:h-full file:cursor-pointer file:border-0 file:border-r file:border-custom-bg-dark file:bg-custom-bg-dark file:px-3 file:text-sm file:text-custom-shadow hover:file:bg-custom-secondary/20"
                                            @change="
                                                handleFile(doc.key, $event)
                                            "
                                        />
                                        <div
                                            v-if="step3.documents[doc.key].file"
                                            class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-custom-bg-dark/40 bg-custom-bg-dark/10 px-3 py-2 text-xs text-custom-shadow dark:border-custom-bg-light/30"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate font-semibold"
                                                >
                                                    {{
                                                        step3.documents[doc.key]
                                                            .file?.name
                                                    }}
                                                </p>
                                                <p
                                                    class="text-custom-shadow/70"
                                                >
                                                    {{
                                                        formatFileSize(
                                                            step3.documents[
                                                                doc.key
                                                            ].file?.size,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                            <div
                                                class="flex items-center gap-1"
                                            >
                                                <Button
                                                    v-if="
                                                        canPreviewFile(
                                                            step3.documents[
                                                                doc.key
                                                            ].file,
                                                        )
                                                    "
                                                    type="button"
                                                    variant="ghost-outline"
                                                    size="icon-text"
                                                    @click="
                                                        openSelectedFilePreview(
                                                            `documents.${doc.key}`,
                                                            doc.label,
                                                            step3.documents[
                                                                doc.key
                                                            ].file,
                                                        )
                                                    "
                                                >
                                                    <RiEyeLine
                                                        class="h-4 w-4"
                                                    />
                                                    Preview
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost-outline"
                                                    size="icon-text"
                                                    @click="
                                                        removeDocumentFile(
                                                            doc.key,
                                                        )
                                                    "
                                                >
                                                    <RiCloseLine
                                                        class="h-4 w-4"
                                                    />
                                                    Remove
                                                </Button>
                                            </div>
                                        </div>
                                        <InputError
                                            :message="docError(doc.key, 'file')"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <Label :for="`${doc.key}_iss`"
                                            >Issue Date</Label
                                        >
                                        <Popover
                                            :open="
                                                openDatePicker ===
                                                `${doc.key}_issued_at`
                                            "
                                            @update:open="
                                                (open) =>
                                                    (openDatePicker = open
                                                        ? `${doc.key}_issued_at`
                                                        : null)
                                            "
                                        >
                                            <div class="flex">
                                                <Input
                                                    :id="`${doc.key}_iss`"
                                                    v-model="
                                                        step3.documents[doc.key]
                                                            .issued_at
                                                    "
                                                    type="text"
                                                    inputmode="numeric"
                                                    maxlength="10"
                                                    placeholder="YYYY-MM-DD"
                                                    class="rounded-r-none"
                                                />
                                                <PopoverTrigger as-child>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        class="shrink-0 rounded-l-none border border-0 border-custom-bg-dark bg-custom-bg hover:bg-custom-secondary/20 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                                                        aria-label="Choose issue date"
                                                    >
                                                        <RiCalendarLine
                                                            class="h-4 w-4"
                                                        />
                                                    </Button>
                                                </PopoverTrigger>
                                            </div>
                                            <PopoverContent
                                                align="start"
                                                class="w-auto p-0"
                                            >
                                                <CalendarPicker
                                                    :model-value="
                                                        parseCalendarDate(
                                                            step3.documents[
                                                                doc.key
                                                            ].issued_at,
                                                        )
                                                    "
                                                    initial-focus
                                                    @update:model-value="
                                                        (value) =>
                                                            selectDocumentDate(
                                                                doc.key,
                                                                'issued_at',
                                                                value as
                                                                    | CalendarDate
                                                                    | undefined,
                                                            )
                                                    "
                                                />
                                            </PopoverContent>
                                        </Popover>
                                        <InputError
                                            :message="
                                                docError(doc.key, 'issued_at')
                                            "
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <Label :for="`${doc.key}_exp`"
                                            >Expiration Date</Label
                                        >
                                        <Popover
                                            :open="
                                                openDatePicker ===
                                                `${doc.key}_expires_at`
                                            "
                                            @update:open="
                                                (open) =>
                                                    (openDatePicker = open
                                                        ? `${doc.key}_expires_at`
                                                        : null)
                                            "
                                        >
                                            <div class="flex">
                                                <Input
                                                    :id="`${doc.key}_exp`"
                                                    v-model="
                                                        step3.documents[doc.key]
                                                            .expires_at
                                                    "
                                                    type="text"
                                                    inputmode="numeric"
                                                    maxlength="10"
                                                    placeholder="YYYY-MM-DD"
                                                    class="rounded-r-none"
                                                />
                                                <PopoverTrigger as-child>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        class="shrink-0 rounded-l-none border border-0 border-custom-bg-dark bg-custom-bg hover:bg-custom-secondary/20 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                                                        aria-label="Choose expiration date"
                                                    >
                                                        <RiCalendarLine
                                                            class="h-4 w-4"
                                                        />
                                                    </Button>
                                                </PopoverTrigger>
                                            </div>
                                            <PopoverContent
                                                align="start"
                                                class="w-auto p-0"
                                            >
                                                <CalendarPicker
                                                    :model-value="
                                                        parseCalendarDate(
                                                            step3.documents[
                                                                doc.key
                                                            ].expires_at,
                                                        )
                                                    "
                                                    initial-focus
                                                    @update:model-value="
                                                        (value) =>
                                                            selectDocumentDate(
                                                                doc.key,
                                                                'expires_at',
                                                                value as
                                                                    | CalendarDate
                                                                    | undefined,
                                                            )
                                                    "
                                                />
                                            </PopoverContent>
                                        </Popover>
                                        <InputError
                                            :message="
                                                docError(doc.key, 'expires_at')
                                            "
                                        />
                                    </div>
                                </div>
                            </template>

                            <div class="flex items-center gap-3 pt-2">
                                <p
                                    class="shrink-0 text-base font-semibold text-custom-accent-3"
                                >
                                    Supporting Documents
                                    <span class="text-xs text-custom-shadow/80"
                                        >(optional)</span
                                    >
                                </p>
                                <Separator class="flex-1" />
                                <Button
                                    type="button"
                                    variant="ghost-outline"
                                    size="icon-text"
                                    @click="addSupportingDocument"
                                >
                                    <RiAddLine class="h-4 w-4" />
                                    Add
                                </Button>
                            </div>

                            <div
                                v-for="(
                                    document, index
                                ) in step3.supporting_documents"
                                :key="document.id"
                                class="rounded-md border border-dashed border-custom-bg-dark p-3 dark:border-custom-bg-light"
                            >
                                <div
                                    class="mb-2 flex items-start justify-between gap-2"
                                >
                                    <div>
                                        <p
                                            class="font-semibold text-custom-shadow"
                                        >
                                            Supporting Document {{ index + 1 }}
                                        </p>
                                        <p
                                            class="text-xs text-custom-shadow/70"
                                        >
                                            Allowed: {{ allowedFileTypesText }}.
                                            Maximum: {{ maxFileSizeText }}.
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost-outline"
                                        size="icon"
                                        aria-label="Remove supporting document"
                                        @click="removeSupportingDocument(index)"
                                    >
                                        <RiDeleteBinLine class="h-4 w-4" />
                                    </Button>
                                </div>

                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div class="space-y-1 sm:col-span-2">
                                        <Label
                                            :for="`supporting_title_${document.id}`"
                                            >Title</Label
                                        >
                                        <Input
                                            :id="`supporting_title_${document.id}`"
                                            v-model="document.title"
                                            class="bg-custom-bg dark:bg-custom-bg-dark"
                                        />
                                        <InputError
                                            :message="
                                                supportingDocError(
                                                    index,
                                                    'title',
                                                )
                                            "
                                        />
                                    </div>
                                    <div class="space-y-1 sm:col-span-2">
                                        <Label
                                            :for="`supporting_file_${document.id}`"
                                            >Document</Label
                                        >
                                        <Input
                                            :id="`supporting_file_${document.id}`"
                                            :key="
                                                supportingInputResetKeys[
                                                    document.id
                                                ] ?? 0
                                            "
                                            type="file"
                                            :accept="uploadRules.accept"
                                            class="cursor-pointer p-0 pr-3 file:mr-3 file:h-full file:cursor-pointer file:border-0 file:border-r file:border-custom-bg-dark file:bg-custom-bg-dark file:px-3 file:text-sm file:text-custom-shadow hover:file:bg-custom-secondary/20"
                                            @change="
                                                handleSupportingFile(
                                                    index,
                                                    $event,
                                                )
                                            "
                                        />
                                        <div
                                            v-if="document.file"
                                            class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-custom-bg-dark/40 bg-custom-bg-dark/10 px-3 py-2 text-xs text-custom-shadow dark:border-custom-bg-light/30"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate font-semibold"
                                                >
                                                    {{ document.file.name }}
                                                </p>
                                                <p
                                                    class="text-custom-shadow/70"
                                                >
                                                    {{
                                                        formatFileSize(
                                                            document.file.size,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                            <div
                                                class="flex items-center gap-1"
                                            >
                                                <Button
                                                    v-if="
                                                        canPreviewFile(
                                                            document.file,
                                                        )
                                                    "
                                                    type="button"
                                                    variant="ghost-outline"
                                                    size="icon-text"
                                                    @click="
                                                        openSelectedFilePreview(
                                                            `supporting_documents.${document.id}`,
                                                            document.title ||
                                                                `Supporting Document ${index + 1}`,
                                                            document.file,
                                                        )
                                                    "
                                                >
                                                    <RiEyeLine
                                                        class="h-4 w-4"
                                                    />
                                                    Preview
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost-outline"
                                                    size="icon-text"
                                                    @click="
                                                        removeSupportingFile(
                                                            index,
                                                        )
                                                    "
                                                >
                                                    <RiCloseLine
                                                        class="h-4 w-4"
                                                    />
                                                    Remove
                                                </Button>
                                            </div>
                                        </div>
                                        <InputError
                                            :message="
                                                supportingDocError(
                                                    index,
                                                    'file',
                                                )
                                            "
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <RegistrationStatus
                        v-else-if="currentStep === 4 && company && meta"
                        :company="company"
                        :meta="meta"
                        :upload-rules="uploadRules"
                        embedded
                    />
                </div>

                <Separator v-if="currentStep !== 4" class="my-2 flex-1" />

                <div
                    v-if="currentStep !== 4"
                    class="flex w-full flex-row items-center justify-end gap-x-2 pt-2"
                >
                    <!-- TODO: make this button strictly go only to step 1 -->
                    <Button
                        v-if="currentStep !== 1"
                        type="button"
                        variant="ghost-outline"
                        size="icon-text"
                        @click="goBack"
                        class="cursor-pointer"
                    >
                        <RiArrowLeftSLine class="h-4 w-4 text-custom-shadow" />
                        <span class="hidden lg:block">Back</span>
                    </Button>

                    <Button
                        v-if="currentStep === 1"
                        type="button"
                        variant="float-primary"
                        size="text"
                        :disabled="step1.processing || !isStep1Valid"
                        @click="submitStep1"
                    >
                        <RiLoaderLine
                            v-if="step1.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        {{ step1.processing ? 'Validating' : 'Continue' }}
                    </Button>

                    <Button
                        v-else-if="currentStep === 1.5"
                        type="button"
                        variant="float-primary"
                        size="text"
                        :disabled="
                            otpAccount.processing || otpAccount.otp.length < 6
                        "
                        @click="submitAccountOtp"
                    >
                        <RiLoaderLine
                            v-if="otpAccount.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        {{ otpAccount.processing ? 'Verifying...' : 'Verify' }}
                    </Button>

                    <Button
                        v-else-if="currentStep === 2"
                        type="button"
                        variant="float-primary"
                        size="text"
                        :disabled="step2.processing"
                        @click="submitStep2"
                    >
                        <RiLoaderLine
                            v-if="step2.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        {{ step2.processing ? 'Validating' : 'Continue' }}
                    </Button>

                    <Button
                        v-else-if="currentStep === 2.5"
                        type="button"
                        variant="float-primary"
                        size="text"
                        :disabled="
                            otpCompany.processing || otpCompany.otp.length < 6
                        "
                        @click="submitCompanyOtp"
                    >
                        <RiLoaderLine
                            v-if="otpCompany.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        {{ otpCompany.processing ? 'Verifying' : 'Verify' }}
                    </Button>

                    <Button
                        v-else-if="currentStep === 3"
                        type="button"
                        variant="float-primary"
                        size="text"
                        :disabled="step3.processing"
                        @click="requestStep3Submission"
                    >
                        <RiLoaderLine
                            v-if="step3.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        {{ step3.processing ? 'Submitting...' : 'Submit' }}
                    </Button>
                </div>
            </div>
        </Card>

        <AlertDialog v-model:open="confirmStep3Open">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Submit Documents</AlertDialogTitle>
                    <AlertDialogDescription>
                        These documents will be sent for company registration
                        review.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div
                    class="max-h-56 space-y-2 overflow-auto text-sm text-custom-shadow"
                >
                    <div
                        v-for="document in selectedSubmissionDocuments"
                        :key="`${document.label}-${document.file.name}`"
                        class="flex items-center gap-2 rounded-md border border-custom-bg-dark/40 px-3 py-2 dark:border-custom-bg-light/30"
                    >
                        <RiFileTextLine class="h-4 w-4 shrink-0" />
                        <div class="min-w-0">
                            <p class="truncate font-semibold">
                                {{ document.label }}
                            </p>
                            <p class="truncate text-xs text-custom-shadow/70">
                                {{ document.file.name }} ·
                                {{ formatFileSize(document.file.size) }}
                            </p>
                        </div>
                    </div>
                    <p
                        v-if="!selectedSubmissionDocuments.length"
                        class="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-destructive"
                    >
                        No files are selected yet.
                    </p>
                </div>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        :disabled="
                            !selectedSubmissionDocuments.length ||
                            step3.processing
                        "
                        @click="submitStep3"
                    >
                        Submit
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <Dialog v-model:open="previewOpen">
            <DialogContent
                class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden p-0"
            >
                <DialogHeader class="border-b px-6 py-4">
                    <DialogTitle class="truncate">
                        {{ previewFile?.title }}
                    </DialogTitle>
                    <DialogDescription class="truncate">
                        {{ previewFile?.name }} · {{ previewFile?.size }}
                    </DialogDescription>
                </DialogHeader>
                <div
                    class="flex-1 overflow-auto bg-custom-bg-dark/10 p-4 dark:bg-custom-bg-light/10"
                >
                    <div
                        v-if="previewFile?.type === 'image'"
                        class="flex min-h-[55vh] items-center justify-center"
                    >
                        <img
                            :src="previewFile.url"
                            :alt="previewFile.name"
                            class="max-h-[70vh] w-auto max-w-full rounded-md border bg-white object-contain"
                        />
                    </div>
                    <div
                        v-else-if="previewFile?.type === 'pdf'"
                        class="h-[70vh] overflow-hidden rounded-md border bg-white"
                    >
                        <iframe
                            :src="previewFile.url"
                            class="h-full w-full"
                            title="PDF preview"
                        />
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AuthLayout>
</template>
