<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue'
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
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'
import { forceDelete, index, restore, trash } from '@/routes/routes'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import { Trash2, ArchiveRestoreIcon, ArrowLeft } from 'lucide-vue-next'

interface Gate {
  id: number
  gate_name: string
}

interface Route {
  id: number
  route_name: string
  deleted_at_human: string | null
  gate: Gate | null
}

const props = defineProps<{
  routes: any // paginator
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Routes', href: index().url },
  { title: 'Trash', href: trash().url },
]

const restoreForm = useForm({})
const deleteRoute = ref('')

const restoreRoute = (id: number) => {
  restoreForm.submit(restore(id), {
    preserveScroll: true,
    onError: () => toast.error('Failed to restore the route.'),
  })
}

const deleteRoutePermanently = (id: number) => {
  router.delete(forceDelete(id).url, {
    preserveScroll: true,
    onSuccess: () => {
      deleteRoute.value = ''
      toast.success('Route deleted permanently')
    },
    onError: () => toast.error('Failed to delete the route permanently.'),
  })
}
</script>

<template>
  <Head title="Routes Trash" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <Card class="mx-10 mt-3">
        <CardHeader>
          <CardTitle>Trashed Routes</CardTitle>
          <CardDescription>List of routes that have been moved to trash.</CardDescription>

          <CardAction>
            <Button variant="link" size="sm" as-child>
              <Link :href="index().url" class="cursor-pointer">
                <ArrowLeft class="mr-2 h-4 w-4" />
                Back to Routes
              </Link>
            </Button>
          </CardAction>
        </CardHeader>

        <!-- ✅ add space-y-4 so pagination spacing matches Companies -->
        <CardContent class="space-y-4">
          <Table>
            <TableCaption>Trashed Routes</TableCaption>

            <TableHeader>
              <TableRow>
                <TableHead>Route Name</TableHead>
                <TableHead>Gate</TableHead>
                <TableHead>Archived At</TableHead>
                <TableHead>Action</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <TableRow v-for="route in routes.data" :key="route.id">
                <TableCell class="font-medium">
                  {{ route.route_name }}
                </TableCell>

                <TableCell>
                  {{ route.gate?.gate_name ?? 'No Gate Assigned' }}
                </TableCell>

                <TableCell>
                  {{ route.deleted_at_human || 'N/A' }}
                </TableCell>

                <TableCell>
                  <div class="flex items-center gap-2">
                    <Dialog>
                      <DialogTrigger as-child>
                        <Button class="cursor-pointer" variant="secondary" size="sm">
                          <ArchiveRestoreIcon class="mr-2 h-4 w-4" />
                          Restore
                        </Button>
                      </DialogTrigger>

                      <DialogContent>
                        <DialogHeader>
                          <DialogTitle>Restore Route</DialogTitle>
                          <DialogDescription>
                            Are you sure you want to restore this route?
                          </DialogDescription>
                        </DialogHeader>

                        <DialogFooter>
                          <DialogClose as-child>
                            <Button class="cursor-pointer" variant="outline">Cancel</Button>
                          </DialogClose>
                          <DialogClose as-child>
                            <Button class="cursor-pointer" @click="restoreRoute(route.id)">
                              Restore
                            </Button>
                          </DialogClose>
                        </DialogFooter>
                      </DialogContent>
                    </Dialog>

                    <Dialog>
                      <DialogTrigger as-child>
                        <Button class="cursor-pointer" variant="destructive" size="sm">
                          <Trash2 class="mr-2 h-4 w-4" />
                          Delete Permanently
                        </Button>
                      </DialogTrigger>

                      <DialogContent>
                        <DialogHeader>
                          <DialogTitle>Delete Route Permanently</DialogTitle>
                          <DialogDescription>
                            Are you sure you want to delete this route permanently?
                          </DialogDescription>
                        </DialogHeader>

                        <Label for="delete_confirmation" class="mb-2">
                          Type <span class="text-red-500">'delete'</span> to confirm:
                        </Label>

                        <Input
                          id="delete_confirmation"
                          v-model="deleteRoute"
                          placeholder="delete"
                          class="mb-4"
                        />

                        <DialogFooter>
                          <DialogClose as-child>
                            <Button variant="outline">Cancel</Button>
                          </DialogClose>

                          <DialogClose as-child>
                            <Button
                              variant="destructive"
                              :disabled="deleteRoute !== 'delete'"
                              @click="deleteRoutePermanently(route.id)"
                            >
                              <Trash2 class="mr-2 h-4 w-4" />
                              Delete Permanently
                            </Button>
                          </DialogClose>
                        </DialogFooter>
                      </DialogContent>
                    </Dialog>
                  </div>
                </TableCell>
              </TableRow>

              <!-- Optional empty state -->
              <TableRow v-if="routes.data.length === 0">
                <TableCell colspan="4" class="py-10 text-center text-muted-foreground">
                  No trashed routes found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <!-- ✅ pagination placed AFTER table with proper spacing -->
          <InertiaPagination
            :links="routes.links"
            :meta="{
              from: routes.from,
              to: routes.to,
              total: routes.total,
            }"
          />
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
