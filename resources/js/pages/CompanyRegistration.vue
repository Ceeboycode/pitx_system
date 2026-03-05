<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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

// Wayfinder generated actions
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
    CheckCircle2,
    Loader2,
    Mail,
    RefreshCcw,
    ShieldCheck,
} from 'lucide-vue-next';

/*
|--------------------------------------------------------------------------
| Sub-step model
|
| The visible step bubbles are 1, 2, 3.
| Each step that requires OTP verification has a ".5" sub-step:
|   1   → Account Details form
|   1.5 → Account email OTP entry
|   2   → Company Details form
|   2.5 → Company email OTP entry
|   3   → Documents upload & submit
|--------------------------------------------------------------------------
*/
type SubStep = 1 | 1.5 | 2 | 2.5 | 3;

const currentStep = ref<SubStep>(1);
const totalSteps  = 3;

// Which visible bubble (1/2/3) is active
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

// ─── Account OTP form ─────────────────────────────────────────────────────────
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
});

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

// ─── Company OTP form ─────────────────────────────────────────────────────────
const otpCompany      = useForm({ otp: '' });
const resendCompany   = useForm({});
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

    // No type chosen yet — show all as optional preview
    return [
        { key: 'AUTHORIZATION_LETTER', label: 'Authorization Letter (Corporate)', required: false },
        { key: 'SEC_CERT',             label: 'SEC Certificate (Corporate)',       required: false },
        { key: 'DTI_CERT',             label: 'DTI Certificate (Sole Prop.)',      required: false },
        ...base.map((d) => ({ ...d, required: false })),
    ];
});

// ─── Navigation helpers ───────────────────────────────────────────────────────

/** Step 1 → validate + send account OTP → advance to OTP screen */
function submitStep1() {
    step1.submit(storeStep1(), {
        onSuccess: () => {
            resentAccountMsg.value = '';
            currentStep.value = 1.5;
        },
    });
}

/** Step 1.5 → verify account OTP → advance to Step 2 */
function submitAccountOtp() {
    otpAccount.submit(verifyStep1Otp(), {
        onSuccess: () => {
            currentStep.value = 2;
            otpAccount.reset();
        },
    });
}

/** Step 2 → validate + send company OTP → advance to company OTP screen */
function submitStep2() {
    step2.submit(storeStep2(), {
        onSuccess: () => {
            resentCompanyMsg.value = '';
            currentStep.value = 2.5;
        },
    });
}

/** Step 2.5 → verify company OTP → advance to Step 3 */
function submitCompanyOtp() {
    otpCompany.submit(verifyStep2Otp(), {
        onSuccess: () => {
            currentStep.value = 3;
            otpCompany.reset();
        },
    });
}

/** Step 3 → submit documents, backend redirects to status page */
function submitStep3() {
    step3.submit(storeStep3(), { forceFormData: true });
}

/** Resend account OTP */
function doResendAccount() {
    resentAccountMsg.value = '';
    resendAccount.submit(resendStep1Otp(), {
        onSuccess: () => {
            resentAccountMsg.value = 'A new code was sent to your email.';
            otpAccount.reset();
        },
    });
}

/** Resend company OTP */
function doResendCompany() {
    resentCompanyMsg.value = '';
    resendCompany.submit(resendStep2Otp(), {
        onSuccess: () => {
            resentCompanyMsg.value = 'A new code was sent to your company email.';
            otpCompany.reset();
        },
    });
}

/** Back navigation */
function goBack() {
    const map: Partial<Record<SubStep, SubStep>> = {
        1.5: 1,
        2:   1.5,
        2.5: 2,
        3:   2.5,
    };
    const prev = map[currentStep.value];
    if (prev !== undefined) currentStep.value = prev;
}

