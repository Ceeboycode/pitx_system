<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import InputError from '@/components/InputError.vue';
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
import { Separator } from '@/components/ui/separator';

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
            <Card class="mx-5">
                <CardHeader>
                    <CardTitle>Edit User</CardTitle>
                    <CardDescription>
                        Update the user details. Username changes only when the selected role type or company changes.
                    </CardDescription>
                </CardHeader>

                <Separator />

                <CardContent class="space-y-6 pt-6">
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

                <Separator />

                <CardFooter class="flex justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="index().url">Cancel</Link>
                    </Button>

                    <Button :disabled="form.processing" @click="submit">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save changes' }}
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
