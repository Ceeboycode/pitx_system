<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/gates';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

import { LeadPanel } from '@/components/ui/_panels';
import { LeadingCard } from '@/components/ui/_leading-card';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/_tabs';
import { ArchiveGateDialog } from '@/components/internal/gate';
import Overview from '@/components/internal/gate/edit/OverviewTab.vue';
import Details from '@/components/internal/gate/edit/DetailsTab.vue';
import Routes from '@/components/internal/gate/edit/RoutesTab.vue';
import Dispatches from '@/components/internal/gate/edit/DispatchesTab.vue';
import History from '@/components/internal/gate/edit/HistoryTab.vue';
import { RiArchive2Line, RiDashboardHorizontalLine, RiFileListLine, RiRoadMapLine, RiRouteLine, RiTimeLine } from 'vue-remix-icons';
import { can } from '@/lib/can';

type Gate = {
    id: number;
    gate_name: string;
    status: 'active' | 'inactive';
    bays: number;
    location: string | null;
    picture_url: string | null;
    created_at_human: string | null;
    updated_at_human: string | null;
    creator: { name: string } | null;
    updater: { name: string } | null;
};

const props = defineProps<{ gate: Gate }>();

const canArchiveGate = can('gates.archive');
const archiveOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gates', href: index().url },
    { title: props.gate.gate_name, href: '#' },
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
        value: 'routes',
        label: 'Routes',
        icon: RiRouteLine,
        component: Routes,
    },
    {
        value: 'dispatches',
        label: 'Dispatches',
        icon: RiRoadMapLine,
        component: Dispatches,
    },
    {
        value: 'history',
        label: 'History',
        icon: RiTimeLine,
        component: History,
    },
] as const;
</script>

<template>
    <Head :title="`Gate — ${gate.gate_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <LeadPanel>
            <LeadingCard
                :title="gate.gate_name"
                description="Review and manage gate details."
                variant="entity-details"
                :back="index().url"
                :status="gate.status"
            >
                <DropdownMenuItem
                    class="group cursor-pointer"
                    :disabled="!canArchiveGate"
                    @click="archiveOpen = true"
                >
                    <RiArchive2Line class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                    Archive
                </DropdownMenuItem>
            </LeadingCard>

            <Tabs default-value="details">
                <TabsList>
                    <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
                        <component :is="tab.icon" class="h-4 w-4"/>
                        <span>{{ tab.label }}</span>
                    </TabsTrigger>
                </TabsList>
                <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value">
                    <component :is="tab.component" :gate="gate" />
                </TabsContent>
            </Tabs>
        </LeadPanel>

        <ArchiveGateDialog v-model:open="archiveOpen" :gate="gate" />
    </AppLayout>
</template>
