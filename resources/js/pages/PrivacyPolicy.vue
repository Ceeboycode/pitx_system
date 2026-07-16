<script setup lang="ts">
import PitxLogo from '@/components/assets/PITX.png';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
import { ArrowRight, ArrowLeft, Shield, Eye, Lock, Database, UserCheck, RefreshCw, Mail } from 'lucide-vue-next';

withDefaults(defineProps<{ canRegister?: boolean }>(), { canRegister: true });

const sections = [
    {
        id: 'overview',
        icon: Shield,
        title: '1. Overview',
        content: `The PITX Centralized Transport System ("the System") is operated by the Parañaque Integrated Terminal Exchange (PITX). This Privacy Policy explains how we collect, use, store, and protect personal information submitted by administrators, dispatchers, operators, and other authorized users of the System. By accessing and using this System, you agree to the terms described in this policy.`,
    },
    {
        id: 'collection',
        icon: Database,
        title: '2. Information We Collect',
        content: `We collect the following types of information when you use the System:`,
        list: [
            'Personal identification details (name, employee ID, position/role)',
            'Login credentials (username, encrypted password)',
            'Contact information (email address, phone number)',
            'System activity logs (login timestamps, actions performed, gate assignments)',
            'Device and session information (IP address, browser type, session duration)',
            'Vehicle and route data entered or managed within the System',
        ],
    },
    {
        id: 'use',
        icon: Eye,
        title: '3. How We Use Your Information',
        content: `Information collected is used strictly for operational and administrative purposes:`,
        list: [
            'Authenticating and authorizing user access based on assigned roles',
            'Managing and monitoring terminal gate operations and dispatch activities',
            'Generating reports and audit logs for compliance and performance review',
            'Notifying users of system updates, assignments, or critical alerts',
            'Improving system performance, reliability, and security',
            'Fulfilling legal and regulatory obligations related to public transport management',
        ],
    },
    {
        id: 'access',
        icon: UserCheck,
        title: '4. Access Control',
        content: `Access to personal data within the System is strictly limited based on user roles. Super Administrators may access full system data for management and auditing purposes. Dispatchers and Operators may only access data relevant to their assigned responsibilities. No user may access data outside the scope of their designated role. All access activities are logged and monitored.`,
    },
    {
        id: 'security',
        icon: Lock,
        title: '5. Data Security',
        content: `We implement industry-standard technical and organizational measures to protect your data:`,
        list: [
            'All passwords are encrypted using secure hashing algorithms',
            'Data transmission is protected using HTTPS/TLS encryption',
            'Session tokens are managed securely and expire after periods of inactivity',
            'The System undergoes regular security reviews and vulnerability assessments',
            'Access to server infrastructure is restricted to authorized technical personnel only',
        ],
    },
    {
        id: 'retention',
        icon: RefreshCw,
        title: '6. Data Retention',
        content: `Personal data is retained for as long as your account remains active within the System or as required by applicable laws and government regulations. Upon deactivation of an account, personal data may be archived for audit trail and compliance purposes for a minimum period of five (5) years, after which it will be securely deleted or anonymized.`,
    },
    {
        id: 'contact',
        icon: Mail,
        title: '7. Contact & Concerns',
        content: `If you have questions, concerns, or requests regarding this Privacy Policy or the handling of your personal data, please contact the PITX System Administrator through official PITX communication channels. We are committed to addressing all privacy concerns in a timely and transparent manner.`,
    },
];
</script>

