<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { update } from '@/routes/gates';

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Logo, LogoFallback, LogoImage } from '@/components/ui/_logo';
import { Separator } from '@/components/ui/separator';
import { CardSeparator } from '@/components/ui/_card-separator';
import Button from '@/components/ui/button/Button.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import EditableField from '@/components/ui/_field/EditableField.vue';
import { can } from '@/lib/can';

import {
  RiBuildingLine,
  RiCalendarLine,
  RiTimeLine,
  RiEditLine,
  RiImageAddLine,
  RiCloseLine,
  RiCircleFill,
} from 'vue-remix-icons';

type Gate = {
    id: number;
    gate_name: string;
    status: 'active' | 'inactive';
    bays: number;
    location: string | null;
    picture_url: string | null;
    created_at_human: string | null;
    updated_at_human: string | null;
    creator: { name: string } | null;
    updater: { name: string } | null;
};

const props = defineProps<{ gate: Gate }>();

// Drives every EditableField below, same convention as Users/Edit.vue: view-only
// roles (no `gates.update` permission) see static text instead of inputs, and
// never see the Save/Cancel actions or validation errors.
const canEdit = can('gates.update');

const pictureInputRef = ref<HTMLInputElement | null>(null);
const picturePreview = ref<string | null>(props.gate.picture_url);

// `form.isDirty` (from useForm) compares against a lodash `cloneDeep` snapshot
// of the initial data - `File` objects clone down to `{}`, so it can't reliably
// detect picture-only changes. Tracked separately and OR'd into the Save/Cancel
// `disabled` checks below.
const pictureChanged = ref(false);

const form = useForm({
    gate_name: props.gate.gate_name ?? '',
    status: props.gate.status ?? 'active',
    bays: props.gate.bays ?? '',
    location: props.gate.location ?? '',
    picture: null as File | null,
    // The backend only touches `picture_path` when a new file is uploaded, so
    // clearing the picture needs an explicit signal to actually persist.
    remove_picture: false,
});

function selectPicture(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.picture = file;
    form.remove_picture = false;
    pictureChanged.value = true;
    if (picturePreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(picturePreview.value);
    }
    picturePreview.value = file ? URL.createObjectURL(file) : props.gate.picture_url;
}

function removePicture() {
    if (picturePreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(picturePreview.value);
    }
    picturePreview.value = null;
    form.picture = null;
    form.remove_picture = true;
    pictureChanged.value = true;
    if (pictureInputRef.value) pictureInputRef.value.value = '';
}

function resetForm() {
    form.reset();
    form.clearErrors();
    pictureChanged.value = false;
    if (picturePreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(picturePreview.value);
    }
    picturePreview.value = props.gate.picture_url;
    if (pictureInputRef.value) pictureInputRef.value.value = '';
}

function submit() {
    form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(update(props.gate.id).url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.picture = null;
                form.remove_picture = false;
                form.defaults();
                pictureChanged.value = false;
            },
            onFinish: () => form.transform((data) => data),
        });
}
</script>

