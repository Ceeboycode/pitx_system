<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
    };
    roles: Role[];
    companies: Company[];
    selectedRole: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: index().url },
    { title: 'Edit', href: '#' },
];

const companySearch = ref('');

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

const selectedCompany = computed(() =>
    props.companies.find((company) => String(company.id) === form.company_id) ?? null,
);

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

function initials(name: string) {
    const parts = name.trim().split(/\s+/).filter(Boolean).slice(0, 2);
    return parts.map((part) => part.charAt(0).toUpperCase()).join('') || 'U';
}

function typeBadgeClass(type: string | null) {
    switch (type) {
        case 'internal':
            return 'border-blue-200 bg-blue-100 text-blue-700';
        case 'external':
            return 'border-amber-200 bg-amber-100 text-amber-700';
        default:
            return 'border-border bg-muted text-muted-foreground';
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
    <Head title="Edit User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <Card>
                <CardHeader class="py-0">
                    <div class="flex items-center gap-4">
                        <div
                            class="relative h-32 w-32 shrink-0 overflow-hidden rounded-lg border-2 shadow-sm"
                        >
                            <div class="flex h-full w-full items-center justify-center bg-primary">
                                <span class="text-2xl font-bold text-primary-foreground">
                                    {{ initials(user.name) }}
                                </span>
                            </div>
                        </div>

                        <div class="gap-2 w-full">
                            <div class="flex flex-row gap-2 pb-2 w-full items-center">
                                <h1 class="text-2xl leading-tight font-bold tracking-tight">
                                    {{ user.name }}
                                </h1>
                                <div class="ml-2 flex flex-1 items-center">
                                    <hr class="h-px w-full border border-rose-500" />
                                    <div class="border-7 border-rose-500 rounded-xs">
                                        <div class="border-3 border-white rounded-xs"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge class="border-0 bg-muted font-mono text-foreground">
                                        {{ user.username ?? 'No username' }}
                                    </Badge>
                                    <Badge :class="['border capitalize', typeBadgeClass(user.type)]">
                                        {{ user.type }}
                                    </Badge>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <Button
                                        as-child
                                        variant="outline"
                                        class="rounded-lg bg-card border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer"
                                    >
                                        <Link :href="index().url">
                                            <ArrowLeft class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="space-y-6 pt-6 border-t border-slate-100">
                    <div class="space-y-2">
                        <Label>Current Username</Label>
                        <div class="rounded-md border bg-muted/40 px-3 py-2 text-sm">
                            {{ props.user.username ?? 'Will be generated automatically' }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Internal roles use year-based usernames. External roles use company-code usernames.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="e.g. John Doe"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="e.g. john@email.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="space-y-2">
                        <Label for="phone_number">Phone Number</Label>
                        <Input
                            id="phone_number"
                            v-model="form.phone_number"
                            placeholder="e.g. 09123456789"
                        />
                        <InputError :message="form.errors.phone_number" />
                    </div>

                    <div class="space-y-2">
                        <Label for="role">Role</Label>
                        <select
                            id="role"
                            v-model="form.role"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="">Select one role</option>

                            <optgroup label="Internal Roles">
                                <option
                                    v-for="role in props.roles.filter((item) => item.type === 'internal')"
                                    :key="role.id"
                                    :value="role.name"
                                >
                                    {{ role.name }}
                                </option>
                            </optgroup>

                            <optgroup label="External Roles">
                                <option
                                    v-for="role in props.roles.filter((item) => item.type === 'external')"
                                    :key="role.id"
                                    :value="role.name"
                                >
                                    {{ role.name }}
                                </option>
                            </optgroup>
                        </select>
                        <InputError :message="form.errors.role" />
                    </div>

                    <div class="space-y-2">
                        <Label>User Type</Label>
                        <div class="rounded-md border bg-muted/40 px-3 py-2 text-sm capitalize">
                            {{ resolvedType }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            User type is automatically based on the selected role.
                        </p>
                    </div>

                    <div v-if="resolvedType === 'external'" class="space-y-2">
                        <Label for="company_search">Search Company</Label>
                        <Input
                            id="company_search"
                            v-model="companySearch"
                            placeholder="Search by name or code..."
                            autocomplete="off"
                        />

                        <div class="space-y-2">
                            <Label for="company_id">Company</Label>
                            <select
                                id="company_id"
                                v-model="form.company_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="">Select a company</option>
                                <option
                                    v-for="company in filteredCompanies"
                                    :key="company.id"
                                    :value="String(company.id)"
                                >
                                    {{ company.company_name }} - {{ company.company_code }}
                                </option>
                            </select>

                            <p
                                v-if="filteredCompanies.length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                No companies found.
                            </p>

                            <p v-if="selectedCompany" class="text-xs text-muted-foreground">
                                Selected company: {{ selectedCompany.company_name }} ({{ selectedCompany.company_code }})
                            </p>

                            <InputError :message="form.errors.company_id" />
                        </div>
                    </div>
                </CardContent>

                <CardFooter class="flex justify-end gap-2 border-t border-slate-100">
                    <Button variant="outline" as-child>
                        <Link :href="index().url">Cancel</Link>
                    </Button>

                    <Button :disabled="form.processing" @click="submit">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save changes' }}
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
