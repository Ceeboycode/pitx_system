<script setup lang="ts">
/* ======================================================
   Shared UI
====================================================== */
import InertiaPagination from '@/components/InertiaPagination.vue';
import InputError from '@/components/InputError.vue';
import SearchInput from '@/components/SearchInput.vue';

/* shadcn-vue */
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

/* ======================================================
   Layout, Routing & Inertia
====================================================== */
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy, index, show, store, trash, update } from '@/routes/gates';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

/* ======================================================
   Icons
====================================================== */
import { Archive, ArchiveX, Download, Edit, Eye, Plus, Save, Upload } from 'lucide-vue-next';

/* ======================================================
   Vue Core
====================================================== */
import { ref } from 'vue';

/* ======================================================
   Types
====================================================== */
interface Gate {
    id: number;
    gate_name: string;
    creator: User | null;
}

/* ======================================================
   Breadcrumbs
====================================================== */
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Gates', href: index().url }];

/* ======================================================
   Props 
====================================================== */
const props = withDefaults(
    defineProps<{
        gates: any; // LengthAwarePaginator
        filters?: { search: string | null };
    }>(),
    {
        filters: () => ({ search: null }),
    },
);

/* ======================================================
   Form + Dialog State
====================================================== */
const form = useForm({
    gate_name: '',
});

const createOpen = ref(false);
const editOpen = ref(false);
const selectedGate = ref<Gate | null>(null);

/* ======================================================
   Actions
====================================================== */
const createGate = () => {
    form.submit(store(), {
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false;
            form.reset();
        },
    });
};

function openEdit(gate: Gate) {
    selectedGate.value = gate;
    form.gate_name = gate.gate_name;
    editOpen.value = true;
}

function closeEdit() {
    editOpen.value = false;
    selectedGate.value = null;
    form.reset();
}

const editGate = () => {
    if (!selectedGate.value) return;

    form.submit(update(selectedGate.value.id), {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
};

const archiveGate = (gateId: number) => {
    router.delete(destroy(gateId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Gates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-10 mt-3">
                <!-- Header -->
                <CardHeader>
                    <CardTitle>Gates</CardTitle>
                    <CardDescription>Manage your gates here.</CardDescription>

                    <CardAction>
                        <Button as-child size="sm" variant="outline" class="mr-2">
                            <Link :href="trash().url">
                                <Archive class="mr-2 h-4 w-4" />
                                View Archived
                            </Link>
                        </Button>

                        <Button class="cursor-pointer" size="sm" @click="createOpen = true">
                            <Plus class="mr-2 h-4 w-4" />
                            New Gate
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters?.search"
                                placeholder="Search gates..."
                                :only="['gates', 'filters']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex gap-2 sm:justify-end">
                            <Button class="cursor-pointer" size="sm" variant="outline">
                                <Upload class="mr-2 h-4 w-4" />
                                Import
                            </Button>

                            <Button class="cursor-pointer" size="sm" variant="outline">
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </div>
                    </div>

                    <!-- Table -->
                    <Table>
                        <TableCaption> List of gates. </TableCaption>

                        <TableHeader>
                            <TableRow>
                                <TableHead>Gate Name</TableHead>
                                <TableHead>Created By</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="gate in gates.data" :key="gate.id">
                                <TableCell class="capitalize">
                                    {{ gate.gate_name }}
                                </TableCell>

                                <TableCell>
                                    {{ gate.creator?.name ?? 'N/A' }}
                                </TableCell>

                                <TableCell class="space-x-2">
                                    <!-- View -->
                                    <Button as-child size="sm" variant="ghost">
                                        <Link :href="show(gate.id).url">
                                            <Eye class="mr-2 h-4 w-4" />
                                            View
                                        </Link>
                                    </Button>

                                    <!-- Edit -->
                                    <Button class="cursor-pointer" size="sm" variant="default" @click="openEdit(gate)">
                                        <Edit class="mr-2 h-4 w-4" />
                                        Edit
                                    </Button>

                                    <!-- Archive -->
                                    <Dialog>
                                        <DialogTrigger as-child>
                                            <Button class="cursor-pointer" size="sm" variant="archive">
                                                <ArchiveX class="mr-2 h-4 w-4" />
                                                Archive
                                            </Button>
                                        </DialogTrigger>

                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle class="flex items-center gap-2">
                                                    <ArchiveX :size="18" class="text-muted-foreground" />
                                                    Archive Gate
                                                </DialogTitle>
                                                <DialogDescription>
                                                    Are you sure you want to archive this gate? You can restore it later
                                                    from the Trash.
                                                </DialogDescription>
                                            </DialogHeader>

                                            <DialogFooter>
                                                <DialogClose as-child>
                                                    <Button class="cursor-pointer" variant="secondary" size="sm">Cancel</Button>
                                                </DialogClose>

                                                <DialogClose as-child>
                                                    <Button
                                                        class="cursor-pointer"
                                                        size="sm"
                                                        variant="archive"
                                                        @click="archiveGate(gate.id)"
                                                    >
                                                        Archive
                                                    </Button>
                                                </DialogClose>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-if="gates.data.length === 0">
                                <TableCell colspan="3" class="py-10 text-center text-muted-foreground">
                                    No gates found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <InertiaPagination
                        :links="gates.links"
                        :meta="{
                            from: gates.from,
                            to: gates.to,
                            total: gates.total,
                        }"
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>

    <!-- CREATE DIALOG -->
    <Dialog v-model:open="createOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Add New Gate</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="createGate">
                <Label>Gate Name</Label>
                <Input v-model="form.gate_name" class="mt-1 mb-2" />
                <InputError :message="form.errors.gate_name" />

                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary" size="sm">Cancel</Button>
                    </DialogClose>

                    <Button size="sm" type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        Save
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- EDIT DIALOG -->
    <Dialog v-model:open="editOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Gate</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="editGate">
                <Label>Gate Name</Label>
                <Input v-model="form.gate_name" class="mt-1 mb-2" />
                <InputError :message="form.errors.gate_name" />

                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary" size="sm" @click="closeEdit">
                            Cancel
                        </Button>
                    </DialogClose>

                    <Button size="sm" type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
