<script setup lang="ts">
/* ======================================================
   Shared UI
====================================================== */
import InertiaPagination from '@/components/InertiaPagination.vue';
import InputError from '@/components/InputError.vue';
import SearchInput from '@/components/SearchInput.vue';

/* shadcn-vue */
import { Badge } from '@/components/ui/badge';
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
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import {
    ArchiveX,
    Download,
    Edit,
    Eye,
    Plus,
    Save,
    Upload,
} from 'lucide-vue-next';

/* ======================================================
   Vue Core
====================================================== */
import { ref } from 'vue';

/* ======================================================
   Permissions
====================================================== */
import { can } from '@/lib/can';

const canCreate    = can('gates.create');
const canUpdate    = can('gates.update');
const canDelete    = can('gates.delete');
const canViewTrash = can('gates.viewTrash');

/* ======================================================
   Types
====================================================== */
interface Gate {
    id: number;
    gate_name: string;
    status: 'active' | 'inactive';
    bays: number;
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
        gates: {
            data: Gate[];
            links: any[];
            from: number | null;
            to: number | null;
            total: number;
        };
        filters?: { search: string | null };
    }>(),
    {
        filters: () => ({ search: null }),
    },
);

/* ======================================================
   Suggested Options
====================================================== */
const gateSuggestions = Array.from({ length: 20 }, (_, i) => `Gate ${i + 1}`);
const baySuggestions  = Array.from({ length: 20 }, (_, i) => String(i + 1));

/* ======================================================
   Form + Dialog State
====================================================== */
const form = useForm({
    gate_name: '',
    status: 'active' as 'active' | 'inactive',
    bays: '' as number | string,
});

const createOpen = ref(false);
const editOpen   = ref(false);
const selectedGate = ref<Gate | null>(null);

/* ======================================================
   Helpers
====================================================== */
function badgeVariant(status: Gate['status']) {
    return status === 'active' ? 'default' : 'secondary';
}

/* ======================================================
   Actions
====================================================== */
function createGate() {
    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false;
            form.reset();
            form.gate_name = '';
            form.status    = 'active';
            form.bays      = '';
        },
    });
}

function openEdit(gate: Gate) {
    selectedGate.value = gate;
    form.gate_name     = gate.gate_name;
    form.status        = gate.status;
    form.bays          = gate.bays;
    editOpen.value     = true;
}

function closeEdit() {
    editOpen.value     = false;
    selectedGate.value = null;
    form.reset();
    form.gate_name = '';
    form.status    = 'active';
    form.bays      = '';
}

