<script setup lang="ts">
import { computed } from 'vue';

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Logo, LogoFallback, LogoImage } from '@/components/ui/_logo';
import { Separator } from '@/components/ui/separator';
import { CardSeparator } from '@/components/ui/_card-separator';
import Button from '@/components/ui/button/Button.vue';
import { Link } from '@inertiajs/vue3';
import { show as userShow } from '@/routes/users';

import { useInitials } from '@/composables/useInitials';
import { useClipboard } from '@vueuse/core';
import { toast } from 'vue-sonner';

import {
  RiPhoneLine,
  RiVerifiedBadgeLine,
  RiMailLine,
  RiCalendarLine,
  RiChat4Line,
  RiMapPin2Line,
  RiTimeLine,
  RiBuildingLine,
  RiFileCheckLine,
  RiIdCardLine,
  RiUser3Line,
} from "vue-remix-icons";

import { formatDate, humanize } from '@/lib/format';
import type { Operator } from '@/types/company';


// VIEW ORIGINAL IN SHOW.VUE
const props = defineProps<{
  company: {
    id: number;
    company_name: string;
    company_code?: string | null;
    company_email?: string | null;
    company_phone?: string | null;
    company_address?: string | null;
    business_type?: 'corporate' | 'sole_proprietorship' | null;
    registration_number?: string | null;
    status?: string | null;
    created_at?: string | null;
    updated_at_human?: string | null;
    creator?: { name: string } | null;
    updater?: { name: string } | null;
    operator?: Operator | null;
    authorized_representative_name?: string | null;
    authorized_representative_position?: string | null;
    authorized_representative_contact?: string | null;
    logo?: string | null;
    logo_url?: string | null;
  };
}>();

const company = computed(() => props.company);

const { getInitials } = useInitials();

const { copy } = useClipboard({ legacy: true });

async function copyToClipboard(value?: string | null, label = 'Value') {
  const text = (value ?? '').trim();
  if (!text || text === '—') return;
  try {
    await copy(text);
    toast.success(`${label} copied to clipboard.`);
  } catch {
    toast.error(`Could not copy ${label.toLowerCase()}.`);
  }
}

function openInMaps(value?: string | null) {
  const query = (value ?? '').trim();
  if (!query || query === '—') return;
  window.open(
    `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`,
    '_blank',
    'noopener,noreferrer',
  );
}
</script>

