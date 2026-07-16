<script setup lang="ts">
import { CalendarDate } from '@internationalized/date';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3'

import AuthLayout from '@/layouts/AuthLayout.vue';

import { Button } from '@/components/ui/button';
import { Calendar as CalendarPicker } from '@/components/ui/calendar';
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
import InputError from '@/components/InputError.vue';
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
    RiArrowLeftSLine,
    RiCheckboxCircleFill,
    RiCheckLine,
    RiCloseLine,
    RiEyeLine,
    RiEyeOffLine,
    RiLoaderLine,
    RiImageAddLine,
    RiCalendarLine,
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
}>();

type SubStep = 1 | 1.5 | 2 | 2.5 | 3 | 4;

const currentStep = ref<SubStep>(props.company ? 4 : 1);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const visualStep = computed((): 1 | 2 | 3 | 4 => {
    if (currentStep.value < 2)  return 1;
    if (currentStep.value < 3)  return 2;
    if (currentStep.value < 4) return 3;
    return 4;
});

const stepMeta = [
    { number: 1, title: 'Operator Profile', description: 'Register and verify your operator account details.',},
    { number: 2, title: 'Company Profile', description: 'Enter and verify company details.',},
    { number: 3, title: 'Documents', description: 'Provide required registration documentation for verification.',},
    { number: 4, title: 'Status', description: 'Track the review status of your company registration.',},
];

const step1 = useForm({
    name:                  '',
    email:                 '',
    phone:                 '',
    password:              '',
    password_confirmation: '',
});

const otpAccount       = useForm({ otp: '' });
const resendAccount    = useForm({});
const resentAccountMsg = ref('');

const step2 = useForm({
    company_name:                       '',
    company_email:                      '',
    company_phone:                      '',
    company_address:                    '',
    business_type:                      '' as 'corporate' | 'sole_proprietorship' | '',
    registration_number:                '',
    authorized_representative_name:     '',
    authorized_representative_position: '',
    authorized_representative_contact:  '',

    logo: null as File | null,
});

const logoPreview    = ref<string | null>(null);
const logoInputRef   = ref<HTMLInputElement | null>(null);

function handleLogoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;

    step2.logo = file;
    step2.clearErrors('logo');

    const reader = new FileReader();
    reader.onload = (ev) => { logoPreview.value = ev.target?.result as string; };
    reader.readAsDataURL(file);
}

function removeLogo() {
    step2.logo = null;
    logoPreview.value = null;
    if (logoInputRef.value) logoInputRef.value.value = '';
}

const addressCodes = ref({ regionCode: '', provinceCode: '', cityMunCode: '', barangayCode: '' });

const positionOptions = [
    'Owner', 'Proprietor', 'President', 'CEO', 'COO',
    'General Manager', 'Operations Manager', 'HR Manager', 'Authorized Representative',
] as const;

const positionChoice = ref('');

watch(positionChoice, (val) => {
    if (!val) return;
    if (val !== 'other') {
        step2.authorized_representative_position = val;
        step2.clearErrors('authorized_representative_position');
    } else if (positionOptions.includes(step2.authorized_representative_position as any)) {
        step2.authorized_representative_position = '';
    }
});

const otpCompany       = useForm({ otp: '' });
const resendCompany    = useForm({});
const resentCompanyMsg = ref('');

const step3 = useForm({
    documents: {
        AUTHORIZATION_LETTER: { file: null as File | null, issued_at: '', expires_at: '' },
        SEC_CERT:             { file: null as File | null, issued_at: '', expires_at: '' },
        DTI_CERT:             { file: null as File | null, issued_at: '', expires_at: '' },
        MAYORS_PERMIT:        { file: null as File | null, issued_at: '', expires_at: '' },
        BIR_2303:             { file: null as File | null, issued_at: '', expires_at: '' },
    } as Record<string, { file: File | null; issued_at: string; expires_at: string }>,
});

const isCorporate = computed(() => step2.business_type === 'corporate');
const isSole      = computed(() => step2.business_type === 'sole_proprietorship');

