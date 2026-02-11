<script setup lang="ts">
import Badge from '@/components/ui/badge/Badge.vue'
import Button from '@/components/ui/button/Button.vue'
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

import AppLayout from '@/layouts/AppLayout.vue'
import { index, show } from '@/routes/routes'
import type { BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'


interface Gate {
  id: number
  gate_name: string
}

interface RouteStop {
  id: number
  stop_name: string
  stop_order: number
}

interface User {
  id: number
  name: string
}

interface RouteModel {
  id: number
  route_name: string
  gate: Gate | null
  stops: RouteStop[]
  creator: User | null
  updater: User | null
  created_at_human: string
  updated_at_human: string
}

const props = defineProps<{
  route: RouteModel
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Routes', href: index().url },
  { title: 'Route Details', href: show(props.route.id).url },
]
</script>

<template>
  <Head :title="route.route_name" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <Card class="w-full">
        <CardHeader>
          <CardTitle>Route Details</CardTitle>
          <CardDescription>
            View all information about this route, including gate, stops, and timestamps.
          </CardDescription>

          <CardAction>
            <Button variant="link" as-child>
              <Link :href="index().url"><ArrowLeft class="mr-2 h-4 w-4" />Back</Link>
            </Button>
          </CardAction>
        </CardHeader>

        <CardContent class="space-y-6">
          <!-- DETAILS (shadcn table style like your screenshot) -->
          <div class="overflow-hidden rounded-lg border">
            <Table class="w-full">
              <TableBody>
                <TableRow>
                  <TableCell class="w-1/3 text-muted-foreground">
                    Route Name
                  </TableCell>
                  <TableCell class="w-2/3 text-right font-medium">
                    {{ route.route_name }}
                  </TableCell>
                </TableRow>

                <TableRow>
                  <TableCell class="text-muted-foreground">Gate</TableCell>
                  <TableCell class="text-right font-medium">
                    {{ route.gate?.gate_name ?? '—' }}
                  </TableCell>
                </TableRow>

                <TableRow>
                  <TableCell class="text-muted-foreground">Created By</TableCell>
                  <TableCell class="text-right">
                    <div class="flex flex-col items-end">
                      <span class="font-medium">{{ route.creator?.name ?? '—' }}</span>
                      <span class="text-sm text-muted-foreground">
                        {{ route.created_at_human }}
                      </span>
                    </div>
                  </TableCell>
                </TableRow>

                <TableRow>
                  <TableCell class="text-muted-foreground">Last Updated By</TableCell>
                  <TableCell class="text-right">
                    <div class="flex flex-col items-end">
                      <span class="font-medium">{{ route.updater?.name ?? '—' }}</span>
                      <span class="text-sm text-muted-foreground">
                        {{ route.updated_at_human }}
                      </span>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- STOPS -->
          <div class="space-y-3">
            <div class="flex items-center gap-2">
              <p class="text-sm font-semibold">Stops</p>
              <Badge variant="secondary">{{ route.stops.length }}</Badge>
            </div>

            <div v-if="route.stops.length" class="overflow-hidden rounded-lg border">
              <Table class="w-full">
                <TableHeader>
                  <TableRow>
                    <TableHead class="w-[100px]">Order</TableHead>
                    <TableHead>Stop Name</TableHead>
                  </TableRow>
                </TableHeader>

                <TableBody>
                  <TableRow
                    v-for="stop in route.stops"
                    :key="stop.id"
                    class="hover:bg-muted/50"
                  >
                    <TableCell>
                      <Badge variant="outline">{{ stop.stop_order }}</Badge>
                    </TableCell>
                    <TableCell class="font-medium">
                      {{ stop.stop_name }}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>

            <p
              v-else
              class="rounded-lg border p-4 text-sm text-muted-foreground"
            >
              No stops available.
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
