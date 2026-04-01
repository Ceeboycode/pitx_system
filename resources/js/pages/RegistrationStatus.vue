<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';

import {
    CheckCircle2,
    Clock,
    FileX2,
    LayoutDashboard,
    Loader2,
    RefreshCcw,
    TriangleAlert,
    UploadCloud,
    XCircle,
} from 'lucide-vue-next';

// ✅ Wayfinder action (generated)
import CompanyDashboardController from '@/actions/App/Http/Controllers/CompanyDashboardController';
import { storeResubmission } from '@/actions/App/Http/Controllers/CompanyRegistration';
// ─── Types ────────────────────────────────────────────────────────────────────
type DocRow = {
    id: number;
    doc_type: string;
    status: string;
    original_name?: string | null;
    remarks?: string | null;
    expires_at?: string | null;
};

const props = defineProps<{
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
        icon: string; // 'clock' | 'warning' | 'check' | 'draft'
        color: string;
    };
}>();

// ─── Helpers ──────────────────────────────────────────────────────────────────
function humanize(text?: string | null) {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function docVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'verified':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'expired':
            return 'destructive';
        case 'invalid':
            return 'destructive';
        default:
            return 'outline';
    }
}

// ─── Derived counts ───────────────────────────────────────────────────────────
const allDocs = computed(() => props.company.documents ?? []);
const verifiedDocs = computed(() =>
    allDocs.value.filter((d) => d.status === 'verified'),
);
const pendingDocs = computed(() =>
    allDocs.value.filter((d) => d.status === 'pending'),
);
const invalidDocs = computed(() =>
    allDocs.value.filter((d) => d.status === 'invalid'),
);
const expiredDocs = computed(() =>
    allDocs.value.filter((d) => d.status === 'expired'),
);
const actionRequiredDocs = computed(() =>
    allDocs.value.filter(
        (d) => d.status === 'invalid' || d.status === 'expired',
    ),
);

// ─── Auto-refresh every 30 s when status is "for_verification" ───────────────
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

// ─── Inline resubmission form (for invalid/expired docs) ──────────────────────
const resubmitForm = useForm({
    documents: {} as Record<
        string,
        { file: File | null; issued_at: string; expires_at: string }
    >,
});

// Init documents map from action-required docs
function initResubmit() {
    const map: Record<
        string,
        { file: File | null; issued_at: string; expires_at: string }
    > = {};
    for (const doc of actionRequiredDocs.value) {
        map[doc.doc_type] = { file: null, issued_at: '', expires_at: '' };
    }
    resubmitForm.documents = map;
}
initResubmit();

function handleFile(docType: string, e: Event) {
    const el = e.target as HTMLInputElement;
    resubmitForm.documents[docType].file = el.files?.[0] ?? null;
    resubmitForm.clearErrors(`documents.${docType}.file` as any);
}

