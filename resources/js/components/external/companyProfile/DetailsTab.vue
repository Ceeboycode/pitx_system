<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import { InputMessage } from '@/components/ui/_input-message';
import { CardSeparator } from '@/components/ui/_card-separator';
import EditableField from '@/components/ui/_field/EditableField.vue';
import { Logo, LogoFallback, LogoImage } from '@/components/ui/_logo';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import {
    RiBuildingLine,
    RiCalendarLine,
    RiCloseLine,
    RiEditLine,
    RiFileCheckLine,
    RiIdCardLine,
    RiImageAddLine,
    RiMailLine,
    RiMapPin2Line,
    RiPhoneLine,
    RiTimeLine,
    RiUser3Line,
} from 'vue-remix-icons';

import { humanize } from '@/lib/format';

import type { CompanyProfile, CompanyProfileForm } from './types';

const props = defineProps<{
    company: CompanyProfile;
    /** Drives every EditableField below: without it the tab shows static text and no Save/Cancel. */
    canEdit: boolean;
}>();

const form = defineModel<CompanyProfileForm>('form', { required: true });

const emit = defineEmits<{
    submit: [];
    reset: [];
}>();

const editIconClass =
    'h-4 w-0 shrink-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:ml-2 group-hover:w-4 group-hover:opacity-100 group-focus-within:ml-2 group-focus-within:w-4 group-focus-within:opacity-100';

const editableFields = [
    { key: 'company_phone', label: 'Phone', icon: RiPhoneLine, placeholder: 'Enter phone number' },
    { key: 'company_email', label: 'Email', icon: RiMailLine, placeholder: 'Enter email' },
] as const;

const representativeFields = [
    { key: 'authorized_representative_name', label: 'Name', icon: RiUser3Line, placeholder: 'Enter representative name' },
    { key: 'authorized_representative_position', label: 'Position', icon: RiIdCardLine, placeholder: 'Enter position' },
    { key: 'authorized_representative_contact', label: 'Phone', icon: RiPhoneLine, placeholder: 'Enter contact number' },
] as const;

const logoInputRef = ref<HTMLInputElement | null>(null);
const logoPreview = ref<string | null>(props.company.logo_url ?? null);

// form.isDirty can't see a File (it clones down to {}), so a logo-only change is tracked separately
// and OR'd into the Save/Cancel checks.
const logoChanged = ref(false);
const isDirty = computed(() => form.value.isDirty || logoChanged.value);

function revokePreview() {
    if (logoPreview.value?.startsWith('blob:')) URL.revokeObjectURL(logoPreview.value);
}

function selectLogo(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    revokePreview();
    form.value.logo = file;
    form.value.remove_logo = false;
    logoChanged.value = true;
    logoPreview.value = file ? URL.createObjectURL(file) : (props.company.logo_url ?? null);
}

function removeLogo() {
    revokePreview();
    logoPreview.value = null;
    form.value.logo = null;
    form.value.remove_logo = true;
    logoChanged.value = true;
    if (logoInputRef.value) logoInputRef.value.value = '';
}

function resetLogo() {
    revokePreview();
    logoPreview.value = props.company.logo_url ?? null;
    logoChanged.value = false;
    if (logoInputRef.value) logoInputRef.value.value = '';
}

function cancel() {
    resetLogo();
    emit('reset');
}

// A saved logo change is staged for admin review, so the live logo stays as it was until it is approved.
watch(
    () => form.value.wasSuccessful,
    (wasSuccessful) => {
        if (wasSuccessful) resetLogo();
    },
);
</script>