// Auto-submit OTP when all 6 digits entered
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

    <div class="flex min-h-screen items-center justify-center bg-muted/40 p-4">
        <div class="w-full max-w-2xl space-y-6">

            <!-- ── Step progress indicator ───────────────────────────────── -->
            <div class="flex items-center">
                <template v-for="(step, idx) in stepMeta" :key="step.number">

                    <!-- Step bubble -->
                    <button
                        type="button"
                        class="flex flex-col items-center gap-1.5"
                        :disabled="step.number > visualStep"
                        @click="step.number < visualStep && (currentStep = (step.number as SubStep))"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full border-2 text-sm font-semibold transition-all"
                            :class="{
                                'border-primary bg-primary text-primary-foreground scale-110': visualStep === step.number,
                                'cursor-pointer border-primary/50 bg-primary/15 text-primary':  step.number < visualStep,
                                'border-border bg-muted text-muted-foreground':                 step.number > visualStep,
                            }"
                        >
                            <!-- Checkmark for completed steps -->
                            <svg v-if="step.number < visualStep" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else>{{ step.number }}</span>
                        </div>
                        <span
                            class="hidden text-xs font-medium sm:block"
                            :class="visualStep === step.number ? 'text-foreground' : 'text-muted-foreground'"
                        >
                            {{ step.title }}
                        </span>
                    </button>

                    <!-- Connector line -->
                    <div
                        v-if="idx < stepMeta.length - 1"
                        class="mx-3 h-px flex-1 transition-colors"
                        :class="step.number < visualStep ? 'bg-primary/40' : 'bg-border'"
                    />
                </template>
            </div>

            <!-- ── Wizard card ────────────────────────────────────────────── -->
            <Card>
                <CardHeader class="pb-0">
                    <div class="flex items-center gap-2">
                        <Badge variant="outline" class="text-xs">
                            Step {{ visualStep }} of {{ totalSteps }}
                        </Badge>
                        <!-- OTP sub-step pill -->
                        <Badge
                            v-if="currentStep === 1.5 || currentStep === 2.5"
                            variant="secondary"
                            class="gap-1 text-xs"
                        >
                            <Mail class="h-3 w-3" />
                            Email Verification
                        </Badge>
                    </div>
                    <CardTitle class="mt-1 text-xl">
                        <template v-if="currentStep === 1.5">Verify Account Email</template>
                        <template v-else-if="currentStep === 2.5">Verify Company Email</template>
                        <template v-else>{{ stepMeta[visualStep - 1].title }}</template>
                    </CardTitle>
                    <CardDescription>
                        <template v-if="currentStep === 1.5">
                            We sent a 6-digit code to <strong>{{ step1.email }}</strong>
                        </template>
                        <template v-else-if="currentStep === 2.5">
                            We sent a 6-digit code to <strong>{{ step2.company_email }}</strong>
                        </template>
                        <template v-else>
                            {{ stepMeta[visualStep - 1].description }}
                        </template>
                    </CardDescription>
                </CardHeader>

                <Separator class="mt-5" />

                <CardContent class="pt-6">

                    <!-- ══════════════════════════════════════════════════════
                         STEP 1 – Account Details
                    ═══════════════════════════════════════════════════════ -->
                    <div v-if="currentStep === 1" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="s1_name">Full Name</Label>
                                <Input id="s1_name" v-model="step1.name" placeholder="Juan dela Cruz" autocomplete="name" />
                                <p v-if="step1.errors.name" class="text-xs text-destructive">{{ step1.errors.name }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="s1_email">Email Address</Label>
                                <Input id="s1_email" type="email" v-model="step1.email" placeholder="juan@example.com" autocomplete="email" />
                                <p v-if="step1.errors.email" class="text-xs text-destructive">{{ step1.errors.email }}</p>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="s1_phone">Mobile / Phone</Label>
                            <Input id="s1_phone" v-model="step1.phone" placeholder="+63 9XX XXX XXXX" autocomplete="tel" />
                            <p v-if="step1.errors.phone" class="text-xs text-destructive">{{ step1.errors.phone }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="s1_pw">Password</Label>
                                <Input id="s1_pw" type="password" v-model="step1.password" placeholder="Min. 8 characters" autocomplete="new-password" />
                                <p v-if="step1.errors.password" class="text-xs text-destructive">{{ step1.errors.password }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="s1_pwc">Confirm Password</Label>
                                <Input id="s1_pwc" type="password" v-model="step1.password_confirmation" placeholder="Repeat password" autocomplete="new-password" />
                                <p v-if="step1.errors.password_confirmation" class="text-xs text-destructive">{{ step1.errors.password_confirmation }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- ══════════════════════════════════════════════════════
                         STEP 1.5 – Account Email OTP
                    ═══════════════════════════════════════════════════════ -->
                    <div v-else-if="currentStep === 1.5" class="space-y-6">

                        <!-- Icon banner -->
                        <div class="flex flex-col items-center gap-3 rounded-xl border bg-muted/40 px-6 py-8 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 ring-4 ring-primary/10">
                                <Mail class="h-7 w-7 text-primary" />
                            </div>
                            <p class="text-sm text-muted-foreground max-w-xs">
                                Enter the 6-digit code we sent to
                                <span class="font-semibold text-foreground">{{ step1.email }}</span>.
                                It expires in 10 minutes.
                            </p>
                        </div>

                        <!-- OTP input -->
                        <div class="space-y-1.5">
                            <Label for="otp_acc" class="sr-only">Verification Code</Label>
                            <Input
                                id="otp_acc"
                                v-model="otpAccount.otp"
                                inputmode="numeric"
                                maxlength="6"
                                placeholder="0  0  0  0  0  0"
                                class="h-14 text-center text-3xl font-mono tracking-[.6em] placeholder:text-muted-foreground/40"
                                :disabled="otpAccount.processing"
                                @input="onOtpInput('account')"
                            />
                            <p v-if="otpAccount.errors.otp" class="text-center text-xs text-destructive">
                                {{ otpAccount.errors.otp }}
                            </p>
                        </div>

                        <!-- Success feedback after resend -->
                        <div v-if="resentAccountMsg" class="flex items-center justify-center gap-1.5 text-xs text-primary">
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            {{ resentAccountMsg }}
                        </div>

                        <!-- Resend link -->
                        <div class="flex items-center justify-center gap-1 text-xs text-muted-foreground">
                            Didn't receive it?
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 font-medium text-primary underline-offset-2 hover:underline disabled:opacity-50"
                                :disabled="resendAccount.processing"
                                @click="doResendAccount"
                            >
                                <RefreshCcw class="h-3 w-3" :class="resendAccount.processing ? 'animate-spin' : ''" />
                                Resend code
                            </button>
                        </div>
                    </div>

                    <!-- ══════════════════════════════════════════════════════
                         STEP 2 – Company Details
                    ═══════════════════════════════════════════════════════ -->
                    <div v-else-if="currentStep === 2" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="s2_cname">Company Name</Label>
                                <Input id="s2_cname" v-model="step2.company_name" placeholder="Acme Corp." />
                                <p v-if="step2.errors.company_name" class="text-xs text-destructive">{{ step2.errors.company_name }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="s2_cemail">Company Email</Label>
                                <Input id="s2_cemail" type="email" v-model="step2.company_email" placeholder="info@acme.com" />
                                <p v-if="step2.errors.company_email" class="text-xs text-destructive">{{ step2.errors.company_email }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="s2_cphone">Company Phone</Label>
                                <Input id="s2_cphone" v-model="step2.company_phone" placeholder="+63 2 XXXX XXXX" />
                                <p v-if="step2.errors.company_phone" class="text-xs text-destructive">{{ step2.errors.company_phone }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="s2_btype">Business Type</Label>
                                <Select v-model="step2.business_type">
                                    <SelectTrigger id="s2_btype"><SelectValue placeholder="Select type…" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="corporate">Corporate</SelectItem>
                                        <SelectItem value="sole_proprietorship">Sole Proprietorship</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="step2.errors.business_type" class="text-xs text-destructive">{{ step2.errors.business_type }}</p>
                            </div>
                        </div>

                        <AddressSelectPH
                            v-model:address="step2.company_address"
                            v-model:codes="addressCodes"
                            label="Company Address"
                            street-label="Street / Building / Unit"
                        />
                        <p v-if="step2.errors.company_address" class="text-xs text-destructive">{{ step2.errors.company_address }}</p>

                        <div v-if="step2.business_type" class="space-y-1.5">
                            <Label for="s2_regno">{{ isCorporate ? 'SEC Registration Number' : 'DTI Registration Number' }}</Label>
                            <Input id="s2_regno" v-model="step2.registration_number" placeholder="Enter registration number" />
                            <p v-if="step2.errors.registration_number" class="text-xs text-destructive">{{ step2.errors.registration_number }}</p>
                        </div>

                        <template v-if="step2.business_type">
                            <Separator />
                            <p class="text-sm font-medium">Authorized Representative</p>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <Label for="s2_rname">Full Name</Label>
                                    <Input id="s2_rname" v-model="step2.authorized_representative_name" placeholder="Representative name" />
                                    <p v-if="step2.errors.authorized_representative_name" class="text-xs text-destructive">{{ step2.errors.authorized_representative_name }}</p>
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="s2_rpos">Position</Label>
                                    <Select v-model="positionChoice">
                                        <SelectTrigger id="s2_rpos"><SelectValue placeholder="Select position…" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="p in positionOptions" :key="p" :value="p">{{ p }}</SelectItem>
                                            <SelectItem value="other">Other (type manually)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Input
                                        v-if="positionChoice === 'other'"
                                        class="mt-2"
                                        v-model="step2.authorized_representative_position"
                                        placeholder="Type position…"
                                    />
                                    <p v-if="step2.errors.authorized_representative_position" class="text-xs text-destructive">{{ step2.errors.authorized_representative_position }}</p>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="s2_rcont">Contact Number</Label>
                                <Input id="s2_rcont" v-model="step2.authorized_representative_contact" placeholder="+63 9XX XXX XXXX" />
                                <p v-if="step2.errors.authorized_representative_contact" class="text-xs text-destructive">{{ step2.errors.authorized_representative_contact }}</p>
                            </div>
                        </template>
                    </div>

                    <!-- ══════════════════════════════════════════════════════
                         STEP 2.5 – Company Email OTP
                    ═══════════════════════════════════════════════════════ -->
                    <div v-else-if="currentStep === 2.5" class="space-y-6">

                        <div class="flex flex-col items-center gap-3 rounded-xl border bg-muted/40 px-6 py-8 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 ring-4 ring-primary/10">
                                <ShieldCheck class="h-7 w-7 text-primary" />
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
                                class="h-14 text-center text-3xl font-mono tracking-[.6em] placeholder:text-muted-foreground/40"
                                :disabled="otpCompany.processing"
                                @input="onOtpInput('company')"
                            />
                            <p v-if="otpCompany.errors.otp" class="text-center text-xs text-destructive">
                                {{ otpCompany.errors.otp }}
                            </p>
                        </div>

                        <div v-if="resentCompanyMsg" class="flex items-center justify-center gap-1.5 text-xs text-primary">
                            <CheckCircle2 class="h-3.5 w-3.5" />
                            {{ resentCompanyMsg }}
                        </div>

                        <div class="flex items-center justify-center gap-1 text-xs text-muted-foreground">
                            Didn't receive it?
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 font-medium text-primary underline-offset-2 hover:underline disabled:opacity-50"
                                :disabled="resendCompany.processing"
                                @click="doResendCompany"
                            >
                                <RefreshCcw class="h-3 w-3" :class="resendCompany.processing ? 'animate-spin' : ''" />
                                Resend code
                            </button>
                        </div>
                    </div>

                    <!-- ══════════════════════════════════════════════════════
                         STEP 3 – Documents
                    ═══════════════════════════════════════════════════════ -->
                    <div v-else-if="currentStep === 3" class="space-y-5">

                        <div
                            v-if="step2.business_type"
                            class="flex items-center gap-2 rounded-md border bg-muted/50 px-3 py-2 text-sm text-muted-foreground"
                        >
                            <svg class="h-4 w-4 shrink-0 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 8v4m0 4h.01"/>
                            </svg>
                            Showing required documents for
                            <Badge variant="secondary" class="text-xs capitalize">
                                {{ step2.business_type.replace('_', ' ') }}
                            </Badge>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="doc in requiredDocs"
                                :key="doc.key"
                                class="space-y-3 rounded-lg border p-4 transition-colors"
                                :class="step3.documents[doc.key].file ? 'border-primary/40 bg-primary/5' : ''"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 shrink-0 text-muted-foreground" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span class="text-sm font-medium">{{ doc.label }}</span>
                                        <Badge v-if="doc.required" variant="destructive" class="px-1.5 py-0 text-[10px]">Required</Badge>
                                        <Badge v-else variant="outline" class="px-1.5 py-0 text-[10px]">Optional</Badge>
                                    </div>
                                    <!-- Tick when file chosen -->
                                    <svg v-if="step3.documents[doc.key].file" class="h-4 w-4 text-primary" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>

                                <div class="space-y-1">
                                    <Input
                                        :id="doc.key"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        class="cursor-pointer text-sm"
                                        @change="handleFile(doc.key, $event)"
                                    />
                                    <p v-if="step3.documents[doc.key].file" class="truncate text-xs text-muted-foreground">
                                        {{ step3.documents[doc.key].file?.name }}
                                    </p>
                                    <p v-if="docError(doc.key, 'file')" class="text-xs text-destructive">{{ docError(doc.key, 'file') }}</p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <Label :for="`${doc.key}_iss`" class="text-xs text-muted-foreground">Issued At</Label>
                                        <Input :id="`${doc.key}_iss`" type="date" v-model="step3.documents[doc.key].issued_at" class="h-8 text-sm" />
                                        <p v-if="docError(doc.key, 'issued_at')" class="text-xs text-destructive">{{ docError(doc.key, 'issued_at') }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <Label :for="`${doc.key}_exp`" class="text-xs text-muted-foreground">Expires At</Label>
                                        <Input :id="`${doc.key}_exp`" type="date" v-model="step3.documents[doc.key].expires_at" class="h-8 text-sm" />
                                        <p v-if="docError(doc.key, 'expires_at')" class="text-xs text-destructive">{{ docError(doc.key, 'expires_at') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </CardContent>

                <!-- ── Footer: Back + primary action ─────────────────────── -->
                <Separator />
                <div class="flex items-center justify-between p-6">

                    <Button
                        type="button"
                        variant="outline"
                        :disabled="currentStep === 1"
                        @click="goBack"
                    >
                        ← Back
                    </Button>

                    <!-- Step 1 → send OTP & advance to 1.5 -->
                    <Button v-if="currentStep === 1" type="button" :disabled="step1.processing" @click="submitStep1">
                        <Loader2 v-if="step1.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ step1.processing ? 'Sending code…' : 'Continue →' }}
                    </Button>

                    <!-- Step 1.5 → verify account OTP -->
                    <Button
                        v-else-if="currentStep === 1.5"
                        type="button"
                        :disabled="otpAccount.processing || otpAccount.otp.length < 6"
                        @click="submitAccountOtp"
                    >
                        <Loader2 v-if="otpAccount.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ otpAccount.processing ? 'Verifying…' : 'Verify & Continue →' }}
                    </Button>

                    <!-- Step 2 → send company OTP & advance to 2.5 -->
                    <Button v-else-if="currentStep === 2" type="button" :disabled="step2.processing" @click="submitStep2">
                        <Loader2 v-if="step2.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ step2.processing ? 'Sending code…' : 'Continue →' }}
                    </Button>

                    <!-- Step 2.5 → verify company OTP -->
                    <Button
                        v-else-if="currentStep === 2.5"
                        type="button"
                        :disabled="otpCompany.processing || otpCompany.otp.length < 6"
                        @click="submitCompanyOtp"
                    >
                        <Loader2 v-if="otpCompany.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ otpCompany.processing ? 'Verifying…' : 'Verify & Continue →' }}
                    </Button>

                    <!-- Step 3 → submit application -->
                    <Button v-else-if="currentStep === 3" type="button" :disabled="step3.processing" @click="submitStep3">
                        <Loader2 v-if="step3.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ step3.processing ? 'Submitting…' : 'Submit Application' }}
                    </Button>

                </div>
            </Card>

        </div>
    </div>
</template>