function submitResubmission() {
    resubmitForm.post(storeResubmission().url, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Registration Status" />

    <div
        class="flex min-h-screen flex-col items-center justify-center bg-muted/40 p-4"
    >
        <div class="w-full max-w-lg space-y-5">
            <Card class="overflow-hidden">
                <div
                    class="h-1.5 w-full"
                    :class="{
                        'bg-amber-400': meta.icon === 'clock',
                        'bg-destructive': meta.icon === 'warning',
                        'bg-primary': meta.icon === 'check',
                        'bg-muted-foreground': meta.icon === 'draft',
                    }"
                />

                <CardHeader class="pt-8 pb-0 text-center">
                    <div
                        class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full"
                        :class="{
                            'bg-amber-100 text-amber-600':
                                meta.icon === 'clock',
                            'bg-destructive/10 text-destructive':
                                meta.icon === 'warning',
                            'bg-primary/10 text-primary': meta.icon === 'check',
                            'bg-muted text-muted-foreground':
                                meta.icon === 'draft',
                        }"
                    >
                        <Clock v-if="meta.icon === 'clock'" class="h-8 w-8" />
                        <TriangleAlert
                            v-else-if="meta.icon === 'warning'"
                            class="h-8 w-8"
                        />
                        <CheckCircle2
                            v-else-if="meta.icon === 'check'"
                            class="h-8 w-8"
                        />
                        <FileX2 v-else class="h-8 w-8" />
                    </div>

                    <Badge variant="outline" class="mx-auto w-fit text-xs">
                        {{ company.company_code ?? company.company_name }}
                    </Badge>

                    <h1 class="mt-2 text-xl font-semibold tracking-tight">
                        {{ meta.title }}
                    </h1>
                    <p
                        class="mt-1 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ meta.description }}
                    </p>
                </CardHeader>

                <CardContent class="mt-6 space-y-4">
                    <div class="space-y-2">
                        <p
                            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Document Status
                        </p>

                        <div
                            v-for="doc in allDocs"
                            :key="doc.id"
                            class="space-y-3 rounded-lg border px-3 py-2.5 transition-colors"
                            :class="{
                                'border-destructive/40 bg-destructive/5':
                                    doc.status === 'invalid',
                                'border-amber-400/50 bg-amber-50':
                                    doc.status === 'expired',
                                'border-primary/30 bg-primary/5':
                                    doc.status === 'verified',
                                'bg-muted/30': doc.status === 'pending',
                            }"
                        >
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 shrink-0">
                                    <CheckCircle2
                                        v-if="doc.status === 'verified'"
                                        class="h-4 w-4 text-primary"
                                    />
                                    <XCircle
                                        v-else-if="doc.status === 'invalid'"
                                        class="h-4 w-4 text-destructive"
                                    />
                                    <TriangleAlert
                                        v-else-if="doc.status === 'expired'"
                                        class="h-4 w-4 text-amber-600"
                                    />
                                    <Clock
                                        v-else
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                </div>

                                <div class="min-w-0 flex-1 space-y-0.5">
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <p class="text-sm font-medium">
                                            {{ humanize(doc.doc_type) }}
                                        </p>
                                        <Badge
                                            :variant="docVariant(doc.status)"
                                            class="shrink-0 text-[10px]"
                                        >
                                            {{ humanize(doc.status) }}
                                        </Badge>
                                    </div>

                                    <p
                                        v-if="doc.original_name"
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {{ doc.original_name }}
                                    </p>

                                    <div
                                        v-if="
                                            doc.status === 'invalid' &&
                                            doc.remarks
                                        "
                                        class="mt-1.5 rounded-md bg-destructive/10 px-2.5 py-1.5 text-xs text-destructive"
                                    >
                                        <span class="font-semibold"
                                            >Reason: </span
                                        >{{ doc.remarks }}
                                    </div>

                                    <div
                                        v-if="doc.status === 'expired'"
                                        class="mt-1.5 rounded-md bg-amber-100 px-2.5 py-1.5 text-xs text-amber-800"
                                    >
                                        <span class="font-semibold"
                                            >Expired:</span
                                        >
                                        <span v-if="doc.expires_at">
                                            {{ doc.expires_at }}</span
                                        >
                                        <span v-else>
                                            Document validity date has
                                            passed.</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- ✅ Inline upload UI for invalid/expired docs -->
                            <div
                                v-if="
                                    company.status === 'needs_revision' &&
                                    (doc.status === 'invalid' ||
                                        doc.status === 'expired')
                                "
                                class="rounded-md border bg-background p-3"
                            >
                                <p
                                    class="mb-2 text-xs font-semibold text-muted-foreground"
                                >
                                    Upload replacement file
                                </p>

                                <div class="space-y-2">
                                    <div class="space-y-1">
                                        <Label
                                            :for="`file_${doc.doc_type}`"
                                            class="text-xs"
                                            >File</Label
                                        >
                                        <Input
                                            :id="`file_${doc.doc_type}`"
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            @change="
                                                handleFile(doc.doc_type, $event)
                                            "
                                        />
                                        <p
                                            v-if="
                                                resubmitForm.errors[
                                                    `documents.${doc.doc_type}.file`
                                                ]
                                            "
                                            class="text-xs text-destructive"
                                        >
                                            {{
                                                resubmitForm.errors[
                                                    `documents.${doc.doc_type}.file`
                                                ]
                                            }}
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="space-y-1">
                                            <Label
                                                :for="`iss_${doc.doc_type}`"
                                                class="text-xs"
                                                >Issued At</Label
                                            >
                                            <Input
                                                :id="`iss_${doc.doc_type}`"
                                                type="date"
                                                v-model="
                                                    resubmitForm.documents[
                                                        doc.doc_type
                                                    ].issued_at
                                                "
                                            />
                                            <p
                                                v-if="
                                                    resubmitForm.errors[
                                                        `documents.${doc.doc_type}.issued_at`
                                                    ]
                                                "
                                                class="text-xs text-destructive"
                                            >
                                                {{
                                                    resubmitForm.errors[
                                                        `documents.${doc.doc_type}.issued_at`
                                                    ]
                                                }}
                                            </p>
                                        </div>

                                        <div class="space-y-1">
                                            <Label
                                                :for="`exp_${doc.doc_type}`"
                                                class="text-xs"
                                                >Expires At</Label
                                            >
                                            <Input
                                                :id="`exp_${doc.doc_type}`"
                                                type="date"
                                                v-model="
                                                    resubmitForm.documents[
                                                        doc.doc_type
                                                    ].expires_at
                                                "
                                            />
                                            <p
                                                v-if="
                                                    resubmitForm.errors[
                                                        `documents.${doc.doc_type}.expires_at`
                                                    ]
                                                "
                                                class="text-xs text-destructive"
                                            >
                                                {{
                                                    resubmitForm.errors[
                                                        `documents.${doc.doc_type}.expires_at`
                                                    ]
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="allDocs.length === 0"
                            class="flex items-center gap-2 py-3 text-sm text-muted-foreground"
                        >
                            <FileX2 class="h-4 w-4 shrink-0" />
                            No documents found.
                        </div>
                    </div>

                    <div
                        v-if="allDocs.length > 0"
                        class="flex flex-wrap gap-2 pt-1"
                    >
                        <Badge
                            variant="default"
                            class="gap-1.5"
                            v-if="verifiedDocs.length"
                        >
                            <CheckCircle2 class="h-3 w-3" />
                            {{ verifiedDocs.length }} Verified
                        </Badge>
                        <Badge
                            variant="secondary"
                            class="gap-1.5"
                            v-if="pendingDocs.length"
                        >
                            <Clock class="h-3 w-3" />
                            {{ pendingDocs.length }} Under Review
                        </Badge>
                        <Badge
                            variant="destructive"
                            class="gap-1.5"
                            v-if="invalidDocs.length"
                        >
                            <TriangleAlert class="h-3 w-3" />
                            {{ invalidDocs.length }} Invalid
                        </Badge>
                        <Badge
                            variant="destructive"
                            class="gap-1.5"
                            v-if="expiredDocs.length"
                        >
                            <Clock class="h-3 w-3" />
                            {{ expiredDocs.length }} Expired
                        </Badge>
                    </div>

                    <Separator />

                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-muted-foreground">
                            <template
                                v-if="company.status === 'for_verification'"
                            >
                                Auto-refreshing every 30 s…
                            </template>
                            <template
                                v-else-if="company.status === 'needs_revision'"
                            >
                                Reupload only invalid or expired documents and
                                resubmit.
                            </template>
                        </p>

                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="refreshing"
                                @click="doRefresh"
                            >
                                <RefreshCcw
                                    class="mr-2 h-3.5 w-3.5"
                                    :class="refreshing ? 'animate-spin' : ''"
                                />
                                Refresh
                            </Button>

                            <Button
                                v-if="company.status === 'verified'"
                                size="sm"
                                as-child
                            >
                                <Link
                                    :href="
                                        CompanyDashboardController.index().url
                                    "
                                >
                                    <LayoutDashboard class="mr-2 h-3.5 w-3.5" />
                                    Go to Dashboard
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <!-- ✅ Submit corrected documents -->
                    <div
                        v-if="
                            company.status === 'needs_revision' &&
                            actionRequiredDocs.length > 0
                        "
                        class="pt-2"
                    >
                        <Button
                            class="w-full"
                            :disabled="resubmitForm.processing"
                            @click="submitResubmission"
                        >
                            <Loader2
                                v-if="resubmitForm.processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            <UploadCloud v-else class="mr-2 h-4 w-4" />
                            Submit Reuploaded Documents
                        </Button>
                        <p
                            v-if="resubmitForm.errors.session"
                            class="mt-2 text-xs text-destructive"
                        >
                            {{ resubmitForm.errors.session }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <p class="text-center text-xs text-muted-foreground">
                Need help?
                <a
                    href="mailto:support@example.com"
                    class="underline underline-offset-2 hover:text-foreground"
                >
                    Contact support
                </a>
            </p>
        </div>
    </div>
</template>
