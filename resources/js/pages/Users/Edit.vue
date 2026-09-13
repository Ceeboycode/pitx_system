<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

// import { Badge } from '@/components/ui/badge';
import { LeadPanel } from '@/components/ui/_panels';
import { LeadingCard } from '@/components/ui/_leading-card';
import { 
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/_tabs';
import Overview from '@/components/internal/users/edit/OverviewTab.vue';
import Details from '@/components/internal/users/edit/DetailsTab.vue';
import Vehicles from '@/components/internal/users/edit/VehiclesTab.vue';
import Employees from '@/components/internal/users/edit/EmployeesTab.vue';
import Dispatches from '@/components/internal/users/edit/DispatchesTab.vue';
import IncidentReports from '@/components/internal/users/edit/IncidentReportsTab.vue';
import History from '@/components/internal/users/edit/HistoryTab.vue';
import { RiAlertLine, RiBusLine, RiDashboardHorizontalLine, RiFileListLine, RiTimeLine, RiRoadMapLine, RiGroupLine } from 'vue-remix-icons';

type Role = {
    id: number;
    name: string;
    type: 'internal' | 'external';
};

type Company = {
    id: number;
    company_name: string;
    company_code: string;
};

const props = defineProps<{
    user: {
        id: number;
        username: string | null;
        name: string;
        email: string;
        phone_number: string | null;
        type: 'internal' | 'external';
        company_id: number | null;
    };
    roles: Role[];
    companies: Company[];
    selectedRole: string | null;
    // Sent by UserController@edit: true only when the edited user's role
    // holds every permission in that group (see Role::hasAllPermissionsInGroup).
    canManageExternalUsers: boolean;
    canManageExternalDispatches: boolean;
}>();

// `computed(() => ...)` re-runs its callback whenever a value it reads
// (here, the props above) changes, and the result is what the template
// sees when it writes `tabs` - no `.value` needed there, Vue unwraps it.
// `.filter((tab) => ...)` walks the tab list and keeps only the entries
// whose callback returns true, so the Employees/Dispatches tabs simply
// don't exist in the array (and don't render) when the check fails.
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
            icon: RiTimeLine,
            component: History,
        },
    ].filter((tab) => {
        if (tab.value === 'employees') return props.canManageExternalUsers;
        if (tab.value === 'dispatches') return props.canManageExternalDispatches;

        // Every other tab has no permission gate - always shown.
        return true;
    }),
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: index().url },
    { title: 'Edit', href: '#' },
];

// function initials(name: string) {
//     const parts = name.trim().split(/\s+/).filter(Boolean).slice(0, 2);
//     return parts.map((part) => part.charAt(0).toUpperCase()).join('') || 'U';
// }

// function typeBadgeClass(type: string | null) {
//     switch (type) {
//         case 'internal':
//             return 'border-blue-200 bg-blue-100 text-blue-700';
//         case 'external':
//             return 'border-amber-200 bg-amber-100 text-amber-700';
//         default:
//             return 'border-border bg-muted text-muted-foreground';
//     }
// }

</script>

<template>
    <Head title="Edit User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <LeadPanel>
            <LeadingCard
                :title="user.name"
                description="Review and manage user details."
                variant="entity-details"
                :back="index().url"
                :more="false"
            ></LeadingCard>
            <Tabs default-value="details">
                <TabsList>
                    <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
                        <component :is="tab.icon" class="h-4 w-4"/>
                        <span>{{ tab.label }}</span>
                    </TabsTrigger>
                </TabsList>
                <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value">
                    <!-- `props.selectedRole` (explicit prefix) forwards the raw role-name
                         string this page received from the backend. DetailsTab.vue has its
                         own local `selectedRole` computed (the full Role object it looks up
                         from that string) - naming both "selectedRole" is fine because they
                         live in separate components with separate scopes. -->
                    <component
                        :is="tab.component"
                        :user="user"
                        :roles="roles"
                        :companies="companies"
                        :selected-role="props.selectedRole"
                        :can-manage-external-users="canManageExternalUsers"
                        :can-manage-external-dispatches="canManageExternalDispatches"
                    />
                </TabsContent>
            </Tabs>
        </LeadPanel>
    </AppLayout>
</template>
