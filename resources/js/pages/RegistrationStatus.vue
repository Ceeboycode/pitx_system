<script setup lang="ts">
import CompanyDashboardController from '@/actions/App/Http/Controllers/CompanyDashboardController';
import { storeResubmission } from '@/actions/App/Http/Controllers/CompanyRegistration';
import InputError from '@/components/InputError.vue';
import AllTheDataRafikiUrl from '@/components/assets/All-the-data-rafiki.svg';
import BusDriverRafikiUrl from '@/components/assets/Bus-driver-rafiki.svg';
import FilingSystemRafikiUrl from '@/components/assets/Filing-system-rafiki.svg';
import WarningRafikiUrl from '@/components/assets/Warning-rafiki.svg';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar as CalendarPicker } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { CalendarDate } from '@internationalized/date';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
    RiCalendarLine,
    RiLoaderLine,
    RiRefreshLine,
    RiDashboardHorizontalLine,
} from 'vue-remix-icons';

type DocRow = {
    id: number;
    doc_type: string;
    status: string;
    original_name?: string | null;
    remarks?: string | null;
    expires_at?: string | null;
};

const props = defineProps<{
    embedded?: boolean;
    company: {
        id: number;
        company_name: string;
        company_code?: string | null;
        status: string;
        documents?: DocRow[];
    };
    meta: {
        title: string;
        description: string;
        icon: string;
        color: string;
    };
}>();

