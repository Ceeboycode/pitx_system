<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController';
import { ToggleVehicleStatusDialog } from '@/components/external/vehicle';
import Details from '@/components/external/vehicle/edit/DetailsTab.vue';
import Dispatches from '@/components/external/vehicle/edit/DispatchesTab.vue';
import Documents from '@/components/external/vehicle/edit/DocumentsTab.vue';
import History from '@/components/external/vehicle/edit/HistoryTab.vue';
import Overview from '@/components/external/vehicle/edit/OverviewTab.vue';
import {
    buildFormValues,
    type DispatchRow,
    type HistoryEntry,
    type MapConfig,
    type RouteItem,
    type VehicleFormData,
    type VehicleModel,
} from '@/components/external/vehicle/edit/types';
import { DocumentPreviewCard } from '@/components/internal/preview-cards';
import { LeadingCard } from '@/components/ui/_leading-card';
import { LeadPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/_tabs';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';
import { businessCanToggle, toggleLabel } from '@/lib/company-vehicle';
import { vehicleDocumentPreview } from '@/lib/document-preview';
import {
    RiDashboardHorizontalLine,
    RiFileListLine,
    RiFolderLine,
    RiHistoryLine,
    RiRoadMapLine,
    RiShutDownLine,
} from 'vue-remix-icons';

type Company = {
    id: number;
    company_name: string;
    company_code?: string | null;
    status: string;
    logo_url?: string | null;
};

type User = {
    id: number;
    name: string;
    username: string;
    email: string;
};

const props = defineProps<{
    company: Company;
    user: User;
    vehicle: VehicleModel;
    dispatches: DispatchRow[];
    history: HistoryEntry[];
    routes: RouteItem[];
    docTypes: Record<string, string>;
    vehicleTypes: Array<{ id: number; type_name: string }>;
    mapConfig: MapConfig;
}>();

const canUpdateVehicle = can('external_vehicles.update');
const canToggleStatus = can('external_vehicles.toggleStatus');
const canViewDispatches = can('external_dispatches.viewAny');

// One form serves the Details and Documents tabs. It is only used when the account may update and the
// vehicle is not suspended; every other visitor sees the same page as read-only text.
const canEdit = computed(() => canUpdateVehicle && props.vehicle.status !== 'suspended');

const form = useForm<VehicleFormData>(buildFormValues(props.vehicle, props.docTypes));

function submit() {
    if (!canEdit.value) return;

    form.transform((data) => ({ ...data, _method: 'put' })).post(CompanyVehicleController.update(props.vehicle.id).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            form.defaults(buildFormValues(page.props.vehicle as VehicleModel, props.docTypes));
            form.reset();
        },
        onFinish: () => {
            form.transform((data) => data);
        },
    });
}

function resetForm() {
    form.reset();
    form.clearErrors();
}

const toggleOpen = ref(false);
const canToggleVehicle = computed(() => canToggleStatus && businessCanToggle(props.vehicle));

const activeTab = ref('details');

// The Documents tab picks the document; the page shows it in the side panel. Held as an id so the
// card follows fresh data, and only while that tab is open.
const previewedDocumentId = ref<number | null>(null);
const previewedDocument = computed(() => {
    const doc = props.vehicle.documents.find((document) => document.id === previewedDocumentId.value);

    return doc ? { ...vehicleDocumentPreview(doc), downloadUrl: doc.download_url ?? doc.file_url ?? null } : null;
});

watch(activeTab, (tab) => {
    if (tab !== 'documents') previewedDocumentId.value = null;
});

const tabs = computed(() =>
    [
        { value: 'overview', label: 'Overview', icon: RiDashboardHorizontalLine },
        { value: 'details', label: 'Details', icon: RiFileListLine },
        { value: 'documents', label: 'Documents', icon: RiFolderLine },
        { value: 'dispatches', label: 'Dispatches', icon: RiRoadMapLine },
        { value: 'history', label: 'History', icon: RiHistoryLine },
    ].filter((tab) => tab.value !== 'dispatches' || canViewDispatches),
);
</script>

<template>
    <Head :title="`Vehicle — ${vehicle.plate_number}`" />

    <ExternalLayout :company="company" :user="user">
        <PanelLayout>
            <LeadPanel class="flex-1">
                <LeadingCard
                    :title="vehicle.plate_number"
                    description="Review and manage vehicle details, route, and documents."
                    variant="entity-details"
                    entity="vehicle"
                    :back="CompanyVehicleController.index().url"
                    :status="vehicle.status === 'active' || vehicle.status === 'inactive' ? vehicle.status : null"
                >
                    <DropdownMenuItem
                        class="group cursor-pointer"
                        :disabled="!canToggleVehicle"
                        @click="toggleOpen = true"
                    >
                        <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        {{ toggleLabel(vehicle.status) }}
                    </DropdownMenuItem>
                </LeadingCard>

                <Tabs v-model="activeTab">
                    <TabsList>
                        <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
                            <component :is="tab.icon" class="h-4 w-4" />
                            <span>{{ tab.label }}</span>
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <Overview :vehicle="vehicle" :doc-types="docTypes" />
                    </TabsContent>

                    <TabsContent value="details">
                        <Details
                            :vehicle="vehicle"
                            :vehicle-types="vehicleTypes"
                            :routes="routes"
                            :map-config="mapConfig"
                            v-model:form="form"
                            :can-edit="canEdit"
                            @submit="submit"
                            @reset="resetForm"
                        />
                    </TabsContent>

                    <TabsContent value="documents">
                        <Documents
                            v-model:previewed-id="previewedDocumentId"
                            :vehicle="vehicle"
                            :doc-types="docTypes"
                            v-model:form="form"
                            :can-edit="canEdit"
                            @submit="submit"
                            @reset="resetForm"
                        />
                    </TabsContent>

                    <TabsContent v-if="canViewDispatches" value="dispatches">
                        <Dispatches :dispatches="dispatches" />
                    </TabsContent>

                    <TabsContent value="history">
                        <History :history="history" />
                    </TabsContent>
                </Tabs>
            </LeadPanel>

            <SidePanel v-if="previewedDocument" class="hidden lg:flex">
                <DocumentPreviewCard :doc="previewedDocument" @close="previewedDocumentId = null" />
            </SidePanel>
        </PanelLayout>

        <ToggleVehicleStatusDialog v-model:open="toggleOpen" :vehicle="vehicle" />
    </ExternalLayout>
</template>
