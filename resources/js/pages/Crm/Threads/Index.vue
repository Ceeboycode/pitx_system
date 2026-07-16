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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

import { index } from '@/routes/crm/threads';
import { type BreadcrumbItem } from '@/types';
import { fetchWithAuth } from '@/utils/fetchWithAuth';

import {
    RiAttachment2 as Paperclip,
    RiBugLine as Bug,
    RiBus2Line as BusFront,
    RiIdCardLine as IdCard,
    RiMore2Line as MoreHorizontal,
    RiSendPlane2Line as SendHorizontal,
    RiToolsLine as Wrench,
} from 'vue-remix-icons';

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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: index().url },
];

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
const isTogglingState = ref(false);
const draftMessage = ref('');
const internalOnly = ref(false);
const pendingFiles = ref<File[]>([]);
let activeRequest = 0;

const selectedAssigneeId = ref<string>('unassigned');
const attachmentInput = ref<HTMLInputElement | null>(null);
const messagePane = ref<HTMLElement | null>(null);

const page = usePage();
const currentUserId = computed(
    () => ((page.props.auth as { user?: UserSummary | null } | undefined)?.user?.id ?? null),
);

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

function autoResizeTextarea(event: Event) {
    const textarea = event.target as HTMLTextAreaElement;
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
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
        selectedAssigneeId.value = 'unassigned';
        threadError.value = null;
        return;
    }

    const requestId = ++activeRequest;
    loadingThread.value = true;
    threadError.value = null;
    actionError.value = null;

    try {
        const response = await fetchWithAuth(`/crm/threads/${threadId}`, {
            method: 'GET',
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
            : 'unassigned';
        void scrollMessagesToBottom();
    } catch (error) {
        if (requestId !== activeRequest) return;
        selectedThread.value = null;
        selectedAssigneeId.value = 'unassigned';
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
        const response = await fetch(`/crm/threads/${selectedThread.value.id}`, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                assigned_to_user_id: selectedAssigneeId.value === 'unassigned' 
                    ? null 
                    : (selectedAssigneeId.value ? Number(selectedAssigneeId.value) : null),
            }),
        });

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

        const response = await fetchWithAuth(
            `/crm/threads/${threadId}/messages/${messageId}/attachments`,
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
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
        const response = await fetchWithAuth(
            `/crm/threads/${selectedThread.value.id}/messages`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
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

    isTogglingState.value = true;
    actionError.value = null;

    try {
        const response = await fetchWithAuth(endpoint, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
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
    } finally {
        isTogglingState.value = false;
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
            <Card class="p-0 max-h-[85vh]">
                <!-- CODE: <Card class="mx-5"> -->
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

                            <div class="min-h-0 flex-1 overflow-y-auto max-h-[60vh]">
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
                                                <!-- CODE: {{ categoryLabel(thread.category) }} -->
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
                                    <span class=""text-sm font-medium>
                                        {{ categoryLabel(selectedThread.category) }}
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
                                <DropdownMenu v-if="canAssignThreads">
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="outline"
                                            class="rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground cursor-pointer"
                                        >
                                            <MoreHorizontal class="h-4 w-4" />
                                            
                                        </Button>
                                    </DropdownMenuTrigger>

                                    <DropdownMenuContent
                                        align="end"
                                        class="w-fit rounded-lg border-slate-200 shadow-lg"
                                    >
                                        <DropdownMenuLabel
                                            class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                        >
                                            {{ selectedThread?.subject || 'Report Actions' }}
                                        </DropdownMenuLabel>
                                        <DropdownMenuSeparator />

                                        <div class="p-2">
                                            <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                                                Assign to
                                            </label>
                                            <Select
                                                v-model="selectedAssigneeId"
                                                class="mt-1"
                                            >
                                                <SelectTrigger class="h-8 w-full">
                                                    <SelectValue placeholder="Select assignee..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="unassigned">
                                                        Unassigned
                                                    </SelectItem>
                                                    <SelectItem
                                                        v-for="assignee in assignees"
                                                        :key="assignee.id"
                                                        :value="String(assignee.id)"
                                                    >
                                                        {{ assignee.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <Button
                                                size="sm"
                                                class="mt-2 w-full"
                                                :disabled="isSavingAssignment"
                                                @click="saveAssignment"
                                            >
                                                {{ isSavingAssignment ? 'Assigning...' : 'Assign' }}
                                            </Button>
                                        </div>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem
                                            v-if="selectedThread"
                                            class="cursor-pointer rounded-lg"
                                            :disabled="isTogglingState"
                                            @click="toggleThreadState"
                                        >   
                                            <Button variant="outline" class="w-full cursor-pointer hover:bg-slate-100">
                                                <span class="flex items-center">
                                                    {{
                                                        selectedThread.is_closed
                                                            ? 'Reopen'
                                                            : 'Close'
                                                    }}
                                                    Report
                                                </span>
                                            </Button>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            class="lg:hidden cursor-pointer rounded-lg"
                                            @click="isThreadListOpen = true"
                                        >
                                            <span class="flex items-center">
                                                Reports
                                            </span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>

                                <p v-else class="text-sm text-muted-foreground">
                                    Assigned to:
                                    {{
                                        selectedThread?.assigned_to
                                            ?.name || 'Unassigned'
                                    }}
                                </p>

                                <p
                                    v-if="actionError"
                                    class="mt-3 text-sm text-destructive"
                                >
                                    {{ actionError }}
                                </p>
                            </div>
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
                            class="flex min-h-0 flex-1 flex-col items-between"
                        >

                            <div
                                ref="messagePane"
                                class="min-h-0 flex-auto overflow-y-auto p-4 max-h-[50vh] space-y-6"
                            >
                                <div
                                    v-if="
                                        (selectedThread.messages?.length ?? 0) >
                                        0
                                    "
                                    class="flex min-h-full flex-col justify-end space-y-3"
                                >
                                    <div
                                        v-for="(message, index) in selectedThread.messages"
                                        :key="message.id"
                                        :class="{
                                            'w-full flex': true,
                                            'justify-end': message.sender?.id === currentUserId,
                                        }"
                                    >
                                        <!-- CODE: <div> {{ message.sender }} </div> -->
                                        <!-- CODE: <div> {{  }} </div> -->

                                        <div>
                                            <p
                                                v-if="
                                                    message.sender?.id !== currentUserId &&
                                                    (
                                                        index === 0 ||
                                                        selectedThread.messages[index - 1]?.sender?.id !==
                                                            message.sender?.id
                                                    )
                                                "
                                                class="text-xs font-medium mb-1 px-3"
                                            >
                                                {{
                                                    message.sender?.name ||
                                                    'Unknown sender'
                                                }}
                                            </p>
                                            <div class="group">
                                                <div
                                                    :class="{
                                                        'rounded-md border px-3 py-2 max-w-lg min-w-0': true,
                                                        'border-blue-300 bg-blue-50/70': message.is_internal
                                                    }"
                                                >
                                                    <p class="whitespace-pre-wrap text-sm">
                                                        {{ message.body || 'No content' }}
                                                    </p>
                                                CODE: <p
                                                    v-if="message.is_internal"
                                                    class="mt-2 text-[11px] font-medium text-blue-700"
                                                >
                                                    Internal note
                                                </p> -->

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
                                                <p
                                                    :class="{
                                                        'text-[11px] text-muted-foreground px-3 max-h-0 overflow-hidden opacity-0 transition-all duration-300 ease-out group-hover:max-h-6 group-hover:opacity-100': true,
                                                        'text-end': message.sender?.id === currentUserId,
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
                                class="border-t p-4 flex-1"
                            >
                                <div class="py-2">
                                    <div class="flex gap-2">
                                        <textarea
                                            v-model="draftMessage"
                                            class="min-h-fit w-full rounded-md border bg-background px-3 py-2 text-sm resize-none"
                                            placeholder="Write a reply or internal note..."
                                            rows="1"
                                            @input="autoResizeTextarea"
                                        />
                                        <div class="flex flex-col gap-2">
                                            <Button :disabled="isSendingMessage" @click="attachmentInput?.click()">
                                                <Paperclip class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                @click="sendMessage"
                                            >
                                                <SendHorizontal class="h-4 w-4" />
                                            </Button>
                                            <label
                                                class="flex items-center gap-2 text-sm whitespace-nowrap"
                                            >
                                                <input
                                                    v-model="internalOnly"
                                                    type="checkbox"
                                                    class="h-4 w-4 rounded border"
                                                />
                                                Post as internal note
                                            </label>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <input
                                            ref="attachmentInput"
                                            type="file"
                                            accept="image/*,video/*"
                                            multiple
                                            class="hidden"
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
