<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

import { computed, ref } from 'vue';

import {
    RiFileListLine,
    RiGroupLine,
} from 'vue-remix-icons';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/_tabs';
import { LeadPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import { LeadingCard } from '@/components/ui/_leading-card';
import Details from '@/components/internal/roles/edit/DetailsTab.vue';
import Users from '@/components/internal/roles/edit/UsersTab.vue';


type Permission = {
    id: number;
    name: string;
};


const props = defineProps<{
    role: {
        id: number;
        name: string;
        type: 'internal' | 'external';
    };
    permissions: Permission[];
    rolePermissionIds: number[];
    roleTypes: string[];
}>();


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: index().url },
    { title: 'Edit', href: '#' },
];


const form = useForm({
    name: props.role.name ?? '',
    type: props.role.type ?? 'internal',
    permissions: (props.rolePermissionIds ?? []) as number[],
});


const collapsedModules = ref<Record<string, boolean>>({});

function toggleCollapse(moduleKey: string) {
    collapsedModules.value[moduleKey] = !collapsedModules.value[moduleKey];
}

function isCollapsed(moduleKey: string) {
    return collapsedModules.value[moduleKey] ?? false;
}


function moduleLabel(moduleKey: string) {
    return moduleKey
        .replace(/^external_/, '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

function actionLabel(permissionName: string) {
    const action = permissionName.split('.').slice(1).join('.') || permissionName;
    return action
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}


const internalPermissions = computed(() =>
    props.permissions.filter((p) => !p.name.startsWith('external_')),
);

const externalPermissions = computed(() =>
    props.permissions.filter((p) => p.name.startsWith('external_')),
);


function groupPermissions(list: Permission[]) {
    const map: Record<string, Permission[]> = {};

    for (const p of list) {
        const moduleKey = p.name.split('.')[0] || 'other';
        if (!map[moduleKey]) map[moduleKey] = [];
        map[moduleKey].push(p);
    }

    return Object.entries(map)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(
            ([key, perms]) =>
                [key, perms.sort((x, y) => x.name.localeCompare(y.name))] as const,
        );
}

const groupedInternal = computed(() => groupPermissions(internalPermissions.value));
const groupedExternal = computed(() => groupPermissions(externalPermissions.value));


const allIds = computed(() => props.permissions.map((p) => p.id));

const allChecked = computed(
    () =>
        allIds.value.length > 0 &&
        allIds.value.every((id) => form.permissions.includes(id)),
);

const someChecked = computed(
    () => form.permissions.length > 0 && !allChecked.value,
);

function toggleAll(checked: boolean) {
    form.permissions = checked ? [...allIds.value] : [];
}


function togglePermission(permissionId: number, checked: boolean) {
    if (checked) {
        if (!form.permissions.includes(permissionId))
            form.permissions.push(permissionId);
        return;
    }
    form.permissions = form.permissions.filter((id) => id !== permissionId);
}


function moduleIds(moduleKey: string) {
    const all = groupPermissions(props.permissions);
    const list = all.find(([key]) => key === moduleKey)?.[1] ?? [];
    return list.map((p) => p.id);
}

function moduleChecked(moduleKey: string) {
    const ids = moduleIds(moduleKey);
    return ids.length > 0 && ids.every((id) => form.permissions.includes(id));
}

function moduleSomeChecked(moduleKey: string) {
    const ids = moduleIds(moduleKey);
    return (
        ids.some((id) => form.permissions.includes(id)) &&
        !moduleChecked(moduleKey)
    );
}

function moduleSelectedCount(moduleKey: string) {
    const ids = moduleIds(moduleKey);
    return ids.filter((id) => form.permissions.includes(id)).length;
}

function toggleModule(moduleKey: string, checked: boolean) {
    const ids = moduleIds(moduleKey);

    if (checked) {
        const set = new Set(form.permissions);
        for (const id of ids) set.add(id);
        form.permissions = Array.from(set);
        return;
    }

    form.permissions = form.permissions.filter((id) => !ids.includes(id));
}


function tabSelectedCount(list: Permission[]) {
    return list.filter((p) => form.permissions.includes(p.id)).length;
}


function submit() {
    form.put(update({ role: props.role.id }).url, { preserveScroll: true });
}

const tabs = [
    {
        value: 'details',
        label: 'Details',
        icon: RiFileListLine,
        component: Details,
    },
    {
        value: 'users',
        label: 'Users',
        icon: RiGroupLine,
        component: Users,
    },
] as const;
</script>

<template>
    <Head title="Edit Role" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <LeadPanel>
                <LeadingCard
                    :title="role.name"
                    description="Update the role name, type, and permissions."
                    variant="entity-details"
                    :back="index().url"
                    :more="false"
                >
                    <!-- <DropdownMenuItem
                        class="group cursor-pointer"
                        :disabled="!canArchiveRoute"
                        @click="archiveOpen = true"
                    >
                        <RiArchive2Line class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        Archive
                    </DropdownMenuItem> -->
                </LeadingCard>
                <Tabs default-value="details">
                    <TabsList>
                        <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
                            <component :is="tab.icon" class="h-4 w-4"/>
                            <span>{{ tab.label }}</span>
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value">
                        <component
                            :is="tab.component"
                            :role="role"
                            :permissions="permissions"
                            :role-permission-ids="rolePermissionIds"
                            :role-types="roleTypes"
                        />
                    </TabsContent>
                </Tabs>
            </LeadPanel>
            
            <SidePanel>

            </SidePanel>
        </PanelLayout>
    </AppLayout>
</template>
