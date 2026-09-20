<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { update } from '@/routes/roles';
import { can } from '@/lib/can';
import { permissionsOfType, type Permission } from '@/lib/permissions';
import EditableField from '@/components/ui/_field/EditableField.vue';
import RolePermissionsPicker from '@/components/internal/roles/RolePermissionsPicker.vue';

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

// import SearchInput from '@/components/SearchInput.vue';

import {
  RiPhoneLine,
  RiEditLine,
  RiShieldCheckLine,
  RiTimeLine,
  RiCalendarLine,
} from "vue-remix-icons";


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

// A role holds permissions of its own type only: external roles the "external_" ones, internal roles the rest.
function permissionIdsOfType(type: string): number[] {
    return permissionsOfType(props.permissions, type).map((p) => p.id);
}

const form = useForm({
    name: props.role.name ?? '',
    type: props.role.type ?? 'internal',
    permissions: (props.rolePermissionIds ?? []).filter((id) =>
        permissionIdsOfType(props.role.type ?? 'internal').includes(id),
    ) as number[],
});

// Switching the type discards whatever was selected for the other type (Cancel brings it back).
watch(
    () => form.type,
    (type) => {
        const allowed = permissionIdsOfType(type);
        form.permissions = form.permissions.filter((id) => allowed.includes(id));
    },
);

// Drives every EditableField below, same convention as Gates/edit/DetailsTab.vue:
// view-only users get the page (server authorizes on 'view'), but only
// 'roles.update' holders see inputs, permission checkboxes, and Save/Cancel.
const canEdit = can('roles.update');

// Only the permissions of the selected role type are listed and counted.
const typePermissions = computed(() => permissionsOfType(props.permissions, form.type));

const selectedCount = computed(
    () => typePermissions.value.filter((p) => form.permissions.includes(p.id)).length,
);

// Laravel reports a bad item as "permissions.N" rather than "permissions", so show whichever came back.
const permissionsError = computed(
    () =>
        form.errors.permissions ??
        Object.entries(form.errors).find(([key]) => key.startsWith('permissions.'))?.[1],
);

function submit() {
    form.put(update({ role: props.role.id }).url, { preserveScroll: true });
}

</script>

<template>
  <Card class="lg:col-span-2">
    <CardHeader class="flex flex-row items-start justify-between gap-4">
      <div class="flex flex-col">
        <CardTitle>Details</CardTitle>
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
        <CardSeparator title="Role Info" />

        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
          <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
            <div class="inline-flex gap-2 items-center">
              <RiPhoneLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
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
                      class="capitalize"
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
                      class="capitalize"
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

          <div class="flex flex-row justify-between items-center overflow-hidden">
            <div class="inline-flex gap-2 items-center">
              <RiShieldCheckLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
              <span>Permissions</span>
            </div>
            <span class="line-clamp-1 text-ellipsis">
              {{ selectedCount }} of {{ typePermissions.length }} selected
            </span>
          </div>
        </div>

        <CardSeparator title="Others" />

        <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
          <div class="flex flex-row justify-between items-center overflow-hidden">
            <div class="inline-flex gap-2 items-center">
              <RiCalendarLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
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
              <RiTimeLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
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

      <Separator orientation="vertical"/>

      <div class="flex-1">
        <CardSeparator title="Permissions" />

        <RolePermissionsPicker
          v-model="form.permissions"
          :permissions="permissions"
          :type="form.type"
          :disabled="!canEdit"
          :error="permissionsError"
        />
      </div>
    </CardContent>
  </Card>
</template>