const requiredDocs = computed<{ key: string; label: string; required: boolean }[]>(() => {
    const base = [
        { key: 'MAYORS_PERMIT', label: "Mayor's Permit", required: true },
        { key: 'BIR_2303',      label: 'BIR Form 2303',  required: true },
    ];

    if (isCorporate.value) return [
        { key: 'AUTHORIZATION_LETTER', label: 'Authorization Letter', required: true },
        { key: 'SEC_CERT',             label: 'SEC Certificate',       required: true },
        ...base,
    ];

    if (isSole.value) return [
        { key: 'DTI_CERT', label: 'DTI Certificate', required: true },
        ...base,
    ];

    return [
        { key: 'AUTHORIZATION_LETTER', label: 'Authorization Letter (Corporate)', required: false },
        { key: 'SEC_CERT',             label: 'SEC Certificate (Corporate)',       required: false },
        { key: 'DTI_CERT',             label: 'DTI Certificate (Sole Prop.)',      required: false },
        ...base.map((d) => ({ ...d, required: false })),
    ];
});

function submitStep1() {
    step1.submit(storeStep1(), {
        onSuccess: () => { resentAccountMsg.value = ''; currentStep.value = 1.5; },
    });
}

function submitAccountOtp() {
    otpAccount.submit(verifyStep1Otp(), {
        onSuccess: () => { currentStep.value = 2; otpAccount.reset(); },
    });
}

function submitStep2() {
    step2.submit(storeStep2(), {
        forceFormData: true,   
        onSuccess: () => { resentCompanyMsg.value = ''; currentStep.value = 2.5; },
    });
}

function submitCompanyOtp() {
    otpCompany.submit(verifyStep2Otp(), {
        onSuccess: () => { currentStep.value = 3; otpCompany.reset(); },
    });
}

function submitStep3() {
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
        onSuccess: () => { resentAccountMsg.value = 'A new code was sent to your email.'; otpAccount.reset(); },
    });
}

function doResendCompany() {
    resentCompanyMsg.value = '';
    resendCompany.submit(resendStep2Otp(), {
        onSuccess: () => { resentCompanyMsg.value = 'A new code was sent to the company email.'; otpCompany.reset(); },
    });
}

function goBack() {
    const map: Partial<Record<SubStep, SubStep>> = {
        1.5: 1,
        2: 1.5,
        2.5: 2,
        3: 2.5,
    }

    const prev = map[currentStep.value]

    if (prev !== undefined) {
        currentStep.value = prev
    } else {
        router.visit('/')
    }
}

function onOtpInput(type: 'account' | 'company') {
    const form = type === 'account' ? otpAccount : otpCompany;
    if (form.otp.replace(/\D/g, '').length === 6) {
        type === 'account' ? submitAccountOtp() : submitCompanyOtp();
    }
}

function handleFile(docKey: string, event: Event) {
    const el = event.target as HTMLInputElement;
    step3.documents[docKey].file = el.files?.[0] ?? null;
    step3.clearErrors(`documents.${docKey}.file` as any);
}

