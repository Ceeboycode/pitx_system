<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';
import { index } from '@/routes/employee-users';

import { ArchiveEmployeeDialog, ResetEmployeePasswordDialog, ToggleEmployeeStatusDialog } from '@/components/external/employee';
import Details from '@/components/external/employee/edit/DetailsTab.vue';
import Dispatches from '@/components/external/employee/edit/DispatchesTab.vue';
import History from '@/components/external/employee/edit/HistoryTab.vue';
import Overview from '@/components/external/employee/edit/OverviewTab.vue';
import Vehicles from '@/components/external/employee/edit/VehiclesTab.vue';
import { LeadingCard } from '@/components/ui/_leading-card';
import { LeadPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import { PreviewCard, PreviewCardRow } from '@/components/ui/_preview-card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/_tabs';
import { Badge } from '@/components/ui/badge';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import {
    RiArchive2Line,
    RiBusLine,
    RiDashboardHorizontalLine,
    RiFileListLine,
    RiHistoryLine,
    RiRoadMapLine,
    RiShieldKeyholeLine,
    RiShutDownLine,
} from 'vue-remix-icons';

type Company = {
    id: number;
    company_name: string;
    company_code?: string | null;
    status: string;
    logo_url?: string | null;
};

type AuthUser = {
    id: number;
    name: string;
    username: string;
    email: string;
};

type RoleItem = {
    name: string;
    guard_name: string;
    type: string;
};

type EmployeeRole = {
    id: number;
    name: string;
};

type Employee = {
    id: number;
    username: string;
    name: string;
    email?: string | null;
    phone_number?: string | null;
    avatar?: string | null;
    status: string;
    created_at?: string | null;
    roles?: EmployeeRole[];
};

const props = defineProps<{
    company: Company;
    user: AuthUser;
    employee: Employee;
    roles: RoleItem[];
    selectedRole?: string | null;
    // Sent by CompanyUserController@edit: whether the viewed employee's role
    // gets each permission-gated tab (see CompanyUserController::tabAccessFor).
    tabAccess: {
        vehicles: boolean;
        dispatches: boolean;
    };
}>();

const isOwnAccount = props.user.id === props.employee.id;
const canToggleEmployee = can('external_users.toggleStatus');
const canResetEmployee = can('external_users.resetPassword');
const canArchiveEmployee = can('external_users.archive');

function humanize(value?: string | null) {
    if (!value) return '—';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function statusVariant(status?: string | null) {
    if (status === 'active') return 'success';
    if (status === 'pending') return 'warning';
    if (status === 'suspended') return 'orange';
    return 'muted';
}

function statusDotClass(status?: string | null) {
    if (status === 'active') return 'bg-emerald-500';
    if (status === 'pending') return 'bg-amber-500';
    if (status === 'suspended') return 'bg-orange-500';
    return 'bg-slate-400';
}

function toggleStatusLabel(status?: string | null) {
    if (status === 'active') return 'Inactivate';
    if (status === 'inactive') return 'Activate';
    if (status === 'pending') return 'Activate Account';
    if (status === 'suspended') return 'Set Active';
    return 'Update Status';
}

// Same convention as Users/Edit.vue: `.filter((tab) => ...)` walks the tab
// list and keeps only the entries whose callback returns true, so the
// Vehicles/Dispatches tabs simply don't exist in the array (and don't
// render) when the backend's `tabAccess` says the employee's role doesn't
// get them.
const tabs = computed(() =>
    [
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
            value: 'vehicles',
            label: 'Vehicles',
            icon: RiBusLine,
            component: Vehicles,
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
            icon: RiHistoryLine,
            component: History,
        },
    ].filter((tab) => {
        if (tab.value === 'vehicles') return props.tabAccess.vehicles;
        if (tab.value === 'dispatches') return props.tabAccess.dispatches;

        // Every other tab has no permission gate - always shown.
        return true;
    }),
);

const toggleOpen = ref(false);
const resetOpen = ref(false);
const archiveOpen = ref(false);
</script>

<template>
    <Head :title="`Employee — ${employee.name}`" />

    <ExternalLayout :company="company" :user="user">
        <PanelLayout>
            <LeadPanel>
                <LeadingCard
                    :title="employee.name"
                    description="Review and manage employee details."
                    variant="entity-details"
                    entity="employee"
                    :back="index().url"
                    :status="employee.status === 'active' || employee.status === 'inactive' ? employee.status : null"
                >
                    <DropdownMenuItem
                        class="group cursor-pointer"
                        :disabled="!canToggleEmployee || isOwnAccount"
                        @click="toggleOpen = true"
                    >
                        <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        {{ toggleStatusLabel(employee.status) }}
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        class="group cursor-pointer"
                        :disabled="!canResetEmployee || isOwnAccount"
                        @click="resetOpen = true"
                    >
                        <RiShieldKeyholeLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        Reset Password
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        class="group cursor-pointer"
                        :disabled="!canArchiveEmployee || isOwnAccount"
                        @click="archiveOpen = true"
                    >
                        <RiArchive2Line class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        Archive Employee
                    </DropdownMenuItem>
                </LeadingCard>

                <Tabs default-value="details">
                    <TabsList>
                        <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
                            <component :is="tab.icon" class="h-4 w-4" />
                            <span>{{ tab.label }}</span>
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value">
                        <component :is="tab.component" :employee="employee" :roles="roles" :selected-role="props.selectedRole" />
                    </TabsContent>
                </Tabs>
            </LeadPanel>

            <SidePanel class="hidden lg:flex">
                <PreviewCard title="Company" description="Employer details." :closable="false">
                    <div class="my-2 flex flex-col gap-2">
                        <PreviewCardRow label="Company Name">{{ company.company_name }}</PreviewCardRow>
                        <PreviewCardRow label="Company Code">
                            <span class="font-mono">{{ company.company_code || '—' }}</span>
                        </PreviewCardRow>
                        <PreviewCardRow label="Company Status">
                            <Badge :variant="statusVariant(company.status)" class="gap-1.5">
                                <span :class="['h-1.5 w-1.5 rounded-full', statusDotClass(company.status)]" />
                                {{ humanize(company.status) }}
                            </Badge>
                        </PreviewCardRow>
                    </div>
                </PreviewCard>
            </SidePanel>
        </PanelLayout>

        <ToggleEmployeeStatusDialog v-model:open="toggleOpen" :employee="employee" />
        <ResetEmployeePasswordDialog v-model:open="resetOpen" :employee="employee" />
        <ArchiveEmployeeDialog v-model:open="archiveOpen" :employee="employee" />
    </ExternalLayout>
</template>
