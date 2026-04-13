<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckSquare,
    ChevronDown,
    ChevronRight,
    KeyRound,
    Save,
    Shield,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

/* ======================================================
   Types
====================================================== */
type Permission = {
    id: number;
    name: string;
};

/* ======================================================
   Props
====================================================== */
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

/* ======================================================
   Breadcrumbs
====================================================== */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: index().url },
    { title: 'Edit', href: '#' },
];

/* ======================================================
   Form
====================================================== */
const form = useForm({
    name: props.role.name ?? '',
    type: props.role.type ?? 'internal',
    permissions: (props.rolePermissionIds ?? []) as number[],
});

/* ======================================================
   Collapsed modules state
====================================================== */
const collapsedModules = ref<Record<string, boolean>>({});

function toggleCollapse(moduleKey: string) {
    collapsedModules.value[moduleKey] = !collapsedModules.value[moduleKey];
}

function isCollapsed(moduleKey: string) {
    return collapsedModules.value[moduleKey] ?? false;
}

/* ======================================================
   Label helpers
====================================================== */
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

/* ======================================================
   Permission split — external_ prefix = external tab
====================================================== */
const internalPermissions = computed(() =>
    props.permissions.filter((p) => !p.name.startsWith('external_')),
);

const externalPermissions = computed(() =>
    props.permissions.filter((p) => p.name.startsWith('external_')),
);

/* ======================================================
   Grouping
====================================================== */
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

/* ======================================================
   Select all
====================================================== */
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

/* ======================================================
   Per-permission toggle
====================================================== */
function togglePermission(permissionId: number, checked: boolean) {
    if (checked) {
        if (!form.permissions.includes(permissionId))
            form.permissions.push(permissionId);
        return;
    }
    form.permissions = form.permissions.filter((id) => id !== permissionId);
}

/* ======================================================
   Per-module toggle
====================================================== */
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

/* ======================================================
   Tab selected counts
====================================================== */
function tabSelectedCount(list: Permission[]) {
    return list.filter((p) => form.permissions.includes(p.id)).length;
}

