<script setup lang="ts">
import PitxLogo from '@/components/assets/PITX.png';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Separator from '@/components/ui/separator/Separator.vue';
import { dashboard, login } from '@/routes';
import { show } from '@/routes/company-registration';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import {
    RiArrowRightLine,
    RiFacebookCircleFill,
    RiInstagramFill,
    RiMapPin2Line,
    RiShieldCheckLine,
    RiSpotifyFill,
    RiTiktokFill,
} from 'vue-remix-icons';

withDefaults(defineProps<{ canRegister: boolean }>(), { canRegister: true });

const features = [
    {
        title: 'Role-Based Access',
        subtitle: 'Granular dashboards per user type.',
        description:
            'Dedicated views for Super Admins, Dispatchers, and Operators — each scoped to exactly what they need.',
        icon: RiShieldCheckLine,
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
        iconBg: 'bg-[#1a3a6b]/10 group-hover:bg-[#1a3a6b]/20',
        iconColor: 'text-[#1a3a6b]',
        borderHover: 'hover:border-[#1a3a6b]/30',
        line: 'from-[#1a3a6b] to-transparent',
        cardClass: 'border-gray-200 bg-white',
    },
];

const pitxName = "Parañaque Integrated Terminal Exchange"

const address = "Paranaque Integrated Terminal Exchange, #1 Kennedy Rd., Tambo, Paranaque City"

// const sections = [
//     {
//         id: 'about',
//         title: 'About',
//         description: '',
//     },
//     {
//         id: 'dispatch-management-system',
//         title: 'Products',
//         description: '',
//     },
//     {
//         id: 'features',
//         title: 'Features',
//         description: '',
//     },
//     {
//         id: 'driver-app',
//         title: 'Driver App',
//         description: '',
//     },
//     {
//         id: 'commuter-app',
//         title: 'Commuter App',
//         description: '',
//     },
//     {
//         id: 'contact',
//         title: 'Contact',
//         description: '',
//     },
// ]

const footerSocials = [
    {
        icon: RiFacebookCircleFill,
        href: 'https://www.facebook.com/ParanaqueITX',
    },
    {
        icon: RiInstagramFill,
        href: 'https://www.instagram.com/pitx.landport',
    },
    {
        icon: RiTiktokFill,
        href: 'https://www.tiktok.com/@pitx.landport',
    },
    {
        icon: RiSpotifyFill,
        href: 'https://open.spotify.com/user/31gpv7sdxutcwzt3skcjwbeqzqhi',
    }
]

const footerLinks = [
    {
        title: 'Company',
        links: [
            {
                name: 'Home',
                href: '#'
            },
            {
                name: 'About',
                href: '#'
            },
            {
                name: 'Contact',
                href: '#footer'
            },
            {
                name: 'Development Team',
                href: '#'
            },
        ]
    },
    {
        title: 'Products',
        links: [
            {
                name: 'Dispatch Management Web App',
                href: '#dispatch-management-system'
            },
            {
                name: 'Driver App',
                href: '#driver-app'
            },
            {
                name: 'Commuter App',
                href: '#commuter-app'
            },
        ]
    },
]

function openInMaps(value?: string | null) {
  const query = (value ?? '').trim();
  if (!query || query === '—') return;
  window.open(
    `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`,
    '_blank',
    'noopener,noreferrer',
  );
}

const showNavbar = ref(true);
const isAtTop = ref(true);
const lastScrollY = ref(0);

function handleScroll() {
    const currentScrollY = window.scrollY;
    showNavbar.value = currentScrollY <= lastScrollY.value || currentScrollY <= 0;
    isAtTop.value = currentScrollY <= 0;
    lastScrollY.value = currentScrollY;
}

