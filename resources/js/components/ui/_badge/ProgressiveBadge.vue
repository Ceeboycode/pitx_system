<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { Link } from '@inertiajs/vue3'
import { cn } from "@/lib/utils"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { LeadingCardDecoration } from "@/components/ui/_leading-card"
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Button } from "@/components/ui/button"
import { 
    RiMore2Line,
    RiArrowLeftLine,
} from "vue-remix-icons"

const props = withDefaults(defineProps<{
    class?: HTMLAttributes["class"]
    title: string
    description?: string
    today?: string
    variant?: string
    back?: string
    entity?: string
    open?: boolean
    status?: "active" | "inactive" | null
}>(), {
    variant: "default",
    back: "",
    entity: ""
})

const emit = defineEmits<{
  "update:open": [value: boolean]
}>()

</script>

<template>
    <Card
      :class="cn('shrink-0 bg-custom-primary dark:bg-custom-secondary group', props.class)"
      :data-variant="variant"
    >
        <CardHeader
          class="flex flex-row justify-between"
        >
            <div>
                <CardTitle class="text-custom-bg-light dark:text-custom-shadow flex flex-row gap-2 items-center">
                    {{ props.title || "Welcome to the PITX Centralized Terminal Management System" }}
                    <div v-if="props.status === 'active'" class="h-2 w-2 bg-success rounded-full">
                        
                    </div>
                </CardTitle
                >
                <CardDescription class="mt-2 text-custom-bg-light dark:text-custom-shadow">
                    {{ props.description || "See terminal and system overviews, manage your tasks." }}
                </CardDescription>
            </div>

            <div v-if="props.variant === 'dashboard' || props.variant === 'default'" class="text-right h-full flex items-center">
                <div
                    class=" text-custom-bg-light text-sm"
                >
                    {{ props.today }}
                </div>
            </div>

            <div v-if="props.variant === 'entity-details'" class="flex flex-1 justify-end gap-2 items-center">
                <div class="lg:flex items-center gap-2 sm:justify-end">
                    <Button as-child variant="header-actions" size="icon">
                        <Link :href="props.back" :aria-label="`Back to ${props.entity} list`">
                            <RiArrowLeftLine class="h-4 w-4 shrink-0" />
                        </Link>
                    </Button>

                    <DropdownMenu
                        class="w-fit"
                        :open="props.open ?? false"
                        @update:open="emit('update:open', $event)"
                    >
                        <DropdownMenuTrigger as-child class="m-0">
                            <Button
                                variant="header-actions"
                                class="text-custom-shadow"
                                size="icon"
                                aria-label="Open vehicle actions"
                                @click.stop="emit('update:open', true)"
                                @contextmenu.prevent.stop
                            >
                                <RiMore2Line class="h-4 w-4 shrink-0" />
                            </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent
                            align="end"
                            class="w-fit"
                        >
                            <slot />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </CardHeader>

        <LeadingCardDecoration v-if="props.variant === 'dashboard' || props.variant === 'default'" />

    </Card>
</template>