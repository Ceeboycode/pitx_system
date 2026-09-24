<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { can } from '@/lib/can';
import EditableField from '@/components/ui/_field/EditableField.vue';

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
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { InputMessage } from '@/components/ui/_input-message';

import {
  RiBusLine,
  RiCalendarLine,
  RiCloseLine,
  RiEditLine,
  RiFileTextLine,
  RiImageAddLine,
  RiTimeLine,
} from 'vue-remix-icons';

import { update } from '@/actions/App/Http/Controllers/VehicleTypeController';

type VehicleType = {
    id: number;
    type_name: string;
    description: string | null;
    picture_url: string | null;
    is_active: boolean;
    created_at_human: string | null;
    updated_at_human: string | null;
    creator: { name: string } | null;
    updater: { name: string } | null;
};

const props = defineProps<{ vehicleType: VehicleType }>();

// Drives every EditableField below, same convention as Gates/edit/DetailsTab.vue:
// view-only users get the page (server authorizes on 'view'), but only
// 'vehicle_types.update' holders see inputs and Save/Cancel.
const canEdit = can('vehicle_types.update');

const pictureInputRef = ref<HTMLInputElement | null>(null);
const picturePreview = ref<string | null>(props.vehicleType.picture_url);

// Same reasoning as Gates/edit/DetailsTab.vue: `form.isDirty` can't reliably
// detect picture-only changes (a `File` clones down to `{}`), so it's
// tracked separately and OR'd into the Save/Cancel `disabled` checks below.
const pictureChanged = ref(false);

