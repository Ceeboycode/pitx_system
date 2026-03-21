<script setup lang="ts">
/* ======================================================
   Shared UI
====================================================== */
import InertiaPagination from '@/components/InertiaPagination.vue';
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
} from '@/components/ui/dialog';
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
import { ArchiveRestore, ArrowLeft, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

/* ======================================================
   Toaster
====================================================== */
import { toast } from 'vue-sonner';

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

const props = defineProps<{
    gates: PaginatedGates;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gates', href: index().url },
    { title: 'Trash', href: '#' },
];

const restoreOpen = ref(false);
const deleteOpen = ref(false);
const selectedGate = ref<Gate | null>(null);
const confirmDelete = ref('');

const canConfirmForceDelete = computed(() => confirmText.value.trim() === 'delete');

/* ======================================================
   Dialog Helpers
====================================================== */
function openRestoreDialog(gate: Gate) {
    selectedGate.value = gate;
    restoreOpen.value  = true;
}

function closeRestoreDialog() {
    restoreOpen.value  = false;
    selectedGate.value = null;
}

function openDeleteDialog(gate: Gate) {
    selectedGate.value = gate;
    confirmDelete.value = '';
    deleteOpen.value = true;
}

function closeDeleteDialog() {
    deleteOpen.value = false;
    selectedGate.value = null;
    confirmDelete.value = '';
}

function restoreGate() {
    if (!selectedGate.value) return;

    router.post(
        restore(selectedGate.value.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                closeRestoreDialog();
            },
            onError: () => {
                toast.error('Failed to restore gate.');
            },
        },
    );
}

function forceDeleteGate() {
    if (!selectedGate.value || !canForceDelete.value) return;

    router.delete(forceDelete(selectedGate.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Gate deleted permanently.');
            closeDeleteDialog();
        },
        onError: () => {
            toast.error('Failed to delete gate permanently.');
        },
    });
}
</script>

<template>
    <Head title="Trash — Gates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-10 mt-3">
                <CardHeader
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
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
                    <Table>
                        <!-- <TableCaption>List of trashed gates.</TableCaption> -->

                        <TableHeader>
                            <TableRow>
                                <TableHead>Gate Name</TableHead>
                                <TableHead>Archived At</TableHead>
                                <TableHead class="w-[260px]">Action</TableHead>
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

                                <TableCell class="space-x-2">
                                    <Button
                                        size="sm"
                                        class="cursor-pointer"
                                        @click="openRestoreDialog(gate)"
                                    >
                                        <ArchiveRestore class="mr-2 h-4 w-4" />
                                        Restore
                                    </Button>

                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        class="cursor-pointer"
                                        @click="openDeleteDialog(gate)"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Delete Permanently
                                    </Button>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="props.gates.data.length === 0">
                                <TableCell
                                    colspan="3"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No trashed gates found.
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

    <!-- Restore Dialog -->
    <Dialog v-model:open="restoreOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Restore Gate</DialogTitle>
                <DialogDescription>
                    Are you sure you want to restore
                    <span class="font-medium">
                        {{ selectedGate?.gate_name ?? 'this gate' }}
                    </span>
                    ?
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <DialogClose as-child>
                    <Button variant="outline" @click="closeRestoreDialog">
                        Cancel
                    </Button>
                </DialogClose>

                <Button @click="restoreGate">
                    Restore
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Dialog -->
    <Dialog v-model:open="deleteOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Delete Gate Permanently</DialogTitle>
                <DialogDescription>
                    This action cannot be undone. Type
                    <span class="font-medium text-destructive">delete</span>
                    to permanently remove
                    <span class="font-medium">
                        {{ selectedGate?.gate_name ?? 'this gate' }}
                    </span>.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-2">
                <Label for="confirm_delete">Type delete</Label>
                <Input
                    id="confirm_delete"
                    v-model="confirmDelete"
                    placeholder="delete"
                />
            </div>

            <DialogFooter>
                <DialogClose as-child>
                    <Button variant="outline" @click="closeDeleteDialog">
                        Cancel
                    </Button>
                </DialogClose>

                <Button
                    variant="destructive"
                    :disabled="!canForceDelete"
                    @click="forceDeleteGate"
                >
                    Delete Permanently
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
