<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'


import {
  ChartContainer,
  ChartCrosshair,
  ChartTooltip,
} from '@/components/ui/chart'
import { VisXYContainer, VisArea, VisAxis, VisLine } from '@unovis/vue'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
]

/* --------------------------------------------------------------------------
   Static “analytics” dataset 
--------------------------------------------------------------------------- */
type Point = { date: string; trips: number; revenue: number; incidents: number }

const allData = ref<Point[]>([
  { date: 'Jan 01', trips: 320, revenue: 52000, incidents: 4 },
  { date: 'Jan 02', trips: 410, revenue: 61000, incidents: 2 },
  { date: 'Jan 03', trips: 380, revenue: 58000, incidents: 3 },
  { date: 'Jan 04', trips: 460, revenue: 69000, incidents: 1 },
  { date: 'Jan 05', trips: 510, revenue: 74000, incidents: 2 },
  { date: 'Jan 06', trips: 495, revenue: 71000, incidents: 2 },
  { date: 'Jan 07', trips: 530, revenue: 78000, incidents: 1 },
  { date: 'Jan 08', trips: 565, revenue: 82000, incidents: 1 },
  { date: 'Jan 09', trips: 540, revenue: 80000, incidents: 3 },
  { date: 'Jan 10', trips: 600, revenue: 89000, incidents: 1 },
  { date: 'Jan 11', trips: 630, revenue: 92000, incidents: 2 },
  { date: 'Jan 12', trips: 610, revenue: 90000, incidents: 1 },
  { date: 'Jan 13', trips: 670, revenue: 98000, incidents: 2 },
  { date: 'Jan 14', trips: 720, revenue: 105000, incidents: 1 },
])

const range = ref<'7d' | '14d'>('14d')

const filtered = computed(() => {
  const n = range.value === '7d' ? 7 : 14
  return allData.value.slice(-n)
})

/* --------------------------------------------------------------------------
   KPIs 
--------------------------------------------------------------------------- */
const kpi = computed(() => {
  const d = filtered.value
  const totalTrips = d.reduce((acc, p) => acc + p.trips, 0)
  const totalRevenue = d.reduce((acc, p) => acc + p.revenue, 0)
  const totalIncidents = d.reduce((acc, p) => acc + p.incidents, 0)

  const avgTrips = Math.round(totalTrips / d.length)
  const avgRevenue = Math.round(totalRevenue / d.length)

  return { totalTrips, totalRevenue, avgTrips, avgRevenue, totalIncidents }
})

/* --------------------------------------------------------------------------
   Chart accessors (Unovis)
--------------------------------------------------------------------------- */
const x = (d: Point) => d.date
const yTrips = (d: Point) => d.trips
const yRevenue = (d: Point) => d.revenue

const activity = ref([
  { time: '10:12 AM', action: 'Route created', detail: 'EDSA ⇄ PITX', status: 'Success' },
  { time: '09:46 AM', action: 'Gate updated', detail: 'Gate 3 capacity', status: 'Success' },
  { time: '09:10 AM', action: 'Vehicle type disabled', detail: 'Modern Jeepney', status: 'Warning' },
  { time: '08:30 AM', action: 'Company archived', detail: 'ABC Transport', status: 'Success' },
])

const chartConfig = computed(() => ({
  trips: { label: 'Trips', color: 'hsl(var(--primary))' },
  revenue: { label: 'Revenue', color: 'hsl(var(--muted-foreground))' },
}))
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Total trips</CardDescription>
            <CardTitle class="text-2xl">{{ kpi.totalTrips.toLocaleString() }}</CardTitle>
          </CardHeader>
          <CardContent class="text-sm text-muted-foreground">
            Avg/day: {{ kpi.avgTrips.toLocaleString() }}
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Total revenue</CardDescription>
            <CardTitle class="text-2xl">₱{{ kpi.totalRevenue.toLocaleString() }}</CardTitle>
          </CardHeader>
          <CardContent class="text-sm text-muted-foreground">
            Avg/day: ₱{{ kpi.avgRevenue.toLocaleString() }}
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Incidents</CardDescription>
            <CardTitle class="text-2xl">{{ kpi.totalIncidents }}</CardTitle>
          </CardHeader>
          <CardContent class="text-sm text-muted-foreground">
            Across selected range
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardDescription>System status</CardDescription>
            <CardTitle class="text-2xl">Operational</CardTitle>
          </CardHeader>
          <CardContent class="text-sm text-muted-foreground">
            <Badge variant="outline">All services up</Badge>
          </CardContent>
        </Card>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <!-- Analytics -->
        <Card class="lg:col-span-2">
          <CardHeader class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <CardTitle>Data analysis</CardTitle>
              <CardDescription>Trips and revenue trend</CardDescription>
            </div>

            <div class="flex gap-2">
              <Button size="sm" variant="outline" :disabled="range === '7d'" @click="range = '7d'">
                Last 7 days
              </Button>
              <Button size="sm" variant="outline" :disabled="range === '14d'" @click="range = '14d'">
                Last 14 days
              </Button>
            </div>
          </CardHeader>

          <CardContent>
            <ChartContainer :config="chartConfig" class="w-full">
              <div class="h-[320px] w-full">
                <VisXYContainer :data="filtered">
                  <VisAxis type="x" :x="x" />
                  <VisAxis type="y" />

                  <VisArea :x="x" :y="yTrips" />
                  <VisLine :x="x" :y="yTrips" />

                  <VisLine :x="x" :y="yRevenue" />

                  <ChartTooltip />
                  <ChartCrosshair />
                </VisXYContainer>
              </div>
            </ChartContainer>
          </CardContent>
        </Card>

        <!-- Recent activity -->
        <Card>
          <CardHeader>
            <CardTitle>Recent activity</CardTitle>
            <CardDescription>Latest actions in the system</CardDescription>
          </CardHeader>

          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Time</TableHead>
                  <TableHead>Action</TableHead>
                  <TableHead class="text-right">Status</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                <TableRow v-for="(row, i) in activity" :key="i">
                  <TableCell class="whitespace-nowrap">{{ row.time }}</TableCell>
                  <TableCell>
                    <div class="font-medium">{{ row.action }}</div>
                    <div class="text-xs text-muted-foreground">{{ row.detail }}</div>
                  </TableCell>
                  <TableCell class="text-right">
                    <Badge :variant="row.status === 'Warning' ? 'destructive' : 'success'">
                      {{ row.status }}
                    </Badge>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
