<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'

import AppLayout from '@/layouts/AppLayout.vue'


import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import {
  Accordion,
  AccordionItem,
  AccordionTrigger,
  AccordionContent,
} from '@/components/ui/accordion'


import { Info, User, Truck, HelpCircle } from 'lucide-vue-next'


const breadcrumbs = [{ title: 'FAQ', href: '/faq' }]

type FaqItem = { q: string; a: string }
type FaqSection = {
  key: string
  title: string
  icon: any
  items: FaqItem[]
}

const query = ref('')

const sections = ref<FaqSection[]>([
  {
    key: 'general',
    title: 'General',
    icon: Info,
    items: [
      {
        q: 'What is this system for?',
        a: 'This system helps manage terminals, vehicles, companies, and routes in one place.',
      },
      {
        q: 'Who is this system intended for?',
        a: 'It’s designed for administrators and staff who handle daily transport operations.',
      },
    ],
  },
  {
    key: 'accounts',
    title: 'Accounts & Access',
    icon: User,
    items: [
      {
        q: 'Who can access the system?',
        a: 'Only authorized users with approved accounts can log in and use the system.',
      },
      {
        q: 'What if I forget my password?',
        a: 'Contact your system administrator to reset your access.',
      },
      {
        q: 'Why can’t I see certain features?',
        a: 'Some features are only available to users with specific roles or permissions.',
      },
    ],
  },
  {
    key: 'vehicles',
    title: 'Vehicles & Operations',
    icon: Truck,
    items: [
      {
        q: 'How do I add a vehicle?',
        a: 'Go to the Vehicles section and click Add Vehicle to register a new one.',
      },
      {
        q: 'Can I edit or delete vehicle records?',
        a: 'Yes—if your role allows it, you can update or remove existing records.',
      },
    ],
  },
  {
    key: 'support',
    title: 'Support & Troubleshooting',
    icon: HelpCircle,
    items: [
      {
        q: 'What should I do if something doesn’t work?',
        a: 'Try refreshing the page. If the problem continues, report it to your administrator.',
      },
      {
        q: 'Who do I contact for support?',
        a: 'Reach out to the system administrator or the assigned IT support team.',
      },
    ],
  },
])

const normalizedQuery = computed(() => query.value.trim().toLowerCase())

const filteredSections = computed(() => {
  if (!normalizedQuery.value) return sections.value

  return sections.value
    .map((section) => {
      const items = section.items.filter((item) =>
        `${item.q} ${item.a}`.toLowerCase().includes(normalizedQuery.value)
      )
      return { ...section, items }
    })
    .filter((section) => section.items.length > 0)
})


const accordionType = computed(() =>
  normalizedQuery.value ? 'multiple' : 'single'
)
</script>

<template>
  <Head title="FAQ" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl p-4 space-y-5">
      
      <Card>
        <CardHeader>
          <CardTitle>Search FAQs</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2">
          <Label for="faq-search">
            Type a keyword (e.g., vehicle, password)
          </Label>
          <Input
            id="faq-search"
            v-model="query"
            placeholder="Search FAQs..."
          />
        </CardContent>
      </Card>

      
      <Card v-if="filteredSections.length === 0">
        <CardContent class="py-6 text-sm text-muted-foreground">
          No FAQs matched your search.
        </CardContent>
      </Card>

      
      <div v-else class="space-y-8">
        <section
          v-for="section in filteredSections"
          :key="section.key"
          class="space-y-4"
        >
          <div class="flex items-center gap-2">
            <component
              :is="section.icon"
              class="h-5 w-5 text-muted-foreground"
            />
            <h2 class="text-lg font-semibold">
              {{ section.title }}
            </h2>
          </div>

          <Card>
            <CardContent class="p-0">
              
              <div class="px-6">
                <Accordion :type="accordionType" collapsible class="w-full">
                  <AccordionItem
                    v-for="(item, idx) in section.items"
                    :key="`${section.key}-${idx}`"
                    :value="`${section.key}-${idx}`"
                  >
                    <AccordionTrigger class="py-4 text-left">
                      {{ item.q }}
                    </AccordionTrigger>

                    <AccordionContent class="pb-4 text-sm text-muted-foreground">
                      {{ item.a }}
                    </AccordionContent>
                  </AccordionItem>
                </Accordion>
              </div>
            </CardContent>
          </Card>

          <Separator />
        </section>
      </div>
    </div>
  </AppLayout>
</template>
