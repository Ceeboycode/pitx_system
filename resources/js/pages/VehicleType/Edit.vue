<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ArchivedNotice } from '@/components/ui/_archived-notice';
import { index } from '@/routes/vehicle-types';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { LeadPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import { LeadingCard } from '@/components/ui/_leading-card';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/_tabs';
import ArchiveVehicleTypeDialog from '@/components/internal/vehicleType/ArchiveVehicleTypeDialog.vue';
import ToggleVehicleTypeStatusDialog from '@/components/internal/vehicleType/ToggleVehicleTypeStatusDialog.vue';
import Overview from '@/components/internal/vehicleType/edit/OverviewTab.vue';
import Details from '@/components/internal/vehicleType/edit/DetailsTab.vue';
import History from '@/components/internal/vehicleType/edit/HistoryTab.vue';
import { RiArchive2Line, RiDashboardHorizontalLine, RiFileListLine, RiHistoryLine, RiShutDownLine } from 'vue-remix-icons';
import { can } from '@/lib/can';

type VehicleType = {
    id: number;
    type_name: string;
    description: string | null;
    picture_url: string | null;
    is_active: boolean;
    created_at_human: string | null;
    updated_at_human: string | null;
    creator: { name: string } | null;
    updater: { name: string } | null;
};

type RecentVehicle = {
    id: number;
    plate_number: string | null;
    body_number: string | null;
    status: string | null;
    company_name: string | null;
};

type AuditLogEntry = {
    id: number;
    action: string;
    action_label: string;
    user_name: string | null;
    created_at_human: string | null;
    changes: { field: string; label: string; old: unknown; new: unknown }[];
};

const props = defineProps<{
    isArchived?: boolean;
    vehicleType: VehicleType;
    vehicleStats: { total: number; active: number; inactive: number; suspended: number };
    recentVehicles: RecentVehicle[];
    auditLogs: AuditLogEntry[];
}>();

const canArchiveVehicleType = can('vehicle_types.archive');
const canUpdateVehicleType = can('vehicle_types.update');
const archiveOpen = ref(false);
const toggleOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicle Types', href: index().url },
    { title: props.vehicleType.type_name, href: '#' },
];

const tabs = [
    {
        value: 'overview',
        label: 'Overview',
        icon: RiDashboardHorizontalLine,
        component: Overview,
    },
    {
        value: 'details',
        label: 'Details',
        icon: RiFileListLine,
        component: Details,
    },
    {
        value: 'history',
        label: 'History',
        icon: RiHistoryLine,
        component: History,
    },
] as const;

// Each tab only needs a slice of the page's props - binding them explicitly
// per tab (rather than spreading everything onto every <component>) keeps
// unused props from leaking onto a tab's root element as DOM attributes.
const tabProps = computed(() => ({
    overview: { vehicleStats: props.vehicleStats, recentVehicles: props.recentVehicles },
    details: { vehicleType: props.vehicleType },
    history: { auditLogs: props.auditLogs },
}));
</script>

<template>
    <Head :title="`Vehicle Type — ${vehicleType.type_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <LeadPanel>
                <ArchivedNotice v-if="props.isArchived" entity="vehicle type" />
                <LeadingCard
                    :title="vehicleType.type_name"
                    description="Review and manage vehicle type details."
                    variant="entity-details"
                    :back="index().url"
                    :status="vehicleType.is_active ? 'active' : 'inactive'"
                >
                    <DropdownMenuItem
                        class="group cursor-pointer"
                        :disabled="!canUpdateVehicleType"
                        @click="toggleOpen = true"
                    >
                        <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        {{ vehicleType.is_active ? 'Inactivate' : 'Activate' }}
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        class="group cursor-pointer"
                        :disabled="!canArchiveVehicleType"
                        @click="archiveOpen = true"
                    >
                        <RiArchive2Line class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        Archive
                    </DropdownMenuItem>
                </LeadingCard>

                <Tabs default-value="details">
                    <TabsList>
                        <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
                            <component :is="tab.icon" class="h-4 w-4 shrink-0"/>
                            <span>{{ tab.label }}</span>
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value">
                        <component :is="tab.component" v-bind="tabProps[tab.value]" />
                    </TabsContent>
                </Tabs>
            </LeadPanel>

            <SidePanel>

            </SidePanel>
        </PanelLayout>

        <ArchiveVehicleTypeDialog v-model:open="archiveOpen" :vehicle-type="vehicleType" />
        <ToggleVehicleTypeStatusDialog v-model:open="toggleOpen" :vehicle_type="vehicleType" />
    </AppLayout>
</template>
