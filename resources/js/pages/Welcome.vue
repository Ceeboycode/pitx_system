<script setup lang="ts">
import PitxLogo from '@/components/assets/PITX.png';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, login } from '@/routes';
import { show } from '@/routes/company-registration';
import { Head, Link } from '@inertiajs/vue3';

withDefaults(defineProps<{ canRegister: boolean }>(), { canRegister: true });
</script>

<template>
    <Head title="PITX | Centralized Transport System" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Header -->
        <header class="border-b">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4"
            >
                <div class="flex items-center gap-3">
                    <img
                        :src="PitxLogo"
                        alt="PITX Logo"
                        class="h-10 w-16 object-contain"
                    />

                    <span class="text-2xl font-semibold tracking-wide"
                        >Parañaque Integrated Terminal Exchange</span
                    >
                </div>

                <nav class="flex items-center gap-2">
                    <Button v-if="$page.props.auth.user" as-child>
                        <Link :href="dashboard()">Dashboard</Link>
                    </Button>

                    <template v-else>
                        <Button variant="ghost" as-child>
                            <Link :href="login()">Log in</Link>
                        </Button>

                        <Button variant="ghost" as-child>
                            <Link :href="show()">Register Company</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div
                class="absolute inset-0 bg-gradient-to-br from-red-600/10 via-white to-blue-600/10"
            />

            <div
                class="relative mx-auto grid max-w-7xl items-center gap-16 px-6 py-24 lg:grid-cols-2"
            >
                <!-- Text -->
                <div>
                    <h1 class="text-4xl leading-tight font-bold lg:text-5xl">
                        Centralized Terminal
                        <span class="text-red-600">Operations</span><br />
                        Made <span class="text-blue-600">Simple</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-lg text-muted-foreground">
                        A unified system for PITX administrators, dispatchers,
                        and operators to manage gates, routes, and vehicle
                        dispatch in real time.
                    </p>

                    <div class="mt-8 flex gap-4">
                        <Button size="lg" variant="destructive" as-child>
                            <Link :href="login()">Get Started</Link>
                        </Button>
                        <Button size="lg" variant="outline" as-child>
                            <a href="#features">Learn More</a>
                        </Button>
                    </div>
                </div>

                <!-- Mini Dashboard Preview -->
                <Card class="shadow-xl">
                    <CardHeader
                        class="flex flex-row items-center justify-between"
                    >
                        <CardTitle class="text-base"
                            >Gate 3 – Southbound</CardTitle
                        >
                        <Badge class="bg-green-100 text-green-700"
                            >Active</Badge
                        >
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <Card class="p-4">
                                <p class="text-xs text-muted-foreground">
                                    Active Vehicles
                                </p>
                                <p class="text-2xl font-bold">12</p>
                            </Card>
                            <Card class="p-4">
                                <p class="text-xs text-muted-foreground">
                                    Dispatchers
                                </p>
                                <p class="text-2xl font-bold">5</p>
                            </Card>
                        </div>

                        <div
                            class="flex h-28 items-center justify-center rounded-lg border bg-muted text-sm text-muted-foreground"
                        >
                            Route Activity Overview
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="bg-muted/50 py-20">
            <div class="mx-auto max-w-7xl px-6">
                <h2 class="text-center text-3xl font-bold">Key Features</h2>
                <p
                    class="mx-auto mt-4 max-w-2xl text-center text-muted-foreground"
                >
                    Designed for large-scale transport terminals like PITX.
                </p>

                <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Role-Based Access</CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm text-muted-foreground">
                            Dedicated dashboards for Super Admins, Dispatchers,
                            and Operators.
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Centralized Dispatch</CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm text-muted-foreground">
                            Control multiple gates and dispatcher teams from one
                            system.
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Real-Time Monitoring</CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm text-muted-foreground">
                            Live visibility of routes, vehicles, and terminal
                            activity.
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t py-6">
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 sm:flex-row"
            >
                <p class="text-sm text-muted-foreground">
                    © 2026 PITX System. All rights reserved.
                </p>
                <div class="flex gap-4 text-sm">
                    <a href="#" class="hover:text-red-600">Privacy</a>
                    <a href="#" class="hover:text-blue-600">Terms</a>
                </div>
            </div>
        </footer>
    </div>
</template>
