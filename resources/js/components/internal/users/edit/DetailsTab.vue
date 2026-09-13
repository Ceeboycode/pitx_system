<script setup lang="ts">
// `ref`/`watch` added for the company-search state below (moved here from
// Users/Edit.vue so this component is the single owner of the whole form).
import { computed, nextTick, ref, watch } from 'vue';
import { update } from '@/routes/users';

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Logo, LogoFallback, LogoImage } from '@/components/ui/_logo';
import { Separator } from '@/components/ui/separator';
import { CardSeparator } from '@/components/ui/_card-separator';
import Button from '@/components/ui/button/Button.vue';
import { useForm } from '@inertiajs/vue3';

import { useClipboard } from '@vueuse/core';
import { toast } from 'vue-sonner';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import InputError from '@/components/InputError.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    SelectLabel,
} from '@/components/ui/select';
import SearchInput from '@/components/SearchInput.vue'

import {
  RiPhoneLine,
  RiCalendarLine,
  RiTimeLine,
  RiBuildingLine,
  RiEditLine,
} from "vue-remix-icons";

import { formatDate } from '@/lib/format';

type Role = {
    id: number;
    name: string;
    type: 'internal' | 'external';
};

type Company = {
    id: number;
    company_name: string;
    company_code: string;
};

const props = defineProps<{
    user: {
        id: number;
        username: string | null;
        name: string;
        email: string;
        phone_number: string | null;
        type: 'internal' | 'external';
        company_id: number | null;
        // Sent by UserController@edit: storage URL or null when no photo is set.
        avatar: string | null;
        created_at: string;
        // Pre-formatted by the backend (Carbon::diffForHumans()), e.g. "2 days ago".
        updated_at_human: string | null;
    };
    roles: Role[];
    companies: Company[];
    selectedRole: string | null;
    // Sent by UserController@edit: true only when the edited user's role
    // holds every permission in that group (see Role::hasAllPermissionsInGroup).
    canManageExternalUsers: boolean;
    canManageExternalDispatches: boolean;
}>();

// const { getInitials } = useInitials();

const { copy } = useClipboard({ legacy: true });

const form = useForm({
    name: props.user.name ?? '',
    email: props.user.email ?? '',
    phone_number: props.user.phone_number ?? '',
    role: props.selectedRole ?? '',
    company_id: props.user.company_id ? String(props.user.company_id) : '',
});

const selectedRole = computed(() =>
    props.roles.find((role) => role.name === form.role) ?? null,
);

const resolvedType = computed<'internal' | 'external'>(() =>
    selectedRole.value?.type === 'external' ? 'external' : 'internal',
);

// Company search/select state, moved here from the page-level form so this
// tab is the single owner of every field it submits (name, email, phone,
// role, and now company). `ref('')` creates a reactive string the <Input>
// below can v-model against.
const companySearch = ref('');

// Recomputes to a new filtered array any time `companySearch` or
// `props.companies` changes - this is what the <option> list in the
// template loops over.
const filteredCompanies = computed(() => {
    const query = companySearch.value.trim().toLowerCase();

    return props.companies.filter((company) => {
        if (!query) {
            return true;
        }

        return (
            company.company_name.toLowerCase().includes(query) ||
            company.company_code.toLowerCase().includes(query)
        );
    });
});

// Looks up the full Company object matching the currently selected
// form.company_id, purely so the template can show its name/code back
// to the user as confirmation.
// const selectedCompany = computed(() =>
//     props.companies.find((company) => String(company.id) === form.company_id) ?? null,
// );

// If the chosen role stops being "external" (e.g. the user picks a
// different role), clear out any company selection so a stale company_id
// can't be submitted for an internal user. `{ immediate: true }` also runs
// this once on mount to normalise the initial state.
watch(
    () => resolvedType.value,
    (newType) => {
        if (newType !== 'external') {
            form.company_id = '';
            companySearch.value = '';
        }
    },
    { immediate: true },
);

// Tracks whether the company <Select> below is currently expanded. Bound via
// `v-model:open` on <Select>, which Reka UI (the headless library behind our
// Select component) supports natively.
const companySelectOpen = ref(false);

// Template ref to the company search <Input>, so we can move focus into it
// when the select opens (see the `.$el.focus()` pattern already used in
// DeleteUser.vue for the same purpose).
const companySearchInputRef = ref<InstanceType<typeof Input> | null>(null);

