<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { ArrowLeft, MessageSquare, Plus, Send } from 'lucide-vue-next'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'

type Thread = {
    id: number
    subject: string
    is_closed: boolean
    created_by: { id: number; name: string } | null
    messages_count: number
    last_message_at: string | null
    last_message_at_human: string | null
    created_at: string | null
    created_at_human: string | null
}

type Message = {
    id: number
    body: string
    created_at: string | null
    created_at_human: string | null
    sender: { id: number; name: string } | null
}

const page = usePage()
const currentUserId = computed(() => (page.props.auth as any)?.user?.id)

const open = ref(false)
const view = ref<'list' | 'detail' | 'new'>('list')
const threads = ref<Thread[]>([])
const selectedThread = ref<Thread | null>(null)
const messages = ref<Message[]>([])

const isLoadingThreads = ref(false)
const isLoadingMessages = ref(false)
const isSending = ref(false)
const isCreating = ref(false)

const listError = ref('')
const detailError = ref('')
const sendError = ref('')
const createError = ref('')

const replyBody = ref('')
const newSubject = ref('')
const newBody = ref('')

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''
}

async function parseJson(response: Response) {
    const text = await response.text()
    if (!text) return {}
    try { return JSON.parse(text) } catch { return { message: text } }
}

function errorMessage(data: any, fallback: string): string {
    return data?.message ?? data?.error ?? fallback
}

async function loadThreads() {
    isLoadingThreads.value = true
    listError.value = ''
    try {
        const response = await fetch('/messaging/threads', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
        const data = await parseJson(response)
        if (!response.ok) throw new Error(errorMessage(data, 'Failed to load messages.'))
        threads.value = data.data ?? []
    } catch (e: any) {
        listError.value = e.message
    } finally {
        isLoadingThreads.value = false
    }
}

async function openThread(thread: Thread) {
    selectedThread.value = thread
    view.value = 'detail'
    replyBody.value = ''
    sendError.value = ''
    detailError.value = ''
    isLoadingMessages.value = true
    try {
        const response = await fetch(`/messaging/threads/${thread.id}/messages`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
        const data = await parseJson(response)
        if (!response.ok) throw new Error(errorMessage(data, 'Failed to load messages.'))
        messages.value = data.data ?? []
    } catch (e: any) {
        detailError.value = e.message
    } finally {
        isLoadingMessages.value = false
    }
}

async function sendReply() {
    if (!replyBody.value.trim() || !selectedThread.value) return
    isSending.value = true
    sendError.value = ''
    try {
        const response = await fetch(`/messaging/threads/${selectedThread.value.id}/messages`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ body: replyBody.value.trim() }),
        })
        const data = await parseJson(response)
        if (!response.ok) throw new Error(errorMessage(data, 'Failed to send message.'))
        messages.value.push(data.data)
        replyBody.value = ''
        const idx = threads.value.findIndex((t) => t.id === selectedThread.value!.id)
        if (idx !== -1) threads.value[idx].messages_count++
    } catch (e: any) {
        sendError.value = e.message
    } finally {
        isSending.value = false
    }
}

async function createThread() {
    if (!newSubject.value.trim() || !newBody.value.trim()) return
    isCreating.value = true
    createError.value = ''
    try {
        const response = await fetch('/messaging/threads', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                subject: newSubject.value.trim(),
                body: newBody.value.trim(),
            }),
        })
        const data = await parseJson(response)
        if (!response.ok) throw new Error(errorMessage(data, 'Failed to create conversation.'))
        threads.value.unshift(data.data)
        newSubject.value = ''
        newBody.value = ''
        view.value = 'list'
    } catch (e: any) {
        createError.value = e.message
    } finally {
        isCreating.value = false
    }
}

function onOpenChange(val: boolean) {
    open.value = val
    if (val) {
        view.value = 'list'
        loadThreads()
    }
}

function goBack() {
    view.value = 'list'
    selectedThread.value = null
    messages.value = []
    detailError.value = ''
}
</script>