/* ======================================================
   Submit
====================================================== */
function submit() {
    form.put(update({ role: props.role.id }).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Edit Role" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="">
                <CardHeader class="py-0">
                    <div class="flex items-center gap-4">
                        <div
                            class="relative h-32 w-32 shrink-0 overflow-hidden rounded-lg border-2 bg-primary shadow-sm flex items-center justify-center"
                        >
                            <Shield class="h-10 w-10 text-white" />
                        </div>

                        <div class="gap-2 w-full">
                            <div class="flex flex-row gap-2 pb-2 w-full items-center">
                                <h1 class="text-2xl leading-tight font-bold tracking-tight">
                                    {{ role.name }}
                                </h1>
                                <div class="ml-2 flex flex-1 items-center">
                                    <hr class="h-px w-full border border-rose-500" />
                                    <div class="border-7 border-rose-500 rounded-xs">
                                        <div class="border-3 border-white rounded-xs"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge
                                        :class="[
                                            'border capitalize',
                                            role.type === 'internal'
                                                ? 'border-blue-200 bg-blue-100 text-blue-700'
                                                : 'border-amber-200 bg-amber-100 text-amber-700',
                                        ]"
                                    >
                                        {{ role.type }}
                                    </Badge>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <Button
                                        as-child
                                        variant="outline"
                                        class="rounded-lg bg-card border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer"
                                    >
                                        <Link :href="index().url">
                                            <ArrowLeft class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="space-y-8 pt-6 border-t border-slate-100">
                    <!-- ── Role details ───────────────────────────── -->
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name" class="flex items-center gap-1.5">
                                <!-- <KeyRound class="h-3.5 w-3.5 text-muted-foreground" /> -->
                                Role name
                            </Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g. Manager"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="type" class="flex items-center gap-1.5">
                                <!-- <Users class="h-3.5 w-3.5 text-muted-foreground" /> -->
                                Role type
                            </Label>
                            <Select v-model="form.type">
                                <SelectTrigger id="type" class="w-full">
                                    <SelectValue placeholder="Select role type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="t in props.roleTypes"
                                        :key="t"
                                        :value="t"
                                    >
                                        {{ t.charAt(0).toUpperCase() + t.slice(1) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.type" />
                        </div>
                    </div>

                    <!-- ── Permissions ────────────────────────────── -->
                    <div class="space-y-4">
                        <!-- Header row -->
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold">Permissions</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ form.permissions.length }} of
                                    {{ props.permissions.length }} selected
                                </p>
                            </div>

                            <label class="flex cursor-pointer items-center gap-2 rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-muted">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 cursor-pointer rounded border accent-primary"
                                    :checked="allChecked"
                                    :indeterminate="someChecked"
                                    @change="toggleAll(($event.target as HTMLInputElement).checked)"
                                />
                                <!-- <CheckSquare class="h-3.5 w-3.5 text-muted-foreground" /> -->
                                <span>Select all</span>
                            </label>
                        </div>

                        <InputError :message="form.errors.permissions" />

                        <!-- Tabs -->
                        <Tabs default-value="internal" class="w-full">
                            <TabsList class="mb-4 w-full">
                                <TabsTrigger
                                    value="internal"
                                    class="flex flex-1 cursor-pointer items-center gap-2"
                                >
                                    Internal
                                    <Badge
                                        v-if="tabSelectedCount(internalPermissions) > 0"
                                        variant="secondary"
                                        class="ml-1 h-5 px-1.5 text-xs"
                                    >
                                        {{ tabSelectedCount(internalPermissions) }}
                                    </Badge>
                                </TabsTrigger>

                                <TabsTrigger
                                    value="external"
                                    class="flex flex-1 cursor-pointer items-center gap-2"
                                >
                                    External
                                    <Badge
                                        v-if="tabSelectedCount(externalPermissions) > 0"
                                        variant="secondary"
                                        class="ml-1 h-5 px-1.5 text-xs"
                                    >
                                        {{ tabSelectedCount(externalPermissions) }}
                                    </Badge>
                                </TabsTrigger>
                            </TabsList>

                            <!-- ── Internal tab ───────────────────── -->
                            <TabsContent value="internal" class="mt-0 space-y-3">
                                <p
                                    v-if="groupedInternal.length === 0"
                                    class="py-6 text-center text-sm text-muted-foreground"
                                >
                                    No internal permissions found.
                                </p>

                                <div
                                    v-for="[moduleKey, perms] in groupedInternal"
                                    :key="moduleKey"
                                    class="overflow-hidden rounded-lg border"
                                >
                                    <!-- Module header -->
                                    <button
                                        type="button"
                                        class="flex w-full cursor-pointer items-center justify-between gap-3 bg-muted/40 px-4 py-3 transition-colors hover:bg-muted/70"
                                        @click="toggleCollapse(moduleKey)"
                                    >
                                        <div class="flex items-center gap-3">
                                            <input
                                                type="checkbox"
                                                class="h-4 w-4 cursor-pointer rounded border accent-primary"
                                                :checked="moduleChecked(moduleKey)"
                                                :indeterminate="moduleSomeChecked(moduleKey)"
                                                @click.stop
                                                @change="toggleModule(moduleKey, ($event.target as HTMLInputElement).checked)"
                                            />
                                            <span class="text-sm font-semibold">
                                                {{ moduleLabel(moduleKey) }}
                                            </span>
                                            <Badge
                                                variant="outline"
                                                class="h-5 px-1.5 text-xs"
                                            >
                                                {{ moduleSelectedCount(moduleKey) }}/{{ perms.length }}
                                            </Badge>
                                        </div>

                                        <ChevronDown
                                            v-if="!isCollapsed(moduleKey)"
                                            class="h-4 w-4 shrink-0 text-muted-foreground"
                                        />
                                        <ChevronRight
                                            v-else
                                            class="h-4 w-4 shrink-0 text-muted-foreground"
                                        />
                                    </button>

                                    <!-- Permission grid -->
                                    <div
                                        v-if="!isCollapsed(moduleKey)"
                                        class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-3"
                                    >
                                        <label
                                            v-for="p in perms"
                                            :key="p.id"
                                            class="flex cursor-pointer items-start gap-2.5 rounded-md border p-3 transition-colors hover:bg-muted/40"
                                            :class="
                                                form.permissions.includes(p.id)
                                                    ? 'border-primary/30 bg-primary/5'
                                                    : ''
                                            "
                                        >
                                            <input
                                                type="checkbox"
                                                class="mt-0.5 h-4 w-4 cursor-pointer rounded border accent-primary"
                                                :checked="form.permissions.includes(p.id)"
                                                @change="togglePermission(p.id, ($event.target as HTMLInputElement).checked)"
                                            />
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium leading-none">
                                                    {{ actionLabel(p.name) }}
                                                </p>
                                                <p class="mt-1 truncate text-xs text-muted-foreground">
                                                    {{ p.name }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </TabsContent>

                            <!-- ── External tab ───────────────────── -->
                            <TabsContent value="external" class="mt-0 space-y-3">
                                <p
                                    v-if="groupedExternal.length === 0"
                                    class="py-6 text-center text-sm text-muted-foreground"
                                >
                                    No external permissions found.
                                </p>

                                <div
                                    v-for="[moduleKey, perms] in groupedExternal"
                                    :key="moduleKey"
                                    class="overflow-hidden rounded-lg border"
                                >
                                    <button
                                        type="button"
                                        class="flex w-full cursor-pointer items-center justify-between gap-3 bg-muted/40 px-4 py-3 transition-colors hover:bg-muted/70"
                                        @click="toggleCollapse(moduleKey)"
                                    >
                                        <div class="flex items-center gap-3">
                                            <input
                                                type="checkbox"
                                                class="h-4 w-4 cursor-pointer rounded border accent-primary"
                                                :checked="moduleChecked(moduleKey)"
                                                :indeterminate="moduleSomeChecked(moduleKey)"
                                                @click.stop
                                                @change="toggleModule(moduleKey, ($event.target as HTMLInputElement).checked)"
                                            />
                                            <span class="text-sm font-semibold">
                                                {{ moduleLabel(moduleKey) }}
                                            </span>
                                            <Badge
                                                variant="outline"
                                                class="h-5 px-1.5 text-xs"
                                            >
                                                {{ moduleSelectedCount(moduleKey) }}/{{ perms.length }}
                                            </Badge>
                                        </div>

                                        <ChevronDown
                                            v-if="!isCollapsed(moduleKey)"
                                            class="h-4 w-4 shrink-0 text-muted-foreground"
                                        />
                                        <ChevronRight
                                            v-else
                                            class="h-4 w-4 shrink-0 text-muted-foreground"
                                        />
                                    </button>

                                    <div
                                        v-if="!isCollapsed(moduleKey)"
                                        class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-3"
                                    >
                                        <label
                                            v-for="p in perms"
                                            :key="p.id"
                                            class="flex cursor-pointer items-start gap-2.5 rounded-md border p-3 transition-colors hover:bg-muted/40"
                                            :class="
                                                form.permissions.includes(p.id)
                                                    ? 'border-primary/30 bg-primary/5'
                                                    : ''
                                            "
                                        >
                                            <input
                                                type="checkbox"
                                                class="mt-0.5 h-4 w-4 cursor-pointer rounded border accent-primary"
                                                :checked="form.permissions.includes(p.id)"
                                                @change="togglePermission(p.id, ($event.target as HTMLInputElement).checked)"
                                            />
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium leading-none">
                                                    {{ actionLabel(p.name) }}
                                                </p>
                                                <p class="mt-1 truncate text-xs text-muted-foreground">
                                                    {{ p.name }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </TabsContent>
                        </Tabs>
                    </div>
                </CardContent>

                <CardFooter class="flex flex-wrap justify-end gap-2 border-t border-slate-100">
                    <Button variant="outline" as-child class="cursor-pointer">
                        <Link :href="index().url">Cancel</Link>
                    </Button>

                    <Button :disabled="form.processing" @click="submit" variant="outline" class="cursor-pointer bg-primary text-primary-foreground hover:bg-primary/90 rounded-lg hover:text-primary-foreground">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
