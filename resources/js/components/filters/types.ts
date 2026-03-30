import type { Component } from 'vue';

export type FilterOption = {
    label: string;
    value: string;
    icon?: Component;
};

export type SelectFilter = {
    key: string;
    value?: string | null;
    placeholder: string;
    allLabel?: string;
    allValue?: string | null;
    includeAllOption?: boolean;
    desktopWidthClass?: string;
    desktopMaxWidth?: string;
    options: FilterOption[];
};

export type SortOption = {
    label: string;
    value: string;
};