const form = useForm({
    type_name: props.vehicleType.type_name ?? '',
    description: props.vehicleType.description ?? '',
    picture: null as File | null,
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
    picturePreview.value = file ? URL.createObjectURL(file) : props.vehicleType.picture_url;
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
    picturePreview.value = props.vehicleType.picture_url;
    if (pictureInputRef.value) pictureInputRef.value.value = '';
}

function submit() {
    form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(update(props.vehicleType.id).url, {
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
        <CardTitle>Vehicle Type</CardTitle>
        <CardDescription>{{ canEdit ? 'Manage vehicle type details.' : 'View vehicle type details.' }}</CardDescription>
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
                  :aria-label="picturePreview ? 'Change vehicle type picture' : 'Upload vehicle type picture'"
                  class="group w-full aspect-video relative flex shrink-0 cursor-pointer overflow-hidden rounded-md transition-colors"
                  :class="picturePreview ? '' : 'border border-dashed border-custom-primary/60 dark:border-custom-secondary/60'"
                  @click="pictureInputRef?.click()"
                  @keydown.enter.prevent="pictureInputRef?.click()"
                  @keydown.space.prevent="pictureInputRef?.click()"
                >
                  <img
                    v-if="picturePreview"
                    :src="picturePreview"
                    alt="Vehicle type picture preview"
                    class="h-full w-full object-cover transition duration-200 group-hover:brightness-80"
                  />
                  <div v-if="picturePreview" class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-200 group-hover:opacity-100 group-focus-visible:opacity-100">
                    <RiImageAddLine class="h-7 w-7 shrink-0 text-custom-bg-light dark:text-custom-shadow" />
                  </div>
                  <div v-else class="flex h-full w-full items-center justify-center bg-custom-primary/10 dark:bg-custom-secondary/10 text-sm text-custom-shadow">
                    <RiImageAddLine class="h-6 w-6 shrink-0 text-custom-shadow/80" />
                  </div>
                  <Button
                    v-if="picturePreview"
                    type="button"
                    aria-label="Remove vehicle type picture"
                    class="absolute right-1 top-1 z-10 flex h-6 w-6 cursor-pointer items-center rounded-full border border-custom-bg-light/50 dark:border-custom-shadow/50 text-custom-shadow transition-all duration-200 hover:border-destructive hover:bg-destructive/20 hover:text-destructive"
                    @click.stop="removePicture"
                  >
                    <RiCloseLine class="text-custom-bg-light dark:text-custom-shadow h-4 w-4 shrink-0" />
                  </Button>
                </div>
                <input
                  id="vehicle_type_picture"
                  ref="pictureInputRef"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  class="sr-only"
                  @change="selectPicture"
                />
              </template>
              <Logo class="rounded-md">
                <LogoImage
                  v-if="vehicleType.picture_url"
                  :src="vehicleType.picture_url"
                  :alt="vehicleType.type_name"
                />
                <LogoFallback>
                  No picture uploaded.
                </LogoFallback>
              </Logo>
            </EditableField>
            <InputMessage variant="destructive" v-if="canEdit" :message="form.errors.picture" />
          </div>
        </div>

        <!-- <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow"> -->
          <!-- <div class="flex flex-row justify-between items-start gap-2 overflow-hidden group">
            <div class="inline-flex shrink-0 gap-2 items-center pt-0.5">
              <RiFileTextLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
            </div>
            <EditableField :editable="canEdit">
              <template #edit>
                <div class="relative min-w-0 flex-1">
                  <Textarea
                      id="description"
                      v-model="form.description"
                      placeholder="Describe this vehicle type..."
                      variant="inline-edit"
                  />
                </div>
              </template>
              <span class="min-w-0 flex-1 whitespace-pre-line text-right text-sm font-semibold">{{ vehicleType.description || 'No description provided.' }}</span>
            </EditableField>
            <InputMessage variant="destructive" v-if="canEdit" :message="form.errors.description" />
          </div> -->
        <!-- </div> -->
      </div>

      <Separator orientation="vertical"/>

      <div class="flex-1">
        <CardSeparator title="Details" />

        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
          <div class="flex flex-row justify-between items-center gap-2 overflow-hidden group">
            <div class="inline-flex shrink-0 gap-2 items-center">
              <RiBusLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
              <Label for="type_name">Name</Label>
            </div>
            <EditableField :editable="canEdit">
              <template #edit>
                <span class="flex min-w-0 flex-1 flex-row items-center">
                  <Input
                      id="type_name"
                      v-model="form.type_name"
                      placeholder="Enter vehicle type name"
                      variant="inline-edit"
                  />
                  <RiEditLine class="shrink-0 h-4 w-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:w-4 group-hover:ml-2 group-hover:opacity-100 group-focus-within:w-4 group-focus-within:ml-2 group-focus-within:opacity-100"/>
                </span>
              </template>
              <span class="min-w-0 flex-1 truncate text-right text-sm font-semibold capitalize">{{ vehicleType.type_name }}</span>
            </EditableField>
            <InputMessage variant="destructive" v-if="canEdit" :message="form.errors.type_name" />
          </div>

          <div class="flex flex-row justify-between items-start gap-2 overflow-hidden group">
            <div class="inline-flex shrink-0 gap-2 items-center pt-0.5">
              <RiFileTextLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
              <Label for="description">Description</Label>
            </div>
            <EditableField :editable="canEdit">
              <template #edit>
                <div class="relative min-w-0 flex-1">
                  <Textarea
                      id="description"
                      v-model="form.description"
                      placeholder="Describe this vehicle type..."
                      variant="inline-edit"
                      class="text-end no-scrollbar min-h-fit"
                  />
                  <!-- <RiEditLine class="pointer-events-none absolute top-1 right-1 h-4 w-4 shrink-0 text-custom-shadow/80 opacity-0 transition-opacity duration-200 ease-out group-hover:opacity-100 group-focus-within:opacity-100"/> -->
                </div>
              </template>
              <span class="min-w-0 flex-1 whitespace-pre-line text-right text-sm font-semibold">{{ vehicleType.description || 'No description provided.' }}</span>
            </EditableField>
            <InputMessage variant="destructive" v-if="canEdit" :message="form.errors.description" />
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
              {{ vehicleType.created_at_human ?? '—' }}
              <span class="text-custom-accent-3"> • </span>
              {{ vehicleType.creator?.name ?? '' }}
            </span>
          </div>

          <div class="flex flex-row justify-between items-center overflow-hidden">
            <div class="inline-flex shrink-0 gap-2 items-center">
              <RiTimeLine class="shrink-0 h-4 w-4 text-custom-shadow/80"/>
              <span>Updated</span>
            </div>
            <span class="min-w-0 flex-1 truncate text-right">
              {{ vehicleType.updated_at_human ?? '—' }}
              <span class="text-custom-accent-3"> • </span>
              {{ vehicleType.updater?.name ?? '' }}
            </span>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
