<script setup lang="ts">
import PitxLogo from '@/components/assets/PITX.png';
import { home } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Terms & Conditions — PITX" />
    <div class="flex min-h-screen flex-col bg-[#f4f5f7] text-[#1a1a2e]">
        <!-- HEADER -->
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
            <h1 class="mb-2 text-3xl font-extrabold text-[#1a1a2e]">Terms &amp; Conditions</h1>
            <p class="mb-8 text-sm text-gray-400">Effective date: January 2026</p>

            <div class="space-y-8 text-sm leading-relaxed text-gray-700">
                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">1. Acceptance of Terms</h2>
                    <p>
                        By accessing or using the PITX System (including the Commuter App and the Web Admin System), you agree to be bound by these Terms and Conditions. If you do not agree to these terms, you may not use our services.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">2. Use of the Service</h2>
                    <p>You agree to use the PITX System only for lawful purposes. You must not:</p>
                    <ul class="mt-2 list-inside list-disc space-y-1 pl-2">
                        <li>Submit false or misleading information in any report or registration form.</li>
                        <li>Attempt to gain unauthorized access to any part of the system.</li>
                        <li>Use the system to harass, threaten, or harm any individual.</li>
                        <li>Interfere with or disrupt the operation of the PITX System.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">3. Accounts and Authentication</h2>
                    <p>
                        You are responsible for maintaining the confidentiality of your credentials. You agree to notify PITX immediately of any unauthorized use of your account. PITX reserves the right to terminate accounts that violate these terms.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">4. Commuter Reports</h2>
                    <p>
                        Reports submitted through the Commuter App are reviewed by PITX staff. We reserve the right to close, reject, or escalate reports at our discretion. Submission of false reports may result in account suspension.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">5. Intellectual Property</h2>
                    <p>
                        All content, branding, and software in the PITX System are the property of the Parañaque Integrated Terminal Exchange. You may not reproduce, distribute, or create derivative works without prior written consent.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">6. Limitation of Liability</h2>
                    <p>
                        PITX provides the system on an "as is" basis. We do not guarantee uninterrupted or error-free operation. To the maximum extent permitted by law, PITX shall not be liable for any indirect, incidental, or consequential damages arising from your use of the service.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">7. Modifications</h2>
                    <p>
                        PITX reserves the right to modify these Terms and Conditions at any time. Changes will be posted on this page. Continued use of the service after changes are posted constitutes acceptance.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">8. Governing Law</h2>
                    <p>
                        These Terms shall be governed by and construed in accordance with the laws of the Republic of the Philippines.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">9. Contact</h2>
                    <p>
                        For questions regarding these Terms, please visit our
                        <Link href="/contact" class="text-[#1a3a6b] underline underline-offset-2 hover:text-[#c0392b]">Contact page</Link>.
                    </p>
                </section>
            </div>
        </main>

        <footer class="border-t border-gray-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5 text-xs text-gray-400">
                <span>© 2026 PITX System. All rights reserved.</span>
                <div class="flex gap-4">
                    <Link href="/privacy" class="hover:text-[#1a3a6b]">Privacy</Link>
                    <Link href="/contact" class="hover:text-[#1a3a6b]">Contact</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