function editGate() {
    if (!selectedGate.value) return;

    form.put(update(selectedGate.value.id).url, {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
}

function archiveGate(gateId: number) {
    router.delete(destroy(gateId).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Gates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle>Gates</CardTitle>
                        <CardDescription>List of all gates in the system.</CardDescription>
                    </div>

                    <CardAction class="flex gap-2">
                        <Button v-if="canViewTrash" as-child size="sm" variant="outline">
                            <Link :href="trash().url">
                                <Eye class="mr-2 h-4 w-4" />
                                View Trash
                            </Link>
                        </Button>

                        <Button v-if="canCreate" size="sm" @click="createOpen = true">
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
                            <Button size="sm" variant="outline">
                                <Upload class="mr-2 h-4 w-4" />
                                Import
                            </Button>

                            <Button size="sm" variant="outline">
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </div>
                    </div>

                    <Table>
                        <!-- <TableCaption>List of gates.</TableCaption> -->

                        <TableHeader>
                            <TableRow>
                                <TableHead>Gate Name</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Bays</TableHead>
                                <TableHead>Created By</TableHead>
                                <TableHead class="w-[260px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="gate in props.gates.data" :key="gate.id">
                                <TableCell class="capitalize">{{ gate.gate_name }}</TableCell>

                                <TableCell>
                                    <Badge :variant="badgeVariant(gate.status)" class="capitalize">
                                        {{ gate.status }}
                                    </Badge>
                                </TableCell>

                                <TableCell>{{ gate.bays }}</TableCell>

                                <TableCell>{{ gate.creator?.name ?? 'N/A' }}</TableCell>

                                <TableCell class="space-x-2">
                                    <Button as-child size="sm" variant="ghost">
                                        <Link :href="show(gate.id).url">
                                            <Eye class="mr-2 h-4 w-4" />
                                            View
                                        </Link>
                                    </Button>

                                    <Button
                                        v-if="canUpdate"
                                        size="sm"
                                        @click="openEdit(gate)"
                                    >
                                        <Edit class="mr-2 h-4 w-4" />
                                        Edit
                                    </Button>

                                    <AlertDialog v-if="canDelete">
                                        <AlertDialogTrigger as-child>
                                            <Button size="sm" variant="destructive">
                                                <ArchiveX class="mr-2 h-4 w-4" />
                                                Archive
                                            </Button>
                                        </AlertDialogTrigger>

                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>Archive Gate</AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Are you sure you want to archive
                                                    <span class="font-medium text-foreground">
                                                        {{ gate.gate_name }}
                                                    </span>?
                                                    You can restore it later from the Trash.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>

                                            <AlertDialogFooter>
                                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                <AlertDialogAction
                                                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                    @click="archiveGate(gate.id)"
                                                >
                                                    Archive
                                                </AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="props.gates.data.length === 0">
                                <TableCell
                                    colspan="5"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No gates found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <InertiaPagination
                        :links="props.gates.links"
                        :meta="{
                            from: props.gates.from,
                            to: props.gates.to,
                            total: props.gates.total,
                        }"
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>

    <!-- CREATE DIALOG -->
    <Dialog v-model:open="createOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Add New Gate</DialogTitle>
                <DialogDescription>
                    Create a new gate with status and number of bays.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="createGate">
                <div class="space-y-2">
                    <Label for="create_gate_name">Gate Name</Label>
                    <Input
                        id="create_gate_name"
                        v-model="form.gate_name"
                        list="gate-name-suggestions"
                        placeholder="Type gate name or select Gate 1 - Gate 20"
                    />
                    <datalist id="gate-name-suggestions">
                        <option
                            v-for="option in gateSuggestions"
                            :key="option"
                            :value="option"
                        />
                    </datalist>
                    <InputError :message="form.errors.gate_name" />
                </div>

                <div class="space-y-2">
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
                    <InputError :message="form.errors.status" />
                </div>

                <div class="space-y-2">
                    <Label for="create_bays">Bays</Label>
                    <Input
                        id="create_bays"
                        v-model="form.bays"
                        list="bay-suggestions"
                        type="number"
                        min="0"
                        placeholder="Type bays or select 1 - 20"
                    />
                    <datalist id="bay-suggestions">
                        <option
                            v-for="option in baySuggestions"
                            :key="option"
                            :value="option"
                        />
                    </datalist>
                    <InputError :message="form.errors.bays" />
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary" size="sm" type="button">
                            Cancel
                        </Button>
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
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Edit Gate</DialogTitle>
                <DialogDescription>Update the gate details.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="editGate">
                <div class="space-y-2">
                    <Label for="edit_gate_name">Gate Name</Label>
                    <Input
                        id="edit_gate_name"
                        v-model="form.gate_name"
                        list="gate-name-suggestions"
                        placeholder="Type gate name or select Gate 1 - Gate 20"
                    />
                    <InputError :message="form.errors.gate_name" />
                </div>

                <div class="space-y-2">
                    <Label for="edit_status">Status</Label>
                    <Select v-model="form.status">
                        <SelectTrigger id="edit_status" class="w-full">
                            <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="space-y-2">
                    <Label for="edit_bays">Bays</Label>
                    <Input
                        id="edit_bays"
                        v-model="form.bays"
                        list="bay-suggestions"
                        type="number"
                        min="0"
                        placeholder="Type bays or select 1 - 20"
                    />
                    <InputError :message="form.errors.bays" />
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button
                            variant="secondary"
                            size="sm"
                            type="button"
                            @click="closeEdit"
                        >
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
