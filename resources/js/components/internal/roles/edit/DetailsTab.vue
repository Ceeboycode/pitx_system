<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { update } from '@/routes/roles';
import { can } from '@/lib/can';
import EditableField from '@/components/ui/_field/EditableField.vue';

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { CardSeparator } from '@/components/ui/_card-separator';
import Button from '@/components/ui/button/Button.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

// import SearchInput from '@/components/SearchInput.vue';

import {
  RiPhoneLine,
  RiEditLine,
  RiTimeLine,
  RiCalendarLine,
} from "vue-remix-icons";

type Permission = {
    id: number;
    name: string;
};


const props = defineProps<{
    role: {
        id: number;
        name: string;
        type: 'internal' | 'external';
        created_at_human?: string | null;
        updated_at_human?: string | null;
        creator?: { name: string } | null;
        updater?: { name: string } | null;
    };
    permissions: Permission[];
    rolePermissionIds: number[];
    roleTypes: string[];
}>();

const form = useForm({
    name: props.role.name ?? '',
    type: props.role.type ?? 'internal',
    permissions: (props.rolePermissionIds ?? []) as number[],
});

// Drives every EditableField below, same convention as Gates/edit/DetailsTab.vue:
// view-only users get the page (server authorizes on 'view'), but only
// 'roles.update' holders see inputs, permission checkboxes, and Save/Cancel.
const canEdit = can('roles.update');

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

</script>

<template>
  <Card class="lg:col-span-2">
    <CardHeader class="flex flex-row items-start justify-between gap-4">
      <div class="flex flex-col">
        <CardTitle>Role</CardTitle>
        <CardDescription>{{ canEdit ? 'Manage role details.' : 'View role details.' }}</CardDescription>
      </div>
      <div v-if="canEdit" class="flex flex-row items-center gap-2">
        <Button
          :variant="!form.isDirty || form.processing ? 'disabled' : 'float'"
          :disabled="!form.isDirty || form.processing"
          @click="form.reset(); form.clearErrors()"
        >
          Cancel
        </Button>

        <Button
          :variant="!form.isDirty || form.processing ? 'disabled' : 'float-primary'"
          size="icon-text"
          :disabled="!form.isDirty || form.processing"
          @click="submit"
        >
          {{ form.processing ? 'Saving...' : 'Save Changes' }}
        </Button>
      </div>
    </CardHeader>
    <CardContent class="flex flex-row gap-4">
      <div class="w-1/3">
        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">

          <!-- <CardSeparator title="Role Info" /> -->

          <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
              <div class="inline-flex gap-2 items-center">
                <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <Label for="role_name_sidebar">Name</Label>
              </div>
              <EditableField :editable="canEdit">
                <template #edit>
                  <span class="flex min-w-0 flex-1 flex-row items-center">
                    <Input
                        id="role_name_sidebar"
                        v-model="form.name"
                        placeholder="Enter role name"
                        variant="inline-edit"
                    />
                    <RiEditLine class="shrink-0 h-4 w-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:w-4 group-hover:ml-2 group-hover:opacity-100 group-focus-within:w-4 group-focus-within:ml-2 group-focus-within:opacity-100"/>
                  </span>
                </template>
                <span class="min-w-0 flex-1 truncate text-right text-sm font-medium">{{ role.name }}</span>
              </EditableField>
              <InputError v-if="canEdit" :message="form.errors.name" />
            </div>

            <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
              <div class="inline-flex gap-2 items-center">
                <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <Label for="role_type_sidebar">Type</Label>
              </div>
              <EditableField :editable="canEdit">
                <template #edit>
                  <span class="flex flex-row items-center">
                    <Select v-model="form.type">
                      <SelectTrigger
                        id="role_type_sidebar"
                        variant="inline-edit"
                      >
                        <SelectValue
                          placeholder="Select a role type..."
                        />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="type in props.roleTypes"
                          :key="type"
                          :value="type"
                          class="capitalize"
                        >
                          {{ type }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </span>
                </template>
                <span class="min-w-0 flex-1 truncate text-right text-sm font-medium capitalize">{{ role.type }}</span>
              </EditableField>
              <InputError v-if="canEdit" :message="form.errors.type" />
            </div>
          </div>

          <CardSeparator title="Permissions Info" />

          <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiCalendarLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Internal</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">
                {{ tabSelectedCount(internalPermissions) }} of {{ internalPermissions.length }} selected
              </span>
            </div>

            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiTimeLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>External</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">
                {{ tabSelectedCount(externalPermissions) }} of {{ externalPermissions.length }} selected
              </span>
            </div>
          </div>

          <CardSeparator title="Others" />

          <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiCalendarLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Created</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">
                {{ role.created_at_human ?? '—' }}
                <span class="text-custom-accent-3"> • </span>
                {{ role.creator?.name ?? '—' }}
              </span>
            </div>

            <div class="flex flex-row justify-between items-center overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiTimeLine class="shrink-0 h-4 w-4 text-custom-shadow/80 0"/>
                <span>Updated</span>
              </div>
              <span class="line-clamp-1 text-ellipsis">
                {{ role.updated_at_human ?? '—' }}
                <span class="text-custom-accent-3"> • </span>
                {{ role.updater?.name ?? '—' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <Separator orientation="vertical"/>

      <div class="flex-1">
        <CardSeparator title="Stops Info" />

        <!-- <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow">

        </div> -->

        <div class="space-y-4">
          <div v-if="canEdit" class="flex items-center justify-between">
            <label class="flex cursor-pointer items-center gap-2 rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-muted">
              <input
                type="checkbox"
                class="h-4 w-4 cursor-pointer rounded border accent-primary"
                :checked="allChecked"
                :indeterminate="someChecked"
                @change="toggleAll(($event.target as HTMLInputElement).checked)"
              />
              <!-- CODE: <CheckSquare class="h-3.5 w-3.5 text-muted-foreground" /> -->
              <span>Select all</span>
            </label>
          </div>

          <InputError v-if="canEdit" :message="form.errors.permissions" />

          
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
                      :disabled="!canEdit"
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
                      :disabled="!canEdit"
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
                        :disabled="!canEdit"
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
                        :disabled="!canEdit"
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
      </div>
    </CardContent>
  </Card>
</template>