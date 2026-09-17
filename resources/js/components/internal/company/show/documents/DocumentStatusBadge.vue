<script setup lang="ts">
import { Badge } from '@/components/ui/_badge';
import {
    docStatusClass,
    docStatusDot,
    docStatusLabel,
    type DocumentLike,
} from '@/lib/company-documents';

defineProps<{
    doc?: DocumentLike | string | null;
}>();
</script>

<template>
    <Badge :class="['gap-1.5', docStatusClass(doc)]">
        <span :class="['h-1.5 w-1.5 rounded-full', docStatusDot(doc)]" />
        {{ docStatusLabel(doc) }}
    </Badge>
</template>
