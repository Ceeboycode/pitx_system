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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import SearchInput from '@/components/SearchInput.vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { index, restore, trash } from '@/routes/gates';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import { Archive, ArrowLeft, MoreHorizontal, RotateCcw } from 'lucide-vue-next';

import { ref } from 'vue';
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
const props = defineProps<{
    gates: PaginatedGates;
    filters: { search: string | null };
}>();

/* ── Breadcrumbs ─────────────────────────────────────────────────── */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gates', href: index().url },
    { title: 'Trash', href: '#' },
];

/* ── Dialog state ────────────────────────────────────────────────── */
const restoreOpen = ref(false);
const selectedGate = ref<Gate | null>(null);

function openRestoreDialog(gate: Gate) {
    selectedGate.value = gate;
    restoreOpen.value = true;
}

function closeRestoreDialog() {
    restoreOpen.value = false;
    selectedGate.value = null;
}

/* ── Actions ─────────────────────────────────────────────────────── */
function restoreGate() {
    if (!selectedGate.value) return;
    router.post(
        restore(selectedGate.value.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => closeRestoreDialog(),
            onError: () => toast.error('Failed to restore gate.'),
        },
    );
}
</script>

<template>
    <Head title="Archived Gates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <!-- <span>Change Requests</span> -->
                         <!-- TODO: make the text straight, not wrapped -->
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 mr-2"
                        >
                            <Link :href="index().url">
                                <ArrowLeft class="h-4 w-4" />
                            </Link>
                        </Button>
                        Archives
                        <span class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-rose-500 " />
                            <div class="border-7 border-rose-500 rounded-xs">
                                <div class="border-3 border-white rounded-xs"></div>
                            </div>
                        </span>
                    </CardTitle>
                    <CardDescription class="mt-1">
                        Gates that have been moved to trash. You can restore them back to active.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="trash().url"
                                :initial-value="filters.search"
                                placeholder="Search archived gates…"
                                :only="['gates', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Gate Name</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Archived At</TableHead
                                    >
                                    <TableHead
                                        class="text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Actions</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <!-- Empty state -->
                                <TableRow
                                    v-if="props.gates.data.length === 0"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="3"
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted"
                                            >
                                                <Archive
                                                    class="h-6 w-6 text-muted-foreground/40"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-foreground"
                                                >
                                                    No trashed gates
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-muted-foreground"
                                                >
                                                    Nothing has been moved to
                                                    trash yet.
                                                </p>
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
                                    <TableCell
                                        class="text-sm font-semibold capitalize"
                                    >
                                        {{ gate.gate_name }}
                                    </TableCell>

                                    <!-- Archived At -->
                                    <TableCell
                                        class="text-sm text-muted-foreground"
                                    >
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
                                                    <MoreHorizontal
                                                        class="h-4 w-4"
                                                    />
                                                    <span class="sr-only"
                                                        >Open actions</span
                                                    >
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                class="w-52 rounded-xl border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    {{ gate.gate_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    class="rounded-lg text-emerald-700 focus:bg-emerald-50 focus:text-emerald-700"
                                                    @click="
                                                        openRestoreDialog(gate)
                                                    "
                                                >
                                                    <RotateCcw
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    Restore Gate
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
                        <span class="font-semibold text-foreground">{{
                            selectedGate?.gate_name ?? 'this gate'
                        }}</span
                        >? It will be moved back to the active gates list.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        class="rounded-lg"
                        @click="closeRestoreDialog"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-emerald-600 text-white hover:bg-emerald-700"
                        @click="restoreGate"
                    >
                        <RotateCcw class="mr-2 h-4 w-4" />
                        Restore
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
