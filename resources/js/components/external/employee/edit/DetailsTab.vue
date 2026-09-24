<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import { can } from '@/lib/can';
import { update } from '@/routes/employee-users';

import { CardSeparator } from '@/components/ui/_card-separator';
import EditableField from '@/components/ui/_field/EditableField.vue';
import { InputMessage } from '@/components/ui/_input-message';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import {
    RiCalendarLine,
    RiEditLine,
    RiMailLine,
    RiPhoneLine,
    RiShieldUserLine,
    RiUserLine,
} from 'vue-remix-icons';

type RoleItem = {
    name: string;
    guard_name: string;
    type: string;
};

type EmployeeRole = {
    id: number;
    name: string;
};

type Employee = {
    id: number;
    username: string;
    name: string;
    email?: string | null;
    phone_number?: string | null;
    avatar?: string | null;
    status: string;
    created_at?: string | null;
    roles?: EmployeeRole[];
};

const props = defineProps<{
    employee: Employee;
    roles: RoleItem[];
    selectedRole?: string | null;
}>();

// Drives every EditableField below: view-only accounts (no external_users.update
// permission) see static text instead of inputs, and never see the Save/Cancel
// actions or validation errors. Matches Users/Edit.vue: editing your own basic
// details is allowed, self-action is only blocked for status/password/archive.
const canEdit = can('external_users.update');

const form = useForm({
    name: props.employee.name ?? '',
    email: props.employee.email ?? '',
    phone_number: props.employee.phone_number ?? '',
    role: props.selectedRole ?? '',
});

