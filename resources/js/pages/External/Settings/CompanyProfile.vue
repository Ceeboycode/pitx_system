<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

import CompanyLogo from '@/components/company/CompanyLogo.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'

import {
    Building2,
    CheckCircle2,
    ImagePlus,
    Loader2,
    Trash2,
    Upload,
    X,
} from 'lucide-vue-next'

import ExternalLayout from '@/layouts/ExternalLayout.vue'

const updateLogoUrl = '/company/profile/logo'
const removeLogoUrl = '/company/profile/logo/remove'

const props = defineProps<{
    company: {
        id: number
        company_name: string
        company_code?: string | null
        company_email?: string | null
        company_phone?: string | null
        company_address?: string | null
        status: string
        business_type?: string | null
        registration_number?: string | null
        authorized_representative_name?: string | null
        authorized_representative_position?: string | null
        authorized_representative_contact?: string | null
        logo_url?: string | null
    }
    user: {
        id: number
        name: string
        username: string
        email: string
    }
}>()

const logoForm    = useForm({ logo: null as File | null })
const removeForm  = useForm({})
const logoInputRef = ref<HTMLInputElement | null>(null)

const preview    = ref<string | null>(props.company.logo_url ?? null)
const isDragging = ref(false)

watch(() => props.company.logo_url, (val) => {
    preview.value = val ?? null
})

const hasPendingUpload = computed(() => !!logoForm.logo)

const companyInitials = computed(() =>
    (props.company.company_code ?? props.company.company_name ?? '')
        .replace(/[^A-Za-z0-9]/g, '')
        .slice(0, 2)
        .toUpperCase() || '??'
)

function pickFile(file: File) {
    if (!file.type.match(/image\/(jpeg|png|webp)/)) return
    logoForm.logo = file
    const reader = new FileReader()
    reader.onload = (e) => { preview.value = e.target?.result as string }
    reader.readAsDataURL(file)
}

function handleFileInput(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file) pickFile(file)
}

function handleDrop(e: DragEvent) {
    isDragging.value = false
    const file = e.dataTransfer?.files?.[0]
    if (file) pickFile(file)
}

function clearSelection() {
    logoForm.logo = null
    preview.value = props.company.logo_url ?? null
    if (logoInputRef.value) logoInputRef.value.value = ''
}

function submitLogo() {
    logoForm.post(updateLogoUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            logoForm.reset()
            if (logoInputRef.value) logoInputRef.value.value = ''
        },
    })
}

function submitRemove() {
    if (!confirm('Remove your company logo? The initials will be shown instead.')) return
    removeForm.delete(removeLogoUrl, {
        preserveScroll: true,
        onSuccess: () => {
            preview.value = null
            logoForm.logo = null
        },
    })
}

