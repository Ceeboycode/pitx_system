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
// import ArchiveVehicleDialog from '@/components/vehicle/ArchiveVehicleDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// import InertiaPagination from '@/components/InertiaPagination.vue';
// import { create, edit, index, trash } from '@/routes/crm/threads';
import { index, show } from '@/routes/crm/threads';
import { type BreadcrumbItem } from '@/types';

type ThreadSummary = {
    id: number | string;
    subject?: string | null;
    category?: string | null;
    is_closed?: boolean;
    company?: {
        company_name?: string | null;
    } | null;
    created_by?: {
        name?: string | null;
    } | null;
    assigned_to?: {
        name?: string | null;
    } | null;
    created_at_human?: string | null;
    last_message_at_human?: string | null;
};

type ThreadMessage = {
    id: number | string;
    body?: string | null;
    is_internal?: boolean;
    created_at_human?: string | null;
    created_at?: string | null;
    sender?: {
        name?: string | null;
    } | null;
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
let activeRequest = 0;

async function loadThread(threadId: number | string | null) {
    if (!threadId) {
        selectedThread.value = null;
        threadError.value = null;
        return;
    }

    const requestId = ++activeRequest;
    loadingThread.value = true;
    threadError.value = null;

    try {
        const route = show({ thread: threadId });
        const response = await fetch(route.url, {
            method: route.method.toUpperCase(),
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to load thread');
        }

        const data = await response.json();
        if (requestId !== activeRequest) return;
        selectedThread.value = data?.data ?? null;
    } catch {
        if (requestId !== activeRequest) return;
        selectedThread.value = null;
        threadError.value = 'Unable to load messages for this thread.';
    } finally {
        if (requestId === activeRequest) {
            loadingThread.value = false;
        }
    }
}

function selectThread(threadId: number | string) {
    selectedThreadId.value = threadId;

    if (
        typeof window !== 'undefined' &&
        window.matchMedia('(max-width: 1023px)').matches
    ) {
        isThreadListOpen.value = false;
    }
}

watch(
    () => props.threads.data,
    (threads) => {
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
</script>

<template>
    <Head title="Threads" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-5">
              <div
                  class="grid h-auto grid-cols-1 lg:grid-cols-[20rem_minmax(0,1fr)]"
              >
                <section
                    :class="[
                        'flex min-h-60 min-w-0 flex-col overflow-hidden border-b lg:border-b-0 lg:border-r gap-6',
                        isThreadListOpen ? 'block' : 'hidden lg:block',
                    ]"
                >
                  <CardHeader>
                    <CardTitle>Threads</CardTitle>
                    <CardDescription
                        >List of all threads in the system.</CardDescription
                    >
                  </CardHeader>
                  <CardContent class="flex min-h-0 flex-1 flex-col space-y-4">
                    <div class="w-full max-w-sm">
                        <SearchInput
                            :route="index().url"
                            :initial-value="filters.search"
                            placeholder="Search threads..."
                            :only="['threads', 'filters', 'flash']"
                            :debounce="350"
                        />
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto">
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
                            class="w-full border-b px-4 py-3 text-left transition-colors hover:bg-muted/50"
                            :class="{
                                'bg-muted': String(thread.id) === String(selectedThreadId),
                            }"
                            @click="selectThread(thread.id)"
                        >
                            <p class="truncate text-sm font-medium">
                                {{
                                    thread.subject ||
                                    `Thread #${thread.id}`
                                }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    thread.company?.company_name ||
                                    'No company'
                                }}
                            </p>
                            <div
                                class="mt-2 flex items-center justify-between gap-2 text-[11px] text-muted-foreground"
                            >
                                <span class="truncate">
                                    {{ thread.category || 'General' }}
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
                    </div>
                  </CardContent>
                </section>

                <section
                    :class="[
                        'flex min-h-60 min-w-0 flex-col overflow-hidden bg-card',
                        isThreadListOpen ? 'hidden lg:block' : 'block',
                    ]"
                >
                    <div
                        class="flex items-start justify-between gap-2 border-b px-4 py-3"
                    >
                        <div>
                            <p class="text-sm font-semibold">
                                Messages
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    selectedThread?.subject ||
                                    'Select a thread'
                                }}
                            </p>
                        </div>

                        <Button
                            class="lg:hidden"
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
                        class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4"
                    >
                        <div
                            class="rounded-md border bg-muted/30 px-3 py-2 text-xs text-muted-foreground"
                        >
                            <p>
                                Category:
                                {{
                                    selectedThread.category ||
                                    'General'
                                }}
                            </p>
                            <p>
                                Assigned to:
                                {{
                                    selectedThread.assigned_to?.name ||
                                    'Unassigned'
                                }}
                            </p>
                        </div>

                        <div
                            v-if="
                                (selectedThread.messages?.length ?? 0) >
                                0
                            "
                            class="space-y-3"
                        >
                            <div
                                v-for="message in selectedThread.messages"
                                :key="message.id"
                                class="rounded-md border px-3 py-2"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <p class="text-xs font-medium">
                                        {{
                                            message.sender?.name ||
                                            'Unknown sender'
                                        }}
                                    </p>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        {{
                                            message.created_at_human ||
                                            message.created_at ||
                                            ''
                                        }}
                                    </p>
                                </div>
                                <p
                                    class="mt-2 whitespace-pre-wrap text-sm"
                                >
                                    {{ message.body || 'No content' }}
                                </p>
                                <p
                                    v-if="message.is_internal"
                                    class="mt-2 text-[11px] font-medium text-amber-600"
                                >
                                    Internal note
                                </p>
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
