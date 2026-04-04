<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3'
import AuthBase from '@/layouts/AuthLayout.vue';

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
import { Separator } from '@/components/ui/separator';

import AddressSelectPH from '@/components/AddressSelectPH.vue';

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
    Building2,
    CheckCircle2,
    FileText,
    ImagePlus,
    Loader2,
    Mail,
    RefreshCcw,
    ShieldCheck,
    X,
} from 'lucide-vue-next';

/*
|--------------------------------------------------------------------------
| Sub-step model
|--------------------------------------------------------------------------
*/
type SubStep = 1 | 1.5 | 2 | 2.5 | 3;

const currentStep = ref<SubStep>(1);
const totalSteps  = 3;

const visualStep = computed((): 1 | 2 | 3 => {
    if (currentStep.value < 2)  return 1;
    if (currentStep.value < 3)  return 2;
    return 3;
});

const stepMeta = [
    { number: 1, title: 'Account',   description: 'Your personal login details' },
    { number: 2, title: 'Company',   description: 'Business information' },
    { number: 3, title: 'Documents', description: 'Upload required files' },
];

// ─── Step 1 form ──────────────────────────────────────────────────────────────
const step1 = useForm({
    name:                  '',
    email:                 '',
    phone:                 '',
    password:              '',
    password_confirmation: '',
});

// ─── Account OTP ──────────────────────────────────────────────────────────────
const otpAccount       = useForm({ otp: '' });
const resendAccount    = useForm({});
const resentAccountMsg = ref('');

// ─── Step 2 form ──────────────────────────────────────────────────────────────
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

    // ── Logo (optional) ──
    logo: null as File | null,
});

// ─── Logo preview ─────────────────────────────────────────────────────────────
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

// ─── Step 2 address / position helpers ───────────────────────────────────────
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

// ─── Company OTP ──────────────────────────────────────────────────────────────
const otpCompany       = useForm({ otp: '' });
const resendCompany    = useForm({});
const resentCompanyMsg = ref('');

// ─── Step 3 form ──────────────────────────────────────────────────────────────
const step3 = useForm({
    documents: {
        AUTHORIZATION_LETTER: { file: null as File | null, issued_at: '', expires_at: '' },
        SEC_CERT:             { file: null as File | null, issued_at: '', expires_at: '' },
        DTI_CERT:             { file: null as File | null, issued_at: '', expires_at: '' },
        MAYORS_PERMIT:        { file: null as File | null, issued_at: '', expires_at: '' },
        BIR_2303:             { file: null as File | null, issued_at: '', expires_at: '' },
    } as Record<string, { file: File | null; issued_at: string; expires_at: string }>,
});

// ─── Derived ──────────────────────────────────────────────────────────────────
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

// ─── Navigation ───────────────────────────────────────────────────────────────
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
        forceFormData: true,   // required for file upload
        onSuccess: () => { resentCompanyMsg.value = ''; currentStep.value = 2.5; },
    });
}

function submitCompanyOtp() {
    otpCompany.submit(verifyStep2Otp(), {
        onSuccess: () => { currentStep.value = 3; otpCompany.reset(); },
    });
}

