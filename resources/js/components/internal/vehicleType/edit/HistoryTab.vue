<script setup lang="ts">
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { RiHistoryLine } from 'vue-remix-icons';

type AuditChange = {
    field: string;
    label: string;
    old: unknown;
    new: unknown;
};

type AuditLogEntry = {
    id: number;
    action: string;
    action_label: string;
    user_name: string | null;
    created_at_human: string | null;
    changes: AuditChange[];
};

const props = defineProps<{
    auditLogs: AuditLogEntry[];
}>();

function actionBadgeClass(action: string): string {
    if (action === 'created') return 'border-emerald-200 bg-emerald-100 text-emerald-700';
    if (action === 'updated') return 'border-blue-200 bg-blue-100 text-blue-700';
    if (action === 'deleted') return 'border-rose-200 bg-rose-100 text-rose-700';

    return 'border-slate-200 bg-slate-100 text-slate-600';
}

function displayValue(value: unknown): string {
    if (value === null || value === undefined || value === '') return '—';
    return String(value);
}
</script>

<template>
  <Card class="lg:col-span-2">
    <CardHeader>
      <CardTitle>History</CardTitle>
      <CardDescription>Recent changes made to this vehicle type.</CardDescription>
    </CardHeader>
    <CardContent>
      <div v-if="props.auditLogs.length" class="relative space-y-1">
        <div class="absolute bottom-2 left-[15px] top-2 w-px bg-custom-bg-dark dark:bg-custom-bg-light" />

        <div
            v-for="log in props.auditLogs"
            :key="log.id"
            class="relative flex items-start gap-3 rounded-md p-2"
        >
          <div class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-custom-bg text-custom-shadow dark:bg-custom-bg-light">
            <RiHistoryLine class="h-4 w-4 shrink-0" />
          </div>

          <div class="min-w-0 flex-1 space-y-1.5 pt-1">
            <div class="flex flex-wrap items-center gap-2">
              <Badge :class="actionBadgeClass(log.action)">{{ log.action_label }}</Badge>
              <span class="text-sm font-semibold text-custom-shadow">{{ log.user_name || 'System' }}</span>
              <span class="text-xs text-custom-shadow/70">{{ log.created_at_human }}</span>
            </div>

            <div v-if="log.changes.length" class="space-y-1 rounded-md bg-custom-bg p-2 text-xs dark:bg-custom-bg-dark">
              <div
                  v-for="change in log.changes"
                  :key="change.field"
                  class="flex flex-wrap items-center gap-1.5"
              >
                <span class="font-semibold text-custom-shadow">{{ change.label }}:</span>
                <span class="text-custom-shadow/70 line-through">{{ displayValue(change.old) }}</span>
                <span class="text-custom-shadow/50">→</span>
                <span class="text-custom-shadow">{{ displayValue(change.new) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="rounded-md border border-dashed border-custom-bg-dark p-4 text-center text-sm text-custom-shadow/80 dark:border-custom-bg-light">
        No history recorded for this vehicle type yet.
      </p>
    </CardContent>
  </Card>
</template>
