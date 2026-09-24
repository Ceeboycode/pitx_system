<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import DispatchController from '@/actions/App/Http/Controllers/DispatchController';
import { MarkDepartedDialog } from '@/components/external/dispatch';
import DispatchStepMarker from '@/components/external/dispatch/DispatchStepMarker.vue';
import Details from '@/components/external/dispatch/show/DetailsTab.vue';
import Overview from '@/components/external/dispatch/show/OverviewTab.vue';
import type {
    DispatchGate,
    DispatchMapConfig,
    DispatchModel,
    DispatchRoute,
} from '@/components/external/dispatch/show/types';
import { LeadingCard } from '@/components/ui/_leading-card';
import { LeadPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import { PreviewCard } from '@/components/ui/_preview-card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/_tabs';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';
import { RiDashboardHorizontalLine, RiFileListLine, RiLogoutBoxLine } from 'vue-remix-icons';

const props = defineProps<{
    dispatch: DispatchModel;
    routes: DispatchRoute[];
    gates: DispatchGate[];
    mapConfig: DispatchMapConfig;
}>();

const canDepartDispatch = can('external_dispatches.depart');
const departOpen = ref(false);

const tabs = [
    { value: 'overview', label: 'Overview', icon: RiDashboardHorizontalLine },
    { value: 'details', label: 'Details', icon: RiFileListLine },
] as const;

const timeline = computed(() => [
    { label: 'Dispatched', time: props.dispatch.dispatched_at_formatted },
    { label: 'Arrived', time: props.dispatch.arrived_at_formatted },
    { label: 'Departed', time: props.dispatch.departed_at_formatted },
]);
</script>

<template>
    <Head :title="`Dispatch — ${dispatch.plate_number}`" />

    <ExternalLayout>
        <PanelLayout>
            <LeadPanel class="flex-1">
                <LeadingCard
                    :title="dispatch.plate_number"
                    :description="`Review dispatch details.`"
                    variant="entity-details"
                    entity="dispatch"
                    :back="DispatchController.index().url"
                    :more="canDepartDispatch && dispatch.status === 'arrived'"
                >
                    <DropdownMenuItem class="group cursor-pointer" @click="departOpen = true">
                        <RiLogoutBoxLine class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        Mark as Departed
                    </DropdownMenuItem>
                </LeadingCard>

                <Tabs default-value="overview">
                    <TabsList>
                        <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
                            <component :is="tab.icon" class="h-4 w-4" />
                            <span>{{ tab.label }}</span>
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <Overview :dispatch="dispatch" :routes="routes" :gates="gates" :map-config="mapConfig" />
                    </TabsContent>

                    <TabsContent value="details">
                        <Details :dispatch="dispatch" :routes="routes" :map-config="mapConfig" />
                    </TabsContent>
                </Tabs>
            </LeadPanel>

            <SidePanel class="hidden lg:flex">
                <PreviewCard title="Dispatch Timeline" description="When this dispatch reached each step." :closable="false">
                    <div class="my-2 space-y-1">
                        <div v-for="(step, index) in timeline" :key="step.label" class="flex items-start gap-3 p-2">
                            <DispatchStepMarker
                                :number="index + 1"
                                :done="!!step.time"
                                :final="index === timeline.length - 1"
                                :connector="index < timeline.length - 1"
                                :next-done="!!timeline[index + 1]?.time"
                            />
                            <div class="min-w-0">
                                <p class="truncate text-sm leading-tight font-semibold" :class="step.time ? 'text-custom-shadow' : 'text-custom-shadow/60'">
                                    {{ step.label }}
                                </p>
                                <p class="text-xs text-custom-shadow/80 tabular-nums">{{ step.time ?? 'Not yet' }}</p>
                            </div>
                        </div>
                    </div>
                </PreviewCard>
            </SidePanel>
        </PanelLayout>

        <MarkDepartedDialog v-model:open="departOpen" :dispatch="dispatch" />
    </ExternalLayout>
</template>
