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
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
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

/* ── Suggestions ─────────────────────────────────────────────────── */
const gateSuggestions = Array.from({ length: 20 }, (_, i) => `Gate ${i + 1}`);
const baySuggestions  = Array.from({ length: 20 }, (_, i) => String(i + 1));

/* ── Form + Dialog state ─────────────────────────────────────────── */
const form = useForm({
    gate_name: '',
    status: 'active' as 'active' | 'inactive',
    bays: '' as number | string,
});

const createOpen      = ref(false);
const editOpen        = ref(false);
const archiveOpen     = ref(false);
const selectedGate    = ref<Gate | null>(null);

/* ── Status helpers ──────────────────────────────────────────────── */
function statusClass(status: Gate['status']): string {
    return status === 'active'
        ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
        : 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(status: Gate['status']): string {
    return status === 'active' ? 'bg-emerald-500' : 'bg-slate-400';
}

/* ── Actions ─────────────────────────────────────────────────────── */
function openEdit(gate: Gate) {
    selectedGate.value = gate;
    form.gate_name = gate.gate_name;
    form.status    = gate.status;
    form.bays      = gate.bays;
    editOpen.value = true;
}

function closeEdit() {
    editOpen.value    = false;
    selectedGate.value = null;
    form.reset();
    form.gate_name = '';
    form.status    = 'active';
    form.bays      = '';
}

function openArchive(gate: Gate) {
    selectedGate.value  = gate;
    archiveOpen.value   = true;
}

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

const editGate = () => {
    if (!selectedGate.value) return;
    form.put(update(selectedGate.value.id).url, {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
};

const archiveGate = () => {
    if (!selectedGate.value) return;
    router.delete(destroy(selectedGate.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            archiveOpen.value  = false;
            selectedGate.value = null;
        },
    });
};
</script>

<template>
    <Head title="Gates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            Gates
                        </CardTitle>
                        <CardDescription class="mt-1">
                            List of all gates in the system.
                        </CardDescription>
                    </div>

                    <CardAction class="flex items-center gap-2">
                        <Button
                            as-child
                            size="sm"
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                        >
                            <Link :href="trash().url">
                                <Archive class="mr-2 h-4 w-4" />
                                View Archive 
                            </Link>
                        </Button>

                        <Button
                            size="sm"
                            class="rounded-lg bg-blue-700 text-white hover:bg-blue-800 border-0 shadow-sm"
                            @click="createOpen = true"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            New Gate
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">

                    <!-- Search + bulk actions -->
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
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
                                <!-- Empty state -->
                                <TableRow v-if="props.gates.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="5" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                                <Archive class="h-6 w-6 text-muted-foreground/40" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-foreground">No gates found</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">Try adjusting your search or add a new gate.</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="gate in props.gates.data"
                                    :key="gate.id"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <!-- Gate Name -->
                                    <TableCell class="text-sm font-semibold capitalize">
                                        {{ gate.gate_name }}
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :class="['gap-1.5', statusClass(gate.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(gate.status)]" />
                                            {{ gate.status === 'active' ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Bays -->
                                    <TableCell class="text-sm tabular-nums text-muted-foreground">
                                        {{ gate.bays }}
                                    </TableCell>

                                    <!-- Created By -->
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ gate.creator?.name ?? '—' }}
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                                                >
                                                    <MoreHorizontal class="h-4 w-4" />
                                                    <span class="sr-only">Open actions</span>
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="w-48 rounded-xl border-slate-200 shadow-lg">
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                                    {{ gate.gate_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-blue-700 focus:bg-blue-50 focus:text-blue-700"
                                                >
                                                    <Link :href="show(gate.id).url" class="flex items-center">
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View
                                                        <ChevronRight class="ml-auto h-3.5 w-3.5 text-blue-400" />
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    class="rounded-lg text-amber-600 focus:bg-amber-50 focus:text-amber-700"
                                                    @click="openEdit(gate)"
                                                >
                                                    <Pencil class="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    class="rounded-lg text-rose-600 focus:bg-rose-50 focus:text-rose-600"
                                                    @click="openArchive(gate)"
                                                >
                                                    <Archive class="mr-2 h-4 w-4" />
                                                    Archive
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

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

        <!-- ── Create dialog ──────────────────────────────────────── -->
        <Dialog v-model:open="createOpen">
            <DialogContent class="rounded-2xl sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add New Gate</DialogTitle>
                    <DialogDescription>
                        Create a new gate with status and number of bays.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="createGate">
                    <div class="space-y-1.5">
                        <Label for="create_gate_name" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            Gate Name
                        </Label>
                        <Input
                            id="create_gate_name"
                            v-model="form.gate_name"
                            list="gate-name-suggestions"
                            placeholder="Type gate name or select Gate 1–20"
                            class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                        />
                        <datalist id="gate-name-suggestions">
                            <option v-for="opt in gateSuggestions" :key="opt" :value="opt" />
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

                    <div class="space-y-1.5">
                        <Label for="create_bays" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            Bays
                        </Label>
                        <Input
                            id="create_bays"
                            v-model="form.bays"
                            list="bay-suggestions"
                            type="number"
                            min="0"
                            placeholder="Number of bays"
                            class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                        />
                        <datalist id="bay-suggestions">
                            <option v-for="opt in baySuggestions" :key="opt" :value="opt" />
                        </datalist>
                        <InputError :message="form.errors.bays" />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                            @click="createOpen = false"
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
                            {{ form.processing ? 'Saving…' : 'Save Gate' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ── Edit dialog ────────────────────────────────────────── -->
        <Dialog v-model:open="editOpen">
            <DialogContent class="rounded-2xl sm:max-w-md">
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
                            type="button"
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
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