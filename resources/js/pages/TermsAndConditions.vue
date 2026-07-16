<script setup lang="ts">
import PitxLogo from '@/components/assets/PITX.png';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
import {
    ArrowRight, ArrowLeft, ScrollText, Users, ShieldAlert,
    Gavel, ClipboardList, LogIn, TriangleAlert, RefreshCw, Phone
} from 'lucide-vue-next';

withDefaults(defineProps<{ canRegister?: boolean }>(), { canRegister: true });

const sections = [
    {
        id: 'acceptance',
        icon: ScrollText,
        title: '1. Acceptance of Terms',
        content: `By accessing or using the PITX Centralized Transport System ("the System"), you acknowledge that you have read, understood, and agree to be bound by these Terms and Conditions. These terms apply to all users of the System, including Super Administrators, Dispatchers, and Operators. If you do not agree with any part of these terms, you must discontinue use of the System immediately and notify your System Administrator.`,
    },
    {
        id: 'eligibility',
        icon: Users,
        title: '2. User Eligibility & Account Responsibility',
        content: `Access to the System is granted exclusively to authorized PITX personnel. As a user, you are responsible for:`,
        list: [
            'Maintaining the confidentiality of your login credentials at all times',
            'Ensuring that your account is not shared with or accessed by any unauthorized individual',
            'Reporting lost, stolen, or compromised credentials to the System Administrator immediately',
            'All actions and transactions performed under your account',
            'Completing any required onboarding or orientation before operating within the System',
        ],
    },
    {
        id: 'rules',
        icon: ClipboardList,
        title: '3. Rules & Regulations',
        content: `All users must comply with the following rules when using the System:`,
        list: [
            'Use the System only for its intended operational and administrative purposes',
            'Do not attempt to access system areas, data, or functions beyond your assigned role',
            'Do not alter, falsify, or manipulate dispatch records, vehicle data, or route information',
            'Do not use the System to conduct unauthorized surveillance or data harvesting',
            'Adhere to all PITX internal policies, LTFRB regulations, and applicable Philippine laws',
            'Participate in system training and orientation programs as required by management',
        ],
    },
    {
        id: 'prohibited',
        icon: ShieldAlert,
        title: '4. Prohibited Activities',
        content: `The following activities are strictly prohibited and may result in immediate account suspension and legal action:`,
        list: [
            'Unauthorized access to, or tampering with, system infrastructure, databases, or logs',
            'Sharing, selling, or disclosing system data to third parties without written authorization',
            'Installing unauthorized software, scripts, or tools that interact with the System',
            'Attempting to bypass security controls, authentication mechanisms, or access restrictions',
            "Using the System for personal gain, fraud, or activities contrary to PITX's mission",
            'Deliberately disrupting system operations or introducing malicious content',
        ],
    },
    {
        id: 'agreements',
        icon: Gavel,
        title: '5. Agreements & Consent',
        content: `By using the System, you expressly agree to the following:`,
        list: [
            'Your activities within the System may be monitored, logged, and audited at any time',
            'PITX management reserves the right to review any activity conducted on the System',
            'Data entered into the System may be used for reporting, compliance, and operational analysis',
            'You consent to receive system-generated notifications, alerts, and communications',
            'You agree to promptly update your profile information if it changes',
        ],
    },
    {
        id: 'access-termination',
        icon: LogIn,
        title: '6. Access Termination',
        content: `PITX reserves the right to suspend or permanently terminate a user's access to the System under the following circumstances:`,
        list: [
            'Violation of any provision in these Terms and Conditions',
            'Unauthorized or malicious activity detected within the System',
            'Resignation, termination of employment, or end of contract with PITX',
            'Extended inactivity or failure to comply with mandatory security protocols',
            'Any other circumstance deemed to pose a risk to system integrity or user safety',
        ],
    },
    {
        id: 'liability',
        icon: TriangleAlert,
        title: '7. Limitation of Liability',
        content: `PITX shall not be held liable for any operational disruptions, data loss, or damages arising from user negligence, unauthorized use, or events beyond the control of PITX management. Users acknowledge that the System is provided as a tool for operational efficiency and that its use does not guarantee uninterrupted availability. Scheduled and emergency maintenance windows may result in temporary system downtime.`,
    },
    {
        id: 'amendments',
        icon: RefreshCw,
        title: '8. Amendments to These Terms',
        content: `PITX reserves the right to update or amend these Terms and Conditions at any time without prior notice. Any changes will be effective immediately upon publication within the System. Continued use of the System after an update constitutes your acceptance of the revised terms. Users are encouraged to review this document periodically to stay informed of any changes.`,
    },
    {
        id: 'contact',
        icon: Phone,
        title: '9. Contact & Enforcement',
        content: `For questions, clarifications, or concerns regarding these Terms and Conditions, please contact your designated PITX System Administrator through official PITX communication channels. Violations of these Terms will be handled in accordance with PITX's internal disciplinary procedures and applicable Philippine laws and regulations.`,
    },
];
</script>

<template>
    <Head title="PITX | Terms & Conditions" />

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
            <div class="absolute -top-10 -right-10 h-64 w-64 rounded-full bg-[#c0392b]/20 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/4 h-48 w-48 rounded-full bg-[#1a3a6b]/40 blur-3xl"></div>
            <div class="relative z-10 mx-auto max-w-7xl px-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#c0392b]/20 border border-[#c0392b]/30">
                        <ScrollText class="h-5 w-5 text-[#e74c3c]" />
                    </div>
                    <p class="text-xs font-bold uppercase tracking-widest text-red-400">Legal Document</p>
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight text-white md:text-4xl">
                    Terms & Conditions
                </h1>
                <p class="mt-2 text-sm text-blue-200 max-w-xl">
                    Please read these terms carefully before using the PITX Centralized Transport System. Your continued use constitutes acceptance.
                </p>
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

                
                <div class="mt-4 rounded-2xl border border-[#c0392b]/20 bg-[#c0392b]/5 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-[#c0392b] mb-2">Important Notice</p>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Violation of these terms may result in immediate account suspension and referral to appropriate authorities.
                    </p>
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
                    <a href="/privacy" class="text-gray-400 transition-colors hover:text-[#c0392b]">Privacy</a>
                    <a href="#" class="text-[#1a3a6b]">Terms</a>
                </div>
            </div>
        </footer>
    </div>
</template>