<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckSquare, KeyRound, Layers, Loader2, Save, Shield, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

import InputError from '@/components/InputError.vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
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
import { Separator } from '@/components/ui/separator';

type Permission = { id: number; name: string };

const props = defineProps<{
    role: { id: number; name: string; type: 'internal' | 'external' };
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

function moduleLabel(moduleKey: string) {
    return moduleKey.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function actionLabel(permissionName: string) {
    const action = permissionName.split('.').slice(1).join('.') || permissionName;
    return action.replace(/([a-z])([A-Z])/g, '$1 $2').replace(/\b\w/g, (c) => c.toUpperCase());
}

const grouped = computed(() => {
    const map: Record<string, Permission[]> = {};
    for (const p of props.permissions) {
        const moduleKey = p.name.split('.')[0] || 'other';
        if (!map[moduleKey]) map[moduleKey] = [];
        map[moduleKey].push(p);
    }
    return Object.entries(map)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([key, list]) => [key, list.sort((x, y) => x.name.localeCompare(y.name))] as const);
});

const allIds = computed(() => props.permissions.map((p) => p.id));
const allChecked = computed(() => allIds.value.length > 0 && allIds.value.every((id) => form.permissions.includes(id)));

function toggleAll(checked: boolean) {
    form.permissions = checked ? [...allIds.value] : [];
}

function togglePermission(permissionId: number, checked: boolean) {
    if (checked) { if (!form.permissions.includes(permissionId)) form.permissions.push(permissionId); return; }
    form.permissions = form.permissions.filter((id) => id !== permissionId);
}

function moduleIds(moduleKey: string) {
    const list = grouped.value.find(([key]) => key === moduleKey)?.[1] ?? [];
    return list.map((p) => p.id);
}

function moduleChecked(moduleKey: string) {
    const ids = moduleIds(moduleKey);
    return ids.length > 0 && ids.every((id) => form.permissions.includes(id));
}

function toggleModule(moduleKey: string, checked: boolean) {
    const ids = moduleIds(moduleKey);
    if (checked) { const set = new Set(form.permissions); for (const id of ids) set.add(id); form.permissions = Array.from(set); return; }
    form.permissions = form.permissions.filter((id) => !ids.includes(id));
}

function submit() {
    form.put(update({ role: props.role.id }).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Edit Role" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">

            <!-- ── Page header ─────────────────────────────────────── -->
            <div class="mx-5 flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                        <ShieldCheck class="h-3.5 w-3.5" />
                        Roles · Edit
                    </div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl font-bold tracking-tight">Edit Role</h1>
                    </div>
                    <p class="text-sm text-muted-foreground">Update role name, type, and permissions.</p>
                </div>

                <Button
                    as-child
                    size="sm"
                    variant="outline"
                    class="shrink-0 rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                >
                    <Link :href="index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Roles
                    </Link>
                </Button>
            </div>

            <div class="mx-5 grid gap-6 xl:grid-cols-[1fr_280px]">

                <!-- ── Main form card ──────────────────────────────── -->
                <div class="space-y-4">

                    <!-- Role Details -->
                    <Card>
                        <CardHeader class="border-b border-slate-100 pb-4">
                            <CardTitle class="flex items-center gap-2 text-base">
                                <KeyRound class="h-4 w-4 text-blue-700" />
                                Role Details
                            </CardTitle>
                            <CardDescription>Set the role name and type.</CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-5 pt-5">
                            <div class="grid gap-5 sm:grid-cols-2">

                                <!-- Role Name -->
                                <div class="space-y-1.5">
                                    <Label for="name" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                        Role Name
                                    </Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="e.g. Manager"
                                        class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <!-- Role Type -->
                                <div class="space-y-1.5">
                                    <Label for="type" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                        Role Type
                                    </Label>
                                    <Select v-model="form.type">
                                        <SelectTrigger id="type" class="w-full rounded-lg border-slate-200 focus:ring-blue-500">
                                            <SelectValue placeholder="Select role type" />
                                        </SelectTrigger>
                                        <SelectContent class="rounded-xl">
                                            <SelectItem
                                                v-for="t in props.roleTypes"
                                                :key="t"
                                                :value="t"
                                                class="rounded-lg capitalize"
                                            >
                                                {{ t }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors.type" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Permissions -->
                    <Card>
                        <CardHeader class="border-b border-slate-100 pb-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <CardTitle class="flex items-center gap-2 text-base">
                                        <CheckSquare class="h-4 w-4 text-blue-700" />
                                        Permissions
                                    </CardTitle>
                                    <CardDescription>Toggle individual permissions or select by module.</CardDescription>
                                </div>

                                <!-- Select all -->
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
                                    <input
                                        id="select-all"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 accent-blue-700"
                                        :checked="allChecked"
                                        @change="toggleAll(($event.target as HTMLInputElement).checked)"
                                    />
                                    <span class="text-xs font-semibold text-slate-600">Select all</span>
                                </label>
                            </div>
                            <InputError :message="form.errors.permissions" />
                        </CardHeader>

                        <CardContent class="space-y-3 pt-5">
                            <div
                                v-for="[moduleKey, perms] in grouped"
                                :key="moduleKey"
                                class="overflow-hidden rounded-xl border border-slate-200"
                            >
                                <!-- Module header -->
                                <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-100">
                                            <Layers class="h-3.5 w-3.5 text-blue-700" />
                                        </div>
                                        <span class="text-sm font-semibold">{{ moduleLabel(moduleKey) }}</span>
                                        <Badge class="bg-slate-100 text-slate-500 border-0 text-[11px]">{{ perms.length }}</Badge>
                                    </div>

                                    <label class="flex cursor-pointer items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground">
                                        <input
                                            type="checkbox"
                                            class="h-3.5 w-3.5 rounded border-slate-300 accent-blue-700"
                                            :checked="moduleChecked(moduleKey)"
                                            @change="toggleModule(moduleKey, ($event.target as HTMLInputElement).checked)"
                                        />
                                        Select all
                                    </label>
                                </div>

                                <!-- Permission grid -->
                                <div class="grid gap-2 p-3 sm:grid-cols-2">
                                    <label
                                        v-for="p in perms"
                                        :key="p.id"
                                        class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-slate-200 p-2.5 transition-colors hover:bg-blue-50/50 hover:border-blue-200"
                                        :class="form.permissions.includes(p.id) ? 'bg-blue-50 border-blue-200' : ''"
                                    >
                                        <input
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 rounded border-slate-300 accent-blue-700"
                                            :checked="form.permissions.includes(p.id)"
                                            @change="togglePermission(p.id, ($event.target as HTMLInputElement).checked)"
                                        />
                                        <div class="min-w-0">
                                            <span class="block text-sm font-medium leading-tight">{{ actionLabel(p.name) }}</span>
                                            <span class="block truncate font-mono text-[11px] text-muted-foreground">{{ p.name }}</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- ── Sidebar ─────────────────────────────────────── -->
                <div class="space-y-4">

                    <!-- Summary -->
                    <Card>
                        <CardHeader class="border-b border-slate-100 pb-4">
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Shield class="h-4 w-4 text-blue-700" />
                                Role Summary
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="divide-y divide-slate-100 p-0">
                            <div class="px-5 py-3">
                                <p class="mb-0.5 text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Name</p>
                                <p class="text-sm font-semibold">{{ form.name || '—' }}</p>
                            </div>
                            <div class="px-5 py-3">
                                <p class="mb-0.5 text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Type</p>
                                <Badge :class="form.type === 'internal' ? 'bg-blue-100 text-blue-700 border-blue-200' : 'bg-violet-100 text-violet-700 border-violet-200'" class="capitalize">
                                    {{ form.type || '—' }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">Permissions</p>
                                <span class="rounded bg-muted px-2 py-0.5 font-mono text-xs font-semibold text-foreground">
                                    {{ form.permissions.length }} / {{ props.permissions.length }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Tip -->
                    <div class="rounded-xl border border-slate-200 bg-amber-50/60 p-4">
                        <p class="text-xs font-semibold uppercase tracking-widest text-amber-700">Tip</p>
                        <p class="mt-1 text-xs text-slate-600">
                            Permissions are grouped by module. Use "Select all" on a module to grant all actions at once.
                        </p>
                    </div>
                </div>

            </div>

            <!-- ── Save bar (bottom sticky) ────────────────────────── -->
            <div class="sticky bottom-0 z-10 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
                <div class="mx-5 flex items-center justify-between gap-4 py-3">
                    <p class="text-xs text-muted-foreground">
                        <span class="font-semibold text-foreground tabular-nums">{{ form.permissions.length }}</span>
                        of {{ props.permissions.length }} permissions selected
                    </p>
                    <div class="flex gap-2">
                        <Button
                            as-child
                            variant="outline"
                            size="sm"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                        >
                            <Link :href="index().url">Cancel</Link>
                        </Button>
                        <Button
                            size="sm"
                            :disabled="form.processing"
                            class="rounded-lg bg-blue-700 text-white hover:bg-blue-800 border-0 font-semibold disabled:opacity-60"
                            @click="submit"
                        >
                            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            <Save v-else class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Saving…' : 'Save Changes' }}
                        </Button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>