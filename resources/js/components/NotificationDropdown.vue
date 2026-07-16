<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Bell } from 'lucide-vue-next'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

import { Tooltip } from '@/components/ui/tooltip';

import NotificationController from '@/actions/App/Http/Controllers/NotificationController'

type NotificationItem = {
    id: string
    type: string | null
    title: string
    message: string
    read_at: string | null
    created_at: string | null
    data: Record<string, unknown>
}

type NotificationsPayload = {
    unread_count: number
    items: NotificationItem[]
}

const page = usePage()

const notifications = computed<NotificationsPayload>(() => {
    return (page.props.notifications as NotificationsPayload) ?? {
        unread_count: 0,
        items: [],
    }
})

const markAsRead = (id: string) => {
    router.post(NotificationController.markAsRead(id), {}, {
        preserveScroll: true,
        preserveState: true,
    })
}

const markAllAsRead = () => {
    router.post(NotificationController.markAllAsRead(), {}, {
        preserveScroll: true,
        preserveState: true,
    })
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="float"
                size="icon"
                class="relative h-9 w-9 shrink-0 rounded-full"
            >
                <Bell class="h-4 w-4" />
                

                <Badge
                    v-if="notifications.unread_count > 0"
                    class="absolute -right-1 -top-1 h-4 w-4 rounded-full px-1 text-[10px]"
                >
                    {{ notifications.unread_count > 99 ? '99+' : notifications.unread_count }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-[360px] p-3">
            <div class="flex items-center justify-between px-4 py-3">
                <DropdownMenuLabel class="p-0 text-sm font-semibold">
                    Notifications
                </DropdownMenuLabel>

                <Button
                    v-if="notifications.unread_count > 0"
                    variant="ghost"
                    class="h-8 rounded-full px-2 text-xs hover:bg-custom-secondary/20"
                    @click="markAllAsRead"
                >
                    Mark all as read
                </Button>
            </div>

            <DropdownMenuSeparator />

            <div
                v-if="notifications.items.length === 0"
                class="px-4 py-8 text-center text-sm text-custom-shadow/80"
            >
                No notifications yet.
            </div>

            <div
                v-else
                class="max-h-[420px] space-y-2 overflow-y-auto p-2"
            >
                <Tooltip
                    content="'Mark as read'"
                    placement="top"
                >
                    <button
                        v-for="item in notifications.items"
                        :key="item.id"
                        type="button"
                        class="w-full cursor-pointer rounded-md p-3 text-left text-custom-shadow transition hover:bg-custom-secondary/20"
                        @click="markAsRead(item.id)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">
                                    {{ item.title }}
                                </p>
                                <p class="mt-1 line-clamp-2 text-sm text-custom-shadow/80">
                                    {{ item.message }}
                                </p>
                                <p class="mt-2 text-xs text-custom-shadow/70">
                                    {{ item.created_at }}
                                </p>
                            </div>

                            <Badge
                                v-if="!item.read_at"
                                variant="default"
                                class="shrink-0"
                            >
                                New
                            </Badge>
                        </div>
                    </button>
                </Tooltip>
                <!-- CODE: <button
                    v-for="item in notifications.items"
                    :key="item.id"
                    type="button"
                    class="w-full rounded-lg border p-3 text-left transition hover:bg-slate-100 cursor-pointer"
                    @click="markAsRead(item.id)"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">
                                {{ item.title }}
                            </p>
                            <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                {{ item.message }}
                            </p>
                            <p class="mt-2 text-xs text-muted-foreground">
                                {{ item.created_at }}
                            </p>
                        </div>

                        <Badge
                            v-if="!item.read_at"
                            variant="default"
                            class="shrink-0"
                        >
                            New
                        </Badge>
                    </div>
                </button> -->
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
