<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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

// Wayfinder — swap with generated action import when ready:
// import { updateLogo, removeLogo } from '@/actions/App/Http/Controllers/CompanyProfileController'
const updateLogoUrl = '/company/profile/logo'
const removeLogoUrl = '/company/profile/logo/remove'

// ── Props ──────────────────────────────────────────────────────────
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

// ── Logo upload form ───────────────────────────────────────────────
const logoForm    = useForm({ logo: null as File | null })
const removeForm  = useForm({})
const logoInputRef = ref<HTMLInputElement | null>(null)

// Local preview — starts from the current logo_url prop
const preview     = ref<string | null>(props.company.logo_url ?? null)
const imgError    = ref(false)
const isDragging  = ref(false)

// If the prop changes (after Inertia reloads), sync preview
watch(() => props.company.logo_url, (val) => {
  preview.value = val ?? null
  imgError.value = false
})

const showCurrentImage = computed(() =>
  !!preview.value && !imgError.value
)

function pickFile(file: File) {
  if (!file.type.match(/image\/(jpeg|png|webp)/)) return
  logoForm.logo = file
  imgError.value = false

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
  imgError.value = false
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

// ── Helpers ────────────────────────────────────────────────────────
function humanize(text?: string | null) {
  if (!text) return '—'
  return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

const companyInitials = computed(() =>
  (props.company.company_code ?? props.company.company_name ?? '')
    .replace(/[^A-Za-z0-9]/g, '')
    .slice(0, 2)
    .toUpperCase() || '??'
)

// Has the user picked a new file (not yet saved)?
const hasPendingUpload = computed(() => !!logoForm.logo)
</script>

<template>
  <Head :title="`Profile — ${company.company_name}`" />

  <ExternalLayout :company="company" :user="user">
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6">

      <!-- ── Page heading ────────────────────────────────────────── -->
      <div>
        <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">Company Profile</h1>
        <p class="mt-0.5 text-sm text-muted-foreground">
          Manage your company information and branding.
        </p>
      </div>

      <!-- ── Logo card ───────────────────────────────────────────── -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="text-base">Company Logo</CardTitle>
          <CardDescription class="text-xs">
            Shown in your sidebar and documents. JPG, PNG or WebP · max 2 MB · square recommended.
          </CardDescription>
        </CardHeader>
        <Separator />
        <CardContent class="pt-5">
          <div class="flex flex-col gap-5 sm:flex-row sm:items-start">

            <!-- Current / preview image ── -->
            <div class="flex flex-col items-center gap-2">
              <div
                class="relative h-24 w-24 shrink-0 overflow-hidden rounded-2xl border-2 bg-muted shadow-sm transition-colors"
                :class="hasPendingUpload ? 'border-primary/60' : 'border-border'"
              >
                <!-- Image (current saved OR local preview) -->
                <img
                  v-if="showCurrentImage"
                  :src="preview!"
                  :alt="company.company_name"
                  class="h-full w-full object-cover"
                  @error="imgError = true"
                />
                <!-- Fallback initials -->
                <div
                  v-else
                  class="flex h-full w-full items-center justify-center bg-primary/10"
                >
                  <span class="text-2xl font-bold text-primary select-none">
                    {{ companyInitials }}
                  </span>
                </div>

                <!-- "New" badge when a file is staged -->
                <div
                  v-if="hasPendingUpload"
                  class="absolute bottom-1 right-1 rounded-full bg-primary px-1.5 py-0.5 text-[9px] font-semibold text-primary-foreground leading-none"
                >
                  NEW
                </div>
              </div>

              <!-- Remove current logo (only when one is saved and nothing pending) -->
              <button
                v-if="company.logo_url && !hasPendingUpload"
                type="button"
                class="flex items-center gap-1 text-xs text-destructive hover:underline disabled:opacity-50"
                :disabled="removeForm.processing"
                @click="submitRemove"
              >
                <Trash2 class="h-3 w-3" />
                Remove logo
              </button>

              <!-- Cancel staged selection -->
              <button
                v-if="hasPendingUpload"
                type="button"
                class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                @click="clearSelection"
              >
                <X class="h-3 w-3" />
                Cancel
              </button>
            </div>

            <!-- Upload area ── -->
            <div class="flex-1 space-y-3">

              <!-- Drag-and-drop zone -->
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

              <!-- Validation error -->
              <p v-if="logoForm.errors.logo" class="text-xs text-destructive">
                {{ logoForm.errors.logo }}
              </p>

              <!-- Staged file info + save button -->
              <div v-if="hasPendingUpload" class="flex items-center justify-between rounded-lg border bg-muted/40 px-3 py-2.5">
                <div class="min-w-0">
                  <p class="truncate text-xs font-medium">{{ logoForm.logo?.name }}</p>
                  <p class="text-[11px] text-muted-foreground">
                    {{ logoForm.logo ? (logoForm.logo.size / 1024).toFixed(0) + ' KB' : '' }}
                  </p>
                </div>
                <Button
                  size="sm"
                  class="ml-3 shrink-0"
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

      <!-- ── Company details card ────────────────────────────────── -->
      <Card>
        <CardHeader class="pb-3">
          <div class="flex items-center gap-2">
            <Building2 class="h-4 w-4 text-muted-foreground" />
            <div>
              <CardTitle class="text-base">Company Details</CardTitle>
              <CardDescription class="text-xs">Your registered business information.</CardDescription>
            </div>
          </div>
        </CardHeader>
        <Separator />
        <CardContent class="pt-5">
          <dl class="grid gap-y-4 sm:grid-cols-2">

            <div class="space-y-0.5">
              <dt class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Company Name</dt>
              <dd class="text-sm font-medium">{{ company.company_name }}</dd>
            </div>

            <div class="space-y-0.5">
              <dt class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Company Code</dt>
              <dd class="font-mono text-sm">{{ company.company_code ?? '—' }}</dd>
            </div>

            <div class="space-y-0.5">
              <dt class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Email</dt>
              <dd class="text-sm">{{ company.company_email ?? '—' }}</dd>
            </div>

            <div class="space-y-0.5">
              <dt class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Phone</dt>
              <dd class="text-sm">{{ company.company_phone ?? '—' }}</dd>
            </div>

            <div class="space-y-0.5 sm:col-span-2">
              <dt class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Address</dt>
              <dd class="text-sm">{{ company.company_address ?? '—' }}</dd>
            </div>

            <div class="space-y-0.5">
              <dt class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Business Type</dt>
              <dd class="text-sm">{{ humanize(company.business_type) }}</dd>
            </div>

            <div class="space-y-0.5">
              <dt class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Registration No.</dt>
              <dd class="font-mono text-sm">{{ company.registration_number ?? '—' }}</dd>
            </div>

            <div class="space-y-0.5">
              <dt class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Status</dt>
              <dd>
                <Badge
                  :variant="company.status === 'verified' ? 'default' : 'secondary'"
                  class="gap-1 text-xs"
                >
                  <CheckCircle2 v-if="company.status === 'verified'" class="h-3 w-3" />
                  {{ humanize(company.status) }}
                </Badge>
              </dd>
            </div>

            <Separator class="sm:col-span-2 my-1" />

            <div class="space-y-0.5">
              <dt class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Representative Name</dt>
              <dd class="text-sm">{{ company.authorized_representative_name ?? '—' }}</dd>
            </div>

            <div class="space-y-0.5">
              <dt class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Position</dt>
              <dd class="text-sm">{{ humanize(company.authorized_representative_position) }}</dd>
            </div>

            <div class="space-y-0.5">
              <dt class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Representative Contact</dt>
              <dd class="text-sm">{{ company.authorized_representative_contact ?? '—' }}</dd>
            </div>

          </dl>
        </CardContent>
      </Card>

    </div>
  </ExternalLayout>
</template>