function humanize(text?: string | null) {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

const allDocs = computed(() => props.company.documents ?? []);
const actionRequiredDocs = computed(() =>
    allDocs.value.filter((doc) => ['invalid', 'expired'].includes(doc.status)),
);

const statusIllustration = computed(() => {
    switch (props.meta.icon) {
        case 'clock':
            return AllTheDataRafikiUrl;
        case 'warning':
            return WarningRafikiUrl;
        case 'check':
            return BusDriverRafikiUrl;
        case 'draft':
            return FilingSystemRafikiUrl;
        default:
            return null;
    }
});

let timer: ReturnType<typeof setInterval> | null = null;
const refreshing = ref(false);

function doRefresh() {
    refreshing.value = true;
    router.reload({
        onFinish: () => {
            refreshing.value = false;
        },
    });
}

onMounted(() => {
    if (props.company.status === 'for_verification') {
        timer = setInterval(doRefresh, 30_000);
    }
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const resubmitForm = useForm({
    documents: {} as Record<
        string,
        { file: File | null; issued_at: string; expires_at: string }
    >,
});

for (const doc of actionRequiredDocs.value) {
    resubmitForm.documents[doc.doc_type] = {
        file: null,
        issued_at: '',
        expires_at: '',
    };
}

const openDatePicker = ref<string | null>(null);

function parseCalendarDate(value: string): CalendarDate | undefined {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return undefined;
    const [year, month, day] = value.split('-').map(Number);

    try {
        return new CalendarDate(year, month, day);
    } catch {
        return undefined;
    }
}

function selectDocumentDate(
    docType: string,
    field: 'issued_at' | 'expires_at',
    value: CalendarDate | undefined,
) {
    resubmitForm.documents[docType][field] = value
        ? `${value.year}-${String(value.month).padStart(2, '0')}-${String(value.day).padStart(2, '0')}`
        : '';
    openDatePicker.value = null;
}

function handleFile(docType: string, event: Event) {
    const input = event.target as HTMLInputElement;
    resubmitForm.documents[docType].file = input.files?.[0] ?? null;
    resubmitForm.clearErrors(`documents.${docType}.file` as never);
}

function submitResubmission() {
    resubmitForm.post(storeResubmission().url, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head v-if="!embedded" title="Registration Status" />

    <div
        :class="embedded
            ? 'w-full'
            : 'flex min-h-screen items-center justify-center bg-custom-bg p-6 dark:bg-custom-bg-dark'"
    >
        <div
            class="flex flex-col items-center w-full text-custom-shadow rounded-md border border-dashed border-custom-bg-dark dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5 p-6 text-center"
        >
            <img
                v-if="statusIllustration"
                :src="statusIllustration"
                alt=""
                class="w-1/3 object-contain opacity-90"
                aria-hidden="true"
            />
            <p class="text-custom-shadow mb-2 text-2xl font-semibold">
                {{ meta.title }}
            </p>
            <p class="text-custom-shadow">
                {{ meta.description }}
            </p>
        </div>

        <div v-if="allDocs.length" class="mt-2 space-y-2">
            <section
                v-for="doc in allDocs"
                :key="doc.id"
                class="rounded-md border p-3 transition-colors"
                :class="{
                    'border-custom-accent-3 bg-custom-accent-3/10': doc.status === 'verified',
                    'border-custom-bg-dark dark:border-custom-bg-light border-dashed': doc.status === 'pending',
                    'border-destructive/40 border-dashed bg-destructive/10': doc.status === 'expired' || doc.status === 'invalid',
                }"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <p class="truncate text-sm font-semibold text-custom-shadow">
                            {{ humanize(doc.doc_type) }}
                        </p>
                        <Badge
                            variant="outline"
                            class="shrink-0 text-custom-shadow border-none"
                            :class="{
                                'bg-custom-accent-3 text-custom-bg-light dark:text-custom-bg-dark': doc.status === 'verified',
                                'bg-custom-bg-dark dark:bg-custom-bg-light': doc.status === 'pending',
                                'bg-destructive/30': doc.status === 'expired' || doc.status === 'invalid',
                            }"
                        >
                            {{ humanize(doc.status) }}
                        </Badge>
                    </div>
                    <p v-if="doc.original_name" class="truncate text-custom-shadow/80">
                        {{ doc.original_name }}
                    </p>
                    <p v-if="doc.status === 'invalid' && doc.remarks" class="mt-2 rounded-md bg-destructive/10 p-2 text-custom-shadow">
                        <span class="font-semibold">Reason:</span> {{ doc.remarks }}
                    </p>
                    <p v-if="doc.status === 'expired'" class="mt-2 rounded-md bg-destructive/10 p-2 text-xs text-custom-shadow">
                        <span class="font-semibold">Expired:</span>
                        {{ doc.expires_at ?? 'Document validity date has passed.' }}
                    </p>
                </div>

                <div
                    v-if="company.status === 'needs_revision' && resubmitForm.documents[doc.doc_type]"
                    class="mt-2"
                >
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="space-y-1 sm:col-span-2">
                            <Label :for="`file_${doc.doc_type}`">Document</Label>
                            <Input
                                :id="`file_${doc.doc_type}`"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="cursor-pointer p-0 pr-3 file:mr-3 file:h-full file:cursor-pointer file:border-0 file:border-r file:border-custom-bg-dark file:bg-custom-bg-dark file:px-3 file:text-sm file:text-custom-shadow hover:file:bg-custom-bg"
                                @change="handleFile(doc.doc_type, $event)"
                            />
                            <InputError :message="resubmitForm.errors[`documents.${doc.doc_type}.file`]" />
                        </div>
                        <div class="space-y-1">
                            <Label :for="`iss_${doc.doc_type}`">Issue Date</Label>
                            <Popover
                                :open="openDatePicker === `${doc.doc_type}_issued_at`"
                                @update:open="(open) => openDatePicker = open ? `${doc.doc_type}_issued_at` : null"
                            >
                                <div class="flex">
                                    <Input
                                        :id="`iss_${doc.doc_type}`"
                                        v-model="resubmitForm.documents[doc.doc_type].issued_at"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="10"
                                        placeholder="YYYY-MM-DD"
                                        class="rounded-r-none"
                                    />
                                    <PopoverTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            class="shrink-0 rounded-l-none border border-custom-bg-dark bg-custom-bg hover:bg-custom-secondary/20 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                                            aria-label="Choose issue date"
                                        >
                                            <RiCalendarLine class="h-4 w-4" />
                                        </Button>
                                    </PopoverTrigger>
                                </div>
                                <PopoverContent align="start" class="w-auto p-0">
                                    <CalendarPicker
                                        :model-value="parseCalendarDate(resubmitForm.documents[doc.doc_type].issued_at)"
                                        initial-focus
                                        @update:model-value="(value) => selectDocumentDate(doc.doc_type, 'issued_at', value as CalendarDate | undefined)"
                                    />
                                </PopoverContent>
                            </Popover>
                            <InputError :message="resubmitForm.errors[`documents.${doc.doc_type}.issued_at`]" />
                        </div>
                        <div class="space-y-1">
                            <Label :for="`exp_${doc.doc_type}`">Expiration Date</Label>
                            <Popover
                                :open="openDatePicker === `${doc.doc_type}_expires_at`"
                                @update:open="(open) => openDatePicker = open ? `${doc.doc_type}_expires_at` : null"
                            >
                                <div class="flex">
                                    <Input
                                        :id="`exp_${doc.doc_type}`"
                                        v-model="resubmitForm.documents[doc.doc_type].expires_at"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="10"
                                        placeholder="YYYY-MM-DD"
                                        class="rounded-r-none"
                                    />
                                    <PopoverTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            class="shrink-0 rounded-l-none border border-custom-bg-dark bg-custom-bg hover:bg-custom-secondary/20 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                                            aria-label="Choose expiration date"
                                        >
                                            <RiCalendarLine class="h-4 w-4" />
                                        </Button>
                                    </PopoverTrigger>
                                </div>
                                <PopoverContent align="start" class="w-auto p-0">
                                    <CalendarPicker
                                        :model-value="parseCalendarDate(resubmitForm.documents[doc.doc_type].expires_at)"
                                        initial-focus
                                        @update:model-value="(value) => selectDocumentDate(doc.doc_type, 'expires_at', value as CalendarDate | undefined)"
                                    />
                                </PopoverContent>
                            </Popover>
                            <InputError :message="resubmitForm.errors[`documents.${doc.doc_type}.expires_at`]" />
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div v-else class="mt-2 rounded-md border border-dashed border-custom-bg-dark p-3 text-center text-sm text-custom-shadow dark:border-custom-bg-light">
            No documents found.
        </div>

        <Separator class="my-4" />

        <div class="flex flex-row items-center justify-end gap-2">
            <!-- CODE: <p class="text-xs text-custom-shadow/80">
                <template v-if="company.status === 'for_verification'">
                    This page refreshes automatically every 30 seconds.
                </template>
                <template v-else-if="company.status === 'needs_revision'">
                    Resubmit flagged documents for another review.
                </template>
            </p> -->

            <!-- CODE: <div class="flex items-center gap-2"> -->
                <Button variant="float" size="icon-text" :disabled="refreshing" @click="doRefresh">
                    <RiRefreshLine class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" />
                    <span class="hidden lg:block">Refresh</span>
                </Button>
                <Button v-if="company.status === 'verified'" variant="float-primary" size="icon-text" as-child>
                    <Link :href="CompanyDashboardController.index().url">
                        <RiDashboardHorizontalLine class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" />
                        <span class="hidden lg:block">Dashboard</span>
                    </Link>
                </Button>
                <div v-if="company.status === 'needs_revision' && actionRequiredDocs.length">
                    <Button
                        variant="float-primary"
                        class="w-full"
                        :disabled="resubmitForm.processing"
                        @click="submitResubmission"
                    >
                        <RiLoaderLine v-if="resubmitForm.processing" class="size-4 animate-spin" />
                        {{ resubmitForm.processing ? 'Submitting...' : 'Resubmit' }}
                    </Button>
                    <InputError :message="resubmitForm.errors.session" class="mt-2" />
                </div>
            
        </div>

        

        <p v-if="!embedded" class="mt-4 text-center text-xs text-custom-shadow/70">
            Need help?
            <a href="mailto:support@example.com" class="font-semibold text-custom-accent-3 hover:underline">
                Contact support
            </a>
        </p>
    </div>
</template>
