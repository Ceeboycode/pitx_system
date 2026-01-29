<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { index, create, store } from '@/routes/vehicles'

import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardAction } from '@/components/ui/card'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Vehicles', href: index().url },
  { title: 'Create Vehicle', href: create().url },
]

const props = defineProps<{
  companies: { id: number; company_name: string }[]
  routes: { id: number; route_name: string }[]
  vehicleTypes: { id: number; type_name: string }[]
}>()

const form = useForm({
  plate_number: '',
  body_number: '',
  capacity: '', // keep as string in the UI
  vehicle_type_id: '' as string, // store select ids as strings
  company_id: '' as string,
  route_id: '' as string,
})

const submit = () => {
  form
    .transform((data) => ({
      ...data,
      capacity: data.capacity ? Number(data.capacity) : null,
      vehicle_type_id: data.vehicle_type_id ? Number(data.vehicle_type_id) : null,
      company_id: data.company_id ? Number(data.company_id) : null,
      route_id: data.route_id ? Number(data.route_id) : null,
    }))
    .post(store().url, {
      onSuccess: () => {
        toast.success('Vehicle created successfully!')
        form.reset()
      },
      onError: () => {
        toast.error('Failed to create vehicle.')
      },
    })
}
</script>

<template>
  <Head title="CreateVehicles" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <Card>
        <CardHeader>
          <CardTitle>Create Vehicle</CardTitle>
          <CardDescription>List of all vehicles in the system.</CardDescription>
          <CardAction>
            <Button size="sm" variant="link" asChild>
              <Link :href="index().url">Back to Vehicles</Link>
            </Button>
          </CardAction>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-1">
              <Label for="plate_number">Plate Number</Label>
              <Input id="plate_number" v-model="form.plate_number" required />
              <p v-if="form.errors.plate_number" class="text-sm text-destructive">
                {{ form.errors.plate_number }}
              </p>
            </div>

            <div class="space-y-1">
              <Label for="body_number">Body Number</Label>
              <Input id="body_number" v-model="form.body_number" required />
              <p v-if="form.errors.body_number" class="text-sm text-destructive">
                {{ form.errors.body_number }}
              </p>
            </div>

            <div class="space-y-1">
              <Label for="capacity">Capacity</Label>
              <Input id="capacity" type="number" v-model="form.capacity" required />
              <p v-if="form.errors.capacity" class="text-sm text-destructive">
                {{ form.errors.capacity }}
              </p>
            </div>

            <div class="space-y-1">
              <Label>Vehicle Type</Label>
              <Select v-model="form.vehicle_type_id">
                <SelectTrigger>
                  <SelectValue placeholder="Select Vehicle Type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="type in props.vehicleTypes"
                    :key="type.id"
                    :value="String(type.id)"
                  >
                    {{ type.type_name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.vehicle_type_id" class="text-sm text-destructive">
                {{ form.errors.vehicle_type_id }}
              </p>
            </div>

            <div class="space-y-1">
              <Label>Company</Label>
              <Select v-model="form.company_id">
                <SelectTrigger>
                  <SelectValue placeholder="Select Company" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="company in props.companies"
                    :key="company.id"
                    :value="String(company.id)"
                  >
                    {{ company.company_name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.company_id" class="text-sm text-destructive">
                {{ form.errors.company_id }}
              </p>
            </div>

            <div class="space-y-1">
              <Label>Route</Label>
              <Select v-model="form.route_id">
                <SelectTrigger>
                  <SelectValue placeholder="Select Route" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="route in props.routes"
                    :key="route.id"
                    :value="String(route.id)"
                  >
                    {{ route.route_name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.route_id" class="text-sm text-destructive">
                {{ form.errors.route_id }}
              </p>
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
              {{ form.processing ? 'Creating...' : 'Create Vehicle' }}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