function humanize(text?: string | null) {
    if (!text) return '—'
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

function statusClass(status?: string | null) {
    if (status === 'active' || status === 'verified') return 'bg-emerald-100 text-emerald-700 border-emerald-200'
    if (status === 'pending')   return 'bg-amber-100 text-amber-700 border-amber-200'
    if (status === 'suspended') return 'bg-rose-100 text-rose-600 border-rose-200'
    if (status === 'inactive')  return 'bg-slate-100 text-slate-500 border-0'
    return 'bg-slate-100 text-slate-500 border-0'
}

function statusDot(status?: string | null) {
    if (status === 'active' || status === 'verified') return 'bg-emerald-500'
    if (status === 'pending')   return 'bg-amber-500'
    if (status === 'suspended') return 'bg-rose-500'
    return 'bg-slate-400'
}
</script>

<template>
    <Head :title="`Profile — ${company.company_name}`" />

    <ExternalLayout :company="company" :user="user">
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-3xl space-y-6 p-4 md:p-8">

                <!-- ── Page header ─────────────────────────────────── -->
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                        <Building2 class="h-3.5 w-3.5" />
                        {{ company.company_code ?? company.company_name }}
                        <span class="text-slate-300">·</span>
                        <span>Profile</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">Company Profile</h1>
                    <p class="text-sm text-muted-foreground">
                        Manage your company information and branding.
                    </p>
                </div>

                <!-- ── Logo card ───────────────────────────────────── -->
                <Card>
                    <CardHeader class="border-b border-slate-100 pb-4">
                        <CardTitle class="text-base">Company Logo</CardTitle>
                        <CardDescription>
                            Shown in your sidebar and documents. JPG, PNG or WebP · max 2 MB · square recommended.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="pt-5">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">

                            <!-- Logo preview -->
                            <div class="flex flex-col items-center gap-2">
                                <div class="relative">
                                    <CompanyLogo
                                        :src="preview"
                                        :alt="company.company_name"
                                        :initials="companyInitials"
                                        :class="[
                                            'h-24 w-24 shrink-0 rounded-2xl border-2 shadow-sm transition-colors',
                                            hasPendingUpload ? '!border-primary/60 ring-2 ring-primary/10' : '',
                                        ]"
                                        text-class="select-none text-2xl font-bold"
                                    />
                                    <div
                                        v-if="hasPendingUpload"
                                        class="absolute bottom-1 right-1 rounded-full bg-primary px-1.5 py-0.5 text-[9px] font-semibold leading-none text-primary-foreground"
                                    >
                                        NEW
                                    </div>
                                </div>

                                <Button
                                    v-if="company.logo_url && !hasPendingUpload"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-auto gap-1 px-2 py-1 text-xs text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    :disabled="removeForm.processing"
                                    @click="submitRemove"
                                >
                                    <Trash2 class="h-3 w-3" />
                                    Remove logo
                                </Button>

                                <Button
                                    v-if="hasPendingUpload"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-auto gap-1 px-2 py-1 text-xs text-muted-foreground hover:text-foreground"
                                    @click="clearSelection"
                                >
                                    <X class="h-3 w-3" />
                                    Cancel
                                </Button>
                            </div>

                            <!-- Upload area -->
                            <div class="flex-1 space-y-3">
                                <label
                                    for="logo-input"
                                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-6 py-8 text-center transition-colors"
                                    :class="isDragging
                                        ? 'border-primary bg-primary/5'
                                        : 'border-muted-foreground/25 hover:border-primary/50 hover:bg-muted/40'"
                                    @dragover.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="handleDrop"
                                >
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                        <ImagePlus class="h-5 w-5 text-muted-foreground" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">
                                            <span class="text-primary">Click to upload</span> or drag and drop
                                        </p>
                                        <p class="mt-0.5 text-xs text-muted-foreground">
                                            JPG, PNG, WebP · max 2 MB
                                        </p>
                                    </div>
                                    <input
                                        id="logo-input"
                                        ref="logoInputRef"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        class="sr-only"
                                        @change="handleFileInput"
                                    />
                                </label>

                                <p v-if="logoForm.errors.logo" class="text-xs text-destructive">
                                    {{ logoForm.errors.logo }}
                                </p>

                                <div
                                    v-if="hasPendingUpload"
                                    class="flex items-center justify-between rounded-lg border bg-muted/40 px-3 py-2.5"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-medium">{{ logoForm.logo?.name }}</p>
                                        <p class="text-[11px] text-muted-foreground">
                                            {{ logoForm.logo ? (logoForm.logo.size / 1024).toFixed(0) + ' KB' : '' }}
                                        </p>
                                    </div>
                                    <Button
                                        size="sm"
                                        class="ml-3 shrink-0 rounded-lg bg-blue-700 text-white hover:bg-blue-800 border-0"
                                        :disabled="logoForm.processing"
                                        @click="submitLogo"
                                    >
                                        <Loader2 v-if="logoForm.processing" class="mr-2 h-3.5 w-3.5 animate-spin" />
                                        <Upload v-else class="mr-2 h-3.5 w-3.5" />
                                        {{ logoForm.processing ? 'Saving…' : 'Save Logo' }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- ── Company details card ────────────────────────── -->
                <Card>
                    <CardHeader class="border-b border-slate-100 pb-4">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Building2 class="h-4 w-4 text-blue-700" />
                            Company Details
                        </CardTitle>
                        <CardDescription>Your registered business information.</CardDescription>
                    </CardHeader>

                    <CardContent class="pt-0">

                        <!-- Basic info -->
                        <div class="divide-y divide-slate-100">

                            <div class="grid gap-4 px-0 py-4 sm:grid-cols-2">
                                <div class="space-y-0.5">
                                    <dt class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Company Name</dt>
                                    <dd class="text-sm font-semibold">{{ company.company_name }}</dd>
                                </div>
                                <div class="space-y-0.5">
                                    <dt class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Company Code</dt>
                                    <dd class="font-mono text-sm font-semibold">{{ company.company_code ?? '—' }}</dd>
                                </div>
                            </div>

                            <div class="grid gap-4 py-4 sm:grid-cols-2">
                                <div class="space-y-0.5">
                                    <dt class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Email</dt>
                                    <dd class="text-sm">{{ company.company_email ?? '—' }}</dd>
                                </div>
                                <div class="space-y-0.5">
                                    <dt class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Phone</dt>
                                    <dd class="text-sm">{{ company.company_phone ?? '—' }}</dd>
                                </div>
                            </div>

                            <div class="py-4">
                                <div class="space-y-0.5">
                                    <dt class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Address</dt>
                                    <dd class="text-sm">{{ company.company_address ?? '—' }}</dd>
                                </div>
                            </div>

                            <div class="grid gap-4 py-4 sm:grid-cols-2">
                                <div class="space-y-0.5">
                                    <dt class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Business Type</dt>
                                    <dd class="text-sm">{{ humanize(company.business_type) }}</dd>
                                </div>
                                <div class="space-y-0.5">
                                    <dt class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Registration No.</dt>
                                    <dd class="font-mono text-sm">{{ company.registration_number ?? '—' }}</dd>
                                </div>
                            </div>

                            <div class="flex items-center justify-between py-4">
                                <dt class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Status</dt>
                                <Badge :class="['gap-1.5', statusClass(company.status)]">
                                    <CheckCircle2 v-if="company.status === 'verified'" class="h-3 w-3" />
                                    <span v-else :class="['h-1.5 w-1.5 rounded-full', statusDot(company.status)]" />
                                    {{ humanize(company.status) }}
                                </Badge>
                            </div>

                        </div>

                        <Separator />

                        <!-- Representative -->
                        <div class="divide-y divide-slate-100">

                            <div class="pb-2 pt-4">
                                <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Authorized Representative</p>
                            </div>

                            <div class="grid gap-4 py-4 sm:grid-cols-2">
                                <div class="space-y-0.5">
                                    <dt class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Name</dt>
                                    <dd class="text-sm font-medium">{{ company.authorized_representative_name ?? '—' }}</dd>
                                </div>
                                <div class="space-y-0.5">
                                    <dt class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Position</dt>
                                    <dd class="text-sm">{{ humanize(company.authorized_representative_position) }}</dd>
                                </div>
                            </div>

                            <div class="py-4">
                                <div class="space-y-0.5">
                                    <dt class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Contact</dt>
                                    <dd class="text-sm">{{ company.authorized_representative_contact ?? '—' }}</dd>
                                </div>
                            </div>

                        </div>

                    </CardContent>
                </Card>

            </div>
        </div>
    </ExternalLayout>
</template>
