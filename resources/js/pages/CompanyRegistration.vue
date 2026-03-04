<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// shadcn-vue
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

type Step = 1 | 2 | 3;
const step = ref<Step>(1);

const form = useForm({
    // Step 1
    account_name: '',
    account_email: '',
    account_phone: '',

    // Step 2
    company_name: '',
    company_email: '',
    company_phone: '',
    company_address: '',
    business_type: '' as '' | 'corporate' | 'sole_proprietorship',
    registration_number: '',
    authorized_representative_name: '',
    authorized_representative_position: '',
    authorized_representative_contact: '',

    // Step 3
    AUTHORIZATION_LETTER: null as File | null,
    SEC_CERT: null as File | null,
    DTI_CERT: null as File | null,
    MAYORS_PERMIT: null as File | null,
    BIR_2303: null as File | null,
    issued_at: '',
    expires_at: '',
});

const progress = computed(() =>
    step.value === 1 ? 33 : step.value === 2 ? 66 : 100,
);

const isCorporate = computed(() => form.business_type === 'corporate');
const isSole = computed(() => form.business_type === 'sole_proprietorship');

const secRequired = computed(() => isCorporate.value);
const dtiRequired = computed(() => isSole.value);

const stepTitle = computed(() => {
    if (step.value === 1) return 'Account Information';
    if (step.value === 2) return 'Company Details';
    return 'Document Upload';
});

const stepDesc = computed(() => {
    if (step.value === 1) return 'Tell us who is submitting this registration.';
    if (step.value === 2)
        return 'Enter your company profile and authorized representative.';
    return 'Upload required documents. File types: PDF/JPG/PNG.';
});

const step1Valid = computed(
    () => !!form.account_name.trim() && !!form.account_email.trim(),
);

const step2Valid = computed(() => {
    return (
        !!form.company_name.trim() &&
        !!form.company_email.trim() &&
        !!form.company_phone.trim() &&
        !!form.company_address.trim() &&
        !!form.business_type &&
        !!form.registration_number.trim() &&
        !!form.authorized_representative_name.trim() &&
        !!form.authorized_representative_position.trim() &&
        !!form.authorized_representative_contact.trim()
    );
});

const step3Valid = computed(() => {
    const base =
        !!form.AUTHORIZATION_LETTER &&
        !!form.MAYORS_PERMIT &&
        !!form.BIR_2303 &&
        !!form.issued_at &&
        !!form.expires_at;

    const typeDoc =
        (secRequired.value ? !!form.SEC_CERT : true) &&
        (dtiRequired.value ? !!form.DTI_CERT : true);

    return base && typeDoc;
});

function next() {
    if (step.value === 1 && !step1Valid.value) return;
    if (step.value === 2 && !step2Valid.value) return;
    if (step.value < 3) step.value = (step.value + 1) as Step;
}

function back() {
    if (step.value > 1) step.value = (step.value - 1) as Step;
}

function setFile(field: keyof typeof form.data, e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    // @ts-expect-error inertia typing
    form[field] = file;
}

function clearFile(field: keyof typeof form.data) {
    // @ts-expect-error inertia typing
    form[field] = null;
}