<template>
    <Head title="PITX | Privacy Policy" />

    <div class="flex min-h-screen flex-col bg-[#f4f5f7] text-[#1a1a2e]">

        
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

        
        <div class="relative overflow-hidden bg-gradient-to-r from-[#0d1b2a] via-[#1a3a6b] to-[#0d1b2a] py-14">
            <div class="absolute -top-10 -left-10 h-64 w-64 rounded-full bg-[#c0392b]/20 blur-3xl"></div>
            <div class="absolute bottom-0 right-1/4 h-48 w-48 rounded-full bg-[#1a3a6b]/40 blur-3xl"></div>
            <div class="relative z-10 mx-auto max-w-7xl px-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#c0392b]/20 border border-[#c0392b]/30">
                        <Shield class="h-5 w-5 text-[#e74c3c]" />
                    </div>
                    <p class="text-xs font-bold uppercase tracking-widest text-red-400">Legal Document</p>
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight text-white md:text-4xl">
                    Privacy Policy
                </h1>
                <p class="mt-2 text-sm text-blue-200 max-w-xl">
                    This policy describes how the PITX Centralized Transport System collects, uses, and protects your personal information.
                </p>
                <p class="mt-4 text-xs text-white/40 uppercase tracking-widest">Effective Date: January 1, 2026 &nbsp;·&nbsp; Last Updated: January 1, 2026</p>
            </div>
        </div>

        
        <main class="mx-auto w-full max-w-7xl px-6 py-12 flex gap-8 items-start">

            
            <aside class="hidden lg:block w-64 shrink-0 sticky top-24">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="mb-4 text-[10px] font-bold uppercase tracking-widest text-[#c0392b]">Table of Contents</p>
                    <nav class="space-y-1">
                        <a
                            v-for="s in sections"
                            :key="s.id"
                            :href="`#${s.id}`"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-500 transition-colors hover:bg-[#1a3a6b]/5 hover:text-[#1a3a6b]"
                        >
                            <component :is="s.icon" class="h-3.5 w-3.5 shrink-0 text-[#c0392b]" />
                            <span>{{ s.title }}</span>
                        </a>
                    </nav>
                </div>
            </aside>

            
            <div class="flex-1 min-w-0 space-y-6">
                <div
                    v-for="s in sections"
                    :key="s.id"
                    :id="s.id"
                    class="rounded-2xl border border-gray-200 bg-white p-7 shadow-sm scroll-mt-24"
                >
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1a3a6b]/8 border border-[#1a3a6b]/10">
                            <component :is="s.icon" class="h-4.5 w-4.5 text-[#1a3a6b]" />
                        </div>
                        <h2 class="text-base font-bold text-[#1a1a2e]">{{ s.title }}</h2>
                    </div>
                    <div class="pl-12">
                        <p class="text-sm leading-relaxed text-gray-600">{{ s.content }}</p>
                        <ul v-if="s.list" class="mt-3 space-y-2">
                            <li
                                v-for="(item, i) in s.list"
                                :key="i"
                                class="flex items-start gap-2 text-sm text-gray-600"
                            >
                                <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#c0392b] shrink-0"></span>
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>

                
                <div class="pt-2 flex items-center justify-between">
                    <Button variant="ghost" as-child class="text-sm text-gray-500 hover:text-[#c0392b] gap-2 pl-0">
                        <Link href="/">
                            <ArrowLeft class="h-4 w-4" />
                            Back to Home
                        </Link>
                    </Button>
                    <p class="text-xs text-gray-400">© 2026 PITX System. All rights reserved.</p>
                </div>
            </div>
        </main>

        
        <footer class="border-t border-gray-200 bg-white mt-auto">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-6 py-6 sm:flex-row">
                <div class="flex items-center gap-2">
                    <img :src="PitxLogo" alt="PITX Logo" class="h-7 w-auto object-contain opacity-70" />
                    <p class="text-sm text-gray-400">© 2026 PITX System. All rights reserved.</p>
                </div>
                <div class="flex gap-5 text-xs font-medium uppercase tracking-widest">
                    <a href="#" class="text-[#c0392b]">Privacy</a>
                    <a href="/terms" class="text-gray-400 transition-colors hover:text-[#1a3a6b]">Terms</a>
                </div>
            </div>
        </footer>
    </div>
</template>