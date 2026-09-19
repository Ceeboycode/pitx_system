<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CardSeparator } from '@/components/ui/_card-separator';
import { show } from '@/routes/vehicles';
import {
  operationalStatusClass,
  operationalStatusDot,
  operationalStatusLabel,
} from '@/lib/vehicle-status';
import {
  RiBusLine,
  RiCheckboxCircleLine,
  RiShutDownLine,
  RiSpam2Line,
} from 'vue-remix-icons';

type RecentVehicle = {
    id: number;
    plate_number: string | null;
    body_number: string | null;
    status: string | null;
    company_name: string | null;
};

const props = defineProps<{
    vehicleStats: {
        total: number;
        active: number;
        inactive: number;
        suspended: number;
    };
    recentVehicles: RecentVehicle[];
}>();

const stats = [
    { key: 'total', label: 'Total Vehicles', icon: RiBusLine, accent: 'text-custom-shadow' },
    { key: 'active', label: 'Active', icon: RiCheckboxCircleLine, accent: 'text-emerald-600' },
    { key: 'inactive', label: 'Inactive', icon: RiShutDownLine, accent: 'text-rose-600' },
    { key: 'suspended', label: 'Suspended', icon: RiSpam2Line, accent: 'text-orange-600' },
] as const;
</script>

<template>
  <Card class="lg:col-span-2">
    <CardHeader>
      <CardTitle>Overview</CardTitle>
      <CardDescription>Vehicles currently assigned to this type.</CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div
            v-for="stat in stats"
            :key="stat.key"
            class="flex flex-col gap-1 rounded-md border border-custom-bg-dark p-3 dark:border-custom-bg-light"
        >
          <div class="flex items-center gap-1.5 text-xs text-custom-shadow/80">
            <component :is="stat.icon" :class="['h-3.5 w-3.5 shrink-0', stat.accent]" />
            {{ stat.label }}
          </div>
          <span class="text-xl font-semibold text-custom-shadow">{{ props.vehicleStats[stat.key] }}</span>
        </div>
      </div>

      <CardSeparator title="Recently Added Vehicles" />

      <div v-if="props.recentVehicles.length" class="flex flex-col gap-0.5 text-sm text-custom-shadow">
        <Link
            v-for="vehicle in props.recentVehicles"
            :key="vehicle.id"
            :href="show({ vehicle: vehicle.id }).url"
            class="flex flex-row items-center justify-between gap-2 overflow-hidden rounded-md px-2 py-1.5 transition-colors hover:bg-custom-secondary/10"
        >
          <div class="flex min-w-0 flex-col">
            <span class="truncate font-medium">{{ vehicle.plate_number || '—' }}</span>
            <span class="truncate text-xs text-custom-shadow/70">{{ vehicle.company_name || 'No company assigned' }}</span>
          </div>
          <Badge :class="['gap-1.5', operationalStatusClass(vehicle.status)]">
            <span :class="['h-1.5 w-1.5 rounded-full', operationalStatusDot(vehicle.status)]" />
            {{ operationalStatusLabel(vehicle.status) }}
          </Badge>
        </Link>
      </div>
      <p v-else class="rounded-md border border-dashed border-custom-bg-dark p-4 text-center text-sm text-custom-shadow/80 dark:border-custom-bg-light">
        No vehicles have been assigned this type yet.
      </p>
    </CardContent>
  </Card>
</template>