function humanize(value?: string | null) {
    if (!value) return '—';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatDate(value?: string | null) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

const initials = props.employee.name
    .split(' ')
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('');

function submit() {
    if (!canEdit) return;

    form.put(update(props.employee.id).url, { preserveScroll: true });
}

function resetForm() {
    form.reset();
    form.clearErrors();
}
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-start justify-between gap-4">
            <div class="flex flex-col">
                <CardTitle>Employee</CardTitle>
                <CardDescription>{{ canEdit ? 'Manage employee details.' : 'View employee details.' }}</CardDescription>
            </div>
            <div v-if="canEdit" class="flex flex-row items-center gap-2">
                <Button
                    :variant="!form.isDirty || form.processing ? 'disabled' : 'float'"
                    :disabled="!form.isDirty || form.processing"
                    @click="resetForm"
                >
                    Cancel
                </Button>
                <Button
                    :variant="!form.isDirty || form.processing ? 'disabled' : 'float-primary'"
                    size="icon-text"
                    :disabled="!form.isDirty || form.processing"
                    @click="submit"
                >
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </Button>
            </div>
        </CardHeader>

        <CardContent class="flex flex-row gap-4">
            <div class="max-w-1/3 flex-1">
                <div class="my-2 flex flex-col items-center gap-2">
                    <img
                        v-if="employee.avatar"
                        :src="employee.avatar"
                        :alt="`${employee.name} avatar`"
                        class="h-24 w-24 rounded-full object-cover"
                    />
                    <div v-else class="flex h-24 w-24 items-center justify-center rounded-full bg-custom-secondary/20 text-2xl font-semibold">
                        {{ initials }}
                    </div>
                </div>

                <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                    <div class="flex flex-row items-center justify-between gap-2 overflow-hidden">
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <RiUserLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <span>Username</span>
                        </div>
                        <span class="mr-1 truncate rounded-md bg-custom-bg px-2 font-mono font-normal tracking-widest dark:bg-custom-bg-light">
                            {{ employee.username }}
                        </span>
                    </div>
                </div>
            </div>

            <Separator orientation="vertical" />

            <div class="flex-1">
                <CardSeparator title="Contact Info" />

                <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                    <div class="group flex flex-row items-center justify-between gap-2 overflow-hidden">
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <RiUserLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <Label for="employee_name">Full Name</Label>
                        </div>
                        <EditableField :editable="canEdit">
                            <template #edit>
                                <span class="flex min-w-0 flex-1 flex-row items-center">
                                    <Input id="employee_name" v-model="form.name" placeholder="e.g. Juan Dela Cruz" variant="inline-edit" />
                                    <RiEditLine class="h-4 w-0 shrink-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:ml-2 group-hover:w-4 group-hover:opacity-100 group-focus-within:ml-2 group-focus-within:w-4 group-focus-within:opacity-100" />
                                </span>
                            </template>
                            <span class="min-w-0 flex-1 truncate text-right text-sm font-semibold capitalize">{{ employee.name }}</span>
                        </EditableField>
                        <InputMessage v-if="canEdit" variant="destructive" :message="form.errors.name" />
                    </div>

                    <div class="group flex flex-row items-center justify-between gap-2 overflow-hidden">
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <RiMailLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <Label for="employee_email">Email</Label>
                        </div>
                        <EditableField :editable="canEdit">
                            <template #edit>
                                <span class="flex min-w-0 flex-1 flex-row items-center">
                                    <Input
                                        id="employee_email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="e.g. juan@example.com"
                                        variant="inline-edit"
                                    />
                                    <RiEditLine class="h-4 w-0 shrink-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:ml-2 group-hover:w-4 group-hover:opacity-100 group-focus-within:ml-2 group-focus-within:w-4 group-focus-within:opacity-100" />
                                </span>
                            </template>
                            <span class="min-w-0 flex-1 truncate text-right text-sm font-semibold">{{ employee.email || '—' }}</span>
                        </EditableField>
                        <InputMessage v-if="canEdit" variant="destructive" :message="form.errors.email" />
                    </div>

                    <div class="group flex flex-row items-center justify-between gap-2 overflow-hidden">
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <RiPhoneLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <Label for="employee_phone">Phone</Label>
                        </div>
                        <EditableField :editable="canEdit">
                            <template #edit>
                                <span class="flex min-w-0 flex-1 flex-row items-center">
                                    <Input id="employee_phone" v-model="form.phone_number" placeholder="e.g. 09xxxxxxxxx" variant="inline-edit" />
                                    <RiEditLine class="h-4 w-0 shrink-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:ml-2 group-hover:w-4 group-hover:opacity-100 group-focus-within:ml-2 group-focus-within:w-4 group-focus-within:opacity-100" />
                                </span>
                            </template>
                            <span class="min-w-0 flex-1 truncate text-right text-sm font-semibold">{{ employee.phone_number || '—' }}</span>
                        </EditableField>
                        <InputMessage v-if="canEdit" variant="destructive" :message="form.errors.phone_number" />
                    </div>
                </div>

                <CardSeparator title="Role & Access" />

                <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                    <div class="group flex flex-row items-center justify-between gap-2 overflow-hidden">
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <RiShieldUserLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <Label for="employee_role">Role</Label>
                        </div>
                        <EditableField :editable="canEdit">
                            <template #edit>
                                <span class="flex min-w-0 flex-row items-center">
                                    <Select v-model="form.role">
                                        <SelectTrigger id="employee_role" variant="inline-edit">
                                            <SelectValue placeholder="Select a role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem v-for="role in props.roles" :key="role.name" :value="role.name">
                                                    {{ humanize(role.name) }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </span>
                            </template>
                            <span class="min-w-0 flex-1 truncate text-right text-sm font-semibold capitalize">
                                {{ humanize(employee.roles?.[0]?.name) }}
                            </span>
                        </EditableField>
                    </div>
                    <InputMessage v-if="canEdit" variant="destructive" :message="form.errors.role" />
                </div>

                <CardSeparator title="Others" />

                <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                    <div class="flex flex-row items-center justify-between overflow-hidden">
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <RiCalendarLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <span>Created</span>
                        </div>
                        <span class="min-w-0 flex-1 truncate text-right">{{ formatDate(employee.created_at) }}</span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