function submitStep3() {
    step3.submit(storeStep3(), { forceFormData: true });
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
        onSuccess: () => { resentCompanyMsg.value = 'A new code was sent to your company email.'; otpCompany.reset(); },
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
</script>

<template>
    
    <Head title="Company Registration" />

    <!-- Subtle blue-tinted background, close to original bg-muted/40 -->
    <div class="flex min-h-screen items-center justify-center bg-blue-50/60 p-4">
        <div class="w-full max-w-2xl space-y-6">

            <!-- ── Step progress indicator ───────────────────────────────── -->
            <div class="flex items-center">
                <template v-for="(step, idx) in stepMeta" :key="step.number">
                    <button
                        type="button"
                        class="flex flex-col items-center gap-1.5"
                        :disabled="step.number > visualStep"
                        @click="step.number < visualStep && (currentStep = (step.number as SubStep))"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full border-2 text-sm font-semibold transition-all"
                            :class="{
                                'border-blue-800 bg-blue-800 text-white scale-110 shadow-[0_0_0_3px_rgba(27,63,122,0.20)]':
                                    visualStep === step.number,
                                'cursor-pointer border-green-600 bg-green-600 text-white':
                                    step.number < visualStep,
                                'border-border bg-muted text-muted-foreground':
                                    step.number > visualStep,
                            }"
                        >
                            <svg v-if="step.number < visualStep" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else>{{ step.number }}</span>
                        </div>
                        <span
                            class="hidden text-xs font-medium sm:block"
                            :class="{
                                'text-foreground':      visualStep === step.number,
                                'text-green-600':       step.number < visualStep,
                                'text-muted-foreground': step.number > visualStep,
                            }"
                        >
                            {{ step.title }}
                        </span>
                    </button>
                    <div
                        v-if="idx < stepMeta.length - 1"
                        class="mx-3 h-px flex-1 transition-colors"
                        :class="step.number < visualStep ? 'bg-green-500/50' : 'bg-border'"
                    />
                </template>
            </div>

            <!-- ── Wizard card ────────────────────────────────────────────── -->
            <Card class="overflow-hidden">
                <CardHeader
                    class="relative -mt-6 overflow-hidden rounded-none bg-[#1B3F7A] pb-5 pt-6"
                    
                >
                    <!-- Subtle decorative orb -->
                    <div class="pointer-events-none absolute -right-8 -top-8 h-36 w-36 rounded-full bg-red-600 opacity-[0.15]" />

                    <div class="relative z-10 flex items-center gap-2">
                        <Badge class="border-white/20 bg-white/10 text-white/70 text-xs">
                            Step {{ visualStep }} of {{ totalSteps }}
                        </Badge>
                        <Badge
                            v-if="currentStep === 1.5 || currentStep === 2.5"
                            class="gap-1 text-xs border-red-400/30 bg-red-600/20 text-red-200"
                        >
                            <Mail class="h-3 w-3" />
                            Email Verification
                        </Badge>
                    </div>

                    <CardTitle class="relative z-10 mt-1 text-xl text-white">
                        <template v-if="currentStep === 1.5">Verify Account Email</template>
                        <template v-else-if="currentStep === 2.5">Verify Company Email</template>
                        <template v-else>{{ stepMeta[visualStep - 1].title }}</template>
                    </CardTitle>

                    <CardDescription class="relative z-10 text-white/60">
                        <template v-if="currentStep === 1.5">
                            We sent a 6-digit code to <strong class="font-semibold text-white/90">{{ step1.email }}</strong>
                        </template>
                        <template v-else-if="currentStep === 2.5">
                            We sent a 6-digit code to <strong class="font-semibold text-white/90">{{ step2.company_email }}</strong>
                        </template>
                        <template v-else>
                            {{ stepMeta[visualStep - 1].description }}
                        </template>
                    </CardDescription>
                </CardHeader>


                <CardContent class="pt-6">

                    <!-- ══ STEP 1 – Account Details ══════════════════════════ -->
                    <div v-if="currentStep === 1" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="s1_name">Full Name</Label>
                                <Input id="s1_name" v-model="step1.name" placeholder="Juan dela Cruz" autocomplete="name"
                                       class="focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                                <p v-if="step1.errors.name" class="text-xs text-red-600">{{ step1.errors.name }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="s1_email">Email Address</Label>
                                <Input id="s1_email" type="email" v-model="step1.email" placeholder="juan@example.com" autocomplete="email"
                                       class="focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                                <p v-if="step1.errors.email" class="text-xs text-red-600">{{ step1.errors.email }}</p>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="s1_phone">Mobile / Phone</Label>
                            <Input id="s1_phone" v-model="step1.phone" placeholder="+63 9XX XXX XXXX" autocomplete="tel"
                                   class="focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                            <p v-if="step1.errors.phone" class="text-xs text-red-600">{{ step1.errors.phone }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="s1_pw">Password</Label>
                                <Input id="s1_pw" type="password" v-model="step1.password" placeholder="Min. 8 characters" autocomplete="new-password"
                                       class="focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                                <p v-if="step1.errors.password" class="text-xs text-red-600">{{ step1.errors.password }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="s1_pwc">Confirm Password</Label>
                                <Input id="s1_pwc" type="password" v-model="step1.password_confirmation" placeholder="Repeat password" autocomplete="new-password"
                                       class="focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                                <p v-if="step1.errors.password_confirmation" class="text-xs text-red-600">{{ step1.errors.password_confirmation }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- ══ STEP 1.5 – Account Email OTP ══════════════════════ -->
                    <div v-else-if="currentStep === 1.5" class="space-y-6">
                        <div class="flex flex-col items-center gap-3 rounded-xl border bg-blue-50 px-6 py-8 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-800/10 ring-4 ring-blue-800/10">
                                <Mail class="h-7 w-7 text-blue-800" />
                            </div>
                            <p class="text-sm text-muted-foreground max-w-xs">
                                Enter the 6-digit code we sent to
                                <span class="font-semibold text-foreground">{{ step1.email }}</span>.
                                It expires in 10 minutes.
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="otp_acc" class="sr-only">Verification Code</Label>
                            <Input
                                id="otp_acc"
                                v-model="otpAccount.otp"
                                inputmode="numeric"
                                maxlength="6"
                                placeholder="0  0  0  0  0  0"
                                class="h-14 text-center text-3xl font-mono tracking-[.6em] placeholder:text-muted-foreground/40 focus-visible:ring-blue-800 focus-visible:border-blue-800"
                                :disabled="otpAccount.processing"
                                @input="onOtpInput('account')"
                            />
                            <p v-if="otpAccount.errors.otp" class="text-center text-xs text-red-600">{{ otpAccount.errors.otp }}</p>
                        </div>

                        <div v-if="resentAccountMsg" class="flex items-center justify-center gap-1.5 text-xs text-blue-800 font-medium">
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            {{ resentAccountMsg }}
                        </div>

                        <div class="flex items-center justify-center gap-1 text-xs text-muted-foreground">
                            Didn't receive it?
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 font-medium text-red-600 underline-offset-2 hover:underline disabled:opacity-50"
                                :disabled="resendAccount.processing"
                                @click="doResendAccount"
                            >
                                <RefreshCcw class="h-3 w-3" :class="resendAccount.processing ? 'animate-spin' : ''" />
                                Resend code
                            </button>
                        </div>
                    </div>

                    <!-- ══ STEP 2 – Company Details ══════════════════════════ -->
                    <div v-else-if="currentStep === 2" class="space-y-4">

                        <!-- ── Company Logo upload ── -->
                        <div class="space-y-2">
                            <Label>
                                Company Logo
                                <span class="ml-1 font-normal text-muted-foreground">(optional)</span>
                            </Label>

                            <div class="flex items-center gap-4">
                                <!-- Preview box -->
                                <div
                                    class="relative h-20 w-20 shrink-0 overflow-hidden rounded-xl border-2 bg-muted transition-colors"
                                    :class="logoPreview ? 'border-blue-800/40' : 'border-dashed border-muted-foreground/30'"
                                >
                                    <img
                                        v-if="logoPreview"
                                        :src="logoPreview"
                                        alt="Logo preview"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full flex-col items-center justify-center gap-1 text-muted-foreground"
                                    >
                                        <Building2 class="h-6 w-6" />
                                        <span class="text-[10px]">No logo</span>
                                    </div>

                                    <!-- Remove button -->
                                    <button
                                        v-if="logoPreview"
                                        type="button"
                                        @click="removeLogo"
                                        class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-white shadow hover:bg-red-700 transition-colors"
                                    >
                                        <X class="h-3 w-3" />
                                    </button>
                                </div>

                                <!-- Upload trigger -->
                                <div class="space-y-1.5">
                                    <label
                                        for="logo-upload"
                                        class="inline-flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-muted"
                                    >
                                        <ImagePlus class="h-4 w-4" />
                                        {{ logoPreview ? 'Change logo' : 'Upload logo' }}
                                        <input
                                            id="logo-upload"
                                            ref="logoInputRef"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            class="sr-only"
                                            @change="handleLogoChange"
                                        />
                                    </label>
                                    <p class="text-xs text-muted-foreground leading-relaxed">
                                        JPG, PNG or WebP · max 2 MB<br />
                                        Recommended: square, 200 × 200 px+
                                    </p>
                                </div>
                            </div>

                            <p v-if="step2.errors.logo" class="text-xs text-red-600">
                                {{ step2.errors.logo }}
                            </p>
                        </div>

                        <Separator />

                        <!-- ── Company core fields ── -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="s2_cname">Company Name</Label>
                                <Input id="s2_cname" v-model="step2.company_name" placeholder="Acme Corp."
                                       class="focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                                <p v-if="step2.errors.company_name" class="text-xs text-red-600">{{ step2.errors.company_name }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="s2_cemail">Company Email</Label>
                                <Input id="s2_cemail" type="email" v-model="step2.company_email" placeholder="info@acme.com"
                                       class="focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                                <p v-if="step2.errors.company_email" class="text-xs text-red-600">{{ step2.errors.company_email }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="s2_cphone">Company Phone</Label>
                                <Input id="s2_cphone" v-model="step2.company_phone" placeholder="+63 2 XXXX XXXX"
                                       class="focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                                <p v-if="step2.errors.company_phone" class="text-xs text-red-600">{{ step2.errors.company_phone }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="s2_btype">Business Type</Label>
                                <Select v-model="step2.business_type">
                                    <SelectTrigger id="s2_btype" class="focus:ring-blue-800 focus:border-blue-800">
                                        <SelectValue placeholder="Select type…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="corporate">Corporate</SelectItem>
                                        <SelectItem value="sole_proprietorship">Sole Proprietorship</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="step2.errors.business_type" class="text-xs text-red-600">{{ step2.errors.business_type }}</p>
                            </div>
                        </div>

                        <AddressSelectPH
                            v-model:address="step2.company_address"
                            v-model:codes="addressCodes"
                            label="Company Address"
                            street-label="Street / Building / Unit"
                        />
                        <p v-if="step2.errors.company_address" class="text-xs text-red-600">{{ step2.errors.company_address }}</p>

                        <div v-if="step2.business_type" class="space-y-1.5">
                            <Label for="s2_regno">
                                {{ isCorporate ? 'SEC Registration Number' : 'DTI Registration Number' }}
                            </Label>
                            <Input id="s2_regno" v-model="step2.registration_number" placeholder="Enter registration number"
                                   class="focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                            <p v-if="step2.errors.registration_number" class="text-xs text-red-600">{{ step2.errors.registration_number }}</p>
                        </div>

                        <template v-if="step2.business_type">
                            <Separator />
                            <p class="text-sm font-medium">Authorized Representative</p>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <Label for="s2_rname">Full Name</Label>
                                    <Input id="s2_rname" v-model="step2.authorized_representative_name" placeholder="Representative name"
                                           class="focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                                    <p v-if="step2.errors.authorized_representative_name" class="text-xs text-red-600">{{ step2.errors.authorized_representative_name }}</p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="s2_rpos">Position</Label>
                                    <Select v-model="positionChoice">
                                        <SelectTrigger id="s2_rpos" class="focus:ring-blue-800 focus:border-blue-800">
                                            <SelectValue placeholder="Select position…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="p in positionOptions" :key="p" :value="p">{{ p }}</SelectItem>
                                            <SelectItem value="other">Other (type manually)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Input
                                        v-if="positionChoice === 'other'"
                                        class="mt-2 focus-visible:ring-blue-800 focus-visible:border-blue-800"
                                        v-model="step2.authorized_representative_position"
                                        placeholder="Type position…"
                                    />
                                    <p v-if="step2.errors.authorized_representative_position" class="text-xs text-red-600">{{ step2.errors.authorized_representative_position }}</p>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="s2_rcont">Contact Number</Label>
                                <Input id="s2_rcont" v-model="step2.authorized_representative_contact" placeholder="+63 9XX XXX XXXX"
                                       class="focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                                <p v-if="step2.errors.authorized_representative_contact" class="text-xs text-red-600">{{ step2.errors.authorized_representative_contact }}</p>
                            </div>
                        </template>
                    </div>

                    <!-- ══ STEP 2.5 – Company Email OTP ══════════════════════ -->
                    <div v-else-if="currentStep === 2.5" class="space-y-6">
                        <div class="flex flex-col items-center gap-3 rounded-xl border bg-blue-50 px-6 py-8 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-800/10 ring-4 ring-blue-800/10">
                                <ShieldCheck class="h-7 w-7 text-blue-800" />
                            </div>
                            <p class="max-w-xs text-sm text-muted-foreground">
                                Enter the 6-digit code sent to
                                <span class="font-semibold text-foreground">{{ step2.company_email }}</span>.
                                It expires in 10 minutes.
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="otp_comp" class="sr-only">Verification Code</Label>
                            <Input
                                id="otp_comp"
                                v-model="otpCompany.otp"
                                inputmode="numeric"
                                maxlength="6"
                                placeholder="0  0  0  0  0  0"
                                class="h-14 text-center text-3xl font-mono tracking-[.6em] placeholder:text-muted-foreground/40 focus-visible:ring-blue-800 focus-visible:border-blue-800"
                                :disabled="otpCompany.processing"
                                @input="onOtpInput('company')"
                            />
                            <p v-if="otpCompany.errors.otp" class="text-center text-xs text-red-600">{{ otpCompany.errors.otp }}</p>
                        </div>

                        <div v-if="resentCompanyMsg" class="flex items-center justify-center gap-1.5 text-xs text-blue-800 font-medium">
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            {{ resentCompanyMsg }}
                        </div>

                        <div class="flex items-center justify-center gap-1 text-xs text-muted-foreground">
                            Didn't receive it?
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 font-medium text-red-600 underline-offset-2 hover:underline disabled:opacity-50"
                                :disabled="resendCompany.processing"
                                @click="doResendCompany"
                            >
                                <RefreshCcw class="h-3 w-3" :class="resendCompany.processing ? 'animate-spin' : ''" />
                                Resend code
                            </button>
                        </div>
                    </div>

                    <!-- ══ STEP 3 – Documents ════════════════════════════════ -->
                    <div v-else-if="currentStep === 3" class="space-y-5">
                        <div
                            v-if="step2.business_type"
                            class="flex items-center gap-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-900"
                        >
                            <svg class="h-4 w-4 shrink-0 text-blue-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/>
                            </svg>
                            Showing required documents for
                            <Badge class="bg-blue-800 text-white text-xs capitalize border-0">
                                {{ step2.business_type.replace('_', ' ') }}
                            </Badge>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="doc in requiredDocs"
                                :key="doc.key"
                                class="space-y-3 rounded-lg border-2 p-4 transition-colors"
                                :class="step3.documents[doc.key].file
                                    ? 'border-blue-800/30 bg-blue-50/60'
                                    : 'border-border'"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <FileText
                                            class="h-4 w-4 shrink-0 transition-colors"
                                            :class="step3.documents[doc.key].file ? 'text-blue-800' : 'text-muted-foreground'"
                                        />
                                        <span class="text-sm font-medium">{{ doc.label }}</span>
                                        <Badge
                                            v-if="doc.required"
                                            class="px-1.5 py-0 text-[10px] bg-red-50 text-red-600 border-red-200"
                                        >
                                            Required
                                        </Badge>
                                        <Badge v-else variant="outline" class="px-1.5 py-0 text-[10px]">Optional</Badge>
                                    </div>
                                    <!-- Uploaded check -->
                                    <div
                                        v-if="step3.documents[doc.key].file"
                                        class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-800"
                                    >
                                        <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <Input
                                        :id="doc.key"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        class="cursor-pointer text-sm"
                                        @change="handleFile(doc.key, $event)"
                                    />
                                    <p v-if="step3.documents[doc.key].file" class="truncate text-xs text-blue-800 font-medium">
                                        ✓ {{ step3.documents[doc.key].file?.name }}
                                    </p>
                                    <p v-if="docError(doc.key, 'file')" class="text-xs text-red-600">{{ docError(doc.key, 'file') }}</p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <Label :for="`${doc.key}_iss`" class="text-xs text-muted-foreground">Issued At</Label>
                                        <Input :id="`${doc.key}_iss`" type="date" v-model="step3.documents[doc.key].issued_at"
                                               class="h-8 text-sm focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                                        <p v-if="docError(doc.key, 'issued_at')" class="text-xs text-red-600">{{ docError(doc.key, 'issued_at') }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <Label :for="`${doc.key}_exp`" class="text-xs text-muted-foreground">Expires At</Label>
                                        <Input :id="`${doc.key}_exp`" type="date" v-model="step3.documents[doc.key].expires_at"
                                               class="h-8 text-sm focus-visible:ring-blue-800 focus-visible:border-blue-800" />
                                        <p v-if="docError(doc.key, 'expires_at')" class="text-xs text-red-600">{{ docError(doc.key, 'expires_at') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </CardContent>

                <!-- ── Footer ─────────────────────────────────────────────── -->
                <Separator />
                <div class="flex items-center justify-between p-6">
                    <Button type="button" variant="outline" @click="goBack" class="cursor-pointer">
                        Back
                    </Button>

                    <Button
                        v-if="currentStep === 1"
                        type="button"
                        class="cursor-pointer bg-blue-600 text-white hover:bg-blue-700 border-0 shadow-sm"
                        :disabled="step1.processing"
                        @click="submitStep1"
                    >
                        <Loader2 v-if="step1.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ step1.processing ? 'Sending code…' : 'Continue' }}
                    </Button>

                    <Button
                        v-else-if="currentStep === 1.5"
                        type="button"
                        class="cursor-pointer bg-blue-600 text-white hover:bg-blue-700 border-0 shadow-sm"
                        :disabled="otpAccount.processing || otpAccount.otp.length < 6"
                        @click="submitAccountOtp"
                    >
                        <Loader2 v-if="otpAccount.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ otpAccount.processing ? 'Verifying…' : 'Verify & Continue' }}
                    </Button>

                    <Button
                        v-else-if="currentStep === 2"
                        type="button"
                        class="cursor-pointer bg-blue-600 text-white hover:bg-blue-700 border-0 shadow-sm"
                        :disabled="step2.processing"
                        @click="submitStep2"
                    >
                        <Loader2 v-if="step2.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ step2.processing ? 'Sending code…' : 'Continue' }}
                    </Button>

                    <Button
                        v-else-if="currentStep === 2.5"
                        type="button"
                        class="cursor-pointer bg-blue-600 text-white hover:bg-blue-700 border-0 shadow-sm"
                        :disabled="otpCompany.processing || otpCompany.otp.length < 6"
                        @click="submitCompanyOtp"
                    >
                        <Loader2 v-if="otpCompany.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ otpCompany.processing ? 'Verifying…' : 'Verify & Continue' }}
                    </Button>

                    <Button
                        v-else-if="currentStep === 3"
                        type="button"
                        class="cursor-pointer bg-blue-600 text-white hover:bg-blue-700 border-0 shadow-sm"
                        :disabled="step3.processing"
                        @click="submitStep3"
                    >
                        <Loader2 v-if="step3.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ step3.processing ? 'Submitting…' : 'Submit Application' }}
                    </Button>
                </div>
            </Card>

        </div>
    </div>
</template>