onMounted(() => {
    lastScrollY.value = window.scrollY;
    isAtTop.value = window.scrollY <= 0;
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <Head title="PITX | Centralized Transport System" />

    <div
        id="navbar"
        class="sticky top-0 z-50 bg-custom-bg-light flex flex-row justify-between items-center transition-all duration-300 ease-out"
        :class="[
            showNavbar ? 'translate-y-0' : '-translate-y-full',
            isAtTop ? 'py-4 px-32' : 'py-4 px-6 top-0 mx-26 rounded-b-md shadow-sm',
        ]"
    >
        <div class="flex flex-row gap-4">
            <img :src="PitxLogo" alt="PITX Logo" class="h-9 w-auto object-contain" />
            <div class="flex flex-col text-sm justify-center">
                <span class="text-custom-shadow font-semibold uppercase">{{ pitxName }}</span>
                <span class="text-custom-shadow">Dispatch Management System</span>
            </div>
        </div>
        <div class="flex flex-row gap-6 items-center text-sm text-custom-shadow/80">
            <a href="#about" class="mt-1 relative group uppercase pb-1 hover:text-custom-shadow">
                About
                <span class="absolute inset-x-0 -bottom-1 h-1 origin-left scale-x-0 bg-welcome-red transition-transform duration-100 ease-out group-hover:scale-x-100"></span>
            </a>
            <a href="#dispatch-management-system" class="mt-1 relative group uppercase pb-1 hover:text-custom-shadow">
                Products
                <span class="absolute inset-x-0 -bottom-1 h-1 origin-left scale-x-0 bg-welcome-red transition-transform duration-100 ease-out group-hover:scale-x-100"></span>
            </a>
            <a href="#passenger-guide" class="mt-1 relative group uppercase pb-1 hover:text-custom-shadow">
                Passenger Guide
                <span class="absolute inset-x-0 -bottom-1 h-1 origin-left scale-x-0 bg-welcome-red transition-transform duration-100 ease-out group-hover:scale-x-100"></span>
            </a>
            <a href="#footer" class="mt-1 relative group uppercase pb-1 hover:text-custom-shadow">
                Contact
                <span class="absolute inset-x-0 -bottom-1 h-1 origin-left scale-x-0 bg-welcome-red transition-transform duration-100 ease-out group-hover:scale-x-100"></span>
            </a>
            <Button
                asChild
                variant="float-primary"
                class="uppercase"
                href=""
            >
                <Link :href="login()">LOGIN</Link>
            </Button>
        </div>
    </div>

    <div id="hero" class="min-h-screen bg-custom-bg-light">
        <div class="absolute inset-0 z-0 overflow-hidden">
            <img
                src="../components/assets/pitx-main3.jpg"
                alt="PITX Terminal"
                class="h-full w-full object-cover object-center-right blur-sm"
            />
            <div class="absolute inset-0 bg-gradient-to-r from-black/35 to-transparent/50"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-custom-primary/30 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/35 via-custom-primary/50 to-black/20"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
            <div class="absolute -top-50 -left-50 h-120 w-120 rounded-full bg-custom-accent-1/30 blur-3xl"></div>
            <div class="absolute -bottom-10 -right-50 h-100 w-100 rounded-full bg-custom-accent-1/30 blur-3xl"></div>
        </div>

        <div class="relative z-10 mx-auto px-32 py-24 md:py-36">
                <div class="mx-auto gap-y-4 flex flex-col justify-center items-center">

                    
                    <h1 class="text-5xl text-center font-extrabold leading-[1.1] tracking-wide text-custom-bg-light md:text-7xl">
                        <!-- <span class="text-custom-primary">PITX</span> -->
                        Terminal Dispatch
                        <br/>
                        <span class="text-custom-accent-1">Management System</span>
                    </h1>

                    
                    <p class="max-w-xl text-base leading-relaxed text-custom-bg-light/80 text-center md:text-lg">
                        A integrated system for PITX administrators, dispatchers, and operators to manage
                        gates, routes, and vehicle dispatch in real time — from one powerful platform.
                    </p>

                    
                    <div class="flex flex-wrap gap-4 pt-2">
                        <Button
                            size="lg"
                            variant="float"
                            as-child
                            class="rounded-full hover:bg-custom-bg/60 dark:hover:bg-custom-bg-light/60"
                        >
                            <a href="#features">Learn More</a>
                        </Button>
                        <Button
                            size="lg"
                            as-child
                            variant="float-red"
                            class="rounded-full"
                        >
                            <Link :href="show()">
                                Get Started
                                <RiArrowRightLine class="ml-0 shrink-0 h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </div>
        </div>
    </div>

    <!-- <div id="about">

    </div> -->

    <!-- <div id="dispatch-management-system">

    </div> -->

    <!-- <div id="features">

    </div> -->

    <!-- <div id="driver-app">

    </div> -->

    <!-- <div id="commuter-app">

    </div> -->

    <!-- maybe add the live dispatch schedule, and a prompt for commuters, to start searching. -->
    <!-- add a testimonial part that takes up the whole screen like in antigravity website -->

    <!-- <div id="cta">

    </div> -->

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
                                <RiArrowRightLine class="ml-2 h-4 w-4 shrink-0" />
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

    <div id="footer" class="flex flex-col">
        <div class="w-full px-32 bg-welcome-grey text-custom-bg-dark dark:text-custom-shadow py-16 flex flex-col gap-4">
            <div class="w-full">
                <img :src="PitxLogo" alt="PITX Logo" class="h-9 w-auto object-contain" />
            </div>
            <div class="w-full flex flex-row justify-between">
                <div class="min-w-32 w-fit max-w-72 flex flex-col gap-4 text-xs">
                    <span class="text-custom-bg-dark/80 dark:text-custom-shadow/80 font-semibold uppercase">{{ pitxName }}</span>
                    <span
                        role="button"
                        class="text-custom-bg-dark/80 dark:text-custom-shadow/80 cursor-pointer"
                        title="Open in Google Maps"
                        @click="openInMaps(address)"
                        @keydown.enter.prevent="openInMaps(address)"
                        @keydown.space.prevent="openInMaps(address)"
                    >
                        {{ address }}
                    </span>
                    <div class="flex flex-col gap-1">
                        <a href="">customerservice@pitx.com.ph</a>
                        <a href="">8396-3817 to 18</a>
                    </div>
                    <div id="footer-socials" class="flex flex-row gap-1">
                        <a
                            v-for="(social, i) in footerSocials"
                            :key="i"
                            :href="social.href"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <component :is="social.icon" class="shrink-0 h-4 w-4"/>
                        </a>
                    </div>
                </div>
                <div id="footer-links" class="w-fit flex flex-row justify-center items-start text-xs gap-6">
                    <div
                        v-for="(group, gi) in footerLinks"
                        :key="gi"
                        class="min-w-48 w-fit flex flex-col gap-4"
                    >
                        <span class="text-custom-bg-dark/80 dark:text-custom-shadow/80 font-semibold uppercase">{{ group.title }}</span>
                        <div class="flex flex-col gap-1">
                            <a v-for="(link, li) in group.links" :key="li" :href="link.href">{{ link.name }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-full px-32 py-2 text-xs justify-between flex flex-row bg-custom-accent-1 text-custom-bg-light dark:text-custom-shadow/80">
            <span>©2026 PITX System. All rights reserved.</span>
            <span class="flex flex-row gap-4">
                <a href="">Privacy Policy</a>
                <a href="">Cookie Policy</a>
                <a href="">Terms & Conditions</a>
            </span>
        </div>
    </div>
</template>