<template>
    <div class="flex h-full w-full flex-col gap-4">
        <Card>
            <CardHeader class="flex flex-row items-start justify-between gap-4">
                <div class="flex flex-col">
                    <CardTitle>Company</CardTitle>
                    <CardDescription>
                        {{ canEdit ? 'Manage company details. Saving changes sends them to admin for review.' : 'View company details.' }}
                    </CardDescription>
                </div>
                <div v-if="canEdit" class="flex flex-row items-center gap-2">
                    <Button
                        :variant="!isDirty || form.processing ? 'disabled' : 'float'"
                        :disabled="!isDirty || form.processing"
                        @click="cancel"
                    >
                        Cancel
                    </Button>
                    <Button
                        :variant="!isDirty || form.processing ? 'disabled' : 'float-primary'"
                        size="icon-text"
                        :disabled="!isDirty || form.processing"
                        @click="emit('submit')"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </CardHeader>

            <CardContent class="flex flex-row gap-4">
                <div class="max-w-1/3 flex-1">
                    <div class="my-2 flex flex-col items-start justify-center gap-2 text-sm text-custom-shadow">
                        <EditableField :editable="canEdit">
                            <template #edit>
                                <div
                                    role="button"
                                    tabindex="0"
                                    :aria-label="logoPreview ? 'Change company logo' : 'Upload company logo'"
                                    class="group relative flex aspect-video w-full shrink-0 cursor-pointer overflow-hidden rounded-md transition-colors"
                                    :class="logoPreview ? '' : 'border border-dashed border-custom-primary/60 dark:border-custom-secondary/60'"
                                    @click="logoInputRef?.click()"
                                    @keydown.enter.prevent="logoInputRef?.click()"
                                    @keydown.space.prevent="logoInputRef?.click()"
                                >
                                    <img
                                        v-if="logoPreview"
                                        :src="logoPreview"
                                        alt="Company logo preview"
                                        class="h-full w-full object-cover transition duration-200 group-hover:brightness-80"
                                    />
                                    <div v-if="logoPreview" class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-200 group-hover:opacity-100 group-focus-visible:opacity-100">
                                        <RiImageAddLine class="h-7 w-7 shrink-0 text-custom-bg-light dark:text-custom-shadow" />
                                    </div>
                                    <div v-else class="flex h-full w-full items-center justify-center bg-custom-primary/10 text-sm text-custom-shadow dark:bg-custom-secondary/10">
                                        <RiImageAddLine class="h-6 w-6 shrink-0 text-custom-shadow/80" />
                                    </div>
                                    <Button
                                        v-if="logoPreview"
                                        type="button"
                                        aria-label="Remove company logo"
                                        class="absolute top-1 right-1 z-10 flex h-6 w-6 cursor-pointer items-center rounded-full border border-custom-bg-light/50 text-custom-shadow transition-all duration-200 hover:border-destructive hover:bg-destructive/20 hover:text-destructive dark:border-custom-shadow/50"
                                        @click.stop="removeLogo"
                                    >
                                        <RiCloseLine class="h-4 w-4 shrink-0 text-custom-bg-light dark:text-custom-shadow" />
                                    </Button>
                                </div>
                                <input
                                    id="company_logo"
                                    ref="logoInputRef"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="sr-only"
                                    @change="selectLogo"
                                />
                            </template>
                            <Logo class="rounded-md">
                                <LogoImage v-if="company.logo_url" :src="company.logo_url" :alt="company.company_name" />
                                <LogoFallback>No logo uploaded.</LogoFallback>
                            </Logo>
                        </EditableField>
                        <InputMessage v-if="canEdit" variant="destructive" :message="form.errors.logo" />
                    </div>

                    <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                        <div class="flex flex-row items-center justify-between gap-2 overflow-hidden">
                            <div class="inline-flex shrink-0 items-center gap-2">
                                <RiBuildingLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                                <span>Name</span>
                            </div>
                            <span class="min-w-0 flex-1 truncate text-right">{{ company.company_name || '—' }}</span>
                        </div>

                        <div class="flex flex-row items-center justify-between gap-2 overflow-hidden">
                            <div class="inline-flex shrink-0 items-center gap-2">
                                <RiBuildingLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                                <span>Code</span>
                            </div>
                            <span class="mr-1 truncate rounded-md bg-custom-bg px-2 font-mono font-normal tracking-widest dark:bg-custom-bg-light">
                                {{ company.company_code || '—' }}
                            </span>
                        </div>

                        <div class="group flex flex-row items-start justify-between gap-2 overflow-hidden">
                            <div class="inline-flex shrink-0 items-center gap-2">
                                <RiMapPin2Line class="mt-0.5 h-4 w-4 shrink-0 text-custom-shadow/80" />
                                <Label for="company_address">Address</Label>
                            </div>
                            <EditableField :editable="canEdit">
                                <template #edit>
                                    <span class="flex min-w-0 flex-1 flex-row items-center">
                                        <Textarea
                                            id="company_address"
                                            v-model="form.company_address"
                                            placeholder="Enter company address"
                                            variant="inline-edit"
                                        />
                                        <RiEditLine :class="editIconClass" />
                                    </span>
                                </template>
                                <span class="min-w-0 flex-1 text-right text-sm font-semibold">{{ company.company_address || '—' }}</span>
                            </EditableField>
                            <InputMessage v-if="canEdit" variant="destructive" :message="form.errors.company_address" />
                        </div>
                    </div>
                </div>

                <Separator orientation="vertical" />

                <div class="min-w-0 flex-1">
                    <CardSeparator title="Business Info" />

                    <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                        <div class="flex flex-row items-center justify-between gap-2 overflow-hidden">
                            <div class="inline-flex shrink-0 items-center gap-2">
                                <RiBuildingLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                                <span>Business Type</span>
                            </div>
                            <span class="line-clamp-1 text-ellipsis">{{ company.business_type ? humanize(company.business_type) : '—' }}</span>
                        </div>

                        <div class="flex flex-row items-center justify-between gap-2 overflow-hidden">
                            <div class="inline-flex shrink-0 items-center gap-2">
                                <RiFileCheckLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                                <span>Registration No.</span>
                            </div>
                            <span class="line-clamp-1 text-ellipsis">{{ company.registration_number || '—' }}</span>
                        </div>
                    </div>

                    <CardSeparator title="Contact Info" />

                    <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                        <div
                            v-for="field in editableFields"
                            :key="field.key"
                            class="group flex flex-row items-center justify-between gap-2 overflow-hidden"
                        >
                            <div class="inline-flex shrink-0 items-center gap-2">
                                <component :is="field.icon" class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                                <Label :for="field.key">{{ field.label }}</Label>
                            </div>
                            <EditableField :editable="canEdit">
                                <template #edit>
                                    <span class="flex min-w-0 flex-1 flex-row items-center">
                                        <Input :id="field.key" v-model="form[field.key]" :placeholder="field.placeholder" variant="inline-edit" />
                                        <RiEditLine :class="editIconClass" />
                                    </span>
                                </template>
                                <span class="min-w-0 flex-1 truncate text-right text-sm font-semibold">{{ company[field.key] || '—' }}</span>
                            </EditableField>
                            <InputMessage v-if="canEdit" variant="destructive" :message="form.errors[field.key]" />
                        </div>
                    </div>

                    <CardSeparator title="Others" />

                    <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
                        <div class="flex flex-row items-center justify-between overflow-hidden">
                            <div class="inline-flex shrink-0 items-center gap-2">
                                <RiCalendarLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                                <span>Created</span>
                            </div>
                            <span class="min-w-0 flex-1 truncate text-right">
                                {{ company.created_at_human ?? '—' }}
                                <span class="text-custom-accent-3"> • </span>
                                {{ company.creator?.name ?? '—' }}
                            </span>
                        </div>

                        <div class="flex flex-row items-center justify-between overflow-hidden">
                            <div class="inline-flex shrink-0 items-center gap-2">
                                <RiTimeLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                                <span>Updated</span>
                            </div>
                            <span class="min-w-0 flex-1 truncate text-right">
                                {{ company.updated_at_human ?? '—' }}
                                <span class="text-custom-accent-3"> • </span>
                                {{ company.updater?.name ?? '—' }}
                            </span>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Representative</CardTitle>
                <CardDescription>Authorized company contact.</CardDescription>
            </CardHeader>

            <CardContent>
                <div class="mt-2 flex flex-col gap-0.5 overflow-hidden text-sm text-custom-shadow">
                    <div
                        v-for="field in representativeFields"
                        :key="field.key"
                        class="group flex flex-row items-center justify-between gap-2 overflow-hidden"
                    >
                        <div class="inline-flex shrink-0 items-center gap-2">
                            <component :is="field.icon" class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                            <Label :for="field.key">{{ field.label }}</Label>
                        </div>
                        <EditableField :editable="canEdit">
                            <template #edit>
                                <span class="flex min-w-0 flex-1 flex-row items-center">
                                    <Input :id="field.key" v-model="form[field.key]" :placeholder="field.placeholder" variant="inline-edit" />
                                    <RiEditLine :class="editIconClass" />
                                </span>
                            </template>
                            <span class="min-w-0 flex-1 truncate text-right text-sm font-semibold">{{ company[field.key] || '—' }}</span>
                        </EditableField>
                        <InputMessage v-if="canEdit" variant="destructive" :message="form.errors[field.key]" />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
