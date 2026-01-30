<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'

import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableRow, TableCell } from '@/components/ui/table'

const props = defineProps<{
  vehicleType: {
    id: number
    type_name: string
    is_active: boolean
    created_at_human: string | null
    updated_at_human: string | null
    creator: { id: number; name: string } | null
    updater: { id: number; name: string } | null
  }
}>()

import { index, show } from '@/routes/vehicle-types'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Vehicle Types Table', href: index().url },
  { title: 'Vehicle Type Details', href: show(props.vehicleType.id).url },
]
</script>

<template>
  <Head :title="`Vehicle Type - ${vehicleType.type_name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-5xl space-y-6 p-4">
      <Card>
        <CardHeader>
          <CardTitle>Vehicle Type Details</CardTitle>
        </CardHeader>

        <CardContent>
          <!-- details “table card” look -->
          <div class="overflow-hidden rounded-lg border">
            <Table class="w-full">
              <TableBody>
                <TableRow>
                  <TableCell class="w-1/3 text-muted-foreground">
                    Vehicle Type
                  </TableCell>
                  <TableCell class="w-2/3 text-right font-medium">
                    {{ vehicleType.type_name }}
                  </TableCell>
                </TableRow>

                <TableRow>
                  <TableCell class="text-muted-foreground">Status</TableCell>
                  <TableCell class="text-right">
                    <Badge
                      class="rounded-full px-3 py-1 text-xs font-semibold"
                      :class="
                        vehicleType.is_active
                          ? 'bg-green-100 text-green-700 hover:bg-green-100'
                          : 'bg-red-100 text-red-700 hover:bg-red-100'
                      "
                    >
                      {{ vehicleType.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                  </TableCell>
                </TableRow>

                <TableRow>
                  <TableCell class="text-muted-foreground">Created By</TableCell>
                  <TableCell class="text-right">
                    {{ vehicleType.creator?.name ?? '—' }}
                  </TableCell>
                </TableRow>

                <TableRow>
                  <TableCell class="text-muted-foreground">Created</TableCell>
                  <TableCell class="text-right">
                    {{ vehicleType.created_at_human ?? '—' }}
                  </TableCell>
                </TableRow>

                <TableRow>
                  <TableCell class="text-muted-foreground">
                    Last Updated By
                  </TableCell>
                  <TableCell class="text-right">
                    {{ vehicleType.updater?.name ?? '—' }}
                  </TableCell>
                </TableRow>

                <TableRow>
                  <TableCell class="text-muted-foreground">Last Updated</TableCell>
                  <TableCell class="text-right">
                    {{ vehicleType.updated_at_human ?? '—' }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <div class="flex gap-2">
        <Button as-child variant="secondary">
          <Link :href="index().url">Back to List</Link>
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
