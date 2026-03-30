import { router } from '@inertiajs/vue3';
import { computed, unref, type ComputedRef, type Ref } from 'vue';

type MaybeRef<T> = T | Ref<T> | ComputedRef<T>;
type QueryValue = string | null | undefined;

export function useSortableIndex(options: {
    route: string;
    baseQuery: MaybeRef<Record<string, QueryValue>>;
    sort: MaybeRef<string>;
    direction: MaybeRef<string>;
    only?: string[];
}) {
    const currentSort = computed(() => unref(options.sort));
    const currentDirection = computed(() => unref(options.direction));

    function navigate(sort: string, direction: string) {
        router.get(
            options.route,
            {
                ...unref(options.baseQuery),
                sort,
                direction,
            },
            {
                preserveScroll: true,
                only: options.only,
            },
        );
    }

    function applySort(sort: string) {
        navigate(sort, currentDirection.value);
    }

    function toggleDirection() {
        navigate(
            currentSort.value,
            currentDirection.value === 'asc' ? 'desc' : 'asc',
        );
    }

    return {
        currentSort,
        currentDirection,
        applySort,
        toggleDirection,
    };
}
