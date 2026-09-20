<script setup lang="ts">
import ArchiveCompanyDialog from '@/components/internal/company/ArchiveCompanyDialog.vue';
import ToggleCompanyStatusDialog from '@/components/internal/company/ToggleCompanyStatusDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { ArchivedNotice } from '@/components/ui/_archived-notice';
import type { BreadcrumbItem } from '@/types';
import type { CompanyDocument, Operator } from '@/types/company';
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import { can } from '@/lib/can';
import { companyDocumentPreview } from '@/lib/document-preview';
import { DocumentPreviewCard } from '@/components/internal/preview-cards';

import { DropdownMenuItem } from '@/components/ui/dropdown-menu';

import { index, show } from '@/routes/companies';

import {
    RiDashboardHorizontalLine,
    RiGroupLine,
    RiFileListLine,
    RiFolderLine,
    RiBusLine,
    RiRoadMapLine,
    RiAlertLine,
    RiArchive2Line,
    RiShutDownLine,
    RiHistoryLine,
} from 'vue-remix-icons';
import { LeadPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import { LeadingCard } from '@/components/ui/_leading-card';
import { 
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/_tabs';
import Overview from '@/components/internal/company/show/OverviewTab.vue';
import Details from '@/components/internal/company/show/DetailsTab.vue';
import Documents from '@/components/internal/company/show/DocumentsTab.vue';
import Vehicles from '@/components/internal/company/show/VehiclesTab.vue';
import Employees from '@/components/internal/company/show/EmployeesTab.vue';
import Dispatches from '@/components/internal/company/show/DispatchesTab.vue';
import IncidentReports from '@/components/internal/company/show/IncidentReportsTab.vue';
import History from '@/components/internal/company/show/HistoryTab.vue';

const props = defineProps<{
    isArchived?: boolean;
    company: {
        id: number;
        company_name: string;
        company_code?: string | null;
        company_email?: string | null;
        company_phone?: string | null;
        company_address?: string | null;
        business_type?: 'corporate' | 'sole_proprietorship' | null;
        registration_number?: string | null;
        status?: string | null;
        is_active?: boolean | number | null;
        created_at?: string | null;
        updated_at_human?: string | null;
        creator?: { name: string } | null;
        updater?: { name: string } | null;
        operator?: Operator | null;
        operator_created_at?: string | null;
        authorized_representative_name?: string | null;
        authorized_representative_position?: string | null;
        authorized_representative_contact?: string | null;
        documents?: CompanyDocument[];
        logo?: string | null;
        logo_url?: string | null;
    };
}>();

const company = computed(() => props.company);

const canArchiveCompany = computed(() => can('companies.archive'));
const canUpdateCompany = computed(() => can('companies.update'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Company Details', href: show({ company: company.value.id }).url },
];

const archiveOpen = ref(false);
const toggleOpen = ref(false);

const activeTab = ref('documents');

// The Documents tab picks the document; the page shows it in the side panel. Held as an id so the
// card follows the fresh data after a document is verified, and only while that tab is open.
const previewedDocumentId = ref<number | null>(null);
const previewedDocument = computed(() => {
    const doc = company.value.documents?.find((document) => document.id === previewedDocumentId.value);

    return doc ? companyDocumentPreview(doc, company.value.id) : null;
});

watch(activeTab, (tab) => {
    if (tab !== 'documents') previewedDocumentId.value = null;
});

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
        value: 'documents',
        label: 'Documents',
        icon: RiFolderLine,
        component: Documents,
    },
    {
        value: 'vehicles',
        label: 'Vehicles',
        icon: RiBusLine,
        component: Vehicles,
    },
    {
        value: 'employees',
        label: 'Employees',
        icon: RiGroupLine,
        component: Employees,
    },
    {
        value: 'dispatches',
        label: 'Dispatches',
        icon: RiRoadMapLine,
        component: Dispatches,
    },
    {
        value: 'incident-reports',
        label: 'Incident Reports',
        icon: RiAlertLine,
        component: IncidentReports,
    },
    {
        value: 'history',
        label: 'History',
        icon: RiHistoryLine,
        component: History,
    }
] as const;

// const repHasAny = computed(() => {
//     const c = company.value;
//     return !!(
//         c.authorized_representative_name ||
//         c.authorized_representative_position ||
//         c.authorized_representative_contact
//     );
// });

// USE OTHER INDICATORS FOR THESE

// const totalDocs = computed(() => docs.value.length);
// const verifiedDocs = computed(
//     () => docs.value.filter((doc) => doc.status === 'verified').length,
// );
// const pendingDocs = computed(
//     () =>
//         docs.value.filter((doc) =>
//             ['pending', 'for_verification'].includes(doc.status),
//         ).length,
// );
// const flaggedDocs = computed(
//     () =>
//         docs.value.filter(
//             (doc) =>
//                 ['invalid', 'expired', 'rejected'].includes(doc.status) ||
//                 isExpired(doc.expires_at),
//         ).length,
// );
</script>

<template>
    <Head :title="company.company_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <LeadPanel class="min-w-0 shrink">
                <ArchivedNotice v-if="props.isArchived" entity="company" />
                <LeadingCard
                    :title="company.company_name"
                    description="Review and manage company documents."
                    variant="entity-details"
                    :status="company.is_active ? 'active' : 'inactive'"
                    :back="index().url"
                >
                    <DropdownMenuItem
                        class="group cursor-pointer"
                        :disabled="!canUpdateCompany"
                        @click="toggleOpen = true"
                    >
                        <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        {{ company.is_active ? 'Inactivate' : 'Activate' }}
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        class="group cursor-pointer"
                        :disabled="!canArchiveCompany"
                        @click="archiveOpen = true"
                    >
                        <RiArchive2Line class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        Archive
                    </DropdownMenuItem>
                </LeadingCard>
                <Tabs v-model="activeTab">
                    <TabsList>
                        <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
                            <component :is="tab.icon" class="h-4 w-4"/>
                            <span>{{ tab.label }}</span>
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value">
                        <Documents v-if="tab.value === 'documents'" v-model:previewed-id="previewedDocumentId" :company="company" />
                        <component :is="tab.component" v-else :company="company" />
                    </TabsContent>
                </Tabs>
            </LeadPanel>
            <SidePanel v-if="previewedDocument" class="hidden lg:flex">
                <DocumentPreviewCard :doc="previewedDocument" @close="previewedDocumentId = null" />
            </SidePanel>
        </PanelLayout>
        
        <ArchiveCompanyDialog
            v-if="canArchiveCompany"
            v-model:open="archiveOpen"
            :company="company"
        />

        <ToggleCompanyStatusDialog
            v-if="canUpdateCompany"
            v-model:open="toggleOpen"
            :company="company"
        />
    </AppLayout>
</template>
