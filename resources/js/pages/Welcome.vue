<script setup lang="ts">
import PitxLogo from '@/components/assets/PITX.png';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, login } from '@/routes';
import { show } from '@/routes/company-registration';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ShieldCheck,
    Radio,
    BarChart3,
    MapPin,
    CheckCircle2,
} from 'lucide-vue-next';

withDefaults(defineProps<{ canRegister: boolean }>(), { canRegister: true });

const features = [
    {
        title: 'Role-Based Access',
        subtitle: 'Granular dashboards per user type.',
        description:
            'Dedicated views for Super Admins, Dispatchers, and Operators — each scoped to exactly what they need.',
        icon: ShieldCheck,
        iconBg: 'bg-[#1a3a6b]/10 group-hover:bg-[#1a3a6b]/20',
        iconColor: 'text-[#1a3a6b]',
        borderHover: 'hover:border-[#1a3a6b]/30',
        line: 'from-[#1a3a6b] to-transparent',
        cardClass: 'border-gray-200 bg-white',
    },
    {
        title: 'Centralized Dispatch',
        subtitle: 'One system, multiple gates.',
        description:
            'Control multiple gates and dispatcher teams from a single unified interface — no silos, no confusion.',
        icon: Radio,
        iconBg: 'bg-[#c0392b]/10 group-hover:bg-[#c0392b]/20',
        iconColor: 'text-[#c0392b]',
        borderHover: 'hover:border-[#c0392b]/50',
        line: 'from-[#c0392b] to-transparent',
        cardClass: 'border-[#c0392b]/20 bg-gradient-to-br from-[#c0392b]/5 to-white',
    },
    {
        title: 'Real-Time Monitoring',
        subtitle: 'Live visibility across the terminal.',
        description:
            'Track routes, vehicles, and terminal activity as it happens — enabling faster decisions.',
        icon: BarChart3,
        iconBg: 'bg-[#1a3a6b]/10 group-hover:bg-[#1a3a6b]/20',
        iconColor: 'text-[#1a3a6b]',
        borderHover: 'hover:border-[#1a3a6b]/30',
        line: 'from-[#1a3a6b] to-transparent',
        cardClass: 'border-gray-200 bg-white',
    },
];
</script>

<template>
    <Head title="PITX | Centralized Transport System" />

    <div class="flex min-h-screen flex-col bg-[#f4f5f7] text-[#1a1a2e]">
        <!-- HEADER -->
        <header class="sticky top-0 z-50 border-b border-gray-200 bg-white/90 shadow-sm backdrop-blur-md">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                <div class="flex items-center gap-3">
                    <img :src="PitxLogo" alt="PITX Logo" class="h-9 w-auto object-contain" />
                    <div class="hidden h-6 w-px bg-gray-200 sm:block"></div>
                    <div class="hidden sm:block">
                        <div class="text-sm font-bold uppercase tracking-wide text-[#1a1a2e]">
                            Parañaque Integrated Terminal Exchange
                        </div>
                        <div class="text-[10px] font-medium uppercase tracking-widest text-gray-400">
                            Centralized Transport System
                        </div>
                    </div>
                </div>

                <nav class="flex items-center gap-2">
                    <template v-if="$page.props.auth.user">
                        <Button as-child class="rounded-lg bg-[#c0392b] px-5 text-sm text-white hover:bg-[#a93226]">
                            <Link :href="dashboard()">
                                Dashboard
                                <ArrowRight class="ml-1 inline h-4 w-4" />
                            </Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button variant="ghost" as-child class="text-sm font-medium text-gray-600 hover:text-[#c0392b]">
                            <Link :href="login()">Log in</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <!-- HERO -->
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img
                    src="../components/assets/pitx-main3.jpg"
                    alt="PITX Terminal"
                    class="h-full w-full object-cover object-center"
                />
                <div class="absolute inset-0 bg-gradient-to-r from-[#0d1b2a]/95 via-[#0d1b2a]/80 to-[#0d1b2a]/50"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-[#0d1b2a]/60 via-transparent to-transparent"></div>
                <div class="absolute -top-20 -left-20 h-96 w-96 rounded-full bg-[#c0392b]/20 blur-3xl"></div>
                <div class="absolute top-10 right-1/3 h-72 w-72 rounded-full bg-[#1a3a6b]/25 blur-3xl"></div>
            </div>

            <div class="relative z-10 mx-auto max-w-7xl px-6 py-24 md:py-36">
                <div class="mx-auto max-w-3xl space-y-8">

                    <!-- Headline -->
                    <h1 class="text-5xl font-extrabold leading-[1.1] tracking-tight text-white md:text-7xl">
                        Centralized<br />
                        Terminal<br />
                        <span class="text-[#e74c3c]">Operations.</span>
                    </h1>

                    <!-- Description -->
                    <p class="max-w-xl text-base leading-relaxed text-white/70 md:text-lg">
                        A integrated system for PITX administrators, dispatchers, and operators to manage
                        gates, routes, and vehicle dispatch in real time — from one powerful platform.
                    </p>

                    <!-- CTAs -->
                    <div class="flex flex-wrap gap-3 pt-2">
                        <Button
                            size="lg"
                            as-child
                            class="rounded-xl bg-[#c0392b] px-8 text-base font-semibold text-white shadow-lg shadow-red-900/30 hover:bg-[#a93226]"
                        >
                            <Link :href="show()">
                                Get Started
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>

                        <Button
                            size="lg"
                            variant="outline"
                            as-child
                            class="rounded-xl border-white/30 bg-white/10 px-8 text-base font-semibold text-white backdrop-blur-sm hover:bg-white/20"
                        >
                            <a href="#features">Learn More</a>
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- STATS -->
        <section class="bg-[#1a3a6b]">
            <div class="mx-auto max-w-7xl px-6 py-10">
                <div class="grid grid-cols-3 divide-x divide-white/10 text-center">
                    <div class="px-4 py-2">
                        <div class="text-2xl font-extrabold text-white">Centralized</div>
                        <div class="mt-0.5 text-xs font-medium uppercase tracking-widest text-blue-300">Operations Control</div>
                    </div>
                    <div class="px-4 py-2">
                        <div class="text-2xl font-extrabold text-white">Real-time data</div>
                        <div class="mt-0.5 text-xs font-medium uppercase tracking-widest text-blue-300">System Monitoring</div>
                    </div>
                    <div class="px-4 py-2">
                        <div class="text-2xl font-extrabold text-white">Access Control</div>
                        <div class="mt-0.5 text-xs font-medium uppercase tracking-widest text-blue-300">User Permissions</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
<section id="features" class="mx-auto max-w-7xl px-6 py-16 pt-20">
    <div class="mb-10 text-center">
        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-[#c0392b]">Core Capabilities</p>
        <h2 class="text-2xl font-extrabold tracking-tight text-[#1a1a2e] md:text-3xl">
            Optimized for Large Transport Terminals
        </h2>
        <p class="mx-auto mt-3 max-w-xl text-sm text-gray-500">
            Every feature is crafted to simplify complex operations, from dispatch to compliance, tailored for terminals like PITX.
        </p>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <Card
            v-for="(feature, i) in features"
            :key="i"
            :class="[
                'group relative overflow-hidden rounded-2xl border p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl',
                feature.cardClass,
                feature.borderHover,
            ]"
        >
            <CardHeader class="p-0">
                <div
                    :class="[
                        'mb-4 flex h-12 w-12 items-center justify-center rounded-xl transition-colors',
                        feature.iconBg,
                    ]"
                >
                    <component :is="feature.icon" class="h-6 w-6" :class="feature.iconColor" />
                </div>

                <CardTitle class="text-base font-bold text-[#1a1a2e]">
                    {{ feature.title }}
                </CardTitle>

                <p class="mt-1 text-sm font-medium text-gray-400">
                    {{ feature.subtitle }}
                </p>
            </CardHeader>

            <CardContent class="p-0 pt-3">
                <p class="text-sm leading-relaxed text-gray-500">
                    {{ feature.description }}
                </p>
            </CardContent>

            <div
                :class="[
                    'absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r opacity-0 transition-opacity group-hover:opacity-100',
                    feature.line,
                ]"
            ></div>
        </Card>
    </div>
