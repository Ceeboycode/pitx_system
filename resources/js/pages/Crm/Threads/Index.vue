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
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

import { index } from '@/routes/crm/threads';
import { type BreadcrumbItem } from '@/types';

import {
    Bug,
    BusFront,
    IdCard,
    Paperclip,
    SendHorizontal,
    Wrench,
} from 'lucide-vue-next';

type UserSummary = {
    id: number;
    name: string;
};

type AttachmentSummary = {
    id: number;
    original_name: string;
    mime_type?: string | null;
    size_bytes?: number | null;
    preview_url?: string | null;
    download_url: string;
};

type ThreadMessage = {
    id: number | string;
    body?: string | null;
    is_internal?: boolean;
    created_at_human?: string | null;
    created_at?: string | null;
    sender?: UserSummary | null;
    attachments?: AttachmentSummary[];
};

type ThreadSummary = {
    id: number | string;
    subject?: string | null;
    category?: string | null;
    is_closed?: boolean;
    company?: {
        company_name?: string | null;
    } | null;
    created_by?: UserSummary | null;
    assigned_to?: UserSummary | null;
    created_at_human?: string | null;
    last_message_at_human?: string | null;
    messages?: ThreadMessage[];
};

type ThreadDetail = ThreadSummary & {
    messages?: ThreadMessage[];
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
    canAssignThreads: boolean;
    assignees: UserSummary[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reports', href: index().url }];

function categoryLabel(raw: string | null | undefined): string {
    const map: Record<string, string> = {
        facilities: 'Facilities',
        terminal_operations: 'Terminal Operations',
        commuter_app: 'Commuter App',
        other: 'Other',
        platform_message: 'Platform Message',
    };
    return raw ? (map[raw] ?? raw) : 'General';
}

function statusLabel(thread: ThreadSummary): { text: string; class: string } {
    if (thread.is_closed) {
        return {
            text: 'Resolved',
            class: 'text-green-700 bg-green-50 border-green-200',
        };
    }
    const hasReplies = (thread.messages?.length ?? 0) > 1;
    if (hasReplies) {
        return {
            text: 'Ongoing',
            class: 'text-blue-700 bg-blue-50 border-blue-200',
        };
    }
    return {
        text: 'Open',
        class: 'text-amber-700 bg-amber-50 border-amber-200',
    };
}

const selectedThreadId = ref<number | string | null>(null);
const selectedThread = ref<ThreadDetail | null>(null);
const threadList = ref<ThreadSummary[]>(props.threads.data);
const loadingThread = ref(false);
const threadError = ref<string | null>(null);
const actionError = ref<string | null>(null);
const isThreadListOpen = ref(true);
const isSavingAssignment = ref(false);
const isSendingMessage = ref(false);
const draftMessage = ref('');
const internalOnly = ref(false);
const pendingFiles = ref<File[]>([]);
let activeRequest = 0;

const selectedAssigneeId = ref<string>('');
const attachmentInput = ref<HTMLInputElement | null>(null);
const messagePane = ref<HTMLElement | null>(null);

const hasSelectedThread = computed(() => selectedThread.value !== null);
const selectedThreadIsClosed = computed(
    () => selectedThread.value?.is_closed === true,
);

function csrfToken() {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

async function parseJson(response: Response) {
    const text = await response.text();

    if (!text) {
        return {};
    }

    try {
        return JSON.parse(text);
    } catch {
        return {};
    }
}

function errorMessage(payload: unknown, fallback: string) {
    if (
        payload &&
        typeof payload === 'object' &&
        'message' in payload &&
        typeof payload.message === 'string' &&
        payload.message.trim()
    ) {
        return payload.message;
    }

    if (
        payload &&
        typeof payload === 'object' &&
        'errors' in payload &&
        payload.errors &&
        typeof payload.errors === 'object'
    ) {
        const firstError = Object.values(
            payload.errors as Record<string, unknown>,
        )
            .flat()
            .find((value) => typeof value === 'string');

        if (typeof firstError === 'string' && firstError.trim()) {
            return firstError;
        }
    }

    return fallback;
}

function resetComposer() {
    draftMessage.value = '';
    internalOnly.value = false;
    pendingFiles.value = [];

    if (attachmentInput.value) {
        attachmentInput.value.value = '';
    }
}

function isImageAttachment(attachment: AttachmentSummary) {
    return attachment.mime_type?.startsWith('image/') ?? false;
}

function isVideoAttachment(attachment: AttachmentSummary) {
    return attachment.mime_type?.startsWith('video/') ?? false;
}

async function scrollMessagesToBottom() {
    await nextTick();

    if (messagePane.value) {
        messagePane.value.scrollTop = messagePane.value.scrollHeight;
    }
}

function upsertThreadSummary(thread: ThreadDetail | null) {
    if (!thread) return;

    threadList.value = threadList.value.map((item) =>
        String(item.id) === String(thread.id)
            ? {
                  ...item,
                  subject: thread.subject,
                  category: thread.category,
                  is_closed: thread.is_closed,
                  assigned_to: thread.assigned_to,
                  last_message_at_human: thread.last_message_at_human,
              }
            : item,
    );
}

async function loadThread(threadId: number | string | null) {
    if (!threadId) {
        selectedThread.value = null;
        selectedAssigneeId.value = '';
        threadError.value = null;
        return;
    }

    const requestId = ++activeRequest;
    loadingThread.value = true;
    threadError.value = null;
    actionError.value = null;

    try {
        const response = await fetch(`/crm/threads/${threadId}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await parseJson(response);

        if (!response.ok) {
            throw new Error(errorMessage(data, 'Failed to load thread.'));
        }

        if (requestId !== activeRequest) return;

        selectedThread.value = data?.data ?? null;
        upsertThreadSummary(selectedThread.value);
        selectedAssigneeId.value = selectedThread.value?.assigned_to?.id
            ? String(selectedThread.value.assigned_to.id)
            : '';
        void scrollMessagesToBottom();
    } catch (error) {
        if (requestId !== activeRequest) return;
        selectedThread.value = null;
        selectedAssigneeId.value = '';
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

function selectThread(threadId: number | string) {
    selectedThreadId.value = threadId;
    resetComposer();

    if (
        typeof window !== 'undefined' &&
        window.matchMedia('(max-width: 1023px)').matches
    ) {
        isThreadListOpen.value = false;
    }
}

async function saveAssignment() {
    if (!selectedThread.value) return;

    isSavingAssignment.value = true;
    actionError.value = null;

    try {
        const response = await fetch(
            `/crm/threads/${selectedThread.value.id}`,
            {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    assigned_to_user_id: selectedAssigneeId.value
                        ? Number(selectedAssigneeId.value)
                        : null,
                }),
            },
        );

        const data = await parseJson(response);

        if (!response.ok) {
            throw new Error(errorMessage(data, 'Failed to assign thread.'));
        }

        selectedThread.value = data?.data ?? null;
        upsertThreadSummary(selectedThread.value);
    } catch (error) {
        actionError.value =
            error instanceof Error ? error.message : 'Failed to assign thread.';
    } finally {
        isSavingAssignment.value = false;
    }
}

function onFilesSelected(event: Event) {
    const input = event.target as HTMLInputElement | null;
    pendingFiles.value = Array.from(input?.files ?? []);
}

async function uploadAttachments(
    threadId: number | string,
    messageId: number | string,
) {
    for (const file of pendingFiles.value) {
        const formData = new FormData();
        formData.append('file', file);

        const response = await fetch(
            `/crm/threads/${threadId}/messages/${messageId}/attachments`,
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            },
        );

        const data = await parseJson(response);

        if (!response.ok) {
            throw new Error(
                errorMessage(data, `Failed to upload ${file.name}.`),
            );
        }
    }
}

async function sendMessage() {
    if (!selectedThread.value) return;
    if (!draftMessage.value.trim()) {
        actionError.value = 'Message body is required.';
        return;
    }

    isSendingMessage.value = true;
    actionError.value = null;

    try {
        const response = await fetch(
            `/crm/threads/${selectedThread.value.id}/messages`,
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    body: draftMessage.value,
                    is_internal: internalOnly.value,
                }),
            },
        );

        const data = await parseJson(response);

        if (!response.ok) {
            throw new Error(errorMessage(data, 'Failed to send message.'));
        }

        const createdMessage = data?.data as ThreadMessage | undefined;
        if (!createdMessage) {
            throw new Error('Message response was incomplete.');
        }

        if (pendingFiles.value.length > 0) {
            await uploadAttachments(selectedThread.value.id, createdMessage.id);
        }

        await loadThread(selectedThread.value.id);
        resetComposer();
        void scrollMessagesToBottom();
    } catch (error) {
        actionError.value =
            error instanceof Error ? error.message : 'Failed to send message.';
    } finally {
        isSendingMessage.value = false;
    }
}

async function toggleThreadState() {
    if (!selectedThread.value) return;

    const endpoint = selectedThread.value.is_closed
        ? `/crm/threads/${selectedThread.value.id}/reopen`
        : `/crm/threads/${selectedThread.value.id}/close`;

    actionError.value = null;

    try {
        const response = await fetch(endpoint, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await parseJson(response);

        if (!response.ok) {
            throw new Error(
                errorMessage(data, 'Failed to update thread status.'),
            );
        }

        selectedThread.value = data?.data ?? null;
        upsertThreadSummary(selectedThread.value);
    } catch (error) {
        actionError.value =
            error instanceof Error
                ? error.message
                : 'Failed to update thread status.';
    }
}

watch(
    () => props.threads.data,
    (threads) => {
        threadList.value = threads;

        if (threads.length === 0) {
            selectedThreadId.value = null;
            selectedThread.value = null;
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

watch(
    () => selectedThread.value?.messages?.length ?? 0,
    () => {
        void scrollMessagesToBottom();
    },
);
</script>

<template>
    <Head title="Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-5 p-0">
                <!-- <Card class="mx-5"> -->
                <div
                    class="grid h-auto grid-cols-1 lg:grid-cols-[22rem_minmax(0,1fr)]"
                >
                    <section
                        :class="[
                            'flex min-h-60 min-w-0 flex-col gap-6 overflow-hidden border-b py-6 lg:border-r lg:border-b-0',
                            isThreadListOpen ? 'block' : 'hidden lg:block',
                        ]"
                    >
                        <CardHeader>
                            <CardTitle>Reports</CardTitle>
                            <CardDescription>
                                Superadmin can assign reports.
                            </CardDescription>
                        </CardHeader>
                        <CardContent
                            class="flex min-h-0 flex-1 flex-col space-y-4"
                        >
                            <div class="w-full max-w-sm">
                                <SearchInput
                                    :route="index().url"
                                    :initial-value="filters.search"
                                    placeholder="Search reports..."
                                    :only="['threads', 'filters', 'flash']"
                                    :debounce="350"
                                />
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-if="threadList.length === 0"
                                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                                >
                                    No reports found.
                                </div>

                                <button
                                    v-for="thread in threadList"
                                    :key="thread.id"
                                    type="button"
                                    class="w-full border-b px-4 py-3 text-left transition-colors hover:bg-muted/50"
                                    :class="{
                                        'bg-muted':
                                            String(thread.id) ===
                                            String(selectedThreadId),
                                    }"
                                    @click="selectThread(thread.id)"
                                >
                                    <div class="flex justify-between gap-2 p-0">
                                        <span
                                            class="truncate text-sm font-medium"
                                        >
                                            {{
                                                thread.subject ||
                                                `Thread #${thread.id}`
                                            }}
                                        </span>
                                        <span>
                                            <span
                                                class="text-end text-blue-900"
                                                v-if="
                                                    categoryLabel(
                                                        thread.category,
                                                    ) == 'Facilities'
                                                "
                                            >
                                                <BusFront
                                                    class="inline h-4 w-4"
                                                />
                                            </span>
                                            <span
                                                class="text-end text-blue-900"
                                                v-if="
                                                    categoryLabel(
                                                        thread.category,
                                                    ) == 'Terminal Operations'
                                                "
                                            >
                                                <IdCard
                                                    class="inline h-4 w-4"
                                                />
                                            </span>
                                            <span
                                                class="text-end text-blue-900"
                                                v-if="
                                                    categoryLabel(
                                                        thread.category,
                                                    ) == 'Commuter App'
                                                "
                                            >
                                                <Bug class="inline h-4 w-4" />
                                            </span>
                                            <span
                                                class="text-end text-blue-900"
                                                v-if="
                                                    categoryLabel(
                                                        thread.category,
                                                    ) == 'Other'
                                                "
                                            >
                                                <Wrench
                                                    class="inline h-4 w-4"
                                                />
                                            </span>
                                        </span>
                                    </div>
                                    <div
                                        class="mt-2 flex items-center justify-between gap-2 text-[11px] text-muted-foreground"
                                    >
                                        <div
                                            class="flex min-w-0 items-center gap-1.5 truncate"
                                        >
                                            <span
                                                class="inline-flex shrink-0 items-center rounded border px-1.5 py-0.5 text-[10px] font-medium"
                                                :class="
                                                    statusLabel(thread).class
                                                "
                                            >
                                                {{ statusLabel(thread).text }}
                                            </span>
                                            <span class="truncate">
                                                <!-- {{ categoryLabel(thread.category) }} -->
                                                <span
                                                    v-if="
                                                        thread.assigned_to?.name
                                                    "
                                                >
                                                    ·
                                                    {{
                                                        thread.assigned_to.name
                                                    }}
                                                </span>
                                            </span>
                                        </div>
                                        <span class="shrink-0">
                                            {{
                                                thread.last_message_at_human ||
                                                thread.created_at_human ||
                                                'No activity'
                                            }}
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </CardContent>
                    </section>

                    <section
                        :class="[
                            'flex min-h-60 min-w-0 flex-col gap-6 overflow-hidden py-4',
                            isThreadListOpen ? 'hidden lg:block' : 'block',
                        ]"
                    >
                        <div
                            class="flex items-center justify-between gap-3 border-b ps-4 pe-6 pb-4"
                        >
                            <div class="w-full">
                                <p class="font-semibold">
                                    {{
                                        selectedThread?.subject ||
                                        'Select a report'
                                    }}
                                </p>
                                <span
                                    v-if="selectedThread?.subject != null"
                                    class="flex gap-2 text-sm text-muted-foreground"
                                >
                                    <span>
                                        <span
                                            class="text-end text-blue-900"
                                            v-if="
                                                categoryLabel(
                                                    selectedThread.category,
                                                ) == 'Facilities'
                                            "
                                        >
                                            <BusFront class="inline h-4 w-4" />
                                        </span>
                                        <span
                                            class="text-end text-blue-900"
                                            v-if="
                                                categoryLabel(
                                                    selectedThread.category,
                                                ) == 'Terminal Operations'
                                            "
                                        >
                                            <IdCard class="inline h-4 w-4" />
                                        </span>
                                        <span
                                            class="text-end text-blue-900"
                                            v-if="
                                                categoryLabel(
                                                    selectedThread.category,
                                                ) == 'Commuter App'
                                            "
                                        >
                                            <Bug class="inline h-4 w-4" />
                                        </span>
                                        <span
                                            class="text-end text-blue-900"
                                            v-if="
                                                categoryLabel(
                                                    selectedThread.category,
                                                ) == 'Other'
                                            "
                                        >
                                            <Wrench class="inline h-4 w-4" />
                                        </span>
                                    </span>
                                    <span class="text-sm font-medium">
                                        {{
                                            categoryLabel(
                                                selectedThread.category,
                                            )
                                        }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded border px-1.5 py-0.5 text-[10px] font-medium"
                                        :class="
                                            statusLabel(selectedThread).class
                                        "
                                    >
                                        {{ statusLabel(selectedThread).text }}
                                    </span>
                                </span>
                            </div>

                            <div class="inline">
                                <div
                                    class="grid gap-3 text-xs text-muted-foreground lg:grid-cols-[minmax(0,1fr)_18rem]"
                                >
                                    <div class="space-y-2">
                                        <label
                                            v-if="canAssignThreads"
                                            class="flex flex-col gap-1"
                                        >
                                            <div class="flex gap-2">
                                                <select
                                                    v-model="selectedAssigneeId"
                                                    class="h-9 flex-1 rounded-md border bg-background px-3 text-sm"
                                                >
                                                    <option value="">
                                                        Unassigned
                                                    </option>
                                                    <option
                                                        v-for="assignee in assignees"
                                                        :key="assignee.id"
                                                        :value="
                                                            String(assignee.id)
                                                        "
                                                    >
                                                        {{ assignee.name }}
                                                    </option>
                                                </select>
                                                <Button
                                                    size="sm"
                                                    :disabled="
                                                        isSavingAssignment
                                                    "
                                                    @click="saveAssignment"
                                                >
                                                    Assign
                                                </Button>
                                                <!-- TODO: display these buttons as a dropdown actions icon button isntead of having them all out like that -->
                                                <Button
                                                    v-if="selectedThread"
                                                    size="sm"
                                                    variant="outline"
                                                    @click="toggleThreadState"
                                                >
                                                    {{
                                                        selectedThread.is_closed
                                                            ? 'Reopen'
                                                            : 'Close'
                                                    }}
                                                    Report
                                                </Button>
                                                <Button
                                                    class="lg:hidden"
                                                    size="sm"
                                                    variant="outline"
                                                    @click="
                                                        isThreadListOpen = true
                                                    "
                                                >
                                                    Reports
                                                </Button>
                                            </div>
                                        </label>

                                        <p v-else>
                                            Assigned to:
                                            {{
                                                selectedThread?.assigned_to
                                                    ?.name || 'Unassigned'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <p
                                    v-if="actionError"
                                    class="mt-3 text-sm text-destructive"
                                >
                                    {{ actionError }}
                                </p>
                            </div>

                            <!-- <div class="flex items-center gap-2">
                                <Button
                                    v-if="selectedThread"
                                    size="sm"
                                    variant="outline"
                                    @click="toggleThreadState"
                                >
                                    {{ selectedThread.is_closed ? 'Reopen' : 'Close' }} Report
                                </Button>
                                <Button
                                    class="lg:hidden"
                                    size="sm"
                                    variant="outline"
                                    @click="isThreadListOpen = true"
                                >
                                    Reports
                                </Button>
                            </div> -->
                        </div>

                        <div
                            v-if="loadingThread"
                            class="flex min-h-0 flex-1 items-center justify-center p-10 text-sm text-muted-foreground"
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
                            <!-- <div class="px-4 py-3">
                                <div
                                    class="grid gap-3 text-xs text-muted-foreground lg:grid-cols-[minmax(0,1fr)_18rem]"
                                >
                                    <div class="space-y-2">
                                        <label
                                            v-if="canAssignThreads"
                                            class="flex flex-col gap-1"
                                        >
                                            <div class="flex gap-2">
                                                <select
                                                    v-model="selectedAssigneeId"
                                                    class="h-9 flex-1 rounded-md border bg-background px-3 text-sm"
                                                >
                                                    <option value="">
                                                        Unassigned
                                                    </option>
                                                    <option
                                                        v-for="assignee in assignees"
                                                        :key="assignee.id"
                                                        :value="String(assignee.id)"
                                                    >
                                                        {{ assignee.name }}
                                                    </option>
                                                </select>
                                                <Button
                                                    size="sm"
                                                    :disabled="isSavingAssignment"
                                                    @click="saveAssignment"
                                                >
                                                    Assign
                                                </Button>
                                            </div>
                                        </label>

                                        <p v-else>
                                            Assigned to:
                                            {{ selectedThread.assigned_to?.name || 'Unassigned' }}
                                        </p>
                                    </div>
                                </div>

                                <p
                                    v-if="actionError"
                                    class="mt-3 text-sm text-destructive"
                                >
                                    {{ actionError }}
                                </p>
                            </div> -->

                            <div
                                ref="messagePane"
                                class="min-h-0 flex-1 overflow-y-auto p-4"
                            >
                                <div
                                    v-if="
                                        (selectedThread.messages?.length ?? 0) >
                                        0
                                    "
                                    class="flex min-h-full flex-col justify-end space-y-3"
                                >
                                    <div
                                        v-for="message in selectedThread.messages"
                                        :key="message.id"
                                        :class="{
                                            'flex w-full': true,
                                            'justify-end':
                                                message.sender?.id ===
                                                selectedThread.created_by?.id,
                                        }"
                                    >
                                        <div>
                                            <p
                                                v-if="
                                                    message.sender?.id !=
                                                    selectedThread.created_by
                                                        ?.id
                                                "
                                                class="mb-1 px-3 text-xs font-medium"
                                            >
                                                {{
                                                    message.sender?.name ||
                                                    'Unknown sender'
                                                }}
                                            </p>
                                            <!-- TODO: paragraph element above shoudl not show if the  previous message's sender is the same as this message's sender-->
                                            <div
                                                :class="{
                                                    'group max-w-lg min-w-0 rounded-md border px-3 py-2': true,
                                                    'border-blue-300 bg-blue-50/70':
                                                        message.is_internal,
                                                }"
                                            >
                                                <!-- TODO: when this div is hovered, this message should show the created_at_human data -->
                                                <p
                                                    class="text-sm whitespace-pre-wrap"
                                                >
                                                    {{
                                                        message.body ||
                                                        'No content'
                                                    }}
                                                </p>
                                                <p
                                                    v-if="message.is_internal"
                                                    class="mt-2 text-[11px] font-medium text-blue-700"
                                                >
                                                    Internal note
                                                </p>

                                                <div
                                                    v-if="
                                                        (message.attachments
                                                            ?.length ?? 0) > 0
                                                    "
                                                    class="mt-2 space-y-3"
                                                >
                                                    <div
                                                        v-for="attachment in message.attachments"
                                                        :key="attachment.id"
                                                    >
                                                        <img
                                                            v-if="
                                                                isImageAttachment(
                                                                    attachment,
                                                                ) &&
                                                                attachment.preview_url
                                                            "
                                                            :src="
                                                                attachment.preview_url
                                                            "
                                                            :alt="
                                                                attachment.original_name
                                                            "
                                                            class="max-h-72 w-full rounded-md border object-cover"
                                                        />

                                                        <video
                                                            v-else-if="
                                                                isVideoAttachment(
                                                                    attachment,
                                                                ) &&
                                                                attachment.preview_url
                                                            "
                                                            :src="
                                                                attachment.preview_url
                                                            "
                                                            controls
                                                            preload="metadata"
                                                            class="max-h-72 w-full rounded-md border bg-black"
                                                        />

                                                        <a
                                                            :href="
                                                                attachment.download_url
                                                            "
                                                            class="my-2 inline-flex items-center rounded-md border px-2 py-1 text-xs text-primary hover:bg-muted"
                                                        >
                                                            {{
                                                                attachment.original_name
                                                            }}
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- TODO: fix hover function, only shows the paragraph element below when the div with class 'group' is hovered -->
                                            <p
                                                :class="{
                                                    'hidden px-3 text-[11px] text-muted-foreground transition-all group-hover:block': true,
                                                    'text-end':
                                                        message.sender?.id ===
                                                        selectedThread
                                                            .created_by?.id,
                                                }"
                                            >
                                                {{
                                                    message.created_at_human ||
                                                    message.created_at ||
                                                    ''
                                                }}
                                            </p>
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

                            <div
                                v-if="!selectedThreadIsClosed"
                                class="border-t p-4"
                            >
                                <div class="space-y-3">
                                    <div
                                        class="flex items-end justify-between gap-2"
                                    >
                                        <textarea
                                            v-model="draftMessage"
                                            class="min-h-fit w-full rounded-md border bg-background px-3 py-2 text-sm"
                                            placeholder="Write a reply or internal note..."
                                        />
                                        <Button>
                                            <!-- <input
                                                ref="attachmentInput"
                                                type="file"
                                                accept="image/*,video/*"
                                                multiple
                                                @change="onFilesSelected"
                                            /> -->
                                            <input
                                                ref="attachmentInput"
                                                type="file"
                                                accept="image/*,video/*"
                                                multiple
                                            />
                                            <!-- TODO: fix this button -->
                                            <Paperclip class="h-4 w-4" />
                                        </Button>
                                        <Button @click="sendMessage">
                                            <SendHorizontal class="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <div
                                        class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                                    >
                                        <div class="flex flex-col gap-3">
                                            <label
                                                class="flex items-center gap-2 text-sm"
                                            >
                                                <input
                                                    v-model="internalOnly"
                                                    type="checkbox"
                                                    class="h-4 w-4 rounded border"
                                                />
                                                Post as internal note
                                            </label>
                                            <!-- TODO: fix this code when the first input for images/videos are fixed -->
                                            <div class="flex flex-col gap-2">
                                                <input
                                                    ref="attachmentInput"
                                                    type="file"
                                                    accept="image/*,video/*"
                                                    multiple
                                                    @change="onFilesSelected"
                                                />
                                                <p
                                                    v-if="
                                                        pendingFiles.length > 0
                                                    "
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        pendingFiles.length
                                                    }}
                                                    file(s) selected
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex min-h-0 flex-1 items-center justify-center px-6 text-center text-sm text-muted-foreground"
                        >
                            Select a report from the left panel.
                        </div>
                    </section>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
