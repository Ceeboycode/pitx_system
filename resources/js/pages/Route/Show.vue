<script setup lang="ts">
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show } from '@/routes/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';



interface Gate {
    id: number;
    gate_name: string;
}

interface RouteStop {
    id: number;
    stop_name: string;
    stop_order: number;
}

interface User {
    id: number;
    name: string;
}

interface RouteModel {
    id: number;
    route_name: string;
    gate: Gate | null;
    stops: RouteStop[];
    creator: User | null;
    updater: User | null;
    created_at_human: string;
    updated_at_human: string;
}


const props = defineProps<{
    route: RouteModel;
}>();



const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Routes',
        href: index().url,
    },
    {
        title: 'Route Details',
        href: show(props.route.id).url,
    },
];
</script>

<template>
    <Head :title="route.route_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Route Details</CardTitle>
                    <CardDescription>
                        View all information about this route, including gate,
                        stops, and timestamps.
                    </CardDescription>
                    <CardAction>
                        <Button variant="link" as-child>
                            <Link :href="index().url">Back</Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Route Name -->
                        <div class="rounded-lg border p-4">
                            <p class="text-xs text-muted-foreground uppercase">
                                Route Name
                            </p>
                            <p class="mt-1 text-lg font-semibold">
                                {{ route.route_name }}
                            </p>
                        </div>

                        <!-- Gate -->
                        <div class="rounded-lg border p-4">
                            <p class="text-xs text-muted-foreground uppercase">
                                Gate
                            </p>
                            <p class="mt-1 text-lg font-semibold">
                                {{ route.gate?.gate_name ?? '—' }}
                            </p>
                        </div>

                        <!-- Created By -->
                        <div class="rounded-lg border p-4">
                            <p class="text-xs text-muted-foreground uppercase">
                                Created By
                            </p>
                            <p class="mt-1 font-semibold">
                                {{ route.creator?.name ?? '—' }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ route.created_at_human }}
                            </p>
                        </div>

                        <!-- Updated By -->
                        <div class="rounded-lg border p-4">
                            <p class="text-xs text-muted-foreground uppercase">
                                Last Updated By
                            </p>
                            <p class="mt-1 font-semibold">
                                {{ route.updater?.name ?? '—' }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ route.updated_at_human }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-sm font-semibold">
                                Stops
                                <Badge variant="secondary">
                                    {{ route.stops.length }}
                                </Badge>
                            </p>
                        </div>

                        <div
                            v-if="route.stops.length"
                            class="divide-y rounded-lg border"
                        >
                            <div
                                v-for="stop in route.stops"
                                :key="stop.id"
                                class="flex items-center justify-between px-4 py-2 transition hover:bg-muted"
                            >
                                <div class="flex items-center gap-3">
                                    <Badge variant="outline">
                                        {{ stop.stop_order }}
                                    </Badge>
                                    <span class="font-medium">
                                        {{ stop.stop_name }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p
                            v-else
                            class="rounded-lg border p-4 text-sm text-muted-foreground"
                        >
                            No stops available.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