</section>

<!-- CTA -->
<section class="mx-auto max-w-7xl px-6 pb-16">
    <Card class="relative overflow-hidden rounded-2xl border-0 bg-gradient-to-r from-[#1a3a6b] to-[#0d1b2a] px-6 py-10 text-center shadow-xl sm:px-8 sm:py-12">
        <div class="absolute right-0 top-0 h-64 w-64 -translate-y-1/2 translate-x-1/4 rounded-full bg-[#c0392b]/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-48 w-48 -translate-x-1/4 translate-y-1/3 rounded-full bg-[#1a3a6b]/40 blur-3xl"></div>

        <CardContent class="relative z-10 p-0">
            <p class="mb-3 text-xs font-bold uppercase tracking-widest text-red-400">Get Onboard</p>
            <h2 class="text-2xl font-extrabold text-white md:text-3xl">
                Take Your Terminal Operations to the Next Level
            </h2>
            <p class="mx-auto mt-3 max-w-md text-sm text-blue-200">
                Sign up today to streamline dispatch, verification, and compliance — all from a single platform.
            </p>

            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <Button as-child class="rounded-xl bg-[#c0392b] px-8 py-3 font-semibold text-white shadow-lg hover:bg-[#a93226]">
                    <Link :href="show()">
                        Get Started
                        <ArrowRight class="ml-2 h-4 w-4" />
                    </Link>
                </Button>

                <Button
                    variant="outline"
                    as-child
                    class="rounded-xl border-white/30 bg-transparent px-8 py-3 font-semibold text-white hover:bg-white/10"
                >
                    <Link :href="login()">Log In</Link>
                </Button>
            </div>
        </CardContent>
    </Card>
</section>

        <!-- FOOTER -->
        <footer class="border-t border-gray-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-6 py-6 sm:flex-row">
                <div class="flex items-center gap-2">
                    <img :src="PitxLogo" alt="PITX Logo" class="h-7 w-auto object-contain opacity-70" />
                    <p class="text-sm text-gray-400">© 2026 PITX System. All rights reserved.</p>
                </div>
                <div class="flex gap-5 text-xs text-gray-400">
                    <Link href="/privacy" class="hover:text-[#1a3a6b]">Privacy Policy</Link>
                    <Link href="/terms" class="hover:text-[#1a3a6b]">Terms</Link>
                    <Link href="/contact" class="hover:text-[#1a3a6b]">Contact</Link>
                </div>
            </div>
        </footer>
    </div>
</template>