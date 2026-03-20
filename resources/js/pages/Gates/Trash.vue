<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';

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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import AppLayout from '@/layouts/AppLayout.vue';
import { forceDelete, index, restore } from '@/routes/gates';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import {
    Archive,
    ArrowLeft,
    MoreHorizontal,
    RotateCcw,
    Trash2,
} from 'lucide-vue-next';

import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

/* ── Types ──────────────────────────────────────────────────────── */
interface Gate {
    id: number;
    gate_name: string;
    deleted_at_human: string | null;
}

interface PaginatedGates {
    data: Gate[];
    links: any[];
    from: number | null;
    to: number | null;
    total: number;
}

/* ── Props ───────────────────────────────────────────────────────── */
const props = defineProps<{ gates: PaginatedGates }>();

/* ── Breadcrumbs ─────────────────────────────────────────────────── */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gates', href: index().url },
    { title: 'Trash', href: '#' },
];

/* ── Dialog state ────────────────────────────────────────────────── */
const restoreOpen    = ref(false);
const deleteOpen     = ref(false);
const selectedGate   = ref<Gate | null>(null);
const confirmDelete  = ref('');

const canForceDelete = computed(() => confirmDelete.value.trim() === 'delete');

function openRestoreDialog(gate: Gate) {
    selectedGate.value = gate;
    restoreOpen.value  = true;
}

function closeRestoreDialog() {
    restoreOpen.value  = false;
    selectedGate.value = null;
}

function openDeleteDialog(gate: Gate) {
    selectedGate.value  = gate;
    confirmDelete.value = '';
    deleteOpen.value    = true;
}

function closeDeleteDialog() {
    deleteOpen.value    = false;
    selectedGate.value  = null;
    confirmDelete.value = '';
}

/* ── Actions ─────────────────────────────────────────────────────── */
function restoreGate() {
    if (!selectedGate.value) return;
    router.post(restore(selectedGate.value.id).url, {}, {
        preserveScroll: true,
        onSuccess: () => closeRestoreDialog(),
        onError:   () => toast.error('Failed to restore gate.'),
    });
}

function forceDeleteGate() {
    if (!selectedGate.value || !canForceDelete.value) return;
    router.delete(forceDelete(selectedGate.value.id).url, {
        preserveScroll: true,
        onSuccess: () => { toast.success('Gate deleted permanently.'); closeDeleteDialog(); },
        onError:   () => toast.error('Failed to delete gate permanently.'),
    });
}
</script>

<template>
    <Head title="Trash — Gates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-5">
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <Archive class="h-5 w-5 text-muted-foreground" />
                            Trashed Gates
                        </CardTitle>
                        <CardDescription class="mt-1">
                            Gates that have been moved to trash. Restore or permanently delete them.
                        </CardDescription>
                    </div>

                    <CardAction>
                        <Button
                            as-child
                            size="sm"
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                        >
                            <Link :href="index().url">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back to Gates
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Gate Name</TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Archived At</TableHead>
                                    <TableHead class="text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <!-- Empty state -->
                                <TableRow v-if="props.gates.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="3" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                                <Archive class="h-6 w-6 text-muted-foreground/40" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-foreground">No trashed gates</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">Nothing has been moved to trash yet.</p>
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

                                    <!-- Archived At -->
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ gate.deleted_at_human ?? '—' }}
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

                                            <DropdownMenuContent align="end" class="w-52 rounded-xl border-slate-200 shadow-lg">
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                                    {{ gate.gate_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    class="rounded-lg text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700"
                                                    @click="openRestoreDialog(gate)"
                                                >
                                                    <RotateCcw class="mr-2 h-4 w-4" />
                                                    Restore Gate
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    class="rounded-lg text-rose-600 focus:bg-rose-50 focus:text-rose-600"
                                                    @click="openDeleteDialog(gate)"
                                                >
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    Delete Permanently
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

        <!-- ── Restore dialog ─────────────────────────────────────── -->
        <AlertDialog v-model:open="restoreOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Restore Gate</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to restore
                        <span class="font-semibold text-foreground">{{ selectedGate?.gate_name ?? 'this gate' }}</span>?
                        It will be moved back to the active gates list.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg" @click="closeRestoreDialog">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 border-0"
                        @click="restoreGate"
                    >
                        <RotateCcw class="mr-2 h-4 w-4" />
                        Restore
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- ── Force delete dialog ────────────────────────────────── -->
        <Dialog v-model:open="deleteOpen">
            <DialogContent class="rounded-2xl sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete Gate Permanently</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. Type
                        <code class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs font-semibold text-rose-600">delete</code>
                        to permanently remove
                        <span class="font-semibold text-foreground">{{ selectedGate?.gate_name ?? 'this gate' }}</span>.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-1.5">
                    <Label for="confirm_delete" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                        Confirm by typing "delete"
                    </Label>
                    <Input
                        id="confirm_delete"
                        v-model="confirmDelete"
                        placeholder="delete"
                        class="rounded-lg border-slate-200 focus-visible:ring-rose-500"
                    />
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        variant="outline"
                        class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                        @click="closeDeleteDialog"
                    >
                        Cancel
                    </Button>
                    <Button
                        :disabled="!canForceDelete"
                        class="rounded-lg bg-rose-600 text-white hover:bg-rose-700 border-0 font-semibold disabled:opacity-50"
                        @click="forceDeleteGate"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete Permanently
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>