<script setup lang="ts">
import {
  SidebarGroup,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from "@/components/ui/sidebar"
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible"
import { ChevronRight } from "lucide-vue-next"
import { useActiveUrl } from "@/composables/useActiveUrl"
import type { NavItem, Item } from "@/types"
import { Link, usePage } from "@inertiajs/vue3"
import { onMounted, ref, watch } from "vue"

const props = defineProps<{ items: NavItem[] }>()

const { urlIsActive } = useActiveUrl()

const STORAGE_KEY = "sidebar:navmain:open"
const openMap = ref<Record<string, boolean>>({})

function isOpenByRoute(item: NavItem) {
  return item.items.some((sub) => urlIsActive(sub.href))
}

onMounted(() => {
  // load saved opens
  try {
    openMap.value = JSON.parse(localStorage.getItem(STORAGE_KEY) || "{}")
  } catch {
    openMap.value = {}
  }

  // ensure active section is open too (doesn't close others)
  for (const item of props.items) {
    if (isOpenByRoute(item)) openMap.value[item.id] = true
  }
})

watch(
  openMap,
  (v) => localStorage.setItem(STORAGE_KEY, JSON.stringify(v)),
  { deep: true }
)

</script>

<template>
  <SidebarGroup>
    <SidebarMenu>
      <Collapsible
        v-for="item in props.items"
        :key="item.id ?? item.title"
        as-child
        class="group/collapsible"
        v-model:open="openMap[item.id]"

      >
        <SidebarMenuItem>
          <CollapsibleTrigger as-child class="mx-auto px-auto w-full">
            <SidebarMenuButton
              :tooltip="item.title"
              class="flex w-full items-center gap-2"
            >
              <component v-if="item.icon" :is="item.icon"/>
              <span>{{ item.title }}</span>
              <ChevronRight
                class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
              />
            </SidebarMenuButton>
          </CollapsibleTrigger>

          <CollapsibleContent>
            <SidebarMenuSub>
              <SidebarMenuSubItem
                v-for="subItem in item.items"
                :key="subItem.id ?? subItem.title"
              >
                <SidebarMenuSubButton as-child :is-active="urlIsActive(subItem.href)">
                  <Link :href="subItem.href">
                    <span>{{ subItem.title }}</span>
                  </Link>
                </SidebarMenuSubButton>
              </SidebarMenuSubItem>
            </SidebarMenuSub>
          </CollapsibleContent>
        </SidebarMenuItem>
      </Collapsible>
    </SidebarMenu>
  </SidebarGroup>
</template>
