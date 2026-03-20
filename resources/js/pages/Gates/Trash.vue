<script setup lang="ts">
/* ======================================================
   Shared UI
====================================================== */
import InertiaPagination from '@/components/InertiaPagination.vue';

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
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
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
import { forceDelete, index, restore } from '@/routes/gates';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

/* ======================================================
   Icons
====================================================== */
import { ArchiveRestore, ArrowLeft, Trash2 } from 'lucide-vue-next';

/* ======================================================
   Vue Core
====================================================== */
import { computed, ref } from 'vue';

/* ======================================================
   Toaster
====================================================== */
import { toast } from 'vue-sonner';

/* ======================================================
   Permissions
====================================================== */
import { can } from '@/lib/can';

const canRestore     = can('gates.restore');
const canForceDelete = can('gates.forceDelete');

/* ======================================================
   Types
====================================================== */
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

/* ======================================================
   Props
====================================================== */
const props = defineProps<{
    gates: PaginatedGates;
}>();

/* ======================================================
   Breadcrumbs
====================================================== */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gates', href: index().url },
    { title: 'Trash', href: '#' },
];

/* ======================================================
   Dialog State
====================================================== */
const restoreOpen  = ref(false);
const deleteOpen   = ref(false);
const selectedGate = ref<Gate | null>(null);
const confirmText  = ref('');

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
    confirmText.value  = '';
    deleteOpen.value   = true;
}

function closeDeleteDialog() {
    deleteOpen.value   = false;
    selectedGate.value = null;
    confirmText.value  = '';
}

/* ======================================================
   Actions
====================================================== */
function restoreGate() {
    if (!selectedGate.value) return;

    router.post(
        restore(selectedGate.value.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => closeRestoreDialog(),
            onError:   () => toast.error('Failed to restore gate.'),
        },
    );
}

function forceDeleteGate() {
    if (!selectedGate.value || !canConfirmForceDelete.value) return;

    router.delete(forceDelete(selectedGate.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Gate permanently deleted.');
            closeDeleteDialog();
        },
        onError: () => toast.error('Failed to permanently delete gate.'),
    });
}
</script>

<template>
    <Head title="Trash" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-10 mt-3">
                <CardHeader class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle>Trashed Gates</CardTitle>
                        <CardDescription>
                            List of gates that have been moved to trash.
                        </CardDescription>
                    </div>

                    <CardAction>
                        <Button size="sm" variant="link" as-child>
                            <Link :href="index().url">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back to Gates
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <Table>
                        <TableCaption>List of trashed gates.</TableCaption>

                        <TableHeader>
                            <TableRow>
                                <TableHead>Gate Name</TableHead>
                                <TableHead>Archived At</TableHead>
                                <TableHead class="w-[260px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="gate in props.gates.data"
                                :key="gate.id"
                            >
                                <TableCell>{{ gate.gate_name }}</TableCell>

                                <TableCell>
                                    {{ gate.deleted_at_human ?? 'N/A' }}
                                </TableCell>

                                <TableCell class="space-x-2">
                                    <Button
                                        v-if="canRestore"
                                        size="sm"
                                        @click="openRestoreDialog(gate)"
                                    >
                                        <ArchiveRestore class="mr-2 h-4 w-4" />
                                        Restore
                                    </Button>

                                    <Button
                                        v-if="canForceDelete"
                                        size="sm"
                                        variant="destructive"
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

    <!-- RESTORE DIALOG -->
    <AlertDialog v-if="canRestore" v-model:open="restoreOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle class="flex items-center gap-2">
                    <ArchiveRestore class="h-5 w-5 text-emerald-600" />
                    Restore Gate
                </AlertDialogTitle>
                <AlertDialogDescription>
                    Are you sure you want to restore
                    <span class="font-medium text-foreground">
                        {{ selectedGate?.gate_name ?? 'this gate' }}
                    </span>?
                    It will be moved back to the active gates list.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel @click="closeRestoreDialog">Cancel</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-emerald-600 hover:bg-emerald-700 focus-visible:ring-emerald-500"
                    @click="restoreGate"
                >
                    Restore Gate
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>

    <!-- FORCE DELETE DIALOG -->
    <AlertDialog v-if="canForceDelete" v-model:open="deleteOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle class="flex items-center gap-2">
                    <Trash2 class="h-5 w-5 text-destructive" />
                    Delete Permanently
                </AlertDialogTitle>
                <AlertDialogDescription>
                    This action <span class="font-semibold text-destructive">cannot be undone</span>.
                    Type <span class="font-mono font-semibold text-destructive">delete</span> below
                    to permanently remove
                    <span class="font-medium text-foreground">
                        {{ selectedGate?.gate_name ?? 'this gate' }}
                    </span>.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <div class="space-y-2 px-1">
                <Label for="confirm_delete">Confirmation</Label>
                <Input
                    id="confirm_delete"
                    v-model="confirmText"
                    placeholder="Type delete to confirm"
                />
            </div>

            <AlertDialogFooter>
                <AlertDialogCancel @click="closeDeleteDialog">Cancel</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90 disabled:pointer-events-none disabled:opacity-50"
                    :disabled="!canConfirmForceDelete"
                    @click="forceDeleteGate"
                >
                    Delete Permanently
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
