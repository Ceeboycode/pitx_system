<script setup lang="ts">
import SearchInput from '@/components/SearchInput.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { close, index, reopen, show, update } from '@/routes/crm/threads';
import { store as storeAttachment } from '@/routes/crm/threads/messages/attachments';
import { store as storeMessage } from '@/routes/crm/threads/messages';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import { type BreadcrumbItem } from '@/types';

type ThreadSummary = {
    id: number | string;
    subject?: string | null;
    category?: string | null;
    is_closed?: boolean;
    messages_count?: number;
    company?: {
        id?: number | string;
        company_name?: string | null;
    } | null;
    created_by?: {
        id?: number | string;
        name?: string | null;
    } | null;
    assigned_to?: {
        id?: number | string;
        name?: string | null;
    } | null;
    created_at?: string | null;
    created_at_human?: string | null;
    closed_at?: string | null;
    closed_at_human?: string | null;
    last_message_at?: string | null;
    last_message_at_human?: string | null;
};

type ThreadAttachment = {
    id: number | string;
    original_name?: string | null;
    mime_type?: string | null;
    size_bytes?: number | null;
    download_url?: string | null;
};

type ThreadMessage = {
    id: number | string;
    body?: string | null;
    is_internal?: boolean;
    created_at_human?: string | null;
    created_at?: string | null;
    sender?: {
        id?: number | string;
        name?: string | null;
    } | null;
    attachments?: ThreadAttachment[];
};

type ThreadDetail = ThreadSummary & {
    details?: unknown;
    messages?: ThreadMessage[];
};

type StaffUser = {
    id: number | string;
    name?: string | null;
    email?: string | null;
    label?: string | null;
};