<template>
  <Card class="lg:col-span-2">
    <CardHeader class="flex flex-row items-start justify-between gap-4">
      <div class="flex flex-col">
        <CardTitle>Gate</CardTitle>
        <CardDescription>{{ canEdit ? 'Manage gate details.' : 'View gate details.' }}</CardDescription>
      </div>
      <div v-if="canEdit" class="flex flex-row items-center gap-2">
        <Button
          :variant="(!form.isDirty && !pictureChanged) || form.processing ? 'disabled' : 'float'"
          :disabled="(!form.isDirty && !pictureChanged) || form.processing"
          @click="resetForm"
        >
          Cancel
        </Button>

        <Button
          :variant="(!form.isDirty && !pictureChanged) || form.processing ? 'disabled' : 'float-primary'"
          size="icon-text"
          :disabled="(!form.isDirty && !pictureChanged) || form.processing"
          @click="submit"
        >
          {{ form.processing ? 'Saving...' : 'Save Changes' }}
        </Button>
      </div>
    </CardHeader>
    <CardContent class="flex flex-row gap-4">
      <div class="flex-1 max-w-1/3">
        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
          <div class="flex flex-col gap-2 justify-center items-start">
            <EditableField :editable="canEdit">
              <template #edit>
                <div
                  role="button"
                  tabindex="0"
                  :aria-label="picturePreview ? 'Change gate picture' : 'Upload gate picture'"
                  class="group w-full aspect-video relative flex shrink-0 cursor-pointer overflow-hidden rounded-md transition-colors"
                  :class="picturePreview ? '' : 'border border-dashed border-custom-primary/60 dark:border-custom-secondary/60'"
                  @click="pictureInputRef?.click()"
                  @keydown.enter.prevent="pictureInputRef?.click()"
                  @keydown.space.prevent="pictureInputRef?.click()"
                >
                  <img
                    v-if="picturePreview"
                    :src="picturePreview"
                    alt="Gate picture preview"
                    class="h-full w-full object-cover transition duration-200 group-hover:brightness-80"
                  />
                  <div v-if="picturePreview" class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-200 group-hover:opacity-100 group-focus-visible:opacity-100">
                    <RiImageAddLine class="h-7 w-7 text-custom-bg-light dark:text-custom-shadow" />
                  </div>
                  <div v-else class="flex h-full w-full items-center justify-center bg-custom-primary/10 dark:bg-custom-secondary/10 text-sm text-custom-shadow">
                    <RiImageAddLine class="h-6 w-6 text-custom-shadow/80" />
                  </div>
                  <Button
                    v-if="picturePreview"
                    type="button"
                    aria-label="Remove gate picture"
                    class="absolute right-1 top-1 z-10 flex h-6 w-6 cursor-pointer items-center rounded-full border border-custom-bg-light/50 dark:border-custom-shadow/50 text-custom-shadow transition-all duration-200 hover:border-destructive hover:bg-destructive/20 hover:text-destructive"
                    @click.stop="removePicture"
                  >
                    <RiCloseLine class="text-custom-bg-light dark:text-custom-shadow h-4 w-4" />
                  </Button>
                </div>
                <input
                  id="gate_picture"
                  ref="pictureInputRef"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  class="sr-only"
                  @change="selectPicture"
                />
              </template>
              <Logo class="rounded-md">
                <LogoImage
                  v-if="gate.picture_url"
                  :src="gate.picture_url"
                  :alt="gate.gate_name"
                />
                <LogoFallback>
                  No picture uploaded.
                </LogoFallback>
              </Logo>
            </EditableField>
            <InputError v-if="canEdit" :message="form.errors.picture" />
          </div>

          <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <div class="flex flex-row justify-between items-center gap-2 overflow-hidden">
              <div class="inline-flex gap-2 items-center">
                <RiBuildingLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
                <span>Gate ID</span>
              </div>
              <span class="tracking-widest bg-custom-bg dark:bg-custom-bg-light px-2 rounded-md font-mono mr-1 font-normal">
                #{{ gate.id }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <Separator orientation="vertical"/>

      <div class="flex-1">
        <CardSeparator title="Details" />

        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
          <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
            <div class="inline-flex shrink-0 gap-2 items-center">
              <RiBuildingLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
              <Label for="gate_name">Gate Name</Label>
            </div>
            <EditableField :editable="canEdit">
              <template #edit>
                <span class="flex min-w-0 flex-1 flex-row items-center">
                  <Input
                      id="gate_name"
                      v-model="form.gate_name"
                      placeholder="Enter gate name"
                      variant="inline-edit"
                  />
                  <RiEditLine class="shrink-0 h-4 w-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:w-4 group-hover:ml-2 group-hover:opacity-100 group-focus-within:w-4 group-focus-within:ml-2 group-focus-within:opacity-100"/>
                </span>
              </template>
              <span class="min-w-0 flex-1 truncate text-right text-sm font-medium">{{ gate.gate_name }}</span>
            </EditableField>
            <InputError v-if="canEdit" :message="form.errors.gate_name" />
          </div>

          <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
            <div class="inline-flex shrink-0 gap-2 items-center">
              <RiBuildingLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
              <Label for="gate_status">Status</Label>
            </div>
            <EditableField :editable="canEdit">
              <template #edit>
                <span class="flex flex-row items-center">
                  <Select v-model="form.status">
                    <SelectTrigger
                      id="gate_status"
                      variant="inline-edit"
                    >
                      <RiCircleFill :class="['shrink-0 h-2 w-2 size-2 mr-1', form.status === 'active' ? 'text-success' : 'text-custom-shadow']" />
                      <SelectValue placeholder="Select status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="active">
                        <RiCircleFill class="shrink-0 h-2 w-2 size-2 text-success mr-1" />
                        Active
                      </SelectItem>
                      <SelectItem value="inactive">
                        <RiCircleFill class="shrink-0 h-2 w-2 size-2 text-custom-shadow mr-1" />
                        Inactive
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </span>
              </template>
              <span class="min-w-0 flex-1 truncate text-right text-sm font-medium capitalize">{{ gate.status }}</span>
            </EditableField>
            <InputError v-if="canEdit" :message="form.errors.status" />
          </div>

          <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
            <div class="inline-flex shrink-0 gap-2 items-center">
              <RiBuildingLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
              <Label for="gate_bays">Bays</Label>
            </div>
            <EditableField :editable="canEdit">
              <template #edit>
                <span class="flex min-w-0 flex-1 flex-row items-center">
                  <Input
                      id="gate_bays"
                      v-model="form.bays"
                      inputmode="numeric"
                      placeholder="Number of bays"
                      variant="inline-edit"
                  />
                  <RiEditLine class="shrink-0 h-4 w-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:w-4 group-hover:ml-2 group-hover:opacity-100 group-focus-within:w-4 group-focus-within:ml-2 group-focus-within:opacity-100"/>
                </span>
              </template>
              <span class="min-w-0 flex-1 truncate text-right text-sm font-medium">{{ gate.bays }}</span>
            </EditableField>
            <InputError v-if="canEdit" :message="form.errors.bays" />
          </div>

          <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
            <div class="inline-flex shrink-0 gap-2 items-center">
              <RiBuildingLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
              <Label for="gate_location">Location</Label>
            </div>
            <EditableField :editable="canEdit">
              <template #edit>
                <span class="flex min-w-0 flex-1 flex-row items-center">
                  <Input
                      id="gate_location"
                      v-model="form.location"
                      placeholder="e.g. Ground Floor boarding concourse"
                      variant="inline-edit"
                  />
                  <RiEditLine class="shrink-0 h-4 w-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:w-4 group-hover:ml-2 group-hover:opacity-100 group-focus-within:w-4 group-focus-within:ml-2 group-focus-within:opacity-100"/>
                </span>
              </template>
              <span class="min-w-0 flex-1 truncate text-right text-sm font-medium">{{ gate.location || 'Location not configured' }}</span>
            </EditableField>
            <InputError v-if="canEdit" :message="form.errors.location" />
          </div>
        </div>

        <CardSeparator title="Others" />

        <div class="mt-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
          <div class="flex flex-row justify-between items-center overflow-hidden">
            <div class="inline-flex shrink-0 gap-2 items-center">
              <RiCalendarLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
              <span>Created</span>
            </div>
            <span class="min-w-0 flex-1 truncate text-right">
              {{ gate.created_at_human ?? '—' }}
              <span v-if="gate.creator?.name" class="text-custom-accent-3"> • </span>
              {{ gate.creator?.name ?? '' }}
            </span>
          </div>

          <div class="flex flex-row justify-between items-center overflow-hidden">
            <div class="inline-flex shrink-0 gap-2 items-center">
              <RiTimeLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
              <span>Updated</span>
            </div>
            <span class="min-w-0 flex-1 truncate text-right">
              {{ gate.updated_at_human ?? '—' }}
              <span v-if="gate.updater?.name" class="text-custom-accent-3"> • </span>
              {{ gate.updater?.name ?? '' }}
            </span>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
