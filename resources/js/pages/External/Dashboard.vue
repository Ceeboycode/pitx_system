<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'

import {
  ArrowRight,
  Building2,
  CheckCircle2,
  Clock,
  FileText,
  TrendingUp,
  Truck,
} from 'lucide-vue-next'

import ExternalLayout from '@/layouts/ExternalLayout.vue'

// ── Props ──────────────────────────────────────────────────────────
const props = defineProps<{
  company: {
    id: number
    company_name: string
    company_code?: string | null
    company_email?: string | null
    company_phone?: string | null
    status: string
    business_type?: string | null
    authorized_representative_name?: string | null
    logo_url?: string | null
  }
  user: {
    id: number
    name: string
    username: string
    email: string
  }
  stats: {
    total_dispatches: number
    pending_dispatches: number
    total_documents: number
    verified_documents: number
  }
}>()

function humanize(text?: string | null) {
  if (!text) return '—'
  return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Good morning'
  if (h < 17) return 'Good afternoon'
  return 'Good evening'
})

const statCards = computed(() => [
  {
    title: 'Total Dispatches',
    value: props.stats.total_dispatches,
    sub: `${props.stats.pending_dispatches} pending`,
    icon: Truck,
    color: 'text-blue-700',
    bg: 'bg-blue-50',
    border: 'border-blue-100',
  },
  {
    title: 'Documents',
    value: props.stats.total_documents,
    sub: `${props.stats.verified_documents} verified`,
    icon: FileText,
    color: 'text-blue-700',
    bg: 'bg-blue-50',
    border: 'border-blue-100',
  },
  {
    title: 'Pending Review',
    value: props.stats.pending_dispatches,
    sub: 'awaiting action',
    icon: Clock,
    color: 'text-red-600',
    bg: 'bg-red-50',
    border: 'border-red-100',
  },
  {
    title: 'Verified Docs',
    value: props.stats.verified_documents,
    sub: `of ${props.stats.total_documents} total`,
    icon: CheckCircle2,
    color: 'text-green-700',
    bg: 'bg-green-50',
    border: 'border-green-100',
  },
])

const companyInitials = computed(() =>
  (props.company.company_code ?? props.company.company_name)
    .slice(0, 2)
    .toUpperCase(),
)
</script>