const props = defineProps<{
    threads: {
        data: ThreadSummary[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search: string | null;
        category?: string | null;
        status?: string | null;
    };
    staffUsers: StaffUser[];
    currentUser: {
        id: number | string;
        name?: string | null;
        can_assign_threads?: boolean;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Threads', href: index().url },
];

const selectedThreadId = ref<number | string | null>(null);
const selectedThread = ref<ThreadDetail | null>(null);
const loadingThread = ref(false);
const threadError = ref<string | null>(null);
const isThreadListOpen = ref(true);
const actionError = ref<string | null>(null);
const actionSuccess = ref<string | null>(null);
const categoryFilter = ref(props.filters.category ?? 'all');
const statusFilter = ref(props.filters.status ?? 'all');
const assigneeId = ref('');
const replyBody = ref('');
const replyIsInternal = ref(false);
const replyAttachment = ref<File | null>(null);
const replyFileInput = ref<HTMLInputElement | null>(null);
const savingAssignment = ref(false);
const togglingThread = ref(false);
const sendingReply = ref(false);
let activeRequest = 0;

const searchRoute = computed(() =>
    index.url({
        query: {
            category: categoryFilter.value !== 'all' ? categoryFilter.value : undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        },
    }),
);
const canAssignThreads = computed(() => props.currentUser.can_assign_threads === true);

function getCsrfToken() {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

async function parseError(response: Response) {
    try {
        const payload = await response.json();

        if (payload?.message) {
            return payload.message as string;
        }

        const errors = payload?.errors;

        if (errors && typeof errors === 'object') {
            const messages = Object.values(errors)
                .flat()
                .filter((value): value is string => typeof value === 'string');

            if (messages.length > 0) {
                return messages[0];
            }
        }
    } catch {
        // Fall back to a generic message below.
    }

    return 'Something went wrong. Please try again.';
}

async function sendRequest(
    url: string,
    method: string,
    payload?: BodyInit | Record<string, unknown>,
    expectsJson = true,
) {
    const csrfToken = getCsrfToken();
    const isFormData = payload instanceof FormData;
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    if (payload && !isFormData) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers,
        body: payload
            ? isFormData
                ? payload
                : JSON.stringify(payload)
            : undefined,
    });

    if (!response.ok) {
        throw new Error(await parseError(response));
    }

    if (!expectsJson) {
        return null;
    }

    return response.json();
}

async function reloadThreadList() {
    return new Promise<void>((resolve) => {
        router.reload({
            only: ['threads', 'filters', 'staffUsers', 'currentUser', 'flash'],
            preserveState: true,
            preserveScroll: true,
            onFinish: () => resolve(),
        });
    });
}

async function loadThread(threadId: number | string | null) {
    if (!threadId) {
        selectedThread.value = null;
        threadError.value = null;
        assigneeId.value = '';
        return;
    }

    const requestId = ++activeRequest;
    loadingThread.value = true;
    threadError.value = null;

    try {
        const route = show({ thread: threadId });
        const response = await sendRequest(route.url, route.method.toUpperCase());

        if (requestId !== activeRequest) {
            return;
        }

        selectedThread.value = response?.data ?? null;
        assigneeId.value = selectedThread.value?.assigned_to?.id
            ? String(selectedThread.value.assigned_to.id)
            : '';
    } catch (error) {
        if (requestId !== activeRequest) {
            return;
        }

        selectedThread.value = null;
        assigneeId.value = '';
        threadError.value =
            error instanceof Error
                ? error.message
                : 'Unable to load messages for this thread.';
    } finally {
        if (requestId === activeRequest) {
            loadingThread.value = false;
        }
    }
}

function clearReplyForm() {
    replyBody.value = '';
    replyIsInternal.value = false;
    replyAttachment.value = null;

    if (replyFileInput.value) {
        replyFileInput.value.value = '';
    }
}

function setActionState(message: string | null, error = false) {
    if (error) {
        actionError.value = message;
        actionSuccess.value = null;
        return;
    }

    actionSuccess.value = message;
    actionError.value = null;
}

async function refreshSelectedThread() {
    await loadThread(selectedThreadId.value);
}

function selectThread(threadId: number | string) {
    selectedThreadId.value = threadId;
    setActionState(null);

    if (
        typeof window !== 'undefined' &&
        window.matchMedia('(max-width: 1023px)').matches
    ) {
        isThreadListOpen.value = false;
    }
}

function onReplyAttachmentChange(event: Event) {
    const input = event.target as HTMLInputElement;
    replyAttachment.value = input.files?.[0] ?? null;
}

function formatThreadDetails(details: unknown) {
    if (typeof details === 'string') {
        return details;
    }

    if (details == null) {
        return '';
    }

    try {
        return JSON.stringify(details, null, 2);
    } catch {
        return String(details);
    }
}

function applyFilters() {
    router.get(
        index().url,
        {
            search: props.filters.search ?? undefined,
            category: categoryFilter.value !== 'all' ? categoryFilter.value : undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        },
        {
            only: ['threads', 'filters', 'staffUsers', 'currentUser', 'flash'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

async function saveAssignment() {
    if (!selectedThreadId.value) {
        return;
    }

    if (!canAssignThreads.value) {
        setActionState('Only super-admin users can assign threads.', true);
        return;
    }

    savingAssignment.value = true;
    setActionState(null);

    try {
        const route = update({ thread: selectedThreadId.value });
        const response = await sendRequest(route.url, route.method.toUpperCase(), {
            assigned_to_user_id: assigneeId.value ? Number(assigneeId.value) : null,
        });

        selectedThread.value = response?.data ?? selectedThread.value;
        assigneeId.value = selectedThread.value?.assigned_to?.id
            ? String(selectedThread.value.assigned_to.id)
            : '';
        await reloadThreadList();
        setActionState('Thread assignment updated.');
    } catch (error) {
        setActionState(
            error instanceof Error ? error.message : 'Unable to update assignment.',
            true,
        );
    } finally {
        savingAssignment.value = false;
    }
}

async function toggleThreadStatus(nextAction: 'close' | 'reopen') {
    if (!selectedThreadId.value) {
        return;
    }

    togglingThread.value = true;
    setActionState(null);

    try {
        const route =
            nextAction === 'close'
                ? close({ thread: selectedThreadId.value })
                : reopen({ thread: selectedThreadId.value });

        const response = await sendRequest(route.url, route.method.toUpperCase());
        selectedThread.value = response?.data ?? selectedThread.value;
        await reloadThreadList();
        setActionState(
            nextAction === 'close'
                ? 'Thread closed successfully.'
                : 'Thread reopened successfully.',
        );
    } catch (error) {
        setActionState(
            error instanceof Error ? error.message : 'Unable to update thread status.',
            true,
        );
    } finally {
        togglingThread.value = false;
    }
}

async function sendReply() {
    if (!selectedThreadId.value || !replyBody.value.trim()) {
        setActionState('Enter a message before sending.', true);
        return;
    }

    sendingReply.value = true;
    setActionState(null);

    try {
        const route = storeMessage({ thread: selectedThreadId.value });
        const messageResponse = await sendRequest(route.url, route.method.toUpperCase(), {
            body: replyBody.value.trim(),
            is_internal: replyIsInternal.value,
        });

        const messageId = messageResponse?.data?.id;

        if (replyAttachment.value && messageId) {
            const formData = new FormData();
            formData.append('file', replyAttachment.value);

            const attachmentRoute = storeAttachment({
                thread: selectedThreadId.value,
                message: messageId,
            });

            await sendRequest(
                attachmentRoute.url,
                attachmentRoute.method.toUpperCase(),
                formData,
            );
        }

        clearReplyForm();
        await Promise.all([refreshSelectedThread(), reloadThreadList()]);
        setActionState('Reply sent successfully.');
    } catch (error) {
        setActionState(
            error instanceof Error ? error.message : 'Unable to send reply.',
            true,
        );
    } finally {
        sendingReply.value = false;
    }
}

watch(
    () => props.filters,
    (filters) => {
        categoryFilter.value = filters.category ?? 'all';
        statusFilter.value = filters.status ?? 'all';
    },
    { deep: true },
);

watch(
    () => props.threads.data,
    (threads) => {
        if (threads.length === 0) {
            selectedThreadId.value = null;
            selectedThread.value = null;
            assigneeId.value = '';
            return;
        }

        const hasSelected = threads.some(
            (thread) => String(thread.id) === String(selectedThreadId.value),
        );

        if (!hasSelected) {
            selectedThreadId.value = threads[0].id;
        }
    },
    { immediate: true },
);

watch(
    selectedThreadId,
    (threadId) => {
        void loadThread(threadId);
    },
    { immediate: true },
);
</script>

<template>
    <Head title="Threads" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-5">
                <div
                    class="grid h-auto grid-cols-1 lg:grid-cols-[22rem_minmax(0,1fr)]"
                >
                    <section
                        :class="[
                            'flex min-h-[42rem] min-w-0 flex-col overflow-hidden border-b lg:border-b-0 lg:border-r',
                            isThreadListOpen ? 'block' : 'hidden lg:block',
                        ]"
                    >
                        <CardHeader class="gap-4 border-b">
                            <div>
                                <CardTitle>Threads</CardTitle>
                                <CardDescription>
                                    Review commuter conversations, assign ownership,
                                    and keep tickets moving.
                                </CardDescription>
                            </div>

                            <div class="space-y-3">
                                <div class="w-full">
                                    <SearchInput
                                        :route="searchRoute"
                                        :initial-value="filters.search"
                                        placeholder="Search threads..."
                                        :only="['threads', 'filters', 'staffUsers', 'currentUser', 'flash']"
                                        :debounce="350"
                                    />
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="space-y-1 text-xs font-medium text-muted-foreground">
                                        <span>Category</span>
                                        <select
                                            v-model="categoryFilter"
                                            class="w-full rounded-md border bg-background px-3 py-2 text-sm text-foreground"
                                            @change="applyFilters"
                                        >
                                            <option value="all">All categories</option>
                                            <option value="compliance">Compliance</option>
                                            <option value="system">System</option>
                                        </select>
                                    </label>

                                    <label class="space-y-1 text-xs font-medium text-muted-foreground">
                                        <span>Status</span>
                                        <select
                                            v-model="statusFilter"
                                            class="w-full rounded-md border bg-background px-3 py-2 text-sm text-foreground"
                                            @change="applyFilters"
                                        >
                                            <option value="all">All statuses</option>
                                            <option value="open">Open</option>
                                            <option value="closed">Closed</option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="min-h-0 flex-1 overflow-y-auto p-0">
                            <div
                                v-if="threads.data.length === 0"
                                class="px-4 py-10 text-center text-sm text-muted-foreground"
                            >
                                No threads found.
                            </div>

                            <button
                                v-for="thread in threads.data"
                                :key="thread.id"
                                type="button"
                                class="w-full border-b px-4 py-4 text-left transition-colors hover:bg-muted/50"
                                :class="{
                                    'bg-muted': String(thread.id) === String(selectedThreadId),
                                }"
                                @click="selectThread(thread.id)"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">
                                            {{ thread.subject || `Thread #${thread.id}` }}
                                        </p>
                                        <p class="mt-1 truncate text-xs text-muted-foreground">
                                            {{ thread.company?.company_name || 'No company' }}
                                        </p>
                                    </div>

                                    <span
                                        class="rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-wide"
                                        :class="
                                            thread.is_closed
                                                ? 'bg-slate-200 text-slate-700'
                                                : 'bg-emerald-100 text-emerald-700'
                                        "
                                    >
                                        {{ thread.is_closed ? 'Closed' : 'Open' }}
                                    </span>
                                </div>

                                <div
                                    class="mt-3 flex items-center justify-between gap-2 text-[11px] text-muted-foreground"
                                >
                                    <span class="truncate">
                                        {{ thread.category || 'General' }}
                                    </span>
                                    <span>{{ thread.messages_count ?? 0 }} messages</span>
                                </div>

                                <div
                                    class="mt-2 flex items-center justify-between gap-2 text-[11px] text-muted-foreground"
                                >
                                    <span class="truncate">
                                        {{
                                            thread.assigned_to?.name
                                                ? `Assigned: ${thread.assigned_to.name}`
                                                : 'Unassigned'
                                        }}
                                    </span>
                                    <span>
                                        {{
                                            thread.last_message_at_human ||
                                            thread.created_at_human ||
                                            'No activity'
                                        }}
                                    </span>
                                </div>
                            </button>
                        </CardContent>
                    </section>

                    <section
                        :class="[
                            'flex min-h-[42rem] min-w-0 flex-col overflow-hidden bg-card',
                            isThreadListOpen ? 'hidden lg:flex' : 'flex',
                        ]"
                    >
                        <div
                            class="flex items-start justify-between gap-3 border-b px-4 py-3"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-semibold">Conversation</p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ selectedThread?.subject || 'Select a thread' }}
                                </p>
                            </div>

                            <Button
                                class="shrink-0 lg:hidden"
                                size="sm"
                                variant="outline"
                                @click="isThreadListOpen = true"
                            >
                                Threads
                            </Button>
                        </div>

                        <div
                            v-if="loadingThread"
                            class="flex min-h-0 flex-1 items-center justify-center text-sm text-muted-foreground"
                        >
                            Loading messages...
                        </div>

                        <div
                            v-else-if="threadError"
                            class="flex min-h-0 flex-1 items-center justify-center px-6 text-center text-sm text-destructive"
                        >
                            {{ threadError }}
                        </div>

                        <div
                            v-else-if="selectedThread"
                            class="flex min-h-0 flex-1 flex-col"
                        >
                            <div class="space-y-4 border-b px-4 py-4">
                                <div
                                    v-if="actionError || actionSuccess"
                                    class="rounded-md border px-3 py-2 text-sm"
                                    :class="
                                        actionError
                                            ? 'border-destructive/30 bg-destructive/5 text-destructive'
                                            : 'border-emerald-300 bg-emerald-50 text-emerald-700'
                                    "
                                >
                                    {{ actionError || actionSuccess }}
                                </div>

                                <div
                                    class="grid gap-3"
                                    :class="
                                        canAssignThreads
                                            ? 'md:grid-cols-[minmax(0,1fr)_15rem_auto]'
                                            : 'md:grid-cols-1'
                                    "
                                >
                                    <template v-if="canAssignThreads">
                                        <label class="space-y-1 text-xs font-medium text-muted-foreground">
                                            <span>Assign To</span>
                                            <select
                                                v-model="assigneeId"
                                                class="w-full rounded-md border bg-background px-3 py-2 text-sm text-foreground"
                                            >
                                                <option value="">Unassigned</option>
                                                <option
                                                    v-for="staffUser in staffUsers"
                                                    :key="staffUser.id"
                                                    :value="String(staffUser.id)"
                                                >
                                                    {{ staffUser.label || staffUser.name || `User #${staffUser.id}` }}
                                                </option>
                                            </select>
                                        </label>
                                    </template>

                                    <div
                                        class="grid gap-3 sm:grid-cols-2"
                                        :class="canAssignThreads ? 'md:col-span-2' : ''"
                                    >
                                        <Button
                                            v-if="canAssignThreads"
                                            class="w-full"
                                            :disabled="savingAssignment"
                                            variant="outline"
                                            @click="saveAssignment"
                                        >
                                            {{ savingAssignment ? 'Saving...' : 'Save Assignment' }}
                                        </Button>

                                        <Button
                                            class="w-full"
                                            :disabled="togglingThread"
                                            variant="outline"
                                            @click="
                                                toggleThreadStatus(
                                                    selectedThread.is_closed ? 'reopen' : 'close',
                                                )
                                            "
                                        >
                                            {{
                                                togglingThread
                                                    ? 'Updating...'
                                                    : selectedThread.is_closed
                                                      ? 'Reopen Thread'
                                                      : 'Close Thread'
                                            }}
                                        </Button>
                                    </div>
                                </div>

                                <div
                                    class="grid gap-4 rounded-md border bg-muted/20 p-4 text-sm md:grid-cols-2"
                                >
                                    <div class="space-y-1">
                                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                            Company
                                        </p>
                                        <p>{{ selectedThread.company?.company_name || 'No company linked' }}</p>
                                    </div>

                                    <div class="space-y-1">
                                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                            Created By
                                        </p>
                                        <p>{{ selectedThread.created_by?.name || 'Unknown user' }}</p>
                                    </div>

                                    <div class="space-y-1">
                                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                            Category
                                        </p>
                                        <p>{{ selectedThread.category || 'General' }}</p>
                                    </div>

                                    <div class="space-y-1">
                                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                            Status
                                        </p>
                                        <p>
                                            {{
                                                selectedThread.is_closed
                                                    ? `Closed ${selectedThread.closed_at_human || ''}`.trim()
                                                    : 'Open'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="selectedThread.details"
                                    class="rounded-md border bg-background px-4 py-3"
                                >
                                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                        Thread Details
                                    </p>
                                    <p class="mt-2 whitespace-pre-wrap text-sm">
                                        {{ formatThreadDetails(selectedThread.details) }}
                                    </p>
                                </div>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4">
                                <div
                                    v-if="(selectedThread.messages?.length ?? 0) > 0"
                                    class="space-y-3"
                                >
                                    <div
                                        v-for="message in selectedThread.messages"
                                        :key="message.id"
                                        class="rounded-md border px-4 py-3"
                                        :class="
                                            message.is_internal
                                                ? 'border-amber-200 bg-amber-50/60'
                                                : ''
                                        "
                                    >
                                        <div class="flex items-center justify-between gap-2">
                                            <div>
                                                <p class="text-sm font-medium">
                                                    {{ message.sender?.name || 'Unknown sender' }}
                                                </p>
                                                <p
                                                    v-if="message.is_internal"
                                                    class="text-[11px] font-medium text-amber-700"
                                                >
                                                    Internal note
                                                </p>
                                            </div>

                                            <p class="text-[11px] text-muted-foreground">
                                                {{ message.created_at_human || message.created_at || '' }}
                                            </p>
                                        </div>

                                        <p class="mt-3 whitespace-pre-wrap text-sm">
                                            {{ message.body || 'No content' }}
                                        </p>

                                        <div
                                            v-if="(message.attachments?.length ?? 0) > 0"
                                            class="mt-3 space-y-2 rounded-md bg-background/70 p-3"
                                        >
                                            <p class="text-xs font-medium text-muted-foreground">
                                                Attachments
                                            </p>

                                            <a
                                                v-for="attachment in message.attachments"
                                                :key="attachment.id"
                                                :href="attachment.download_url || '#'"
                                                class="block rounded-md border px-3 py-2 text-sm text-primary underline-offset-4 hover:underline"
                                            >
                                                {{ attachment.original_name || `Attachment #${attachment.id}` }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="rounded-md border border-dashed px-4 py-10 text-center text-sm text-muted-foreground"
                                >
                                    No messages in this thread yet.
                                </div>
                            </div>

                            <div class="border-t px-4 py-4">
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm font-semibold">Reply</p>
                                        <p class="text-xs text-muted-foreground">
                                            Respond to the commuter, or add a private
                                            note for internal follow-up.
                                        </p>
                                    </div>

                                    <label class="space-y-1 text-xs font-medium text-muted-foreground">
                                        <span>Message</span>
                                        <textarea
                                            v-model="replyBody"
                                            rows="4"
                                            class="w-full rounded-md border bg-background px-3 py-2 text-sm text-foreground"
                                            placeholder="Type your reply here..."
                                        />
                                    </label>

                                    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
                                        <label class="space-y-1 text-xs font-medium text-muted-foreground">
                                            <span>Attachment</span>
                                            <input
                                                ref="replyFileInput"
                                                class="block w-full rounded-md border bg-background px-3 py-2 text-sm"
                                                type="file"
                                                @change="onReplyAttachmentChange"
                                            />
                                        </label>

                                        <label
                                            class="flex items-center gap-2 text-sm font-medium text-foreground md:pt-6"
                                        >
                                            <input
                                                v-model="replyIsInternal"
                                                class="h-4 w-4 rounded border"
                                                type="checkbox"
                                            />
                                            Internal note only
                                        </label>
                                    </div>

                                    <div class="flex justify-end">
                                        <Button
                                            :disabled="sendingReply"
                                            @click="sendReply"
                                        >
                                            {{ sendingReply ? 'Sending...' : 'Send Reply' }}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex min-h-0 flex-1 items-center justify-center px-6 text-center text-sm text-muted-foreground"
                        >
                            Select a thread from the left panel.
                        </div>
                    </section>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