<template>
    <Popover :open="open" @update:open="onOpenChange">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                size="icon"
                class="relative h-9 w-9 shrink-0 rounded-full"
            >
                <MessageSquare class="h-4 w-4" />
                <span class="sr-only">Open messages</span>
            </Button>
        </PopoverTrigger>

        <PopoverContent align="end" class="w-[380px] p-0">
            <!-- Thread list -->
            <template v-if="view === 'list'">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <p class="text-sm font-semibold">Messages</p>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="h-8 gap-1.5 px-2 text-xs"
                        @click="view = 'new'; createError = ''"
                    >
                        <Plus class="h-3.5 w-3.5" />
                        New
                    </Button>
                </div>

                <div
                    v-if="isLoadingThreads"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    Loading...
                </div>

                <div
                    v-else-if="listError"
                    class="px-4 py-6 text-center text-sm text-destructive"
                >
                    {{ listError }}
                </div>

                <div
                    v-else-if="threads.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    No conversations yet.
                </div>

                <div v-else class="max-h-[420px] divide-y overflow-y-auto">
                    <button
                        v-for="thread in threads"
                        :key="thread.id"
                        type="button"
                        class="w-full px-4 py-3 text-left transition hover:bg-muted"
                        @click="openThread(thread)"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="min-w-0 flex-1 truncate text-sm font-medium leading-snug">
                                {{ thread.subject }}
                            </p>
                            <Badge
                                v-if="thread.is_closed"
                                variant="secondary"
                                class="shrink-0 text-[10px]"
                            >
                                Closed
                            </Badge>
                        </div>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ thread.last_message_at_human ?? thread.created_at_human }}
                            &middot;
                            {{ thread.messages_count }}
                            {{ thread.messages_count === 1 ? 'message' : 'messages' }}
                        </p>
                    </button>
                </div>
            </template>

            <!-- Thread detail -->
            <template v-else-if="view === 'detail' && selectedThread">
                <div class="flex items-center gap-2 border-b px-3 py-3">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7 shrink-0"
                        @click="goBack"
                    >
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                    <p class="min-w-0 flex-1 truncate text-sm font-semibold">
                        {{ selectedThread.subject }}
                    </p>
                    <Badge
                        v-if="selectedThread.is_closed"
                        variant="secondary"
                        class="shrink-0 text-[10px]"
                    >
                        Closed
                    </Badge>
                </div>

                <div class="max-h-[320px] space-y-3 overflow-y-auto p-4">
                    <div
                        v-if="isLoadingMessages"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        Loading...
                    </div>
                    <div
                        v-else-if="detailError"
                        class="py-4 text-center text-sm text-destructive"
                    >
                        {{ detailError }}
                    </div>
                    <template v-else>
                        <div
                            v-for="message in messages"
                            :key="message.id"
                            :class="[
                                'rounded-lg px-3 py-2 text-sm',
                                message.sender?.id === currentUserId
                                    ? 'ml-6 bg-primary text-primary-foreground'
                                    : 'mr-6 bg-muted',
                            ]"
                        >
                            <p class="mb-1 text-[11px] font-semibold opacity-70">
                                {{ message.sender?.name ?? 'Unknown' }}
                            </p>
                            <p class="leading-snug">{{ message.body }}</p>
                            <p class="mt-1 text-[10px] opacity-60">
                                {{ message.created_at_human }}
                            </p>
                        </div>
                    </template>
                </div>

                <div v-if="!selectedThread.is_closed" class="border-t p-3">
                    <p v-if="sendError" class="mb-2 text-xs text-destructive">
                        {{ sendError }}
                    </p>
                    <div class="flex gap-2">
                        <textarea
                            v-model="replyBody"
                            rows="2"
                            placeholder="Write a reply..."
                            class="flex-1 resize-none rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                            @keydown.ctrl.enter.prevent="sendReply"
                        />
                        <Button
                            size="icon"
                            class="h-auto shrink-0 self-end"
                            :disabled="isSending || !replyBody.trim()"
                            @click="sendReply"
                        >
                            <Send class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </template>

            <!-- New thread form -->
            <template v-else-if="view === 'new'">
                <div class="flex items-center gap-2 border-b px-3 py-3">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7 shrink-0"
                        @click="view = 'list'"
                    >
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                    <p class="text-sm font-semibold">New Conversation</p>
                </div>

                <div class="space-y-3 p-4">
                    <p v-if="createError" class="text-xs text-destructive">
                        {{ createError }}
                    </p>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">
                            Subject
                        </label>
                        <input
                            v-model="newSubject"
                            type="text"
                            placeholder="Enter subject..."
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">
                            Message
                        </label>
                        <textarea
                            v-model="newBody"
                            rows="4"
                            placeholder="Type your message..."
                            class="w-full resize-none rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                    <Button
                        class="w-full"
                        :disabled="isCreating || !newSubject.trim() || !newBody.trim()"
                        @click="createThread"
                    >
                        {{ isCreating ? 'Sending...' : 'Start Conversation' }}
                    </Button>
                </div>
            </template>
        </PopoverContent>
    </Popover>
</template>
