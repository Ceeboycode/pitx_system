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
import AppLayout from '@/layouts/AppLayout.vue';
import { forceDelete, index, restore } from '@/routes/gates';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { ArrowLeft } from 'lucide-vue-next';

interface Gate {
    id: number;
    gate_name: string;
    deleted_at_human: string | null;
}

defineProps<{
    gates: any; // paginator
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gates', href: index().url },
    { title: 'Trash', href: '#' },
];

const restoreGate = (gateId: number) => {
    router.post(
        restore(gateId).url,
        {},
        {
            preserveScroll: true,
            onError: () => toast.error('Failed to restore gate.'),
        },
    );
};

const confirmDelete = ref('');
const forceDeleteGate = (gateId: number) => {
    router.delete(forceDelete(gateId).url, {
        preserveScroll: true,
        onSuccess: () => {
            confirmDelete.value = '';
        },
        onError: () => toast.error('Failed to delete gate permanently.'),
    });
};
</script>

<template>
    <Head title="Trash" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-10 mt-3">
                <CardHeader>
                    <CardTitle>Trashed Gates</CardTitle>
                    <CardDescription>
                        List of gates that have been moved to trash.
                    </CardDescription>

                    <CardAction>
                        <Button size="sm" variant="link" as-child>
                            <Link :href="index().url"> <ArrowLeft class="mr-2 h-4 w-4" /> Back to Gates</Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <Table>
                        <TableCaption> List of trashed gates. </TableCaption>

                        <TableHeader>
                            <TableRow>
                                <TableHead>Gate Name</TableHead>
                                <TableHead>Archived At</TableHead>
                                <TableHead>Action</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="gate in gates.data" :key="gate.id">
                                <TableCell>{{ gate.gate_name }}</TableCell>

                                <TableCell>
                                    {{ gate.deleted_at_human ?? 'N/A' }}
                                </TableCell>

                                <TableCell class="space-x-2">
                                    <!-- Restore -->
                                    <Dialog>
                                        <DialogTrigger as-child>
                                            <Button
                                                class="cursor-pointer"
                                                size="sm"
                                            >
                                                Restore
                                            </Button>
                                        </DialogTrigger>

                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Restore Gate
                                                </DialogTitle>
                                                <DialogDescription>
                                                    Are you sure you want to
                                                    restore this gate?
                                                </DialogDescription>
                                            </DialogHeader>

                                            <DialogFooter>
                                                <DialogClose as-child>
                                                    <Button variant="outline">
                                                        Cancel
                                                    </Button>
                                                </DialogClose>

                                                <DialogClose as-child>
                                                    <Button
                                                        @click="
                                                            restoreGate(gate.id)
                                                        "
                                                    >
                                                        Restore
                                                    </Button>
                                                </DialogClose>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>

                                    <!-- Delete permanently -->
                                    <Dialog>
                                        <DialogTrigger as-child>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                class="cursor-pointer"
                                            >
                                                Delete Permanently
                                            </Button>
                                        </DialogTrigger>

                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Delete Gate Permanently
                                                </DialogTitle>
                                                <DialogDescription>
                                                    This action cannot be
                                                    undone. Please type
                                                    <span class="text-red-500">
                                                        delete
                                                    </span>
                                                    to confirm.
                                                </DialogDescription>
                                            </DialogHeader>

                                            <Label for="delete"
                                                >Type delete</Label
                                            >
                                            <Input
                                                id="delete"
                                                v-model="confirmDelete"
                                            />

                                            <DialogFooter>
                                                <DialogClose as-child>
                                                    <Button variant="outline">
                                                        Cancel
                                                    </Button>
                                                </DialogClose>

                                                <DialogClose as-child>
                                                    <Button
                                                        variant="destructive"
                                                        :disabled="
                                                            confirmDelete !==
                                                            'delete'
                                                        "
                                                        @click="
                                                            forceDeleteGate(
                                                                gate.id,
                                                            )
                                                        "
                                                    >
                                                        Delete Permanently
                                                    </Button>
                                                </DialogClose>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="gates.data.length === 0">
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
</template>
