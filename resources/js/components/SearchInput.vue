<script setup lang="ts">
import { nextTick, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { RiSearchLine, RiCloseLine } from 'vue-remix-icons';

const props = defineProps<{
    route?: string;
    modelValue?: string;
    initialValue?: string | null;
    placeholder?: string;
    only?: string[];
    debounce?: number;
    // Lets a page merge its own active filters/sort into this component's own
    // debounced request, since router.get() replaces the whole query string
    // rather than merging with it - without this, typing a search term would
    // silently drop any other filters already applied on the page.
    extraParams?: () => Record<string, string | null | undefined>;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const search = ref(props.modelValue ?? props.initialValue ?? '');

let timeout: number | undefined;
let isSyncingFromProp = false;

watch(search, (value) => {
    emit('update:modelValue', value);
    if (isSyncingFromProp) return;

    window.clearTimeout(timeout);

    const route = props.route;
    if (!route) return;

    timeout = window.setTimeout(() => {
        router.get(
            route,
            { search: value || undefined, ...props.extraParams?.() },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: props.only,
            }
        );
    }, props.debounce ?? 350);
});

watch(
    () => props.modelValue,
    (value) => {
        if (value !== undefined && value !== search.value) {
            isSyncingFromProp = true;
            search.value = value;
            nextTick(() => { isSyncingFromProp = false; });
        }
    },
);

onUnmounted(() => window.clearTimeout(timeout));

const clear = () => {
    search.value = '';
};
</script>

<template>
    <div class="relative w-full">
        <RiSearchLine
            class="size-4 shrink-0 pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-custom-shadow/80"
        />

        <Input
            v-model="search"
            :placeholder="placeholder ?? 'Search...'"
            class="px-9 placeholder:text-custom-shadow/50 h-9 w-full rounded-full border border-custom-bg-dark dark:border-none text-sm transition-[color,background-color,border-color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-white bg-custom-light dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
        />

        <button
            v-if="search"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-auto text-custom-shadow/80 hover:text-custom-shadow cursor-pointer transition-all duration-200"
            @click="clear"
            aria-label="Clear search"
        >
            <RiCloseLine class="size-4 shrink-0" aria-hidden="true" />
        </button>
    </div>
</template>
