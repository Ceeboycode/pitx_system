<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { Link } from '@inertiajs/vue3'
import { cn } from "@/lib/utils"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { leadingCardVariants } from "@/components/ui/_leading-card"
import { LeadingCardDecoration } from "@/components/ui/_leading-card"
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger, DropdownMenuItem } from "@/components/ui/dropdown-menu"
import { Button } from "@/components/ui/button"
import { 
    RiMore2Line,
    RiArrowLeftLine,
    RiCircleFill,
    RiCircleLine,
} from "vue-remix-icons"

const props = withDefaults(defineProps<{
    class?: HTMLAttributes["class"]
    title?: string
    user?: string
    description?: string
    today?: string
    variant?: "default" | "dashboard" | "entity-details" | "entity-crud"
    back?: string
    entity?: string
    open?: boolean
    more?: boolean
    status?: "active" | "inactive" | null
    color?: "default" | "red" | "grey" | "accent" | null
}>(), {
    variant: "default",
    back: "",
    entity: "",
    more: true,
    color: "default"
})

const emit = defineEmits<{
  "update:open": [value: boolean]
}>()

</script>

<template>
    <Card
      :class="cn(leadingCardVariants({ variant, color }), props.class)"
      :data-variant="variant"
      :data-color="color"
    >
        <CardHeader
          class="flex flex-row justify-between"
        >
            <div>
                <CardTitle class="flex flex-row gap-2 items-center text-custom-bg-light dark:text-custom-shadow">
                    <span v-if="props.user">Good morning, {{ props.user }}</span>
                    <span v-else>{{ props.title || "Welcome to the PITX Centralized Terminal Management System" }}</span>
                    <!-- TODO: make this into ints own component -->
                    <span
                        :class="cn('flex flex-row gap-2 items-center rounded-full', 
                            {'bg-success/20 px-2 py-0.5': props.status === 'active'},
                            {'bg-custom-shadow/10 px-2 py-0.5': props.status === 'inactive'},
                        )"
                    >
                        <RiCircleFill v-if="props.status === 'active'" class="h-2 w-2 text-success" />
                        <RiCircleLine v-else-if="props.status === 'inactive'" class="h-2 w-2 text-custom-shadow" />
                        <span v-if="props.status === 'active'" class="text-success text-sm">
                            Active
                        </span>
                        <span v-else-if="props.status === 'inactive'" class="text-custom-shadow text-sm">
                            Inactive
                        </span>
                    </span>
                </CardTitle
                >
                <CardDescription class="text-custom-bg-light dark:text-custom-shadow">
                    {{ props.description || "See terminal and system overviews, manage your tasks." }}
                </CardDescription>
            </div>

            <div v-if="props.variant === 'dashboard' || props.variant === 'default'" class="text-right h-full flex items-center">
                <div
                    class="text-custom-bg-light dark:text-custom-shadow text-sm"
                >
                    {{ props.today }}
                </div>
            </div>

            <div v-if="props.variant === 'entity-details' || props.variant === 'entity-crud'" class="flex flex-1 justify-end gap-2 items-start lg:items-center">
                <div class="lg:flex items-center gap-2 sm:justify-end">
                    <Button as-child variant="header-actions" size="icon" class="text-custom-bg-light dark:text-custom-shadow bg-custom-bg-light/10 dark:bg-custom-shadow/10 hidden lg:flex">
                        <Link :href="props.back" :aria-label="`Back to ${props.entity} list`">
                            <RiArrowLeftLine class="h-4 w-4" />
                        </Link>
                    </Button>

                    <DropdownMenu
                        class="w-fit"
                        v-if="props.more === true"
                    >
                        <DropdownMenuTrigger as-child class="m-0">
                            <Button
                                variant="header-actions"
                                class="bg-custom-bg-light/10 dark:bg-custom-shadow/10"
                                size="icon"
                                aria-label="Open vehicle actions"
                            >
                                <RiMore2Line class="h-4 w-4 shrink-0" />
                            </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent
                            align="end"
                            class="w-fit"
                        >
                            <DropdownMenuItem
                                class="group lg:hidden cursor-pointer"
                            >
                                <!-- <Button variant="dropdown" size="dropdown"> -->
                                    <Link :href="props.back" :aria-label="`Back to ${props.entity} list`" class="transition-all duration-200 flex flex-row gap-2 w-full justify-start rounded-md text-custom-shadow group-hover:bg-custom-secondary/20 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow items-center cursor-pointer">
                                        <RiArrowLeftLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                        Back
                                    </Link>
                                <!-- </Button> -->
                            </DropdownMenuItem>
                            <slot />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </CardHeader>

        <LeadingCardDecoration v-if="props.variant === 'dashboard' || props.variant === 'default'" />
    </Card>
</template>