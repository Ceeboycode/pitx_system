<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
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

import { create, index, store } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { LeadPanel, MainPanel, SidePanel, PanelLayout } from '@/components/ui/_panels';
import { LeadingCard } from '@/components/ui/_leading-card';
import { RoleReviewCard } from '@/components/internal/preview-cards';
import RolePermissionsPicker from '@/components/internal/roles/RolePermissionsPicker.vue';
import { permissionsOfType, type Permission } from '@/lib/permissions';

const props = defineProps<{
    permissions: Permission[];
    roleTypes: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: index().url },
    { title: 'New Role', href: create().url },
];

const form = useForm({
    name: '',
    type: 'internal' as 'internal' | 'external',
    permissions: [] as number[],
});

// A role holds permissions of its own type only, so switching the type clears what was ticked for the other one.
watch(
    () => form.type,
    (type) => {
        const allowed = permissionsOfType(props.permissions, type).map((p) => p.id);
        form.permissions = form.permissions.filter((id) => allowed.includes(id));
    },
);

const typePermissions = computed(() => permissionsOfType(props.permissions, form.type));

const selectedPermissions = computed(() =>
    typePermissions.value.filter((p) => form.permissions.includes(p.id)),
);

// Laravel reports a bad item as "permissions.N" rather than "permissions", so show whichever came back.
const permissionsError = computed(
    () =>
        form.errors.permissions ??
        Object.entries(form.errors).find(([key]) => key.startsWith('permissions.'))?.[1],
);

function submit() {
    form.post(store().url, { preserveScroll: true });
}

const requiredMark = '*';
</script>

<template>
    <Head title="Add Role" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <MainPanel>
                <LeadPanel class="h-fit p-0">
                    <LeadingCard
                        title="New role"
                        description="Add a new role and choose what it can do."
                        variant="entity-crud"
                        :back="index().url"
                        :more="false"
                    >
                    </LeadingCard>
                </LeadPanel>
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                        <CardDescription>Fields with <span class="text-destructive font-semibold">*</span> are required.</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-row gap-4">
                        <div class="flex-1">
                            <form @submit.prevent="submit">
                                <CardSeparator title="Role Info" />

                                <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                                    <div class="grid grid-cols-2 gap-x-2">
                                        <div class="space-y-2 col-span-1">
                                            <Label class="flex items-center gap-1">
                                                Name
                                                <span class="text-destructive">
                                                    {{ requiredMark }}
                                                </span>
                                            </Label>
                                            <Input
                                                v-model="form.name"
                                                placeholder="Enter role name"
                                                autocomplete="off"
                                                class="capitalize"
                                            />
                                            <InputError :message="form.errors.name" />
                                        </div>

                                        <div class="space-y-2 col-span-1">
                                            <Label class="flex items-center gap-1">
                                                Role Type
                                                <span class="text-destructive">
                                                    {{ requiredMark }}
                                                </span>
                                            </Label>

                                            <Select v-model="form.type">
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Select internal/external" class="capitalize" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectGroup>
                                                        <SelectItem
                                                            v-for="type in props.roleTypes"
                                                            :key="type"
                                                            :value="type"
                                                            class="capitalize"
                                                        >
                                                            {{ type }}
                                                        </SelectItem>
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>

                                            <InputError :message="form.errors.type" />
                                        </div>
                                    </div>

                                    <CardSeparator title="Permissions" />

                                    <RolePermissionsPicker
                                        v-model="form.permissions"
                                        :permissions="props.permissions"
                                        :type="form.type"
                                        :error="permissionsError"
                                    />
                                </div>

                                <div class="flex items-center justify-end gap-2">
                                    <Button type="button" variant="float" as-child>
                                        <Link :href="index().url">Cancel</Link>
                                    </Button>

                                    <Button type="submit" variant="float-primary" :disabled="form.processing">
                                        {{ form.processing ? 'Adding...' : 'Add new role' }}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </CardContent>
                </Card>
            </MainPanel>

            <SidePanel>
                <RoleReviewCard
                    :values="form"
                    :permissions="selectedPermissions"
                    :total="typePermissions.length"
                />
            </SidePanel>
        </PanelLayout>
    </AppLayout>
</template>
