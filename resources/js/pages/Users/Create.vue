<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardDescription,
    CardTitle,
} from '@/components/ui/card';
import CardSeparator from '@/components/ui/_card-separator/CardSeparator.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';

import { useClipboard } from '@vueuse/core';
import { toast } from 'vue-sonner';

import { create, index, store } from '@/routes/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { LeadPanel, MainPanel } from '@/components/ui/_panels';
import { LeadingCard } from '@/components/ui/_leading-card';
import Separator from '@/components/ui/separator/Separator.vue';

import {
    RiFileCheckLine,
} from 'vue-remix-icons';

type Company = {
    id: number;
    company_name: string;
    company_code: string;
};

type Role = {
    id: number;
    name: string;
    type: 'internal' | 'external';
};

const props = defineProps<{
    companies: Company[];
    roles: Role[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: index().url },
    { title: 'New User', href: create().url },
];

const companySearch = ref('');
const roleSearch = ref('');

const includesText = (value: string, query: string) =>
    value.toLowerCase().includes(query.trim().toLowerCase());

const { copy } = useClipboard({ legacy: true });

const form = useForm({
    name: '',
    email: '',
    phone_number: '',
    type: '' as '' | 'internal' | 'external',
    company_id: null as number | null,
    role: '' as string,
});

const rolesByType = computed(() => {
    if (!form.type) return [];
    return props.roles.filter((r) => r.type === form.type);
});

const filteredCompanies = computed(() =>
    props.companies.filter((c) => {
        const q = companySearch.value;
        return (
            includesText(c.company_name, q) || includesText(c.company_code, q)
        );
    }),
);

// Looks up the full Company object for the currently selected form.company_id,
// so the "Company" preview span can show its name/code instead of just the id.
const selectedCompany = computed(() =>
    props.companies.find((c) => c.id === form.company_id) ?? null,
);

const filteredRoles = computed(() =>
    rolesByType.value.filter((r) => includesText(r.name, roleSearch.value)),
);

watch(
    () => form.type,
    (newType) => {
        form.role = '';
        roleSearch.value = '';

        if (newType !== 'external') {
            form.company_id = null;
            companySearch.value = '';
        }
    },
);

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
    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            companySearch.value = '';
            roleSearch.value = '';
        },
    });
}

const requiredMark = '*';
</script>

