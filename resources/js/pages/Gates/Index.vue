<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import InputError from '@/components/InputError.vue';
import SearchInput from '@/components/SearchInput.vue';

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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import AppLayout from '@/layouts/AppLayout.vue';
import { destroy, index, show, store, trash, update } from '@/routes/gates';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

import {
    Archive,
    ArrowLeft,
    ChevronRight,
    Download,
    Eye,
    Loader2,
    MoreHorizontal,
    Pencil,
    Plus,
    Save,
    Upload,
} from 'lucide-vue-next';

import { ref } from 'vue';

/* ======================================================
   Permissions
====================================================== */
import { can } from '@/lib/can';

const canCreate    = can('gates.create');
const canUpdate    = can('gates.update');
const canDelete    = can('gates.delete');
const canViewTrash = can('gates.viewTrash');

/* ── Types ──────────────────────────────────────────────────────── */
interface Gate {
    id: number;
    gate_name: string;
    status: 'active' | 'inactive';
    bays: number;
    creator: User | null;
}

/* ── Breadcrumbs ─────────────────────────────────────────────────── */
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Gates', href: index().url }];

/* ── Props ───────────────────────────────────────────────────────── */
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
    { filters: () => ({ search: null }) },
);

/* ======================================================
   Suggested Options
====================================================== */
const gateSuggestions = Array.from({ length: 20 }, (_, index) => `Gate ${index + 1}`);
const baySuggestions = Array.from({ length: 20 }, (_, index) => String(index + 1));

/* ── Form + Dialog state ─────────────────────────────────────────── */
const form = useForm({
    gate_name: '',
    status: 'active' as 'active' | 'inactive',
    bays: '' as number | string,
});

const createOpen = ref(false);
const editOpen = ref(false);
const selectedGate = ref<Gate | null>(null);

