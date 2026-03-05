<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'

import { Building2, CheckCircle2, FileText, LayoutDashboard, Truck, User2, LogOut } from 'lucide-vue-next'

// ✅ Wayfinder routes (adjust import paths to your generated routes index)
import { logout } from '@/routes'

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
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 17) return 'Good afternoon'
  return 'Good evening'
})

const statCards = computed(() => [
  {
    title: 'Total Dispatches',
    value: props.stats.total_dispatches,
    sub: `${props.stats.pending_dispatches} pending`,
    icon: Truck,
    color: 'text-blue-500',
    bg: 'bg-blue-50 dark:bg-blue-950/40',
  },
  {
    title: 'Documents',
    value: props.stats.total_documents,
    sub: `${props.stats.verified_documents} verified`,
    icon: FileText,
    color: 'text-emerald-500',
    bg: 'bg-emerald-50 dark:bg-emerald-950/40',
  },
])
</script>

<template>
  <Head :title="`${company.company_name} — Dashboard`" />

  <div class="min-h-screen bg-muted/30">
    <!-- ── Top nav bar ─────────────────────────────────────────────── -->
    <header class="sticky top-0 z-10 border-b bg-background/95 backdrop-blur">
      <div class="mx-auto flex h-14 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
        <div class="flex items-center gap-2.5">
          <LayoutDashboard class="h-5 w-5 text-primary" />
          <span class="text-sm font-semibold">{{ company.company_code }}</span>
          <Badge variant="outline" class="hidden text-xs sm:inline-flex">Company Portal</Badge>
        </div>

        <div class="flex items-center gap-2">
          <span class="hidden text-xs text-muted-foreground sm:block">{{ user.name }}</span>

          <!-- Profile (Wayfinder) -->
          <!-- <Button variant="ghost" size="sm" as-child>
            <Link :href="userProfile().url">
              <User2 class="h-4 w-4" />
            </Link>
          </Button> -->

          <!-- Logout (Wayfinder) -->
          <Button variant="ghost" size="sm" as-child>
            <Link
              :href="logout().url"
              method="post"
              as="button"
              class="inline-flex items-center"
            >
              <LogOut class="h-4 w-4" />
            </Link>
          </Button>
        </div>
      </div>
    </header>

    <!-- ── Page body ──────────────────────────────────────────────── -->
    <main class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6">
      <!-- Greeting -->
      <div class="space-y-1">
        <h1 class="text-2xl font-semibold tracking-tight">
          {{ greeting }}, {{ user.name.split(' ')[0] }} 👋
        </h1>
        <p class="text-sm text-muted-foreground">
          Here's what's happening with <strong>{{ company.company_name }}</strong> today.
        </p>
      </div>

      <!-- Stat cards -->
      <div class="grid gap-4 sm:grid-cols-2">
        <Card v-for="stat in statCards" :key="stat.title">
          <CardContent class="flex items-center gap-4 p-5">
            <div :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-xl', stat.bg]">
              <component :is="stat.icon" :class="['h-5 w-5', stat.color]" />
            </div>
            <div>
              <p class="text-2xl font-bold leading-none">{{ stat.value }}</p>
              <p class="mt-0.5 text-xs text-muted-foreground">{{ stat.title }}</p>
              <p class="text-xs text-muted-foreground">{{ stat.sub }}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Company info card -->
      <Card>
        <CardHeader>
          <div class="flex items-center gap-2">
            <Building2 class="h-5 w-5 text-muted-foreground" />
            <div>
              <CardTitle>Company Profile</CardTitle>
              <CardDescription>Your registered business details.</CardDescription>
            </div>
          </div>
        </CardHeader>
        <Separator />
        <CardContent class="pt-5">
          <dl class="grid gap-3 sm:grid-cols-2">
            <div class="space-y-0.5">
              <dt class="text-xs font-medium text-muted-foreground">Company Name</dt>
              <dd class="text-sm">{{ company.company_name }}</dd>
            </div>
            <div class="space-y-0.5">
              <dt class="text-xs font-medium text-muted-foreground">Company Code</dt>
              <dd class="text-sm font-mono">{{ company.company_code ?? '—' }}</dd>
            </div>
            <div class="space-y-0.5">
              <dt class="text-xs font-medium text-muted-foreground">Email</dt>
              <dd class="text-sm">{{ company.company_email ?? '—' }}</dd>
            </div>
            <div class="space-y-0.5">
              <dt class="text-xs font-medium text-muted-foreground">Phone</dt>
              <dd class="text-sm">{{ company.company_phone ?? '—' }}</dd>
            </div>
            <div class="space-y-0.5">
              <dt class="text-xs font-medium text-muted-foreground">Business Type</dt>
              <dd class="text-sm">{{ humanize(company.business_type) }}</dd>
            </div>
            <div class="space-y-0.5">
              <dt class="text-xs font-medium text-muted-foreground">Status</dt>
              <dd>
                <Badge :variant="company.status === 'verified' ? 'default' : 'secondary'" class="gap-1 text-xs">
                  <CheckCircle2 v-if="company.status === 'verified'" class="h-3 w-3" />
                  {{ humanize(company.status) }}
                </Badge>
              </dd>
            </div>
            <div v-if="company.authorized_representative_name" class="space-y-0.5 sm:col-span-2">
              <dt class="text-xs font-medium text-muted-foreground">Authorized Representative</dt>
              <dd class="text-sm">{{ company.authorized_representative_name }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <!-- Quick actions -->
      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
          <CardDescription>Common tasks for your company account.</CardDescription>
        </CardHeader>
        <Separator />
        <CardContent class="flex flex-wrap gap-3 pt-5">
          <!-- <Button as-child variant="outline" size="sm">
            <Link :href="dispatchesCreate({ company: company.id }).url">
              <Truck class="mr-2 h-4 w-4" />
              New Dispatch
            </Link>
          </Button> -->

          <!-- <Button as-child variant="outline" size="sm">
            <Link :href="registrationStatus().url">
              <FileText class="mr-2 h-4 w-4" />
              View Documents
            </Link>
          </Button> -->
        </CardContent>
      </Card>
    </main>
  </div>
</template>
