<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import CompanyProfileController from '@/actions/App/Http/Controllers/CompanyProfileController';
import Details from '@/components/external/companyProfile/DetailsTab.vue';
import Documents from '@/components/external/companyProfile/DocumentsTab.vue';
import History from '@/components/external/companyProfile/HistoryTab.vue';
import Overview from '@/components/external/companyProfile/OverviewTab.vue';
import { documentLabel, type CompanyProfile, type CompanyProfileChangeRequest, type CompanyProfileFormData } from '@/components/external/companyProfile/types';
import { InputMessage } from '@/components/ui/_input-message';
import { LeadingCard } from '@/components/ui/_leading-card';
import { LeadPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import { PreviewCard, PreviewCardRow } from '@/components/ui/_preview-card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/_tabs';
import { Badge } from '@/components/ui/badge';
import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';
import { formatDate, formatDateTime } from '@/lib/format';
import { docStatusClass, docStatusDot, docStatusLabel } from '@/lib/company-documents';
import { RiDashboardHorizontalLine, RiFileListLine, RiFolderLine, RiHistoryLine } from 'vue-remix-icons';

const props = defineProps<{
    company: CompanyProfile;
    latest_change_request: CompanyProfileChangeRequest | null;
    profile_change_requests?: CompanyProfileChangeRequest[];
    user: {
        id: number;
        name: string;
        username: string;
        email: string;
    };
}>();

const canUpdateCompanyProfile = can('external_companies_settings.update');

// Profile edits are staged for admin review, so a new one can't be submitted while another is pending.
const canEdit = computed(() => canUpdateCompanyProfile && props.latest_change_request?.status !== 'pending');

function buildFormValues(company: CompanyProfile): CompanyProfileFormData {
    return {
        company_email: company.company_email ?? '',
        company_phone: company.company_phone ?? '',
        company_address: company.company_address ?? '',
        authorized_representative_name: company.authorized_representative_name ?? '',
        authorized_representative_position: company.authorized_representative_position ?? '',
        authorized_representative_contact: company.authorized_representative_contact ?? '',
        logo: null,
        remove_logo: false,
    };
}

const form = useForm<CompanyProfileFormData>(buildFormValues(props.company));

function submit() {
    if (!canEdit.value) return;

    form.post(CompanyProfileController.submitUpdate['/profile/logo'].url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.defaults(buildFormValues(props.company));
            form.reset();
        },
    });
}

function resetForm() {
    form.reset();
    form.clearErrors();
}

const activeTab = ref('details');

// The Documents tab picks the document; the page shows it in the side panel, and only while that tab is open.
const previewedDocumentId = ref<number | null>(null);
const previewedDocument = computed(() => props.company.documents.find((doc) => doc.id === previewedDocumentId.value) ?? null);

watch(activeTab, (tab) => {
    if (tab !== 'documents') previewedDocumentId.value = null;
});

const tabs = [
    { value: 'overview', label: 'Overview', icon: RiDashboardHorizontalLine },
    { value: 'details', label: 'Details', icon: RiFileListLine },
    { value: 'documents', label: 'Documents', icon: RiFolderLine },
    { value: 'history', label: 'History', icon: RiHistoryLine },
] as const;
</script>

<template>
    <Head :title="`Profile — ${company.company_name}`" />

    <ExternalLayout :company="company" :user="user">
        <PanelLayout>
            <LeadPanel class="flex-1">
                <LeadingCard
                    :title="company.company_name"
                    description="Review and manage company details and documents."
                    variant="entity-details"
                    entity="company"
                    :more="false"
                />

                <Tabs v-model="activeTab">
                    <TabsList>
                        <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
                            <component :is="tab.icon" class="h-4 w-4" />
                            <span>{{ tab.label }}</span>
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <Overview :company="company" :latest-change-request="latest_change_request" />
                    </TabsContent>

                    <TabsContent value="details">
                        <Details
                            v-model:form="form"
                            :company="company"
                            :can-edit="canEdit"
                            @submit="submit"
                            @reset="resetForm"
                        />
                    </TabsContent>

                    <TabsContent value="documents">
                        <Documents v-model:previewed-id="previewedDocumentId" :company="company" />
                    </TabsContent>

                    <TabsContent value="history">
                        <History :requests="profile_change_requests ?? []" />
                    </TabsContent>
                </Tabs>
            </LeadPanel>

            <SidePanel v-if="previewedDocument" class="hidden lg:flex">
                <PreviewCard :title="documentLabel(previewedDocument.doc_type)" description="Document preview" @close="previewedDocumentId = null">
                    <div class="space-y-3 pt-2">
                        <PreviewCardRow label="Status">
                            <Badge :class="['gap-1.5', docStatusClass(previewedDocument)]">
                                <span :class="['h-1.5 w-1.5 rounded-full', docStatusDot(previewedDocument)]" />
                                {{ docStatusLabel(previewedDocument) }}
                            </Badge>
                        </PreviewCardRow>
                        <PreviewCardRow label="Expires">{{ formatDate(previewedDocument.expires_at) }}</PreviewCardRow>
                        <PreviewCardRow label="Updated">{{ formatDateTime(previewedDocument.updated_at) }}</PreviewCardRow>
                        <div v-if="previewedDocument.remarks" class="space-y-1">
                            <p class="text-sm font-semibold text-custom-shadow">Remarks</p>
                            <InputMessage class="mt-0 whitespace-pre-wrap" :message="previewedDocument.remarks" />
                        </div>
                    </div>
                </PreviewCard>
            </SidePanel>
        </PanelLayout>
    </ExternalLayout>
</template>