function docError(docKey: string, field: 'file' | 'issued_at' | 'expires_at'): string | undefined {
    return (step3.errors as Record<string, string>)[`documents.${docKey}.${field}`];
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
</script>

<template>
    <AuthLayout
        title="Company Registration"
        description=""
    >
        <Head :title="currentStep === 4 ? 'Registration Status' : 'Company Registration'" />
        <Card class="mx-auto max-w-3xl flex flex-row">
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
                        :disabled="company ? step.number !== 4 : step.number > visualStep"
                        @click="!company && step.number < visualStep && (currentStep = (step.number as SubStep))"
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
                                <RiCheckLine v-if="step.number < visualStep" class="h-4 w-4 text-custom-shadow"/>
                                <span v-else
                                    class="w-full text-center"
                                    :class="{
                                        'text-custom-shadow font-semibold': (visualStep === step.number || step.number < visualStep),
                                        'text-custom-shadow/80 font-normal': step.number > visualStep,
                                    }"
                                >{{ step.number }}</span>
                            </div>
                            <span
                                class="inline"
                                :class="{
                                    'text-custom-shadow font-semibold': (visualStep === step.number || step.number < visualStep),
                                    'text-custom-shadow/80': step.number > visualStep,
                                }"
                            >
                                {{ step.title }}
                            </span>
                        </div>
                        <span
                            v-if="visualStep === step.number"
                            class="ml-10 mt-2 text-xs text-custom-shadow"
                        >
                            {{ step.description }}
                        </span>
                    </button>
                    <div
                        v-if="idx < stepMeta.length - 1"
                        class="absolute top-8 -bottom-2 left-4 w-[2px] -translate-x-1/2 transition-colors"
                        :class="step.number < visualStep ? 'bg-custom-accent-2' : 'bg-custom-bg-dark dark:bg-custom-bg-light'"
                    />
                </div>
            </div>
            <Separator orientation="vertical" class="h-auto! self-stretch"/>
            <div class="h-full px-6 w-full">
                <div class="pb-2">
                    <CardTitle class="">
                        <template v-if="currentStep === 1.5">Verify Account Email</template>
                        <template v-else-if="currentStep === 2.5">Verify Company Email</template>
                        <template v-else>{{ stepMeta[visualStep - 1].title }}</template>
                    </CardTitle>
                </div>

                <div class="text-sm py-2">
                    <!-- LABEL: ══ STEP 1 – Account Details ══════════════════════════ -->
                    <div v-if="currentStep === 1" class="flex flex-col gap-y-2">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <!-- TODO: might need to separate the full name into first name, middle name, and last name -->
                            <div class="space-y-1">
                                <Label for="s1_name">Full Name</Label>
                                <Input id="s1_name" v-model="step1.name" placeholder="Juan Dela Cruz" autocomplete="name" class="bg-custom-bg dark:bg-custom-bg-dark"/>
                                <InputError :message="step1.errors.name" />
                            </div>
                            <div class="space-y-1">
                                <Label for="s1_email">Email</Label>
                                <Input id="s1_email" type="email" v-model="step1.email" placeholder="juan.delacruz@example.com" autocomplete="email" class="bg-custom-bg dark:bg-custom-bg-dark"/>
                                <InputError :message="step1.errors.email" />
                            </div>
                        </div>

                        <!-- TODO: add the philipino code number thingy i used in flutter app that automatically validates this -->
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="space-y-1">
                                <Label for="s1_phone">Phone</Label>
                                <Input id="s1_phone" v-model="step1.phone" placeholder="+63 9XX XXX XXXX" autocomplete="tel" class="bg-custom-bg dark:bg-custom-bg-dark"/>
                                <InputError :message="step1.errors.phone" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="s1_pw">Password</Label>
                                <div class="relative">
                                    <Input
                                        id="s1_pw"
                                        v-model="step1.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="••••••••"
                                        autocomplete="new-password"
                                         class="bg-custom-bg dark:bg-custom-bg-dark pr-10"
                                    />
                                    <button
                                        type="button"
                                        aria-label="Hold to show password"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-custom-shadow/60"
                                        @pointerdown.prevent="showPassword = true"
                                        @pointerup="showPassword = false"
                                        @pointerleave="showPassword = false"
                                        @pointercancel="showPassword = false"
                                    >
                                        <RiEyeOffLine v-if="showPassword" class="h-4 w-4" />
                                        <RiEyeLine v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <InputError :message="step1.errors.password" />
                            </div>
                            <!-- TODO: add password creation instructions at the bottom, styled like inputerror component -->
                            <div class="space-y-1.5">
                                <Label for="s1_pwc">Confirm Password</Label>
                                <div class="relative">
                                    <Input
                                        id="s1_pwc"
                                        v-model="step1.password_confirmation"
                                        :type="showPasswordConfirmation ? 'text' : 'password'"
                                        placeholder="••••••••"
                                        autocomplete="new-password"
                                         class="bg-custom-bg dark:bg-custom-bg-dark pr-10"
                                    />
                                    <button
                                        type="button"
                                        aria-label="Hold to show password confirmation"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-custom-shadow/60"
                                        @pointerdown.prevent="showPasswordConfirmation = true"
                                        @pointerup="showPasswordConfirmation = false"
                                        @pointerleave="showPasswordConfirmation = false"
                                        @pointercancel="showPasswordConfirmation = false"
                                    >
                                        <RiEyeOffLine v-if="showPasswordConfirmation" class="h-4 w-4" />
                                        <RiEyeLine v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <InputError :message="step1.errors.password_confirmation" />
                            </div>
                        </div>
                    </div>

                    <!-- LABEL: ══ STEP 1.5 – Account Email OTP ══════════════════════ -->
                    <div v-else-if="currentStep === 1.5" class="space-y-2">
                        <div class="flex flex-col items-center text-custom-shadow rounded-md border border-dashed border-custom-bg-dark dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5 p-6 text-center">
                            
                            <img
                                :src="AuthenticationRaifikiUrl"
                                alt=""
                                class="w-1/3 object-contain opacity-90"
                                aria-hidden="true"
                            />
                            <p class="text-custom-shadow mb-2">
                                Enter the 6-digit code we sent to
                                <span class="font-semibold text-custom-accent-3">{{ step1.email }}</span>.
                                The code expires in 10 minutes.
                            </p>
                        </div>

                        <div class="space-y-0">
                            <Label for="otp_acc" class="sr-only">Verification Code</Label>
                            <Input
                                id="otp_acc"
                                v-model="otpAccount.otp"
                                inputmode="numeric"
                                maxlength="6"
                                placeholder="000000"
                                class="h-14 text-center text-2xl font-mono tracking-widest placeholder:text-custom-shadow/80"
                                :disabled="otpAccount.processing"
                                @input="onOtpInput('account')"
                            />
                            <InputError :message="otpAccount.errors.otp" class="text-center" />
                        </div>

                        <div
                            v-if="resentAccountMsg"
                            class="mt-2 flex w-full flex-row items-start gap-x-2 rounded-md border-2 border-info/50 bg-info/20 p-3 text-info"
                        >
                            <RiCheckboxCircleFill class="mt-0.5 h-4 w-4 shrink-0" />
                            <p class="text-sm">
                                {{ resentAccountMsg }}
                            </p>
                        </div>

                        <div class="flex flex-row gap-x-1 justify-center text-xs py-3">
                            <span>
                                Didn't receive it?
                            </span>
                            <button
                                type="button"
                                class="cursor-pointer text-xs text-custom-accent-3 font-semibold disabled:opacity-50 hover:underline hover:underline-offset-2 transition-colors duration-300 ease-out"
                                :disabled="resendAccount.processing"
                                @click="doResendAccount"
                            >
                                Resend code
                            </button>
                        </div>
                    </div>

                    <!-- LABEL: ══ STEP 2 – Company Details ══════════════════════════ -->
                    <div v-else-if="currentStep === 2" class="flex flex-col gap-y-2">

                        
                        <div class="space-y-2">
                            <Label>
                                Logo
                                <span class="text-custom-shadow/80">(optional)</span>
                            </Label>

                            <div class="flex items-center gap-3 rounded-md border border-dashed border-custom-bg-dark p-3 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5">
                                
                                <div
                                    role="button"
                                    tabindex="0"
                                    :aria-label="logoPreview ? 'Change logo' : 'Upload logo'"
                                    class="group relative h-24 w-24 shrink-0 cursor-pointer overflow-hidden rounded-md border transition-colors"
                                    :class="logoPreview ? 'border-none' : 'border-dashed border-custom-bg-dark dark:border-custom-bg-light'"
                                    @click="logoInputRef?.click()"
                                    @keydown.enter.prevent="logoInputRef?.click()"
                                    @keydown.space.prevent="logoInputRef?.click()"
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
                                        <RiImageAddLine class="h-7 w-7 text-custom-shadow" />
                                    </div>
                                    <div
                                        v-else
                                        class="flex h-full w-full flex-col items-center justify-center"
                                    >
                                        <RiImageAddLine class="h-6 w-6 text-custom-shadow/80" />
                                    </div>

                                    
                                    <Button
                                        v-if="logoPreview"
                                        type="button"
                                        aria-label="Remove logo"
                                        @click.stop="removeLogo"
                                        class="absolute right-1 top-1 z-10 flex h-6 w-6 items-center border rounded-full cursor-pointer border-custom-shadow/50 text-custom-shadow hover:bg-destructive/20 hover:border-destructive hover:text-destructive transition-all duration-300"
                                    >
                                        <RiCloseLine class="h-4 w-4" />
                                    </Button>
                                </div>

                                
                                <div class="space-y-1">
                                    <p class="text-sm text-custom-shadow/80">
                                        <span class="font-semibold">File format: </span>.jpg, .png or .webp<br />
                                        <span class="font-semibold">Max. file size: </span>2 MB<br />
                                        <span class="font-semibold">Recommended: </span>square, 200×200 px+
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
                                <Input id="s2_cname" v-model="step2.company_name" class="bg-custom-bg dark:bg-custom-bg-dark"/>
                                <InputError :message="step2.errors.company_name"/>
                            </div>
                            <div class="space-y-1">
                                <Label for="s2_cemail">Email</Label>
                                <Input id="s2_cemail" type="email" v-model="step2.company_email" autocomplete="email" class="bg-custom-bg dark:bg-custom-bg-dark"/>
                                <InputError :message="step2.errors.company_email" />
                            </div>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="space-y-1">
                                <Label for="s2_cphone">Phone</Label>
                                <Input id="s2_cphone" v-model="step2.company_phone" autocomplete="tel" placeholder="+63 9XX XXX XXXX" class="bg-custom-bg dark:bg-custom-bg-dark"/>
                                <InputError :message="step2.errors.company_phone" />
                            </div>
                            <div class="space-y-1">
                                <Label for="s2_btype">Business Type</Label>
                                <Select v-model="step2.business_type">
                                    <SelectTrigger id="s2_btype" class="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="corporate">Corporate</SelectItem>
                                        <SelectItem value="sole_proprietorship">Sole Proprietorship</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="step2.errors.business_type" />
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
                            <InputError :message="step2.errors.company_address" />
                        </div>

                        <div v-if="step2.business_type" class="space-y-1">
                            <Label for="s2_regno">
                                {{ isCorporate ? 'SEC Registration Number' : 'DTI Registration Number' }}
                            </Label>
                            <Input id="s2_regno" v-model="step2.registration_number" class="bg-custom-bg dark:bg-custom-bg-dark"/>
                            <InputError :message="step2.errors.registration_number" />
                        </div>

                        <template v-if="step2.business_type">
                            <div class="flex items-center gap-3 py-2">
                                <p class="font-semibold text-custom-accent-3 text-base">Authorized Representative</p>
                                <Separator class="flex-1" />
                            </div>

                            <div class="grid gap-2 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <Label for="s2_rname">Full Name</Label>
                                    <Input id="s2_rname" v-model="step2.authorized_representative_name" autocomplete="name" class="bg-custom-bg dark:bg-custom-bg-dark"/>
                                    <InputError :message="step2.errors.authorized_representative_name" />
                                </div>

                                <div class="space-y-1">
                                    <Label for="s2_rpos">Position</Label>
                                    <Select v-model="positionChoice">
                                        <SelectTrigger id="s2_rpos" class="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="p in positionOptions" :key="p" :value="p">{{ p }}</SelectItem>
                                            <SelectItem value="other">Other (type manually)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Input
                                        v-if="positionChoice === 'other'"
                                         class="bg-custom-bg dark:bg-custom-bg-dark mt-2"
                                        v-model="step2.authorized_representative_position"
                                    />
                                    <InputError :message="step2.errors.authorized_representative_position" />
                                </div>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <Label for="s2_rcont">Phone</Label>
                                    <Input id="s2_rcont" v-model="step2.authorized_representative_contact" autocomplete="tel" class="bg-custom-bg dark:bg-custom-bg-dark"/>
                                    <InputError :message="step2.errors.authorized_representative_contact" />
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- LABEL: ══ STEP 2.5 – Company Email OTP ══════════════════════ -->
                    <div v-else-if="currentStep === 2.5" class="space-y-2">
                        <div class="flex flex-col items-center rounded-md border border-dashed border-custom-bg-dark p-6 text-center text-custom-shadow dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5">
                            <img
                                :src="AuthenticationRaifikiUrl"
                                alt=""
                                class="w-1/3 object-contain opacity-90"
                                aria-hidden="true"
                            />
                            <p class="mb-2 text-custom-shadow">
                                Enter the 6-digit code we sent to
                                <span class="font-semibold text-custom-accent-3">{{ step2.company_email }}</span>.
                                The code expires in 10 minutes.
                            </p>
                        </div>

                        <div class="space-y-0">
                            <Label for="otp_comp" class="sr-only">Verification Code</Label>
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
                            <InputError :message="otpCompany.errors.otp" class="text-center" />
                        </div>

                        <div
                            v-if="resentCompanyMsg"
                            class="mt-2 flex w-full flex-row items-start gap-x-2 rounded-md border-2 border-info/50 bg-info/20 p-3 text-info"
                        >
                            <RiCheckboxCircleFill class="mt-0.5 h-4 w-4 shrink-0" />
                            <p class="text-sm">
                                {{ resentCompanyMsg }}
                            </p>
                        </div>

                        <div class="flex flex-row justify-center gap-x-1 py-3 text-xs">
                            <span>
                                Didn't receive it?
                            </span>
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
                    <div v-else-if="currentStep === 3" class="flex flex-col gap-y-2">
                        <div class="space-y-2">
                            <template
                                v-for="(doc, index) in requiredDocs"
                                :key="doc.key"
                            >
                                <div
                                    class="flex items-center gap-2"
                                    :class="index === 0 ? 'pb-2' : 'py-2'"
                                >
                                    <p class="shrink-0 text-base font-semibold text-custom-accent-3">
                                        {{ doc.label }}
                                        <span v-if="doc.required" class="text-destructive">
                                            *
                                        </span>
                                        <span v-else class="text-xs text-custom-shadow/80">
                                            (optional)
                                        </span>
                                    </p>
                                    <Separator class="flex-1" />
                                </div>

                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div class="space-y-1 sm:col-span-2">
                                        <Label :for="doc.key">Document</Label>
                                        <Input
                                            :id="doc.key"
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            class="
                                                cursor-pointer p-0 pr-3
                                                file:mr-3 file:h-full file:cursor-pointer
                                                file:border-0 file:border-r
                                                file:border-custom-bg-dark
                                                file:bg-custom-bg-dark file:px-3
                                                file:text-sm file:text-custom-shadow
                                                hover:file:bg-custom-secondary/20
                                            "
                                            @change="handleFile(doc.key, $event)"
                                        />
                                        <InputError :message="docError(doc.key, 'file')" />
                                    </div>
                                    <div class="space-y-1">
                                        <Label :for="`${doc.key}_iss`">Issue Date</Label>
                                        <Popover
                                            :open="openDatePicker === `${doc.key}_issued_at`"
                                            @update:open="(open) => openDatePicker = open ? `${doc.key}_issued_at` : null"
                                        >
                                            <div class="flex">
                                                <Input
                                                    :id="`${doc.key}_iss`"
                                                    v-model="step3.documents[doc.key].issued_at"
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
                                                        class="shrink-0 rounded-l-none border-0 bg-custom-bg hover:bg-custom-secondary/20 dark:bg-custom-bg-dark border border-custom-bg-dark dark:border-none dark:shadow-sm dark:shadow-white/5"
                                                        aria-label="Choose issue date"
                                                    >
                                                        <RiCalendarLine class="h-4 w-4" />
                                                    </Button>
                                                </PopoverTrigger>
                                            </div>
                                            <PopoverContent align="start" class="w-auto p-0">
                                                <CalendarPicker
                                                    :model-value="parseCalendarDate(step3.documents[doc.key].issued_at)"
                                                    initial-focus
                                                    @update:model-value="(value) => selectDocumentDate(doc.key, 'issued_at', value as CalendarDate | undefined)"
                                                />
                                            </PopoverContent>
                                        </Popover>
                                        <InputError :message="docError(doc.key, 'issued_at')" />
                                    </div>
                                    <div class="space-y-1">
                                        <Label :for="`${doc.key}_exp`">Expiration Date</Label>
                                        <Popover
                                            :open="openDatePicker === `${doc.key}_expires_at`"
                                            @update:open="(open) => openDatePicker = open ? `${doc.key}_expires_at` : null"
                                        >
                                            <div class="flex">
                                                <Input
                                                    :id="`${doc.key}_exp`"
                                                    v-model="step3.documents[doc.key].expires_at"
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
                                                        class="shrink-0 rounded-l-none border-0 bg-custom-bg hover:bg-custom-secondary/20 dark:bg-custom-bg-dark border border-custom-bg-dark dark:border-none dark:shadow-sm dark:shadow-white/5"
                                                        aria-label="Choose expiration date"
                                                    >
                                                        <RiCalendarLine class="h-4 w-4" />
                                                    </Button>
                                                </PopoverTrigger>
                                            </div>
                                            <PopoverContent align="start" class="w-auto p-0">
                                                <CalendarPicker
                                                    :model-value="parseCalendarDate(step3.documents[doc.key].expires_at)"
                                                    initial-focus
                                                    @update:model-value="(value) => selectDocumentDate(doc.key, 'expires_at', value as CalendarDate | undefined)"
                                                />
                                            </PopoverContent>
                                        </Popover>
                                        <InputError :message="docError(doc.key, 'expires_at')" />
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <RegistrationStatus
                        v-else-if="currentStep === 4 && company && meta"
                        :company="company"
                        :meta="meta"
                        embedded
                    />
                </div>

                <Separator v-if="currentStep !== 4" class="flex-1 my-2" />

                <div v-if="currentStep !== 4" class="w-full flex flex-row justify-end items-center gap-x-2 pt-2">
                    <!-- TODO: hide this button when the user is on the first step. -->
                    <Button type="button" variant="ghost-outline" size="icon-text" @click="goBack" class="cursor-pointer">
                        <RiArrowLeftSLine class="h-4 w-4 text-custom-shadow"/>
                        <span class="hidden lg:block">Back</span>
                    </Button>

                    <Button
                        v-if="currentStep === 1"
                        type="button"
                        variant="float-primary"
                        size="icon-text"
                        :disabled="step1.processing"
                        @click="submitStep1"
                    >
                        <RiLoaderLine v-if="step1.processing" class="h-4 w-4 animate-spin" />
                        {{ step1.processing ? 'Validating...' : 'Continue' }}
                    </Button>

                    <Button
                        v-else-if="currentStep === 1.5"
                        type="button"
                        variant="float-primary"
                        size="icon-text"
                        :disabled="otpAccount.processing || otpAccount.otp.length < 6"
                        @click="submitAccountOtp"
                    >
                        <RiLoaderLine v-if="otpAccount.processing" class="h-4 w-4 animate-spin" />
                        {{ otpAccount.processing ? 'Verifying...' : 'Verify' }}
                    </Button>

                    <Button
                        v-else-if="currentStep === 2"
                        type="button"
                        variant="float-primary"
                        size="icon-text"
                        :disabled="step2.processing"
                        @click="submitStep2"
                    >
                        <RiLoaderLine v-if="step2.processing" class="h-4 w-4 animate-spin" />
                        {{ step2.processing ? 'Validating...' : 'Continue' }}
                    </Button>

                    <Button
                        v-else-if="currentStep === 2.5"
                        type="button"
                        variant="float-primary"
                        size="icon-text"
                        :disabled="otpCompany.processing || otpCompany.otp.length < 6"
                        @click="submitCompanyOtp"
                    >
                        <RiLoaderLine v-if="otpCompany.processing" class="h-4 w-4 animate-spin" />
                        {{ otpCompany.processing ? 'Verifying...' : 'Verify' }}
                    </Button>

                    <Button
                        v-else-if="currentStep === 3"
                        type="button"
                        variant="float-primary"
                        size="icon-text"
                        :disabled="step3.processing"
                        @click="submitStep3"
                    >
                        <RiLoaderLine v-if="step3.processing" class="h-4 w-4 animate-spin" />
                        {{ step3.processing ? 'Submitting...' : 'Submit' }}
                    </Button>
                </div>
            </div>
        </Card>
    </AuthLayout>
</template>