<template>
    <Head title="Add User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <MainPanel>
            <!-- TODO: this leadpanel component would have to adjust when the mainpanels come around -->
            <LeadPanel class="h-fit pb-0">
                <LeadingCard
                    title="New user"
                    description="Add a new user account into the system."
                    variant="entity-crud"
                    :back="index().url"
                    :more="false"
                    class=""
                >
                </LeadingCard>
            </LeadPanel>

            <!-- TODO: the padding of leading card will have to adjust in being inside mainpanel -->
            <div class="grid lg:grid-cols-3 gap-4 w-full h-fit p-6 pt-0">
                <Card
                    class="col-span-1"
                >
                    <CardHeader>
                        <CardTitle>Review</CardTitle>
                        <CardDescription>Review new user details before confirming.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <CardSeparator title="Account Info" />

                        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                            <div class="flex flex-row justify-between items-center">
                                <div class="inline-flex gap-2 items-center">
                                    <RiFileCheckLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                                    <span>Name</span>
                                </div>
                                <span class="inline-flex gap-2 items-center overflow-hidden">
                                    <span
                                        role="button"
                                        tabindex="0"
                                        title="Copy to clipboard"
                                        @click="copyToClipboard(form.name, 'Name')"
                                        @keydown.enter.prevent="copyToClipboard(form.name, 'Name')"
                                        @keydown.space.prevent="copyToClipboard(form.name, 'Name')"
                                        class="cursor-pointer line-clamp-1 text-ellipsis"
                                    >
                                        {{ form.name || '—' }}
                                    </span>
                                </span>
                            </div>

                            <div class="flex flex-row justify-between items-center">
                                <div class="inline-flex gap-2 items-center">
                                    <RiFileCheckLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                                    <span>Email</span>
                                </div>
                                <span class="inline-flex gap-2 items-center overflow-hidden">
                                    <span
                                        role="button"
                                        tabindex="0"
                                        title="Copy to clipboard"
                                        @click="copyToClipboard(form.email, 'Email')"
                                        @keydown.enter.prevent="copyToClipboard(form.email, 'Email')"
                                        @keydown.space.prevent="copyToClipboard(form.email, 'Email')"
                                        class="cursor-pointer line-clamp-1 text-ellipsis"
                                    >
                                        {{ form.email || '—' }}
                                    </span>
                                </span>
                            </div>

                            <div class="flex flex-row justify-between items-center">
                                <div class="inline-flex gap-2 items-center">
                                    <RiFileCheckLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                                    <span>Phone</span>
                                </div>
                                <span class="inline-flex gap-2 items-center overflow-hidden">
                                    <span
                                        role="button"
                                        tabindex="0"
                                        title="Copy to clipboard"
                                        @click="copyToClipboard(form.phone_number, 'Phone')"
                                        @keydown.enter.prevent="copyToClipboard(form.phone_number, 'Phone')"
                                        @keydown.space.prevent="copyToClipboard(form.phone_number, 'Phone')"
                                        class="cursor-pointer line-clamp-1 text-ellipsis"
                                    >
                                        {{ form.phone_number || '—' }}
                                    </span>
                                </span>
                            </div>
                        </div>

                        <CardSeparator title="Access & Assignment Info" />

                        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                            <div class="flex flex-row justify-between items-center">
                                <div class="inline-flex gap-2 items-center">
                                    <RiFileCheckLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                                    <span>User Type</span>
                                </div>
                                <span class="inline-flex gap-2 items-center overflow-hidden">
                                    <span
                                        role="button"
                                        tabindex="0"
                                        title="Copy to clipboard"
                                        @click="copyToClipboard(form.type, 'User Type')"
                                        @keydown.enter.prevent="copyToClipboard(form.type, 'User Type')"
                                        @keydown.space.prevent="copyToClipboard(form.type, 'User Type')"
                                        class="cursor-pointer line-clamp-1 text-ellipsis capitalize"
                                    >
                                        {{ form.type || '—' }}
                                    </span>
                                </span>
                            </div>

                            <div v-if="form.type" class="flex flex-row justify-between items-center">
                                <div class="inline-flex gap-2 items-center">
                                    <RiFileCheckLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                                    <span>Role</span>
                                </div>
                                <span class="inline-flex gap-2 items-center overflow-hidden">
                                    <span
                                        role="button"
                                        tabindex="0"
                                        title="Copy to clipboard"
                                        @click="copyToClipboard(form.role, 'Role')"
                                        @keydown.enter.prevent="copyToClipboard(form.role, 'Role')"
                                        @keydown.space.prevent="copyToClipboard(form.role, 'Role')"
                                        class="cursor-pointer line-clamp-1 text-ellipsis capitalize"
                                    >
                                        {{ form.role || '—' }}
                                    </span>
                                </span>
                            </div>
                        </div>

                        <CardSeparator v-if="form.type === 'external'" title="Company Info" />

                        <div v-if="form.type === 'external'" class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                            <div v-if="form.type === 'external'" class="flex flex-row justify-between items-center">
                                <div class="inline-flex gap-2 items-center">
                                    <RiFileCheckLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                                    <span>Company</span>
                                </div>
                                <span class="inline-flex gap-2 items-center overflow-hidden">
                                    <span
                                        role="button"
                                        tabindex="0"
                                        title="Copy to clipboard"
                                        @click="copyToClipboard(selectedCompany?.company_name, 'Company')"
                                        @keydown.enter.prevent="copyToClipboard(selectedCompany?.company_name, 'Company')"
                                        @keydown.space.prevent="copyToClipboard(selectedCompany?.company_name, 'Company')"
                                        class="cursor-pointer line-clamp-1 text-ellipsis capitalize"
                                    >
                                        {{ selectedCompany?.company_name || '—' }}
                                        <span
                                            v-if="selectedCompany"
                                            class="group-hover:text-custom-shadow cursor-pointer tracking-widest bg-custom-bg dark:bg-custom-bg-light px-2 rounded-md font-mono mr-1 font-normal"
                                        >
                                            {{ selectedCompany.company_code }}
                                        </span>
                                    </span>
                                </span>
                            </div>
                        </div>

                        <p class="text-sm text-custom-shadow/80 mt-4 flex flex-col">
                            <span>
                                Default password is <span class="font-semibold">pitx@123</span>.
                            </span>
                            <span>
                                New users are created with <span class="font-semibold">active</span> status.
                            </span>
                        </p>
                    </CardContent>
                </Card>

                <Card
                    class="col-span-2"
                >
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                        <CardDescription>Fields with <span class="text-destructive font-semibold">*</span> are required.</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-row gap-4">
                        <div class="flex-1">
                            <form @submit.prevent="submit">
                                <CardSeparator title="Account Info" />

                                <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                                    <div class="space-y-2">
                                        <Label class="flex items-center gap-1">
                                            Name
                                            <span class="text-destructive">
                                                {{ requiredMark }}
                                            </span>
                                        </Label>
                                        <Input
                                            v-model="form.name"
                                            placeholder="e.g. Juan Dela Cruz"
                                            autocomplete="name"
                                        />
                                        <InputError :message="form.errors.name" />
                                    </div>

                                    <div class="flex flex-row gap-x-2">
                                        <div class="space-y-2 flex-1">
                                            <Label class="flex items-center gap-1">
                                                Email
                                                <span class="text-destructive">
                                                    {{ requiredMark }}
                                                </span>
                                            </Label>
                                            <Input
                                                v-model="form.email"
                                                type="email"
                                                placeholder="e.g. juan@example.com"
                                                autocomplete="email"
                                            />
                                            <InputError :message="form.errors.email" />
                                        </div>

                                        <div class="space-y-2 flex-1">
                                            <Label>Phone Number</Label>
                                            <Input
                                                v-model="form.phone_number"
                                                placeholder="e.g. 09xxxxxxxxx"
                                                autocomplete="tel"
                                            />
                                            <InputError :message="form.errors.phone_number" />
                                        </div>
                                    </div>
                                </div>

                                <CardSeparator title="Access & Assignment Info" />

                                <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                                    <div class="grid grid-cols-2 gap-x-2">
                                        <div class="space-y-2 col-span-1">
                                            <Label class="flex items-center gap-1">
                                                User Type
                                                <span class="text-destructive">
                                                    {{ requiredMark }}
                                                </span>
                                            </Label>

                                            <Select v-model="form.type">
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Select internal/external" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectGroup>
                                                        <SelectItem value="internal">
                                                            Internal
                                                        </SelectItem>
                                                        <SelectItem value="external">
                                                            External
                                                        </SelectItem>
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>

                                            <InputError :message="form.errors.type" />
                                        </div>

                                        <div v-if="form.type" class="space-y-2 col-span-1">
                                            <Label class="flex items-center gap-1">
                                                Role
                                                <span class="text-destructive">
                                                    {{ requiredMark }}
                                                </span>
                                            </Label>

                                            <Select v-model="form.role">
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Select a role" />
                                                </SelectTrigger>

                                                <SelectContent>
                                                    <SelectGroup>
                                                        <div class="p-2">
                                                            <Input
                                                                v-model="roleSearch"
                                                                placeholder="Search role..."
                                                                autocomplete="off"
                                                                @keydown.stop
                                                            />
                                                        </div>

                                                        <SelectItem
                                                            v-for="role in filteredRoles"
                                                            :key="role.id"
                                                            :value="role.name"
                                                            class="capitalize"
                                                        >
                                                            {{ role.name }}
                                                        </SelectItem>

                                                        <p
                                                            v-if="filteredRoles.length === 0"
                                                            class="px-2 py-1 text-sm text-custom-shadow/80"
                                                        >
                                                            No roles found.
                                                        </p>
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>

                                            <InputError :message="form.errors.role" />
                                        </div>
                                    </div>
                                </div>

                                <CardSeparator v-if="form.type === 'external'" title="Company Info" />

                                <div v-if="form.type === 'external'" class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                                    <div class="grid grid-cols-2 gap-x-2">
                                        <div
                                            v-if="form.type === 'external'"
                                            class="space-y-2 col-span-1"
                                        >
                                            <Label class="flex items-center gap-1">
                                                Company
                                                <span class="text-destructive">
                                                    {{ requiredMark }}
                                                </span>
                                            </Label>

                                            <Select v-model="form.company_id">
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Select a company" />
                                                </SelectTrigger>

                                                <SelectContent>
                                                    <SelectGroup>
                                                        <div class="p-2">
                                                            <Input
                                                                v-model="companySearch"
                                                                placeholder="Search by name or code..."
                                                                autocomplete="off"
                                                                @keydown.stop
                                                            />
                                                        </div>

                                                        <SelectItem
                                                            v-for="company in filteredCompanies"
                                                            :key="company.id"
                                                            :value="company.id"
                                                        >
                                                            {{ company.company_name }} -
                                                            {{ company.company_code }}
                                                        </SelectItem>

                                                        <p
                                                            v-if="filteredCompanies.length === 0"
                                                            class="px-2 py-1 text-sm text-custom-shadow/80"
                                                        >
                                                            No companies found.
                                                        </p>
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>

                                            <InputError :message="form.errors.company_id" />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-2">
                                    <Button type="button" variant="float" as-child>
                                        <Link :href="index().url">Cancel</Link>
                                    </Button>

                                    <Button type="submit" variant="float-primary" :disabled="form.processing">
                                        {{ form.processing ? 'Adding...' : 'Add new user' }}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </MainPanel>
    </AppLayout>
</template>
