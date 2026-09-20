<script setup lang="ts">
import { AppDialog } from '@/components/ui/_app-dialog';
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { store } from '@/routes/gates';

import { InputMessage } from '@/components/ui/_input-message';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { RiCloseLine, RiImageAddLine, RiLoaderLine } from 'vue-remix-icons';

const open = defineModel<boolean>('open');

const gateSuggestions = Array.from({ length: 20 }, (_, i) => `Gate ${i + 1}`);
const baySuggestions = Array.from({ length: 20 }, (_, i) => String(i + 1));

const gateSuggestionsOpen = ref(false);
const baySuggestionsOpen = ref(false);
const pictureInputRef = ref<HTMLInputElement | null>(null);
const picturePreview = ref<string | null>(null);

const form = useForm({
    gate_name: '',
    status: 'active' as 'active' | 'inactive',
    bays: '' as number | string,
    location: '',
    picture: null as File | null,
});

const filteredGateSuggestions = computed(() => {
    const query = form.gate_name.trim().toLowerCase();
    return gateSuggestions.filter((suggestion) =>
        suggestion.toLowerCase().includes(query),
    );
});

const filteredBaySuggestions = computed(() => {
    const query = String(form.bays).trim();
    return baySuggestions.filter((suggestion) => suggestion.includes(query));
});

function selectPicture(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.picture = file;
    if (picturePreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(picturePreview.value);
    }
    picturePreview.value = file ? URL.createObjectURL(file) : null;
}

function removePicture() {
    if (picturePreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(picturePreview.value);
    }
    picturePreview.value = null;
    form.picture = null;
    if (pictureInputRef.value) pictureInputRef.value.value = '';
}

function resetForm() {
    if (picturePreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(picturePreview.value);
    }
    picturePreview.value = null;
    form.reset();
    form.clearErrors();
}

watch(open, (isOpen) => {
    if (!isOpen) resetForm();
});

function submit() {
    form.post(store().url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            resetForm();
        },
    });
}
</script>