watch(companySelectOpen, (isOpen) => {
    if (isOpen) {
        // `nextTick` waits for the input to be visible/enabled before we try
        // to focus it, so users can start typing immediately.
        nextTick(() => companySearchInputRef.value?.$el?.focus());
        return;
    }

    // Collapsing: clear whatever was typed so the input's actual value goes
    // back to empty, letting `companySearchPlaceholder` (below) show through.
    companySearch.value = '';
});

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

function submit() {
    form
        .transform((data) => ({
            ...data,
            company_id:
                resolvedType.value === 'external' && data.company_id !== ''
                    ? Number(data.company_id)
                    : null,
        }))
        .put(update({ user: props.user.id }).url, {
            preserveScroll: true,
        });
}
</script>

<template>
  <Card class="lg:col-span-2">
    <CardHeader class="flex flex-row items-start justify-between gap-4">
      <div class="flex flex-col">
        <CardTitle>User</CardTitle>
        <CardDescription>Manage user details.</CardDescription>
      </div>
      <div class="flex flex-row items-center gap-2">
        <!-- TODO: make the cancel button disabled unless change has been made -->
        <Button variant="float" @click="form.reset(); form.clearErrors()">
          Cancel
        </Button>

        <!-- TODO: make the save button disabled unless change has been made -->
        <Button
          :variant="form.processing ? 'disabled' : 'float-primary'"
          size="icon-text"
          :disabled="form.processing"
          @click="submit"
        >
          {{ form.processing ? 'Saving...' : 'Save Changes' }}
        </Button>
      </div>
    </CardHeader>
    <CardContent class="flex flex-row gap-4">
      <div class="flex-1 max-w-1/3">
        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
          <div class="flex flex-col gap-2 justify-center items-start">
            <Logo class="rounded-md">
              <LogoImage
                v-if="user.avatar"
                :src="user.avatar"
                :alt="user.name"
              />
              <LogoFallback>
                No profile picture uploaded.
              </LogoFallback>
            </Logo>
          </div>
          <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
              <div class="inline-flex gap-2 items-center">
                <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <Label for="route_name_sidebar">Name</Label>
              </div>
              <span class="flex flex-row items-center">
                <Input
                    id="route_name_sidebar"
                    :model-value="form.name"
                    placeholder="Enter user name"
                    variant="inline-edit"
                />
                <RiEditLine class="shrink-0 h-4 w-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:w-4 group-hover:ml-2 group-hover:opacity-100 group-focus-within:w-4 group-focus-within:ml-2 group-focus-within:opacity-100"/>
              </span>
              <InputError :message="form.errors.name" />
            </div>

            <div class="flex flex-row justify-between items-center gap-2 overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiBuildingLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Username</span>
              </div>
              <span 
                role="button"
                tabindex="0"
                title="Copy to clipboard"
                @click="copyToClipboard(props.user.username, 'Username')"
                @keydown.enter.prevent="copyToClipboard(props.user.username, 'Username')"
                @keydown.space.prevent="copyToClipboard(props.user.username, 'Username')"
                class="cursor-pointer tracking-widest bg-custom-bg dark:bg-custom-bg-light px-2 rounded-md font-mono mr-1 font-normal"
              >
                {{ props.user.username ?? 'This will be generated automatically.' }}
              </span>
            </div>
          </div>

        </div>
      </div>

      <Separator orientation="vertical"/>

      <div class="flex-1">
        <CardSeparator title="Contact Info" />
        
        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
          <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
            <div class="inline-flex gap-2 items-center">
              <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
              <Label for="route_name_sidebar">Email</Label>
            </div>
            <span class="flex flex-row items-center">
              <Input
                  id="route_name_sidebar"
                  :model-value="form.email"
                  placeholder="Enter email"
                  variant="inline-edit"
              />
              <RiEditLine class="shrink-0 h-4 w-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:w-4 group-hover:ml-2 group-hover:opacity-100 group-focus-within:w-4 group-focus-within:ml-2 group-focus-within:opacity-100"/>
            </span>
            <InputError :message="form.errors.email" />
          </div>

          <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
            <div class="inline-flex gap-2 items-center">
              <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
              <Label for="route_name_sidebar">Phone</Label>
            </div>
            <span class="flex flex-row items-center">
              <Input
                  id="route_name_sidebar"
                  :model-value="form.phone_number"
                  placeholder="Enter user name"
                  variant="inline-edit"
              />
              <RiEditLine class="shrink-0 h-4 w-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:w-4 group-hover:ml-2 group-hover:opacity-100 group-focus-within:w-4 group-focus-within:ml-2 group-focus-within:opacity-100"/>
            </span>
            <InputError :message="form.errors.phone_number" />
          </div>
        </div>

        <CardSeparator title="Access & Assignment Info" />

        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
          <div class="flex flex-row justify-between items-center gap-2 overflow-hidden">
            <div class="inline-flex gap-2 items-center">
              <RiBuildingLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
              <span>User Type</span>
            </div>
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <span class="line-clamp-1 text-ellipsis capitalize" tabindex="0">
                    {{ resolvedType ?? 'This will be generated automatically.' }}
                  </span>
                </TooltipTrigger>
                <TooltipContent>
                  User type is automatically based on the selected role.
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>

          <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
            <div class="inline-flex gap-2 items-center">
              <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
              <Label for="gate_id_sidebar">Role</Label>
            </div>
            <span class="flex flex-row items-center">
              <Select v-model="form.role">
                <SelectTrigger
                  id="role"
                  variant="inline-edit"
                >
                  <SelectValue
                    placeholder="Select a role type..."
                    class="capitalize"
                  />
                </SelectTrigger>
                <SelectContent>
                  <SelectLabel>Internal</SelectLabel>
                  <SelectItem
                    v-for="role in props.roles.filter((item) => item.type === 'internal')"
                    :key="role.id"
                    :value="role.name"
                    class="capitalize"
                  >
                    {{ role.name }}
                  </SelectItem>

                  <SelectLabel>External</SelectLabel>
                  <SelectItem
                    v-for="role in props.roles.filter((item) => item.type === 'external')"
                    :key="role.id"
                    :value="role.name"
                    class="capitalize"
                  >
                    {{ role.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </span>
            <InputError :message="form.errors.role" />
          </div>
        </div>

        <CardSeparator v-if="resolvedType === 'external'" title="Company Info" />

        <div v-if="resolvedType === 'external'" class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
          <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
            <div class="inline-flex gap-2 items-center">
              <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
              <Label for="company_search">Company</Label>
            </div>
            <span class="flex flex-row items-center">
              <!-- `v-model:open` mirrors Select's expanded/collapsed state into
                   companySelectOpen, which drives the focus + placeholder
                   behavior below. -->
              <Select v-model="form.company_id" v-model:open="companySelectOpen">
                <SelectTrigger
                  id="company"
                  variant="inline-edit"
                >
                  <SelectValue
                    placeholder="Select a company..."
                    class="capitalize"
                  />
                </SelectTrigger>
                <SelectContent>
                  <div class="p-2 relative w-full">
                    <SearchInput
                      id="company_search"
                      ref="companySearchInputRef"
                      v-model="companySearch"
                      placeholder="Search by name or code..."
                      @keydown.stop
                    />
                  </div>
                  <span
                    v-if="filteredCompanies.length === 0"
                      class="px-2 pb-3 text-sm text-custom-shadow"
                  >
                    No companies found.
                  </span>
                  <SelectItem
                    v-for="company in filteredCompanies"
                    :key="company.id"
                    :value="String(company.id)"
                    class="capitalize group"
                  >
                    <span>{{ company.company_name }}</span>
                    <span
                      class="group-hover:text-custom-shadow cursor-pointer tracking-widest bg-custom-bg dark:bg-custom-bg-light px-2 rounded-md font-mono mr-1 font-normal"
                    >
                      {{ company.company_code }}
                    </span>
                  </SelectItem>
                </SelectContent>
              </Select>
            </span>

            <InputError :message="form.errors.company_id" />
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
              {{ formatDate(user.created_at) }}
              <!-- <span v-if="user.creator?.name" class="text-custom-accent-3"> • </span>
              {{ user.creator?.name ?? '—' }} -->
            </span>
          </div>

          <div class="flex flex-row justify-between items-center overflow-hidden">
            <div class="inline-flex gap-2 items-center">
              <RiTimeLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
              <span>Updated</span>
            </div>
            <span class="line-clamp-1 text-ellipsis">
              {{ user.updated_at_human }}
              <!-- <span v-if="user.updater?.name" class="text-custom-accent-3"> • </span>
              {{ user.updater?.name ?? '—' }} -->
            </span>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
