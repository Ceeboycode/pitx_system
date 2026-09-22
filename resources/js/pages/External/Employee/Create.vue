<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';
import { index, store } from '@/routes/employee-users';

import CardSeparator from '@/components/ui/_card-separator/CardSeparator.vue';
import { InputMessage } from '@/components/ui/_input-message';
import { LeadingCard } from '@/components/ui/_leading-card';
import { LeadPanel, MainPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import { PreviewCard, ReviewCardRow } from '@/components/ui/_preview-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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

type Company = {
    id: number;
    company_name: string;
    company_code?: string | null;
    status: string;
    logo_url?: string | null;
};

type AuthUser = {
    id: number;
    name: string;
    username: string;
    email: string;
};

type RoleItem = {
    name: string;
    guard_name: string;
    type: string;
};

const props = defineProps<{
    company: Company;
    user: AuthUser;
    roles: RoleItem[];
    defaultStatus: string;
    nextUsernamePreview: string;
}>();

const canCreateEmployee = can('external_users.create');

const requiredMark = '*';
const defaultPassword = 'pitx@123';

const form = useForm({
    name: '',
    email: '',
    phone_number: '',
    role: '',
});

function humanize(value?: string | null) {
    if (!value) return '—';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function statusClass(status?: string | null) {
    if (status === 'active')
        return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'pending')
        return 'bg-amber-100 text-amber-700 border-amber-200';
    if (status === 'suspended')
        return 'bg-rose-100 text-rose-600 border-rose-200';
    return 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(status?: string | null) {
    if (status === 'active') return 'bg-emerald-500';
    if (status === 'pending') return 'bg-amber-500';
    if (status === 'suspended') return 'bg-rose-500';
    return 'bg-slate-400';
}

function submit() {
    if (!canCreateEmployee) return;

    form.post(store().url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Add New Employee" />

    <ExternalLayout :company="company" :user="user">
        <PanelLayout>
            <MainPanel>
                <LeadPanel class="h-fit p-0">
                    <LeadingCard
                        title="Add New Employee"
                        :description="`Add a new employee account to ${props.company.company_name}.`"
                        variant="entity-crud"
                        entity="employee"
                        :back="index().url"
                        :more="false"
                    />
                </LeadPanel>

                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                        <CardDescription>
                            Fields with <span class="font-semibold text-destructive">*</span> are required.
                            The username is generated automatically on save.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form @submit.prevent="submit">
                            <CardSeparator title="Account Info" />

                            <div class="my-2 flex flex-col gap-2 text-sm text-custom-shadow">
                                <div class="space-y-2">
                                    <Label for="name" class="flex items-center gap-1">
                                        Full Name
                                        <span class="text-destructive">{{ requiredMark }}</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="e.g. Juan Dela Cruz"
                                        autocomplete="off"
                                    />
                                    <InputMessage variant="destructive" :message="form.errors.name" />
                                </div>

                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="email">Email Address</Label>
                                        <Input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            placeholder="e.g. juan@example.com"
                                            autocomplete="off"
                                        />
                                        <InputMessage variant="destructive" :message="form.errors.email" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="phone_number">Phone Number</Label>
                                        <Input
                                            id="phone_number"
                                            v-model="form.phone_number"
                                            placeholder="e.g. 09xxxxxxxxx"
                                            autocomplete="tel"
                                        />
                                        <InputMessage variant="destructive" :message="form.errors.phone_number" />
                                    </div>
                                </div>
                            </div>

                            <CardSeparator title="Role & Access" />

                            <div class="my-2 flex flex-col gap-2 text-sm text-custom-shadow">
                                <div class="space-y-2">
                                    <Label for="role" class="flex items-center gap-1">
                                        Role
                                        <span class="text-destructive">{{ requiredMark }}</span>
                                    </Label>
                                    <Select v-model="form.role">
                                        <SelectTrigger id="role" class="w-full">
                                            <SelectValue placeholder="Select a role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem
                                                    v-for="role in props.roles"
                                                    :key="role.name"
                                                    :value="role.name"
                                                >
                                                    {{ humanize(role.name) }}
                                                </SelectItem>
                                                <p
                                                    v-if="props.roles.length === 0"
                                                    class="px-2 py-1 text-sm text-custom-shadow/80"
                                                >
                                                    No roles found.
                                                </p>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <p class="text-sm text-custom-shadow/80">
                                        {{ props.roles.length }} external role{{ props.roles.length === 1 ? '' : 's' }}
                                        available. Assign one per employee.
                                    </p>
                                    <InputMessage
                                        v-if="props.roles.length === 0"
                                        variant="warning"
                                        title="No external roles found"
                                        message="Ask a PITX administrator to set up external roles before adding employees."
                                    />
                                    <InputMessage variant="destructive" :message="form.errors.role" />
                                </div>
                            </div>

                            <InputMessage variant="info" class="lg:hidden">
                                New employees are created with
                                <span class="font-semibold">{{ props.defaultStatus }}</span> status.
                                Their default password is
                                <span class="font-mono font-semibold">{{ defaultPassword }}</span>,
                                which they change on first login.
                            </InputMessage>

                            <div class="mt-4 flex items-center justify-end gap-2">
                                <Button type="button" variant="float" as-child>
                                    <Link :href="index().url">Cancel</Link>
                                </Button>

                                <Button
                                    v-if="canCreateEmployee"
                                    type="submit"
                                    variant="float-primary"
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'Adding...' : 'Add Employee' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </MainPanel>

            <SidePanel class="hidden lg:flex">
                <PreviewCard
                    title="Review"
                    description="Review new employee details before confirming."
                    :closable="false"
                >
                    <CardSeparator title="Account Info" />

                    <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                        <ReviewCardRow label="Name" :value="form.name" />
                        <ReviewCardRow label="Email" :value="form.email" />
                        <ReviewCardRow label="Phone" :value="form.phone_number" />
                        <ReviewCardRow
                            label="Username"
                            :value="props.nextUsernamePreview"
                            value-class="font-mono"
                        />
                    </div>

                    <CardSeparator title="Role & Access" />

                    <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                        <ReviewCardRow label="Role" :value="form.role ? humanize(form.role) : ''" />
                        <ReviewCardRow label="Status" :value="humanize(props.defaultStatus)">
                            <Badge :class="['gap-1.5', statusClass(props.defaultStatus)]">
                                <span :class="['h-1.5 w-1.5 rounded-full', statusDot(props.defaultStatus)]" />
                                {{ humanize(props.defaultStatus) }}
                            </Badge>
                        </ReviewCardRow>
                    </div>

                    <CardSeparator title="Company Info" />

                    <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                        <ReviewCardRow label="Company" :value="props.company.company_name">
                            {{ props.company.company_name }}
                            <span
                                v-if="props.company.company_code"
                                class="mr-1 rounded-md bg-custom-bg px-2 font-mono font-normal tracking-widest dark:bg-custom-bg-light"
                            >
                                {{ props.company.company_code }}
                            </span>
                        </ReviewCardRow>
                        <ReviewCardRow label="Company Status" :value="humanize(props.company.status)">
                            <Badge :class="['gap-1.5', statusClass(props.company.status)]">
                                <span :class="['h-1.5 w-1.5 rounded-full', statusDot(props.company.status)]" />
                                {{ humanize(props.company.status) }}
                            </Badge>
                        </ReviewCardRow>
                    </div>

                    <p class="mt-4 flex flex-col text-sm text-custom-shadow/80">
                        <span>
                            Default password is <span class="font-semibold">{{ defaultPassword }}</span>.
                        </span>
                        <span>
                            New employees are created with
                            <span class="font-semibold">{{ props.defaultStatus }}</span> status.
                        </span>
                    </p>
                </PreviewCard>
            </SidePanel>
        </PanelLayout>
    </ExternalLayout>
</template>
