<script setup lang="ts">
import PitxLogo from '@/components/assets/PITX.png';
import { home } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { Mail, MapPin, Phone, Clock } from 'lucide-vue-next';
</script>

<template>
    <Head title="Contact — PITX" />
    <div class="flex min-h-screen flex-col bg-[#f4f5f7] text-[#1a1a2e]">
        
        <header class="sticky top-0 z-50 border-b border-gray-200 bg-white/90 shadow-sm backdrop-blur-md">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-6">
                <Link :href="home()" class="flex items-center gap-3">
                    <img :src="PitxLogo" alt="PITX Logo" class="h-8 w-auto object-contain" />
                    <span class="hidden text-sm font-bold uppercase tracking-wide text-[#1a1a2e] sm:block">PITX System</span>
                </Link>
                <Link :href="home()" class="text-sm text-gray-500 hover:text-[#1a3a6b]">← Back to Home</Link>
            </div>
        </header>

        <main class="mx-auto w-full max-w-3xl flex-1 px-6 py-12">
            <h1 class="mb-2 text-3xl font-extrabold text-[#1a1a2e]">Contact Us</h1>
            <p class="mb-10 text-sm text-gray-500">
                Reach out to the PITX system administrator for account access, technical issues, or general inquiries.
            </p>

            <div class="grid gap-5 sm:grid-cols-2">
                
                <div class="flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#1a3a6b]/10">
                        <MapPin class="h-5 w-5 text-[#1a3a6b]" />
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Address</p>
                        <p class="mt-1 text-sm font-medium text-[#1a1a2e]">Parañaque Integrated Terminal Exchange</p>
                        <p class="mt-0.5 text-sm text-gray-500">Dr. A. Santos Ave., Parañaque City, Metro Manila, Philippines</p>
                    </div>
                </div>

                
                <div class="flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#c0392b]/10">
                        <Mail class="h-5 w-5 text-[#c0392b]" />
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Email</p>
                        <p class="mt-1 text-sm font-medium text-[#1a1a2e]">System Administrator</p>
                        <a href="mailto:admin@pitx.gov.ph" class="mt-0.5 block text-sm text-[#1a3a6b] hover:underline">admin@pitx.gov.ph</a>
                    </div>
                </div>

                
                <div class="flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#1a3a6b]/10">
                        <Phone class="h-5 w-5 text-[#1a3a6b]" />
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Phone</p>
                        <p class="mt-1 text-sm font-medium text-[#1a1a2e]">Terminal Operations</p>
                        <p class="mt-0.5 text-sm text-gray-500">(02) 8888-PITX (7489)</p>
                    </div>
                </div>

                
                <div class="flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#c0392b]/10">
                        <Clock class="h-5 w-5 text-[#c0392b]" />
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Office Hours</p>
                        <p class="mt-1 text-sm font-medium text-[#1a1a2e]">Monday – Friday</p>
                        <p class="mt-0.5 text-sm text-gray-500">8:00 AM – 5:00 PM PHT</p>
                    </div>
                </div>
            </div>

            <div class="mt-10 rounded-2xl border border-[#1a3a6b]/20 bg-[#1a3a6b]/5 p-6">
                <p class="text-sm font-semibold text-[#1a3a6b]">For bus company account access or system onboarding:</p>
                <p class="mt-1 text-sm text-gray-600">
                    Visit the
                    <Link href="/company-registration" class="text-[#1a3a6b] underline underline-offset-2 hover:text-[#c0392b]">Company Registration</Link>
                    page or email us directly. An administrator will review your application and respond within 2–3 business days.
                </p>
            </div>
        </main>

        <footer class="border-t border-gray-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5 text-xs text-gray-400">
                <span>© 2026 PITX System. All rights reserved.</span>
                <div class="flex gap-4">
                    <Link href="/privacy" class="hover:text-[#1a3a6b]">Privacy</Link>
                    <Link href="/terms" class="hover:text-[#1a3a6b]">Terms</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
