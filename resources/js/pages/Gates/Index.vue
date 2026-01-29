<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import InputError from '@/components/InputError.vue';
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
import { User, type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Archive, Edit, Save, View, Plus } from "lucide-vue-next";

interface Gate {
    id: number;
    gate_name: string;
    creator: User | null;
}

defineProps<{
    gates: {
        data: Gate[];
        links: [];
    };
}>();

import { destroy, index, show, store, trash, update } from '@/routes/gates';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Gates',
        href: index().url,
    },
];

const form = useForm({
    gate_name: '',
});

// ---------- CREATE ----------
const isCreateOpen = ref(false);

const createGate = () => {
    form.submit(store(), {
        preserveScroll: true,
        onSuccess: () => {
            isCreateOpen.value = false;
            form.reset();
        },
        onError: () => {
            toast.error('Failed to create gate.');
        },
    });
};

// ---------- EDIT (FIXED) ----------
const isEditOpen = ref(false);
const selectedGate = ref<Gate | null>(null);

const openEdit = (gate: Gate) => {
    selectedGate.value = gate;
    form.gate_name = gate.gate_name;
    isEditOpen.value = true;
};

const closeEdit = () => {
    isEditOpen.value = false;
    selectedGate.value = null;
    form.reset();
};

const editGate = () => {
    if (!selectedGate.value) return;

    form.submit(update(selectedGate.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEdit();
        },
        onError: () => {
            toast.error('Failed to update gate.');
        },
    });
};

// ---------- ARCHIVE ----------
const archiveGate = (gateId: number) => {
    router.delete(destroy(gateId), {
        preserveScroll: true,
        onError: () => {
            toast.error('Failed to archive gate.');
        },
    });
};
</script>

<template>
    <Head title="Gates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <CardTitle>Gates</CardTitle>
                        <CardDescription>List of all gates in the system.</CardDescription>
                    </div>

                    <div class="flex gap-2">
                        <!-- Trash Button -->
                        <Button asChild size="sm" variant="outline">
                            <Link :href="trash().url"> <View /> View Trash</Link>
                        </Button>

                        <!-- CREATE -->
                        <Dialog v-model:open="isCreateOpen">
                            <DialogTrigger asChild>
                                <Button size="sm"> <Plus /> Create Gate</Button>
                            </DialogTrigger>

                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Add New Gate</DialogTitle>
                                </DialogHeader>

                                <form @submit.prevent="createGate">
                                    <Label>Gate Name</Label>
                                    <Input v-model="form.gate_name" class="mt-1 mb-2" />
                                    <InputError :message="form.errors.gate_name" />

                                    <DialogFooter>
                                        <DialogClose asChild>
                                            <Button variant="secondary" size="sm">
                                                Cancel
                                            </Button>
                                        </DialogClose>
                                        <Button size="sm" type="submit" :disabled="form.processing">
                                            <Save />
                                            Save
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </CardHeader>


                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Gate Name</TableHead>
                                <TableHead>Created By</TableHead>
                                <TableHead>Action</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="gate in gates.data" :key="gate.id">
                                <TableCell>{{ gate.gate_name }}</TableCell>
                                <TableCell>{{ gate.creator?.name ?? 'N/A' }}</TableCell>

                                <TableCell class="space-x-2">
                                    <Button asChild size="sm" variant="outline">
                                        <Link :href="show(gate.id).url"> <View /> View</Link>
                                    </Button>

                                    <Button size="sm" variant="default" @click="openEdit(gate)">
                                        <Edit /> Edit
                                    </Button>

                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button size="sm" variant="archive">
                                                <Archive />
                                                Archive
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle class="flex items-center gap-2">
                                                <Archive :size="18" class="text-muted-foreground" />
                                                Archive Gate
                                                </DialogTitle>
                                                <DialogDescription>
                                                Are you sure you want to archive this gate? You can restore it later from the Trash.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <DialogFooter>
                                                <DialogClose asChild>
                                                    <Button variant="secondary" size="sm">
                                                        Cancel
                                                    </Button>
                                                </DialogClose>
                                                <Button
                                                    size="sm"
                                                    variant="archive"
                                                    @click="archiveGate(gate.id)"
                                                >
                                                    Archive
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>

                <CardAction class="justify-end p-4">
                    <InertiaPagination :links="gates.links" />
                </CardAction>
            </Card>
        </div>
    </AppLayout>

    <!-- EDIT DIALOG  -->
    <Dialog v-model:open="isEditOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Gate</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="editGate">
                <Label>Gate Name</Label>
                <Input v-model="form.gate_name" class="mt-1 mb-2" />
                <InputError :message="form.errors.gate_name" />

                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="secondary" size="sm" @click="closeEdit">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button size="sm" type="submit" :disabled="form.processing">
                        <Save />
                        Save Changes
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