<template>
    <AppDialog
        v-model:open="open"
        title="Add New Gate"
        size="lg"
        form
        @submit="submit"
    >
        <div class="flex flex-col gap-y-2">
            <div class="relative space-y-1">
                <Label for="create_gate_name">Gate Name</Label>
                <Input
                    id="create_gate_name"
                    v-model="form.gate_name"
                    placeholder="Select or type a gate name"
                    autocomplete="off"
                    @focus="gateSuggestionsOpen = true"
                    @blur="gateSuggestionsOpen = false"
                />
                <div
                    v-if="gateSuggestionsOpen && filteredGateSuggestions.length"
                    class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-custom-bg-dark bg-custom-bg-light p-1 text-custom-shadow shadow-md [scrollbar-width:none] [&::-webkit-scrollbar]:hidden dark:border-custom-bg-light dark:bg-custom-bg dark:shadow-none dark:inset-shadow-sm dark:inset-shadow-white/5"
                >
                    <button
                        v-for="opt in filteredGateSuggestions"
                        :key="opt"
                        type="button"
                        class="relative flex w-full cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-custom-bg dark:hover:bg-custom-bg-light"
                        @mousedown.prevent="form.gate_name = opt; gateSuggestionsOpen = false"
                    >
                        {{ opt }}
                    </button>
                </div>
                <InputMessage variant="destructive" :message="form.errors.gate_name" />
            </div>
            <div class="space-y-1">
                <Label for="create_status">Status</Label>
                <Select v-model="form.status">
                    <SelectTrigger id="create_status" class="w-full">
                        <SelectValue placeholder="Select status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="inactive">Inactive</SelectItem>
                    </SelectContent>
                </Select>
                <InputMessage variant="destructive" :message="form.errors.status" />
            </div>
            <div class="relative space-y-1">
                <Label for="create_bays">Bays</Label>
                <Input
                    id="create_bays"
                    v-model="form.bays"
                    inputmode="numeric"
                    placeholder="Number of bays"
                    autocomplete="off"
                    @focus="baySuggestionsOpen = true"
                    @blur="baySuggestionsOpen = false"
                />
                <div
                    v-if="baySuggestionsOpen && filteredBaySuggestions.length"
                    class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-custom-bg-dark bg-custom-bg-light p-1 text-custom-shadow shadow-md [scrollbar-width:none] [&::-webkit-scrollbar]:hidden dark:border-custom-bg-light dark:bg-custom-bg dark:shadow-none dark:inset-shadow-sm dark:inset-shadow-white/5"
                >
                    <button
                        v-for="opt in filteredBaySuggestions"
                        :key="opt"
                        type="button"
                        class="relative flex w-full cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-custom-bg dark:hover:bg-custom-bg-light"
                        @mousedown.prevent="form.bays = opt; baySuggestionsOpen = false"
                    >
                        {{ opt }}
                    </button>
                </div>
                <InputMessage variant="destructive" :message="form.errors.bays" />
            </div>
            <div class="space-y-1">
                <Label for="create_location">Location</Label>
                <Input id="create_location" v-model="form.location" placeholder="e.g. Ground Floor boarding concourse" />
                <InputMessage variant="destructive" :message="form.errors.location" />
            </div>
            <div class="space-y-1">
                <Label for="create_picture">Gate Picture</Label>
                <div class="flex items-center gap-3 rounded-md border border-dashed border-custom-bg-dark p-3 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5">
                    <div role="button" tabindex="0" :aria-label="picturePreview ? 'Change gate picture' : 'Upload gate picture'" class="group relative h-24 w-24 shrink-0 cursor-pointer overflow-hidden rounded-md border transition-colors" :class="picturePreview ? 'border-none' : 'border-dashed border-custom-bg-dark dark:border-custom-bg-light'" @click="pictureInputRef?.click()" @keydown.enter.prevent="pictureInputRef?.click()" @keydown.space.prevent="pictureInputRef?.click()">
                        <img v-if="picturePreview" :src="picturePreview" alt="Gate picture preview" class="h-full w-full object-cover transition duration-200 group-hover:brightness-30" />
                        <div v-if="picturePreview" class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-200 group-hover:opacity-100 group-focus-visible:opacity-100"><RiImageAddLine class="h-7 w-7 shrink-0 text-custom-shadow" /></div>
                        <div v-else class="flex h-full w-full items-center justify-center"><RiImageAddLine class="h-6 w-6 shrink-0 text-custom-shadow/80" /></div>
                        <Button v-if="picturePreview" type="button" aria-label="Remove gate picture" class="absolute right-1 top-1 z-10 flex h-6 w-6 cursor-pointer items-center rounded-full border border-custom-shadow/50 text-custom-shadow transition-all duration-200 hover:border-destructive hover:bg-destructive/20 hover:text-destructive" @click.stop="removePicture"><RiCloseLine class="h-4 w-4 shrink-0" /></Button>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-custom-shadow/80"><span class="font-semibold">File format: </span>.jpg, .png or .webp<br /><span class="font-semibold">Max. file size: </span>2 MB<br /><span class="font-semibold">Recommended: </span>landscape image</p>
                        <input id="create_picture" ref="pictureInputRef" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="selectPicture" />
                    </div>
                </div>
                <InputMessage variant="destructive" :message="form.errors.picture" />
            </div>
        </div>

        <template #footer>
            <Button variant="float" type="button" @click="open = false">Cancel</Button>
            <Button type="submit" variant="float-primary" :disabled="form.processing">
                <RiLoaderLine v-if="form.processing" class="h-4 w-4 shrink-0 animate-spin" />
                {{ form.processing ? 'Adding...' : 'Add Gate' }}
            </Button>
        </template>
    </AppDialog>
</template>