/* ── Status helpers ──────────────────────────────────────────────── */
function statusClass(status: Gate['status']): string {
    return status === 'active'
        ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
        : 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(status: Gate['status']): string {
    return status === 'active' ? 'bg-emerald-500' : 'bg-slate-400';
}

/* ======================================================
   Actions
====================================================== */
const createGate = () => {
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
};

function openEdit(gate: Gate) {
    selectedGate.value = gate;
    form.gate_name = gate.gate_name;
    form.status = gate.status;
    form.bays = gate.bays;
    editOpen.value = true;
}

function closeEdit() {
    editOpen.value = false;
    selectedGate.value = null;
    form.reset();
    form.gate_name = '';
    form.status = 'active';
    form.bays = '';
}

function editGate() {
    if (!selectedGate.value) return;
    form.put(update(selectedGate.value.id).url, {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
}

const archiveGate = (gateId: number) => {
    router.delete(destroy(gateId).url, {
        preserveScroll: true,
        onSuccess: () => {
            archiveOpen.value  = false;
            selectedGate.value = null;
        },
    });
}
</script>

<template>
    <Head title="Gates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <CardTitle>Gates</CardTitle>
                        <CardDescription>
                            List of all gates in the system.
                        </CardDescription>
                    </div>

                    <CardAction class="flex gap-2">
                        <Button as-child size="sm" variant="outline">
                            <Link :href="trash().url">
                                <Archive class="mr-2 h-4 w-4" />
                                View Archive
                            </Link>
                        </Button>

                        <Button size="sm" @click="createOpen = true">
                            <Plus class="mr-2 h-4 w-4" />
                            New Gate
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters?.search"
                                placeholder="Search gates…"
                                :only="['gates', 'filters']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex gap-2 sm:justify-end">
                            <Button
                                size="sm"
                                variant="outline"
                                class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                            >
                                <Upload class="mr-2 h-4 w-4" />
                                Import
                            </Button>

                            <Button
                                size="sm"
                                variant="outline"
                                class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                            >
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Gate Name</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Status</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Bays</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Created By</TableHead>
                                    <TableHead class="text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="gate in props.gates.data"
                                :key="gate.id"
                            >
                                <TableCell class="capitalize">
                                    {{ gate.gate_name }}
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        :variant="badgeVariant(gate.status)"
                                        class="capitalize"
                                    >
                                        {{ gate.status }}
                                    </Badge>
                                </TableCell>

                                    <!-- Bays -->
                                    <TableCell class="text-sm tabular-nums text-muted-foreground">
                                        {{ gate.bays }}
                                    </TableCell>

                                <TableCell>
                                    {{ gate.creator?.name ?? 'N/A' }}
                                </TableCell>

                                <TableCell class="space-x-2">
                                    <Button as-child size="sm" variant="ghost">
                                        <Link :href="show(gate.id).url">
                                            <Eye class="mr-2 h-4 w-4" />
                                            View
                                        </Link>
                                    </Button>

                                    <Button size="sm" @click="openEdit(gate)">
                                        <Edit class="mr-2 h-4 w-4" />
                                        Edit
                                    </Button>

                                    <Dialog>
                                        <DialogTrigger as-child>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                            >
                                                <ArchiveX class="mr-2 h-4 w-4" />
                                                Archive
                                            </Button>
                                        </DialogTrigger>

                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Archive Gate
                                                </DialogTitle>
                                                <DialogDescription>
                                                    Are you sure you want to
                                                    archive this gate? You can
                                                    restore it later from the
                                                    Trash.
                                                </DialogDescription>
                                            </DialogHeader>

                                            <DialogFooter>
                                                <DialogClose as-child>
                                                    <Button
                                                        variant="secondary"
                                                        size="sm"
                                                    >
                                                        Cancel
                                                    </Button>
                                                </DialogClose>

                                                <DialogClose as-child>
                                                    <Button
                                                        size="sm"
                                                        variant="destructive"
                                                        @click="
                                                            archiveGate(gate.id)
                                                        "
                                                    >
                                                        Archive
                                                    </Button>
                                                </DialogClose>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
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

    <!-- CREATE -->
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
                            v-for="gateOption in gateSuggestions"
                            :key="gateOption"
                            :value="gateOption"
                        />
                    </datalist>
                    <InputError :message="form.errors.gate_name" />
                </div>

                    <div class="space-y-1.5">
                        <Label for="create_status" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            Status
                        </Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="create_status" class="w-full rounded-lg border-slate-200 focus:ring-blue-500">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="active" class="rounded-lg">Active</SelectItem>
                                <SelectItem value="inactive" class="rounded-lg">Inactive</SelectItem>
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
                            v-for="bayOption in baySuggestions"
                            :key="bayOption"
                            :value="bayOption"
                        />
                    </datalist>
                    <InputError :message="form.errors.bays" />
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="secondary" size="sm">
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-blue-700 text-white hover:bg-blue-800 border-0 font-semibold disabled:opacity-60"
                        >
                            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            <Save v-else class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Saving…' : 'Save Gate' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

    <!-- EDIT -->
    <Dialog v-model:open="editOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Edit Gate</DialogTitle>
                <DialogDescription>
                    Update the gate details.
                </DialogDescription>
            </DialogHeader>

                <form class="space-y-4" @submit.prevent="editGate">
                    <div class="space-y-1.5">
                        <Label for="edit_gate_name" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            Gate Name
                        </Label>
                        <Input
                            id="edit_gate_name"
                            v-model="form.gate_name"
                            list="gate-name-suggestions"
                            placeholder="Type gate name or select Gate 1–20"
                            class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                        />
                        <InputError :message="form.errors.gate_name" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit_status" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            Status
                        </Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="edit_status" class="w-full rounded-lg border-slate-200 focus:ring-blue-500">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="active" class="rounded-lg">Active</SelectItem>
                                <SelectItem value="inactive" class="rounded-lg">Inactive</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit_bays" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            Bays
                        </Label>
                        <Input
                            id="edit_bays"
                            v-model="form.bays"
                            list="bay-suggestions"
                            type="number"
                            min="0"
                            placeholder="Number of bays"
                            class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                        />
                        <InputError :message="form.errors.bays" />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="closeEdit"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-blue-700 text-white hover:bg-blue-800 border-0 font-semibold disabled:opacity-60"
                        >
                            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            <Save v-else class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Saving…' : 'Save Changes' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ── Archive confirm dialog ─────────────────────────────── -->
        <AlertDialog v-model:open="archiveOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Archive Gate</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to archive
                        <span class="font-semibold text-foreground">{{ selectedGate?.gate_name ?? 'this gate' }}</span>?
                        You can restore it later from the Trash.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg" @click="selectedGate = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="rounded-lg bg-rose-600 text-white hover:bg-rose-700 border-0"
                        @click="archiveGate"
                    >
                        Archive
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </AppLayout>
</template>
