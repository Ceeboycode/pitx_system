<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Search, X } from 'lucide-vue-next';

const props = defineProps<{
    route: string;
    initialValue?: string | null;
    placeholder?: string;
    only?: string[];
    debounce?: number;
}>();

const search = ref(props.initialValue ?? '');

let timeout: number | undefined;

watch(search, (value) => {
    window.clearTimeout(timeout);

    timeout = window.setTimeout(() => {
        router.get(
            props.route,
            { search: value || undefined },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: props.only,
            }
        );
    }, props.debounce ?? 350);
});

const clear = () => {
    search.value = '';
};
</script>

<template>
    <div class="relative w-full">
        <Search
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"
            :size="16"
        />

        <Input
            v-model="search"
            :placeholder="placeholder ?? 'Search...'"
            class="pl-9 pr-10"
        />

        <button
            v-if="search"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-muted-foreground hover:text-foreground"
            @click="clear"
            aria-label="Clear search"
        >
            <X :size="16" />
        </button>
    </div>
</template>