<template>
  <div class="grid lg:grid-cols-3 gap-4 w-full h-full">
    <Card class="lg:col-span-2">
      <CardHeader>
        <CardTitle>Company</CardTitle>
        <CardDescription>View company details.</CardDescription>
      </CardHeader>
      <CardContent class="flex flex-row gap-4">
        <div class="flex-1">
          <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-col gap-2 justify-center items-start">
              <Logo class="rounded-md">
                <LogoImage
                  v-if="company.logo"
                  :src="company.logo"
                  :alt="company.company_name"
                />
                <LogoFallback>
                  No logo uploaded.
                </LogoFallback>
              </Logo>
            </div>
            <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
              <div class="inline-flex gap-2 items-start">
                <RiBuildingLine class="shrink-0 mt-0.5 h-4 w-4 text-custom-shadow/80 0"/>
                <span
                  role="button"
                  tabindex="0"
                  title="Copy to clipboard"
                  @click="copyToClipboard(company.company_name, 'Company Name')"
                  @keydown.enter.prevent="copyToClipboard(company.company_name, 'Company Name')"
                  @keydown.space.prevent="copyToClipboard(company.company_name, 'Company Name')"
                  class="cursor-pointer"
                >
                  {{ company.company_name || '—' }}
                  <span
                    role="button"
                    tabindex="0"
                    title="Copy to clipboard"
                    @click="copyToClipboard(company.company_code, 'Company Code')"
                    @keydown.enter.prevent="copyToClipboard(company.company_code, 'Company Code')"
                    @keydown.space.prevent="copyToClipboard(company.company_code, 'Company Code')"
                    class="cursor-pointer tracking-widest bg-custom-bg dark:bg-custom-bg-light px-2 rounded-md font-mono mr-1 font-normal"
                  >
                    {{ company.company_code || '—' }}
                  </span>
                <!-- {{ company.company_name || '—' }} -->
                </span>
              </div>

              <div class="inline-flex gap-2 items-start">
                <RiMapPin2Line class="shrink-0 mt-0.5 h-4 w-4 text-custom-shadow/80 0"/>
                <span
                  role="button"
                  tabindex="0"
                  title="Open in Google Maps"
                  @click="openInMaps(company.company_address)"
                  @keydown.enter.prevent="openInMaps(company.company_address)"
                  @keydown.space.prevent="openInMaps(company.company_address)"
                  class="cursor-pointer"
                >
                  {{ company.company_address || '—' }}
                </span>
              </div>
            </div>

          </div>
        </div>

        <Separator orientation="vertical"/>

        <div class="flex-1">
          <CardSeparator title="Business Info" />

          <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center gap-2 overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiBuildingLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Business Type</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">
                {{ company.business_type ? humanize(company.business_type) : '—' }}
              </span>
            </div>

            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiFileCheckLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Registration No.</span>
              </div>
              <span class="inline-flex gap-2 items-center overflow-hidden">
                <span
                  role="button"
                  tabindex="0"
                  title="Copy to clipboard"
                  @click="copyToClipboard(company.registration_number, 'Registration No.')"
                  @keydown.enter.prevent="copyToClipboard(company.registration_number, 'Registration No.')"
                  @keydown.space.prevent="copyToClipboard(company.registration_number, 'Registration No.')"
                  class="cursor-pointer line-clamp-1 text-ellipsis"
                >
                  {{ company.registration_number || '—' }}
                </span>
              </span>
            </div>
          </div>

          <CardSeparator title="Contact Info" />

          <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Phone</span>
              </div>
              <span
                role="button"
                tabindex="0"
                title="Copy to clipboard"
                @click="copyToClipboard(company.company_phone, 'Phone')"
                @keydown.enter.prevent="copyToClipboard(company.company_phone, 'Phone')"
                @keydown.space.prevent="copyToClipboard(company.company_phone, 'Phone')"
                class="cursor-pointer line-clamp-1 text-ellipsis"
              >
                {{ company.company_phone || '—' }}
              </span>
            </div>

            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiMailLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Email</span>
              </div>
              <span class="inline-flex gap-2 items-center overflow-hidden">
                <span
                  role="button"
                  tabindex="0"
                  title="Copy to clipboard"
                  @click="copyToClipboard(company.company_email, 'Email')"
                  @keydown.enter.prevent="copyToClipboard(company.company_email, 'Email')"
                  @keydown.space.prevent="copyToClipboard(company.company_email, 'Email')"
                  class="cursor-pointer line-clamp-1 text-ellipsis"
                >
                  {{ company.company_email || '—' }}
                </span>
              </span>
            </div>
          </div>

          <CardSeparator title="Others" />

          <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiCalendarLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Created</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">
                {{ formatDate(company.created_at) }}
                <span v-if="company.creator?.name" class="text-custom-accent-3"> • </span>
                {{ company.creator?.name ?? '—' }}
              </span>
            </div>

            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiTimeLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Updated</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">
                {{ company.updated_at_human }}
                <span v-if="company.updater?.name" class="text-custom-accent-3"> • </span>
                {{ company.updater?.name ?? '—' }}
              </span>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
    <div class="lg:col-span-1 flex flex-col gap-4 h-full">
      <Card>
        <CardHeader>
          <CardTitle>Representative</CardTitle>
          <CardDescription>Authorized representative for contact purposes.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow overflow-hidden">
            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiUser3Line class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Name</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">{{ company.authorized_representative_name || '—' }}</span>
            </div>

            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiIdCardLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Position</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">{{ company.authorized_representative_position || '—' }}</span>
            </div>

            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Phone</span>
              </div>
              <span
                role="button"
                tabindex="0"
                title="Copy to clipboard"
                @click="copyToClipboard(company.authorized_representative_contact, 'Phone')"
                @keydown.enter.prevent="copyToClipboard(company.authorized_representative_contact, 'Phone')"
                @keydown.space.prevent="copyToClipboard(company.authorized_representative_contact, 'Phone')"
                class="cursor-pointer line-clamp-1 text-ellipsis"
              >
                {{ company.authorized_representative_contact || '—' }}
              </span>
            </div>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>Operator</CardTitle>
          <CardDescription>Person in charge of company operations.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="my-2 flex flex-row gap-2 items-center justify-between">
            <div class="inline-flex gap-2 items-center">
              <Avatar class="size-12">
                <AvatarImage
                  v-if="company.operator?.avatar"
                  :src="company.operator?.avatar"
                  :alt="company.operator?.name"
                />
                <AvatarFallback>
                  {{ getInitials(company.operator?.name ?? '') }}
                </AvatarFallback>
              </Avatar>
              <div class="flex flex-col text-sm text-custom-shadow">
                <Link
                  v-if="company.operator?.id"
                  :href="userShow(company.operator.id).url"
                >
                  <span class="font-semibold line-clamp-1 text-ellipsis">
                    {{ company.operator?.name || '—' }}
                  </span>
                </Link>
                <span v-else class="font-semibold line-clamp-1 text-ellipsis">
                  {{ company.operator?.name || '—' }}
                </span>
                <span class="w-fit tracking-wide bg-custom-bg dark:bg-custom-bg-light px-2 rounded-md font-mono">{{ company.operator?.username || '—' }}</span>
              </div>
            </div>
            <div>
              <Button variant="float" size="icon">
                <RiChat4Line class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
              </Button>
            </div>
          </div>

          <CardSeparator title="Contact Info" />

          <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Phone</span>
              </div>
              <span
                role="button"
                tabindex="0"
                title="Copy to clipboard"
                @click="copyToClipboard(company.operator?.phone, 'Phone')"
                @keydown.enter.prevent="copyToClipboard(company.operator?.phone, 'Phone')"
                @keydown.space.prevent="copyToClipboard(company.operator?.phone, 'Phone')"
                class="cursor-pointer line-clamp-1 text-ellipsis"
              >
                {{ company.operator?.phone || '—' }}
              </span>
            </div>

            <div class="flex flex-row justify-between items-center gap-2">
              <div class="inline-flex gap-2 items-center">
                <RiMailLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Email</span>
              </div>
              <span class="inline-flex items-center overflow-hidden">
                <span
                  role="button"
                  tabindex="0"
                  title="Copy to clipboard"
                  @click="copyToClipboard(company.operator?.email, 'Email')"
                  @keydown.enter.prevent="copyToClipboard(company.operator?.email, 'Email')"
                  @keydown.space.prevent="copyToClipboard(company.operator?.email, 'Email')"
                  class="cursor-pointer text-ellipsis line-clamp-1 mr-2"
                >
                  {{ company.operator?.email || '—' }}
                </span>
                <RiVerifiedBadgeLine :v-if="company.operator?.email_verified" class="shrink-0 h-4 w-4 text-custom-shadow/80 text-custom-accent-3"/>
              </span>
            </div>
          </div>

          <CardSeparator title="Others" />

          <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiCalendarLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Joined</span>
              </div>
              <span class="text-ellipsis line-clamp-1">{{ formatDate(company.operator?.created_at) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>