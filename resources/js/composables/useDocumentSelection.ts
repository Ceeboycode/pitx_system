import { computed, ref } from 'vue';

/** "Select" mode of a Documents tab: which rows are ticked for a multi-download. */
export function useDocumentSelection(documentIds: () => number[]) {
    const selectMode = ref(false);
    const selectedIds = ref<number[]>([]);

    const allSelected = computed(() => documentIds().length > 0 && selectedIds.value.length === documentIds().length);

    function reset() {
        selectMode.value = false;
        selectedIds.value = [];
    }

    function toggleSelectMode() {
        if (selectMode.value) {
            reset();
        } else {
            selectMode.value = true;
        }
    }

    function setSelected(id: number, checked: boolean) {
        const isSelected = selectedIds.value.includes(id);

        if (checked && !isSelected) selectedIds.value = [...selectedIds.value, id];
        else if (!checked && isSelected) selectedIds.value = selectedIds.value.filter((selectedId) => selectedId !== id);
    }

    function selectAll() {
        selectedIds.value = allSelected.value ? [] : documentIds();
    }

    return { selectMode, selectedIds, allSelected, toggleSelectMode, setSelected, selectAll, reset };
}
