<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';

import { create, index, store } from '@/routes/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, UserPlus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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
    { title: 'Create User', href: create().url },
];

const companySearch = ref('');
const roleSearch = ref('');

const includesText = (value: string, query: string) =>
    value.toLowerCase().includes(query.trim().toLowerCase());

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
    <Head title="Create User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 items-start justify-center p-4">
            <Card class="w-full">
                <CardHeader class="py-0">
                    <div class="flex items-center gap-4">
                        <div
                            class="relative h-32 w-32 shrink-0 overflow-hidden rounded-lg border-2 bg-primary shadow-sm flex items-center justify-center"
                        >
                            <UserPlus class="h-10 w-10 text-white" />
                        </div>

                        <div class="gap-2 w-full">
                            <div class="flex flex-row gap-2 pb-2 w-full items-center">
                                <h1 class="text-2xl leading-tight font-bold tracking-tight">
                                    Create User
                                </h1>
                                <div class="ml-2 flex flex-1 items-center">
                                    <hr class="h-px w-full border border-rose-500" />
                                    <div class="border-7 border-rose-500 rounded-xs">
                                        <div class="border-3 border-white rounded-xs"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between items-start">
                                <p class="text-sm text-muted-foreground">
                                    Add a new user account and assign access. Default password is
                                    <span class="font-medium text-foreground">pitx@123</span>. New users are created
                                    as <span class="font-medium text-foreground">active</span>. Fields marked with
                                    <span class="font-medium text-red-500">{{ requiredMark }}</span>
                                    are required.
                                </p>
                                <div class="flex shrink-0 items-center gap-2 ml-4">
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

                <CardContent class="border-t border-slate-100">
                    <form class="space-y-8" @submit.prevent="submit">
                        <div class="space-y-4">
                            <div class="space-y-1">
                                <p class="text-sm font-medium">Account Information</p>
                                <p class="text-xs text-muted-foreground">
                                    Basic details used for login and contact.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label class="flex items-center gap-1">
                                        Full Name
                                        <span class="text-red-500">
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

                                <div class="space-y-2">
                                    <Label class="flex items-center gap-1">
                                        Email
                                        <span class="text-red-500">
                                            {{ requiredMark }}
                                        </span>
                                    </Label>
                                    <Input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="e.g. juan@example.com"
                                        autocomplete="email"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        This will be used for login and notifications.
                                    </p>
                                    <InputError :message="form.errors.email" />
                                </div>

                                <div class="space-y-2">
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

                        <div class="space-y-4 border-t pt-6">
                            <div class="space-y-1">
                                <p class="text-sm font-medium">Access & Assignment</p>
                                <p class="text-xs text-muted-foreground">
                                    Choose the user type and assign the correct role.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label class="flex items-center gap-1">
                                        User Type
                                        <span class="text-red-500">
                                            {{ requiredMark }}
                                        </span>
                                    </Label>

                                    <Select v-model="form.type">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Select internal/external" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>User Types</SelectLabel>
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

                                <div
                                    v-if="form.type === 'external'"
                                    class="space-y-2"
                                >
                                    <Label class="flex items-center gap-1">
                                        Company
                                        <span class="text-red-500">
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

                                                <SelectLabel>Companies</SelectLabel>

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
                                                    class="px-2 py-1 text-sm text-muted-foreground"
                                                >
                                                    No companies found.
                                                </p>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>

                                    <InputError :message="form.errors.company_id" />
                                </div>

                                <div v-if="form.type" class="space-y-2">
                                    <Label class="flex items-center gap-1">
                                        Role
                                        <span class="text-red-500">
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

                                                <SelectLabel>Roles</SelectLabel>

                                                <SelectItem
                                                    v-for="role in filteredRoles"
                                                    :key="role.id"
                                                    :value="role.name"
                                                >
                                                    {{ role.name }}
                                                </SelectItem>

                                                <p
                                                    v-if="filteredRoles.length === 0"
                                                    class="px-2 py-1 text-sm text-muted-foreground"
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

                        <div class="flex items-center justify-end gap-2 border-t pt-4">
                            <Button type="button" variant="outline" as-child>
                                <Link :href="index().url">Cancel</Link>
                            </Button>

                            <Button type="submit" variant="outline" :disabled="form.processing" class="rounded-lg bg-primary text-primary-foreground hover:text-primary-foreground hover:bg-primary/90 cursor-pointer">
                                <UserPlus class="h-4 w-4" />
                                {{ form.processing ? 'Creating...' : 'Create User' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