function humanSize(bytes: number) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let i = 0;
    while (size >= 1024 && i < units.length - 1) {
        size /= 1024;
        i++;
    }
    return `${size.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
}

function docBadge(isRequired: boolean, file: File | null) {
    if (file) return { text: 'Selected', variant: 'secondary' as const };
    if (isRequired)
        return { text: 'Required', variant: 'destructive' as const };
    return { text: 'Optional', variant: 'outline' as const };
}

function submitToConsole() {
    const payload = form.data();
    const files: Record<string, any> = {};
    const fields: Record<string, any> = {};

    for (const [key, value] of Object.entries(payload)) {
        if (value instanceof File) {
            files[key] = {
                name: value.name,
                size: value.size,
                type: value.type,
            };
        } else {
            fields[key] = value;
        }
    }

    console.group('✅ Company Registration (Console Only)');
    console.log('Fields:', fields);
    console.log('Files:', files);
    console.log('Raw payload:', payload);
    console.groupEnd();
}
</script>

<template>
    <Head title="Company Registration" />

    <div class="min-h-screen bg-background">
        <div class="mx-auto w-full max-w-5xl space-y-4 p-4">
            <Card>
                <CardHeader class="space-y-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <CardTitle>Company Registration</CardTitle>
                            <CardDescription
                                >{{ stepTitle }} • Step {{ step }} of
                                3</CardDescription
                            >
                            <p class="text-sm text-muted-foreground">
                                {{ stepDesc }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <Badge variant="secondary">{{ stepTitle }}</Badge>
                            <Badge variant="outline">{{ progress }}%</Badge>
                        </div>
                    </div>

                    <Progress :model-value="progress" />
                </CardHeader>

                <Separator />

                <CardContent class="space-y-6 pt-6">
                    <Alert
                        v-if="Object.keys(form.errors).length"
                        variant="destructive"
                    >
                        <AlertTitle>There are validation errors</AlertTitle>
                        <AlertDescription
                            >Fix the fields marked in red on this
                            step.</AlertDescription
                        >
                    </Alert>

                    <!-- STEP 1 -->
                    <div v-if="step === 1" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="account_name"
                                    >Full Name
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    id="account_name"
                                    v-model="form.account_name"
                                    placeholder="e.g., Juan Dela Cruz"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Name of the person submitting this
                                    registration.
                                </p>
                                <p
                                    v-if="form.errors.account_name"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.account_name }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="account_email"
                                    >Email
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    id="account_email"
                                    v-model="form.account_email"
                                    type="email"
                                    placeholder="e.g., juan@email.com"
                                />
                                <p class="text-xs text-muted-foreground">
                                    We’ll use this to contact you about
                                    verification.
                                </p>
                                <p
                                    v-if="form.errors.account_email"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.account_email }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="account_phone">Phone (optional)</Label>
                            <Input
                                id="account_phone"
                                v-model="form.account_phone"
                                placeholder="e.g., 09xxxxxxxxx"
                            />
                            <p
                                v-if="form.errors.account_phone"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.account_phone }}
                            </p>
                        </div>
                    </div>

                    <!-- STEP 2 -->
                    <div v-else-if="step === 2" class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="company_name"
                                >Company Name
                                <span class="text-destructive">*</span></Label
                            >
                            <Input
                                id="company_name"
                                v-model="form.company_name"
                                placeholder="e.g., ABC Transport Services"
                            />
                            <p
                                v-if="form.errors.company_name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.company_name }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="company_email"
                                    >Company Email
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    id="company_email"
                                    v-model="form.company_email"
                                    type="email"
                                    placeholder="e.g., contact@company.com"
                                />
                                <p
                                    v-if="form.errors.company_email"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.company_email }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="company_phone"
                                    >Company Phone
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    id="company_phone"
                                    v-model="form.company_phone"
                                    placeholder="e.g., (046) xxx-xxxx / 09xxxxxxxxx"
                                />
                                <p
                                    v-if="form.errors.company_phone"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.company_phone }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="company_address"
                                >Company Address
                                <span class="text-destructive">*</span></Label
                            >
                            <Textarea
                                id="company_address"
                                v-model="form.company_address"
                                rows="3"
                                placeholder="Complete address..."
                            />
                            <p
                                v-if="form.errors.company_address"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.company_address }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label
                                    >Business Type
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Select v-model="form.business_type">
                                    <SelectTrigger>
                                        <SelectValue
                                            placeholder="Select business type"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="sole_proprietorship"
                                            >Sole Proprietorship
                                            (DTI)</SelectItem
                                        >
                                        <SelectItem value="corporate"
                                            >Corporate (SEC)</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        form.business_type
                                            ? isCorporate
                                                ? 'Corporate requires SEC Certificate.'
                                                : 'Sole proprietorship requires DTI Certificate.'
                                            : 'Choose based on your registration.'
                                    }}
                                </p>
                                <p
                                    v-if="form.errors.business_type"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.business_type }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="registration_number"
                                    >Registration Number
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    id="registration_number"
                                    v-model="form.registration_number"
                                    placeholder="DTI/SEC registration number"
                                />
                                <p
                                    v-if="form.errors.registration_number"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.registration_number }}
                                </p>
                            </div>
                        </div>

                        <Separator />

                        <div class="space-y-4">
                            <div class="font-medium">
                                Authorized Representative
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="ar_name"
                                        >Name
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <Input
                                        id="ar_name"
                                        v-model="
                                            form.authorized_representative_name
                                        "
                                        placeholder="Full name"
                                    />
                                    <p
                                        v-if="
                                            form.errors
                                                .authorized_representative_name
                                        "
                                        class="text-sm text-destructive"
                                    >
                                        {{
                                            form.errors
                                                .authorized_representative_name
                                        }}
                                    </p>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="ar_position"
                                        >Position
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <Input
                                        id="ar_position"
                                        v-model="
                                            form.authorized_representative_position
                                        "
                                        placeholder="e.g., General Manager"
                                    />
                                    <p
                                        v-if="
                                            form.errors
                                                .authorized_representative_position
                                        "
                                        class="text-sm text-destructive"
                                    >
                                        {{
                                            form.errors
                                                .authorized_representative_position
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="ar_contact"
                                    >Contact
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    id="ar_contact"
                                    v-model="
                                        form.authorized_representative_contact
                                    "
                                    placeholder="Phone or email"
                                />
                                <p
                                    v-if="
                                        form.errors
                                            .authorized_representative_contact
                                    "
                                    class="text-sm text-destructive"
                                >
                                    {{
                                        form.errors
                                            .authorized_representative_contact
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3 -->
                    <div v-else class="space-y-6">
                        <div class="space-y-2 rounded-lg border p-4">
                            <div class="font-medium">Review Summary</div>
                            <div class="text-sm text-muted-foreground">
                                <div>
                                    <span class="font-medium text-foreground"
                                        >Company:</span
                                    >
                                    {{ form.company_name || '—' }}
                                </div>
                                <div>
                                    <span class="font-medium text-foreground"
                                        >Business Type:</span
                                    >
                                    {{ form.business_type || '—' }}
                                </div>
                                <div>
                                    <span class="font-medium text-foreground"
                                        >Registration #:</span
                                    >
                                    {{ form.registration_number || '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="issued_at"
                                    >Issued At
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    id="issued_at"
                                    v-model="form.issued_at"
                                    type="date"
                                />
                                <p
                                    v-if="form.errors.issued_at"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.issued_at }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="expires_at"
                                    >Expires At
                                    <span class="text-destructive"
                                        >*</span
                                    ></Label
                                >
                                <Input
                                    id="expires_at"
                                    v-model="form.expires_at"
                                    type="date"
                                />
                                <p
                                    v-if="form.errors.expires_at"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.expires_at }}
                                </p>
                            </div>
                        </div>

                        <Separator />

                        <div class="space-y-4">
                            <div>
                                <div class="font-medium">Upload Documents</div>
                                <div class="text-sm text-muted-foreground">
                                    Allowed: PDF/JPG/PNG.
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <Label
                                            >Authorization Letter
                                            <span class="text-destructive"
                                                >*</span
                                            ></Label
                                        >
                                        <Badge
                                            :variant="
                                                docBadge(
                                                    true,
                                                    form.AUTHORIZATION_LETTER,
                                                ).variant
                                            "
                                            >{{
                                                docBadge(
                                                    true,
                                                    form.AUTHORIZATION_LETTER,
                                                ).text
                                            }}</Badge
                                        >
                                    </div>
                                    <Input
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        @change="
                                            (e) =>
                                                setFile(
                                                    'AUTHORIZATION_LETTER',
                                                    e,
                                                )
                                        "
                                    />
                                </div>

                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <Label
                                            >Mayor’s Permit
                                            <span class="text-destructive"
                                                >*</span
                                            ></Label
                                        >
                                        <Badge
                                            :variant="
                                                docBadge(
                                                    true,
                                                    form.MAYORS_PERMIT,
                                                ).variant
                                            "
                                            >{{
                                                docBadge(
                                                    true,
                                                    form.MAYORS_PERMIT,
                                                ).text
                                            }}</Badge
                                        >
                                    </div>
                                    <Input
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        @change="
                                            (e) => setFile('MAYORS_PERMIT', e)
                                        "
                                    />
                                </div>

                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <Label
                                            >BIR 2303
                                            <span class="text-destructive"
                                                >*</span
                                            ></Label
                                        >
                                        <Badge
                                            :variant="
                                                docBadge(true, form.BIR_2303)
                                                    .variant
                                            "
                                            >{{
                                                docBadge(true, form.BIR_2303)
                                                    .text
                                            }}</Badge
                                        >
                                    </div>
                                    <Input
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        @change="(e) => setFile('BIR_2303', e)"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <Label>
                                            SEC Certificate
                                            <span
                                                v-if="secRequired"
                                                class="text-destructive"
                                                >*</span
                                            >
                                            <span
                                                v-else
                                                class="text-muted-foreground"
                                                >(optional)</span
                                            >
                                        </Label>
                                        <Badge
                                            :variant="
                                                docBadge(
                                                    secRequired,
                                                    form.SEC_CERT,
                                                ).variant
                                            "
                                            >{{
                                                docBadge(
                                                    secRequired,
                                                    form.SEC_CERT,
                                                ).text
                                            }}</Badge
                                        >
                                    </div>
                                    <Input
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        @change="(e) => setFile('SEC_CERT', e)"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <Label>
                                            DTI Certificate
                                            <span
                                                v-if="dtiRequired"
                                                class="text-destructive"
                                                >*</span
                                            >
                                            <span
                                                v-else
                                                class="text-muted-foreground"
                                                >(optional)</span
                                            >
                                        </Label>
                                        <Badge
                                            :variant="
                                                docBadge(
                                                    dtiRequired,
                                                    form.DTI_CERT,
                                                ).variant
                                            "
                                            >{{
                                                docBadge(
                                                    dtiRequired,
                                                    form.DTI_CERT,
                                                ).text
                                            }}</Badge
                                        >
                                    </div>
                                    <Input
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        @change="(e) => setFile('DTI_CERT', e)"
                                    />
                                </div>
                            </div>

                            <div class="rounded-lg border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Document</TableHead>
                                            <TableHead>File</TableHead>
                                            <TableHead
                                                class="w-[120px] text-right"
                                                >Action</TableHead
                                            >
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow>
                                            <TableCell class="font-medium"
                                                >Authorization Letter</TableCell
                                            >
                                            <TableCell>
                                                <span
                                                    v-if="
                                                        form.AUTHORIZATION_LETTER
                                                    "
                                                    >{{
                                                        form
                                                            .AUTHORIZATION_LETTER
                                                            .name
                                                    }}
                                                    •
                                                    {{
                                                        humanSize(
                                                            form
                                                                .AUTHORIZATION_LETTER
                                                                .size,
                                                        )
                                                    }}</span
                                                >
                                                <span
                                                    v-else
                                                    class="text-muted-foreground"
                                                    >No file selected</span
                                                >
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Button
                                                    v-if="
                                                        form.AUTHORIZATION_LETTER
                                                    "
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        clearFile(
                                                            'AUTHORIZATION_LETTER',
                                                        )
                                                    "
                                                    >Remove</Button
                                                >
                                            </TableCell>
                                        </TableRow>

                                        <TableRow>
                                            <TableCell class="font-medium"
                                                >Mayor’s Permit</TableCell
                                            >
                                            <TableCell>
                                                <span v-if="form.MAYORS_PERMIT"
                                                    >{{
                                                        form.MAYORS_PERMIT.name
                                                    }}
                                                    •
                                                    {{
                                                        humanSize(
                                                            form.MAYORS_PERMIT
                                                                .size,
                                                        )
                                                    }}</span
                                                >
                                                <span
                                                    v-else
                                                    class="text-muted-foreground"
                                                    >No file selected</span
                                                >
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Button
                                                    v-if="form.MAYORS_PERMIT"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        clearFile(
                                                            'MAYORS_PERMIT',
                                                        )
                                                    "
                                                    >Remove</Button
                                                >
                                            </TableCell>
                                        </TableRow>

                                        <TableRow>
                                            <TableCell class="font-medium"
                                                >BIR 2303</TableCell
                                            >
                                            <TableCell>
                                                <span v-if="form.BIR_2303"
                                                    >{{ form.BIR_2303.name }} •
                                                    {{
                                                        humanSize(
                                                            form.BIR_2303.size,
                                                        )
                                                    }}</span
                                                >
                                                <span
                                                    v-else
                                                    class="text-muted-foreground"
                                                    >No file selected</span
                                                >
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Button
                                                    v-if="form.BIR_2303"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        clearFile('BIR_2303')
                                                    "
                                                    >Remove</Button
                                                >
                                            </TableCell>
                                        </TableRow>

                                        <TableRow>
                                            <TableCell class="font-medium"
                                                >SEC Certificate</TableCell
                                            >
                                            <TableCell>
                                                <span v-if="form.SEC_CERT"
                                                    >{{ form.SEC_CERT.name }} •
                                                    {{
                                                        humanSize(
                                                            form.SEC_CERT.size,
                                                        )
                                                    }}</span
                                                >
                                                <span
                                                    v-else
                                                    class="text-muted-foreground"
                                                    >No file selected</span
                                                >
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Button
                                                    v-if="form.SEC_CERT"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        clearFile('SEC_CERT')
                                                    "
                                                    >Remove</Button
                                                >
                                            </TableCell>
                                        </TableRow>

                                        <TableRow>
                                            <TableCell class="font-medium"
                                                >DTI Certificate</TableCell
                                            >
                                            <TableCell>
                                                <span v-if="form.DTI_CERT"
                                                    >{{ form.DTI_CERT.name }} •
                                                    {{
                                                        humanSize(
                                                            form.DTI_CERT.size,
                                                        )
                                                    }}</span
                                                >
                                                <span
                                                    v-else
                                                    class="text-muted-foreground"
                                                    >No file selected</span
                                                >
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Button
                                                    v-if="form.DTI_CERT"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        clearFile('DTI_CERT')
                                                    "
                                                    >Remove</Button
                                                >
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </div>
                </CardContent>

                <Separator />

                <CardFooter class="flex items-center justify-between py-4">
                    <Button
                        variant="outline"
                        :disabled="step === 1"
                        @click="back"
                        >Back</Button
                    >

                    <div class="flex items-center gap-2">
                        <Button
                            v-if="step < 3"
                            :disabled="step === 1 ? !step1Valid : !step2Valid"
                            @click="next"
                        >
                            Next
                        </Button>

                        <Button
                            v-else
                            :disabled="!step3Valid"
                            @click="submitToConsole"
                        >
                            Console Submit
                        </Button>
                    </div>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
