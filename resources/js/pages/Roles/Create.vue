<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index, store } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckSquare, KeyRound, Layers, Save, Shield } from 'lucide-vue-next';
import { computed } from 'vue';

import InputError from '@/components/InputError.vue';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
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

type Permission = {
    id: number;
    name: string; // e.g. "vehicle_type.forceDelete"
};

const props = defineProps<{
    permissions: Permission[];
    roleTypes: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: index().url },
    { title: 'Create', href: create().url },
];

const form = useForm({
    name: '',
    type: 'internal',
    permissions: [] as number[],
});

function moduleLabel(moduleKey: string) {
    return moduleKey
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

function actionLabel(permissionName: string) {
    const action =
        permissionName.split('.').slice(1).join('.') || permissionName;
    return action
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

const grouped = computed(() => {
    const map: Record<string, Permission[]> = {};

    for (const p of props.permissions) {
        const moduleKey = p.name.split('.')[0] || 'other';
        if (!map[moduleKey]) map[moduleKey] = [];
        map[moduleKey].push(p);
    }

    const sortedEntries = Object.entries(map)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(
            ([key, list]) =>
                [
                    key,
                    list.sort((x, y) => x.name.localeCompare(y.name)),
                ] as const,
        );

    return sortedEntries;
});

const allIds = computed(() => props.permissions.map((p) => p.id));

const allChecked = computed(() => {
    return (
        allIds.value.length > 0 &&
        allIds.value.every((id) => form.permissions.includes(id))
    );
});

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
    const list = grouped.value.find(([key]) => key === moduleKey)?.[1] ?? [];
    return list.map((p) => p.id);
}

function moduleChecked(moduleKey: string) {
    const ids = moduleIds(moduleKey);
    return ids.length > 0 && ids.every((id) => form.permissions.includes(id));
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

function submit() {
    form.post(store().url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Create Role" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-5">
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Shield class="h-5 w-5" />
                                Create Role
                            </CardTitle>
                            <CardDescription>
                                Enter role name and select permissions.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>

                <Separator />

                <CardContent class="space-y-6">
                    <!-- Role name -->
                    <div class="space-y-2">
                        <Label for="name" class="flex items-center gap-2">
                            <KeyRound class="h-4 w-4" />
                            Role name
                        </Label>

                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="e.g. Manager"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Role type -->
                    <div class="space-y-2">
                        <Label for="type" class="flex items-center gap-2">
                            <Shield class="h-4 w-4" />
                            Role type
                        </Label>

                        <Select v-model="form.type">
                            <SelectTrigger id="type">
                                <SelectValue placeholder="Select role type" />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem
                                    v-for="t in props.roleTypes"
                                    :key="t"
                                    :value="t"
                                >
                                    {{ t }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <InputError :message="form.errors.type" />
                    </div>

                    <!-- Global Permissions -->
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <input
                                id="select-all"
                                type="checkbox"
                                class="h-4 w-4 rounded border"
                                :checked="allChecked"
                                @change="
                                    toggleAll(
                                        ($event.target as HTMLInputElement)
                                            .checked,
                                    )
                                "
                            />
                            <Label
                                for="select-all"
                                class="flex items-center gap-2 font-normal"
                            >
                                <CheckSquare class="h-4 w-4" />
                                Select all permissions
                            </Label>
                        </div>

                        <InputError :message="form.errors.permissions" />

                        <!-- Grouped Modules -->
                        <div class="space-y-4">
                            <div
                                v-for="[moduleKey, perms] in grouped"
                                :key="moduleKey"
                                class="rounded-lg border"
                            >
                                <!-- Module header -->
                                <div
                                    class="flex items-center justify-between gap-3 border-b px-4 py-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <Layers class="h-4 w-4" />
                                        <span class="text-sm font-semibold">{{
                                            moduleLabel(moduleKey)
                                        }}</span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >({{ perms.length }})</span
                                        >
                                    </div>

                                    <label
                                        class="flex cursor-pointer items-center gap-2 text-sm"
                                    >
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 rounded border"
                                            :checked="moduleChecked(moduleKey)"
                                            @change="
                                                toggleModule(
                                                    moduleKey,
                                                    (
                                                        $event.target as HTMLInputElement
                                                    ).checked,
                                                )
                                            "
                                        />
                                        <span class="text-muted-foreground"
                                            >Select all</span
                                        >
                                    </label>
                                </div>

                                <!-- Module permissions -->
                                <div class="grid gap-2 p-4 sm:grid-cols-2">
                                    <label
                                        v-for="p in perms"
                                        :key="p.id"
                                        class="flex cursor-pointer items-center gap-2 rounded-md border p-2 hover:bg-muted/50"
                                    >
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 rounded border"
                                            :checked="
                                                form.permissions.includes(p.id)
                                            "
                                            @change="
                                                togglePermission(
                                                    p.id,
                                                    (
                                                        $event.target as HTMLInputElement
                                                    ).checked,
                                                )
                                            "
                                        />
                                        <div class="flex flex-col">
                                            <span class="text-sm">{{
                                                actionLabel(p.name)
                                            }}</span>
                                            <span
                                                class="text-xs text-muted-foreground"
                                                >{{ p.name }}</span
                                            >
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>

                <Separator />

                <CardFooter class="flex flex-wrap justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="index().url">Cancel</Link>
                    </Button>

                    <Button :disabled="form.processing" @click="submit">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
