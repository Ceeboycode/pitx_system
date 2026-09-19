<script setup lang="ts">
import { computed } from 'vue';

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Logo, LogoFallback, LogoImage } from '@/components/ui/_logo';
import { Separator } from '@/components/ui/separator';
import { CardSeparator } from '@/components/ui/_card-separator';
import Button from '@/components/ui/button/Button.vue';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { show as userShow } from '@/routes/users';
import { show as companyShow } from '@/routes/companies';
import { edit as routeEdit } from '@/routes/routes';
import { toggleStatus } from '@/routes/vehicles';
import { can } from '@/lib/can';

import { useInitials } from '@/composables/useInitials';
import { useClipboard } from '@vueuse/core';
import { toast } from 'vue-sonner';

import {
  RiPhoneLine,
  RiVerifiedBadgeLine,
  RiMailLine,
  RiCalendarLine,
  RiChat4Line,
  RiMapPin2Line,
  RiTimeLine,
  RiBuildingLine,
  RiFileCheckLine,
  RiIdCardLine,
  RiUser3Line,
} from "vue-remix-icons";

import { formatDate, humanize } from '@/lib/format';

type UserMini = { id?: number; name: string };

type RouteStop = {
    id: number;
    stop_name: string;
    stop_type: 'origin' | 'stop' | 'destination' | 'landmark' | string;
    address: string | null;
    latitude: number;
    longitude: number;
    stop_order: number;
};

type RouteData = {
    id: number;
    route_name: string;
    gate: { id: number; gate_name: string } | null;
    origin_name: string;
    origin_lat: number;
    origin_lng: number;
    destination_name: string;
    destination_lat: number;
    destination_lng: number;
    distance_meters: number | null;
    duration_seconds: number | null;
    route_geometry: string | null;
    status: string | null;
    stops: RouteStop[];
} | null;

type VehicleModel = {
    id: number;
    vehicle_type?: string | null;
    plate_number?: string | null;
    body_number?: string | null;
    capacity?: string | number | null;
    color?: string | null;
    engine_number?: string | null;
    chassis_number?: string | null;
    make_model?: string | null;
    status?: string | null;
    verification_status?: string | null;
    docs_status?: string | null;
    verification_remark?: string | null;
    operator_remark?: string | null;
    suspension_remark?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    deleted_at?: string | null;
    company?: {
        id: number;
        company_name: string;
        company_code?: string | null;
        // company_email?: string | null;
        // company_phone?: string | null;
        // company_address?: string | null;
    } | null;
    route?: RouteData;
    // documents?: VehicleDocument[];
    creator?: UserMini | null;
    updater?: UserMini | null;
    deleter?: UserMini | null;
};

const props = defineProps<{
    vehicle: VehicleModel;
    mapConfig: { mapboxToken: string };
}>();

const vehicle = computed(() => props.vehicle);

// const company = computed(() => props.company);

const canUpdateVehicleStatus = computed(() => can('vehicles.toggleStatus'));

const statusForm = useForm({
  status: props.vehicle?.status ?? 'active',
  suspension_remark: props.vehicle?.suspension_remark ?? '',
});

const statusRequiresReason = computed(
  () => statusForm.status === 'suspended',
);

function submitStatusUpdate() {
  statusForm.patch(toggleStatus(vehicle.value.id).url, {
    preserveScroll: true,
  });
}

const { copy } = useClipboard({ legacy: true });

async function copyToClipboard(value?: string | null, label = 'Value') {
  const text = (value ?? '').trim();
  if (!text || text === '—') return;
  try {
    await copy(text);
    toast.success(`${label} copied to clipboard.`);
  } catch {
    toast.error(`Could not copy ${label.toLowerCase()}.`);
  }
}
</script>