<template>
  <Head :title="`Dashboard — ${company.company_name}`" />

  <ExternalLayout :company="company" :user="user">
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6">

      <!-- ── Greeting ────────────────────────────────────────────── -->
      <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">
            {{ greeting }}, {{ user.name.split(' ')[0] }} 👋
          </h1>
          <p class="mt-0.5 text-sm text-muted-foreground">
            Here's what's happening with
            <span class="font-medium text-foreground">{{ company.company_name }}</span> today.
          </p>
        </div>
        <Badge
          :class="[
            'w-fit gap-1.5 self-start sm:self-auto border-0',
            company.status === 'verified'
              ? 'bg-green-100 text-green-700'
              : 'bg-amber-100 text-amber-700',
          ]"
        >
          <CheckCircle2 v-if="company.status === 'verified'" class="h-3 w-3" />
          {{ humanize(company.status) }}
        </Badge>
      </div>

      <!-- ── Stat cards ──────────────────────────────────────────── -->
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Card
          v-for="stat in statCards"
          :key="stat.title"
          :class="['border transition-shadow hover:shadow-sm', stat.border]"
        >
          <CardContent class="p-5">
            <div class="flex items-start justify-between">
              <div>
                <p class="text-xs font-medium text-muted-foreground">{{ stat.title }}</p>
                <p class="mt-1.5 text-3xl font-bold tracking-tight">{{ stat.value }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ stat.sub }}</p>
              </div>
              <div :class="['flex h-9 w-9 items-center justify-center rounded-lg', stat.bg]">
                <component :is="stat.icon" :class="['h-4 w-4', stat.color]" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- ── Bottom grid ─────────────────────────────────────────── -->
      <div class="grid gap-4 lg:grid-cols-5">

        <!-- Company Profile — 3 cols -->
        <Card class="lg:col-span-3">
          <CardHeader class="pb-3">
            <div class="flex items-start gap-4">

              <!-- Company Logo -->
              <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 bg-muted shadow-sm">
                <img
                  v-if="company.logo_url"
                  :src="company.logo_url"
                  :alt="company.company_name"
                  class="h-full w-full object-cover"
                />
                <div
                  v-else
                  class="flex h-full w-full items-center justify-center bg-blue-50 text-xl font-bold text-blue-800"
                >
                  {{ companyInitials }}
                </div>
              </div>

              <div class="flex-1">
                <CardTitle class="text-base">{{ company.company_name }}</CardTitle>
                <CardDescription class="mt-0.5 font-mono text-xs">
                  {{ company.company_code ?? '—' }}
                </CardDescription>
                <Badge
                  :class="[
                    'mt-1.5 gap-1 text-xs border-0',
                    company.status === 'verified'
                      ? 'bg-green-100 text-green-700'
                      : 'bg-amber-100 text-amber-700',
                  ]"
                >
                  <CheckCircle2 v-if="company.status === 'verified'" class="h-3 w-3" />
                  {{ humanize(company.status) }}
                </Badge>
              </div>
            </div>
          </CardHeader>

          <Separator />

          <CardContent class="pt-4">
            <dl class="grid gap-y-3 sm:grid-cols-2">
              <div class="space-y-0.5">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Email</dt>
                <dd class="text-sm">{{ company.company_email ?? '—' }}</dd>
              </div>
              <div class="space-y-0.5">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Phone</dt>
                <dd class="text-sm">{{ company.company_phone ?? '—' }}</dd>
              </div>
              <div class="space-y-0.5">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Business Type</dt>
                <dd class="text-sm">{{ humanize(company.business_type) }}</dd>
              </div>
              <div v-if="company.authorized_representative_name" class="space-y-0.5">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Representative</dt>
                <dd class="text-sm">{{ company.authorized_representative_name }}</dd>
              </div>
            </dl>
          </CardContent>
        </Card>

        <!-- Quick Actions — 2 cols -->
        <Card class="lg:col-span-2">
          <CardHeader class="pb-3">
            <CardTitle class="text-base">Quick Actions</CardTitle>
            <CardDescription class="text-xs">Common tasks for your account</CardDescription>
          </CardHeader>
          <Separator />
          <CardContent class="space-y-2 pt-4">

            <button
              class="group flex w-full items-center justify-between rounded-lg border px-3 py-2.5 text-sm transition-colors hover:border-blue-800/30 hover:bg-blue-50"
            >
              <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-md bg-blue-50">
                  <Truck class="h-3.5 w-3.5 text-blue-700" />
                </div>
                <span class="font-medium">New Dispatch</span>
              </div>
              <ArrowRight class="h-4 w-4 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-blue-700" />
            </button>

            <button
              class="group flex w-full items-center justify-between rounded-lg border px-3 py-2.5 text-sm transition-colors hover:border-blue-800/30 hover:bg-blue-50"
            >
              <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-md bg-blue-50">
                  <FileText class="h-3.5 w-3.5 text-blue-700" />
                </div>
                <span class="font-medium">View Documents</span>
              </div>
              <ArrowRight class="h-4 w-4 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-blue-700" />
            </button>

            <button
              class="group flex w-full items-center justify-between rounded-lg border px-3 py-2.5 text-sm transition-colors hover:border-red-600/30 hover:bg-red-50"
            >
              <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-md bg-red-50">
                  <TrendingUp class="h-3.5 w-3.5 text-red-600" />
                </div>
                <span class="font-medium">View Reports</span>
              </div>
              <ArrowRight class="h-4 w-4 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-red-600" />
            </button>

          </CardContent>
        </Card>

      </div>
    </div>
  </ExternalLayout>
</template>