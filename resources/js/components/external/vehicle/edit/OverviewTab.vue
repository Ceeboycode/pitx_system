<script setup lang="ts">
import { computed } from 'vue';

import { CardSeparator } from '@/components/ui/_card-separator';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { RiFileWarningLine, RiRouteLine } from 'vue-remix-icons';

import { hasDocsNeedingResubmission, needsResubmission } from '@/lib/company-vehicle';
import { formatDate, humanize } from '@/lib/format';
import {
    operationalStatusClass,
    operationalStatusDot,
    operationalStatusLabel,
    vehicleDocumentStatusClass,
    vehicleDocumentStatusDot,
    vehicleDocumentStatusLabel,
    verificationStatusClass,
    verificationStatusDot,
    verificationStatusLabel,
} from '@/lib/vehicle-status';

import type { VehicleModel } from './types';

const props = defineProps<{
    vehicle: VehicleModel;
    docTypes: Record<string, string>;
}>();

const docs = computed(() => props.vehicle.documents ?? []);
const requiredCount = computed(() => Object.keys(props.docTypes).length);
const verifiedCount = computed(() => docs.value.filter((doc) => doc.status === 'verified').length);
const pendingCount = computed(() => docs.value.filter((doc) => doc.status === 'pending').length);
const needsActionDocs = computed(() => docs.value.filter((doc) => needsResubmission(doc)));
const completion = computed(() => (requiredCount.value ? Math.round((verifiedCount.value / requiredCount.value) * 100) : 0));

const remarks = computed(() =>
    [
        { label: 'Verification Remark', value: props.vehicle.verification_remark },
        { label: 'Operator Remark', value: props.vehicle.operator_remark },
        { label: 'Suspension Remark', value: props.vehicle.suspension_remark },
    ].filter((remark) => !!remark.value),
);

function docLabel(type: string) {
    return props.docTypes[type] ?? humanize(type);
}

const sortedStops = computed(() => [...(props.vehicle.route?.stops ?? [])].sort((a, b) => a.stop_order - b.stop_order));
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardContent class="space-y-2">
                    <p class="text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Status</p>
                    <Badge :class="['gap-1.5', operationalStatusClass(vehicle.status)]">
                        <span :class="['h-1.5 w-1.5 rounded-full', operationalStatusDot(vehicle.status)]" />
                        {{ operationalStatusLabel(vehicle.status) }}
                    </Badge>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="space-y-2">
                    <p class="text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Verification</p>
                    <Badge :class="['gap-1.5', verificationStatusClass(vehicle.verification_status)]">
                        <span :class="['h-1.5 w-1.5 rounded-full', verificationStatusDot(vehicle.verification_status)]" />
                        {{ verificationStatusLabel(vehicle.verification_status) }}
                    </Badge>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="space-y-1">
                    <p class="text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Documents Verified</p>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ verifiedCount }}
                        <span class="text-base font-semibold text-custom-shadow/70">/ {{ requiredCount }}</span>
                    </p>
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-custom-bg dark:bg-custom-bg-light">
                        <div class="h-full rounded-full bg-emerald-500 transition-all duration-200" :style="{ width: `${completion}%` }" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="space-y-1">
                    <p class="text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Needs Action</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ needsActionDocs.length }}</p>
                    <p class="text-xs text-custom-shadow/70">{{ pendingCount }} under review</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Route</CardTitle>
                    <CardDescription>The line this vehicle operates on.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="vehicle.route" class="space-y-3">
                        <div class="flex items-center gap-2 rounded-md bg-custom-secondary/10 px-3 py-2 dark:bg-custom-secondary/20">
                            <RiRouteLine class="h-4 w-4 shrink-0 text-custom-shadow" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ vehicle.route.route_name }}</p>
                                <p class="truncate text-xs text-custom-shadow/80">
                                    {{ vehicle.route.origin_name || '—' }} → {{ vehicle.route.destination_name || '—' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-0.5 text-sm text-custom-shadow">
                            <div class="flex flex-row items-center justify-between">
                                <span>Gate</span>
                                <span class="text-custom-shadow/80">{{ vehicle.route.gate?.gate_name ?? '—' }}</span>
                            </div>
                            <div class="flex flex-row items-center justify-between">
                                <span>Total Stops</span>
                                <span class="text-custom-shadow/80">{{ sortedStops.length }}</span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-custom-shadow/80">No route assigned.</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Attention</CardTitle>
                    <CardDescription>What needs to happen before this vehicle can operate.</CardDescription>
                </CardHeader>
                <CardContent>
                    <CardSeparator title="Documents" />

                    <div class="my-2 flex flex-col gap-2 text-sm text-custom-shadow">
                        <div
                            v-for="doc in needsActionDocs"
                            :key="doc.id"
                            class="flex flex-row items-center justify-between gap-3"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <RiFileWarningLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
                                <span class="truncate">{{ docLabel(doc.document_type) }}</span>
                            </span>
                            <Badge :class="['gap-1.5', vehicleDocumentStatusClass(doc.status)]">
                                <span :class="['h-1.5 w-1.5 rounded-full', vehicleDocumentStatusDot(doc.status)]" />
                                {{ vehicleDocumentStatusLabel(doc.status) }}
                            </Badge>
                        </div>

                        <p v-if="!hasDocsNeedingResubmission(vehicle)" class="text-custom-shadow/80">
                            No invalid or expired documents.
                        </p>
                    </div>

                    <template v-if="remarks.length">
                        <CardSeparator title="Remarks" />

                        <div class="my-2 flex flex-col gap-2 text-sm text-custom-shadow">
                            <div v-for="remark in remarks" :key="remark.label" class="space-y-0.5">
                                <p class="font-semibold">{{ remark.label }}</p>
                                <p class="text-custom-shadow/80">{{ remark.value }}</p>
                            </div>
                        </div>
                    </template>

                    <p class="mt-2 text-xs text-custom-shadow/70">Registered {{ formatDate(vehicle.created_at) }}</p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
