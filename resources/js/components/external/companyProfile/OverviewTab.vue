<script setup lang="ts">
import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

import { docStatusClass, docStatusDot } from '@/lib/company-documents';
import { formatDateTime, humanize } from '@/lib/format';

import type { CompanyProfile, CompanyProfileChangeRequest } from './types';

const props = defineProps<{
    company: CompanyProfile;
    latestChangeRequest: CompanyProfileChangeRequest | null;
}>();

const docs = computed(() => props.company.documents ?? []);
const verifiedCount = computed(() => docs.value.filter((doc) => doc.status === 'verified').length);
const pendingCount = computed(() => docs.value.filter((doc) => doc.status === 'pending').length);
const needsActionCount = computed(() => docs.value.filter((doc) => ['invalid', 'expired'].includes(doc.status)).length);
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardContent class="space-y-2">
                    <p class="text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Status</p>
                    <Badge :class="['gap-1.5', docStatusClass(company.status)]">
                        <span :class="['h-1.5 w-1.5 rounded-full', docStatusDot(company.status)]" />
                        {{ humanize(company.status) }}
                    </Badge>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="space-y-1">
                    <p class="text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Documents Verified</p>
                    <p class="text-2xl font-semibold tabular-nums">
                        {{ verifiedCount }}
                        <span class="text-base font-semibold text-custom-shadow/70">/ {{ docs.length }}</span>
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="space-y-1">
                    <p class="text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Under Review</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ pendingCount }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="space-y-1">
                    <p class="text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Needs Action</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ needsActionCount }}</p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Latest Change Request</CardTitle>
                <CardDescription>Track your latest profile update before submitting another.</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="latestChangeRequest" class="flex flex-col gap-2 text-sm text-custom-shadow">
                    <div class="flex flex-row items-center justify-between">
                        <span>Request #{{ latestChangeRequest.id }}</span>
                        <Badge :class="['gap-1.5', docStatusClass(latestChangeRequest.status === 'approved' ? 'verified' : latestChangeRequest.status)]">
                            <span :class="['h-1.5 w-1.5 rounded-full', docStatusDot(latestChangeRequest.status === 'approved' ? 'verified' : latestChangeRequest.status)]" />
                            {{ humanize(latestChangeRequest.status) }}
                        </Badge>
                    </div>
                    <p class="text-xs text-custom-shadow/70">Submitted {{ formatDateTime(latestChangeRequest.created_at) }}</p>
                    <p v-if="latestChangeRequest.status === 'pending'" class="text-custom-shadow/80">
                        Your request is under review. You can submit again once admins finish processing.
                    </p>
                    <p v-if="latestChangeRequest.status === 'rejected'" class="text-custom-shadow/80">
                        {{ latestChangeRequest.rejection_reason || 'No reason provided.' }}
                    </p>
                </div>
                <p v-else class="text-sm text-custom-shadow/80">No profile change requests yet.</p>
            </CardContent>
        </Card>
    </div>
</template>
