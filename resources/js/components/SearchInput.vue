<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { RiSearchLine, RiCloseLine } from 'vue-remix-icons';

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
        <RiSearchLine
            class="size-4 pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-custom-shadow/80"
        />

        <Input
            v-model="search"
            :placeholder="placeholder ?? 'Search...'"
            class="px-9 placeholder:text-custom-shadow/50 h-9 w-full rounded-full border border-custom-bg-dark dark:border-none text-sm transition-[color,background-color,border-color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-white dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5',"
        />

        <button
            v-if="search"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-auto text-custom-shadow/80 hover:text-custom-shadow cursor-pointer transition-all duration-300"
            @click="clear"
            aria-label="Clear search"
        >
            <RiCloseLine class="size-4" aria-hidden="true" />
        </button>
    </div>
</template>