<template>
  <div class="grid lg:grid-cols-3 gap-4 w-full h-full">
    <Card class="lg:col-span-2">
      <CardHeader>
        <CardTitle>Vehicle</CardTitle>
        <CardDescription>View vehicle details.</CardDescription>
      </CardHeader>
      <CardContent class="flex flex-row gap-4">
        <div class="flex-1">
          <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <!-- <div class="flex flex-col gap-2 justify-center items-start">
              <Logo class="rounded-md">
                <LogoImage
                  v-if="company.logo"
                  :src="company.logo"
                  :alt="company.company_name"
                />
                <LogoFallback>
                  No logo uploaded.
                </LogoFallback>
              </Logo>
            </div> -->
            <!-- <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
              <div class="inline-flex gap-2 items-start">
                <RiBuildingLine class="shrink-0 mt-0.5 h-4 w-4 text-custom-shadow/80 0"/>
                <span
                  role="button"
                  tabindex="0"
                  title="Copy to clipboard"
                  @click="copyToClipboard(vehicle.plate_number, 'Plate Number')"
                  @keydown.enter.prevent="copyToClipboard(vehicle.plate_number, 'Plate Number')"
                  @keydown.space.prevent="copyToClipboard(vehicle.plate_number, 'Plate Number')"
                  class="cursor-pointer"
                >
                  {{ vehicle.plate_number || '—' }}
                  <span
                    role="button"
                    tabindex="0"
                    title="Copy to clipboard"
                    @click="copyToClipboard(vehicle.vehicle_type, 'Vehicle Type')"
                    @keydown.enter.prevent="copyToClipboard(vehicle.vehicle_type, 'Vehicle Type')"
                    @keydown.space.prevent="copyToClipboard(vehicle.vehicle_type, 'Vehicle Type')"
                    class="cursor-pointer tracking-widest bg-custom-bg dark:bg-custom-bg-light px-2 rounded-md font-mono mr-1 font-normal"
                  >
                    {{ vehicle.vehicle_type ? humanize(vehicle.vehicle_type) : '—' }}
                  </span>
                
                </span>
              </div>

              <div class="inline-flex gap-2 items-start">
                <RiMapPin2Line class="shrink-0 mt-0.5 h-4 w-4 text-custom-shadow/80 0"/>
                <span
                  role="button"
                  tabindex="0"
                  title="Open in Google Maps"
                  @click="openInMaps(company.company_address)"
                  @keydown.enter.prevent="openInMaps(company.company_address)"
                  @keydown.space.prevent="openInMaps(company.company_address)"
                  class="cursor-pointer"
                >
                  {{ company.company_address || '—' }}
                </span>
              </div>
            </div> -->

          </div>
        </div>

        <Separator orientation="vertical"/>

        <div class="flex-1">
          <CardSeparator title="Vehicle Info" />

          <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center gap-2 overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiBuildingLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Plate No.</span>
              </div>
              <span class="inline-flex gap-2 items-center overflow-hidden">
                <span
                  role="button"
                  tabindex="0"
                  title="Copy to clipboard"
                  @click="copyToClipboard(vehicle.plate_number, 'Plate No.')"
                  @keydown.enter.prevent="copyToClipboard(vehicle.plate_number, 'Plate No.')"
                  @keydown.space.prevent="copyToClipboard(vehicle.plate_number, 'Plate No.')"
                  class="cursor-pointer line-clamp-1 text-ellipsis"
                >
                  {{ vehicle.plate_number ?? '—' }}
                </span>
              </span>
            </div>

            <div class="flex flex-row justify-between items-center gap-2 overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiBuildingLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Vehicle Type</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">
                {{ vehicle.vehicle_type ? humanize(vehicle.vehicle_type) : '—' }}
              </span>
            </div>
            
            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiCalendarLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Capacity</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">
                {{ vehicle.capacity ?? '—' }}
              </span>
            </div>

            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiCalendarLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Color</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">
                {{ vehicle.color ?? '—' }}
              </span>
            </div>

            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiFileCheckLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Body No.</span>
              </div>
              <span class="inline-flex gap-2 items-center overflow-hidden">
                <span
                  role="button"
                  tabindex="0"
                  title="Copy to clipboard"
                  @click="copyToClipboard(vehicle.body_number, 'Body No.')"
                  @keydown.enter.prevent="copyToClipboard(vehicle.body_number, 'Body No.')"
                  @keydown.space.prevent="copyToClipboard(vehicle.body_number, 'Body No.')"
                  class="cursor-pointer line-clamp-1 text-ellipsis"
                >
                  {{ vehicle.body_number ?? '—' }}
                </span>
              </span>
            </div>

            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiFileCheckLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Make / Model</span>
              </div>
              <span class="inline-flex gap-2 items-center overflow-hidden">
                <span
                  role="button"
                  tabindex="0"
                  title="Copy to clipboard"
                  @click="copyToClipboard(vehicle.make_model, 'Make / Model')"
                  @keydown.enter.prevent="copyToClipboard(vehicle.make_model, 'Make / Model')"
                  @keydown.space.prevent="copyToClipboard(vehicle.make_model, 'Make / Model')"
                  class="cursor-pointer line-clamp-1 text-ellipsis"
                >
                  {{ vehicle.make_model ?? '—' }}
                </span>
              </span>
            </div>

            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiFileCheckLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Engine No.</span>
              </div>
              <span class="inline-flex gap-2 items-center overflow-hidden">
                <span
                  role="button"
                  tabindex="0"
                  title="Copy to clipboard"
                  @click="copyToClipboard(vehicle.engine_number, 'Engine No.')"
                  @keydown.enter.prevent="copyToClipboard(vehicle.engine_number, 'Engine No.')"
                  @keydown.space.prevent="copyToClipboard(vehicle.engine_number, 'Engine No.')"
                  class="cursor-pointer line-clamp-1 text-ellipsis"
                >
                  {{ vehicle.engine_number ?? '—' }}
                </span>
              </span>
            </div>

            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiFileCheckLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Chassis No.</span>
              </div>
              <span class="inline-flex gap-2 items-center overflow-hidden">
                <span
                  role="button"
                  tabindex="0"
                  title="Copy to clipboard"
                  @click="copyToClipboard(vehicle.chassis_number, 'Chassis No.')"
                  @keydown.enter.prevent="copyToClipboard(vehicle.chassis_number, 'Chassis No.')"
                  @keydown.space.prevent="copyToClipboard(vehicle.chassis_number, 'Chassis No.')"
                  class="cursor-pointer line-clamp-1 text-ellipsis"
                >
                  {{ vehicle.chassis_number ?? '—' }}
                </span>
              </span>
            </div>
          </div>

          <CardSeparator title="Company Info" />

          <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Name</span>
              </div>
              <Link
                v-if="vehicle.company?.id"
                :href="companyShow(vehicle.company.id).url"
                title="View company"
                class="cursor-pointer hover:underline"
              >
                {{ vehicle.company?.company_name ?? '—' }}
                <span
                  role="button"
                  tabindex="0"
                  title="Copy to clipboard"
                  @click.stop.prevent="copyToClipboard(vehicle.company?.company_code, 'Company Code')"
                  @keydown.enter.prevent="copyToClipboard(vehicle.company?.company_code, 'Company Code')"
                  @keydown.space.prevent="copyToClipboard(vehicle.company?.company_code, 'Company Code')"
                  class="cursor-pointer tracking-widest bg-custom-bg dark:bg-custom-bg-light px-2 rounded-md font-mono mr-1 font-normal"
                >
                  {{ vehicle.company?.company_code ?? '—' }}
                </span>
              </Link>
              <span v-else>—</span>
            </div>
          </div>

          <CardSeparator title="Operational Info" />

          <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Route</span>
              </div>
              <Link
                v-if="vehicle.route?.id"
                :href="routeEdit(vehicle.route.id).url"
                title="Edit route"
                class="cursor-pointer hover:underline"
              >
                {{ vehicle.route?.route_name ?? '—' }}
              </Link>
              <span v-else>—</span>
            </div>
          </div>

          <CardSeparator title="Others" />

          <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiCalendarLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Created</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">
                {{ formatDate(vehicle.created_at) }}
                <span class="text-custom-accent-3"> • </span>
                {{ vehicle.creator?.name ?? '—' }}
              </span>
            </div>

            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiTimeLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Updated</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">
                {{ vehicle.updated_at ? formatDate(vehicle.updated_at) : '—' }}
                <span class="text-custom-accent-3"> • </span>
                {{ vehicle.updater?.name ?? '—' }}
              </span>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
    <div class="lg:col-span-1 flex flex-col gap-4 h-full">
      <Card>
        <CardHeader>
          <CardTitle>Operational Status</CardTitle>
          <CardDescription>View vehicle current status and remarks.</CardDescription>
        </CardHeader>
        <CardContent>
          <!-- HERE!!! -->
          <form class="space-y-4" @submit.prevent="submitStatusUpdate">
            <div class="space-y-1.5">
              <Label for="vehicle-status" class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                Status
              </Label>
              <select
                id="vehicle-status"
                v-model="statusForm.status"
                :disabled="!canUpdateVehicleStatus || statusForm.processing"
                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </select>
              <InputError :message="statusForm.errors.status" />
            </div>

            <div class="space-y-1.5">
              <Label class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                Operator Remark
              </Label>
              <p class="min-h-16 rounded-md border bg-slate-50 p-3 text-sm text-muted-foreground whitespace-pre-wrap">
                {{ vehicle.operator_remark || 'No operator remark.' }}
              </p>
            </div>

            <div class="space-y-1.5">
              <Label for="suspension-remark" class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                Suspension/Admin Remark
              </Label>
              <Textarea
                id="suspension-remark"
                v-model="statusForm.suspension_remark"
                :disabled="!canUpdateVehicleStatus || statusForm.processing"
                class="min-h-24 text-sm"
                placeholder="Reason for suspension..."
              />
              <InputError :message="statusForm.errors.suspension_remark" />
            </div>

            <Button
              v-if="canUpdateVehicleStatus"
              type="submit"
              class="w-full"
              :disabled="statusForm.processing || (statusRequiresReason && !statusForm.suspension_remark.trim())"
            >
              {{ statusForm.processing ? 'Saving...' : 'Save Status' }}
            </Button>
          </form>

          <!-- <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow overflow-hidden">
            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiUser3Line class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Name</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">{{ company.authorized_representative_name || '—' }}</span>
            </div>

            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiIdCardLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Position</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">{{ company.authorized_representative_position || '—' }}</span>
            </div>

            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Phone</span>
              </div>
              <span
                role="button"
                tabindex="0"
                title="Copy to clipboard"
                @click="copyToClipboard(company.authorized_representative_contact, 'Phone')"
                @keydown.enter.prevent="copyToClipboard(company.authorized_representative_contact, 'Phone')"
                @keydown.space.prevent="copyToClipboard(company.authorized_representative_contact, 'Phone')"
                class="cursor-pointer line-clamp-1 text-ellipsis"
              >
                {{ company.authorized_representative_contact || '—' }}
              </span>
            </div>
          </div> -->
        </CardContent>
      </Card>
      <!-- <Card>
        <CardHeader>
          <CardTitle>Operator</CardTitle>
          <CardDescription>Person in charge of company operations.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="my-2 flex flex-row gap-2 items-center justify-between">
            <div class="inline-flex gap-2 items-center">
              <Avatar class="size-12">
                <AvatarImage
                  v-if="company.operator?.avatar"
                  :src="company.operator?.avatar"
                  :alt="company.operator?.name"
                />
                <AvatarFallback>
                  {{ getInitials(company.operator?.name ?? '') }}
                </AvatarFallback>
              </Avatar>
              <div class="flex flex-col text-sm text-custom-shadow">
                <Link
                  v-if="company.operator?.id"
                  :href="userShow(company.operator.id).url"
                >
                  <span class="font-semibold line-clamp-1 text-ellipsis">
                    {{ company.operator?.name || '—' }}
                  </span>
                </Link>
                <span v-else class="font-semibold line-clamp-1 text-ellipsis">
                  {{ company.operator?.name || '—' }}
                </span>
                <span class="w-fit tracking-wide bg-custom-bg dark:bg-custom-bg-light px-2 rounded-md font-mono">{{ company.operator?.username || '—' }}</span>
              </div>
            </div>
            <div>
              <Button variant="float" size="icon">
                <RiChat4Line class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
              </Button>
            </div>
          </div>

          <CardSeparator title="Contact Info" />

          <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Phone</span>
              </div>
              <span
                role="button"
                tabindex="0"
                title="Copy to clipboard"
                @click="copyToClipboard(company.operator?.phone, 'Phone')"
                @keydown.enter.prevent="copyToClipboard(company.operator?.phone, 'Phone')"
                @keydown.space.prevent="copyToClipboard(company.operator?.phone, 'Phone')"
                class="cursor-pointer line-clamp-1 text-ellipsis"
              >
                {{ company.operator?.phone || '—' }}
              </span>
            </div>

            <div class="flex flex-row justify-between items-center gap-2">
              <div class="inline-flex gap-2 items-center">
                <RiMailLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Email</span>
              </div>
              <span class="inline-flex items-center overflow-hidden">
                <span
                  role="button"
                  tabindex="0"
                  title="Copy to clipboard"
                  @click="copyToClipboard(company.operator?.email, 'Email')"
                  @keydown.enter.prevent="copyToClipboard(company.operator?.email, 'Email')"
                  @keydown.space.prevent="copyToClipboard(company.operator?.email, 'Email')"
                  class="cursor-pointer text-ellipsis line-clamp-1 mr-2"
                >
                  {{ company.operator?.email || '—' }}
                </span>
                <RiVerifiedBadgeLine :v-if="company.operator?.email_verified" class="shrink-0 h-4 w-4 text-custom-shadow/80 text-custom-accent-3"/>
              </span>
            </div>
          </div>

          <CardSeparator title="Others" />

          <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center">
              <div class="inline-flex gap-2 items-center">
                <RiCalendarLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Joined</span>
              </div>
              <span class="text-ellipsis line-clamp-1">{{ formatDate(company.operator?.created_at) }}</span>
            </div>
          </div>
        </CardContent>
      </Card> -->
    </div>
  </div>
</template>