<script setup lang="ts">
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
    TableCaption,
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

const canForceDelete = computed(() => confirmDelete.value.trim() === 'delete');

function openRestoreDialog(gate: Gate) {
    selectedGate.value = gate;
    restoreOpen.value = true;
}

function closeRestoreDialog() {
    restoreOpen.value = false;
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
    <Head title="Trash" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-10 mt-3">
                <CardHeader
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
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
                        <!-- <TableCaption>List of trashed gates.</TableCaption> -->

                        <TableHeader>
                            <TableRow>
                                <TableHead>Gate Name</TableHead>
                                <TableHead>Archived At</TableHead>
                                <TableHead class="w-[260px]">Action</TableHead>
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
