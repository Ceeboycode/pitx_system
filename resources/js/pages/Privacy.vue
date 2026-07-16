<script setup lang="ts">
import PitxLogo from '@/components/assets/PITX.png';
import { home } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Privacy Policy — PITX" />
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
            <h1 class="mb-2 text-3xl font-extrabold text-[#1a1a2e]">Privacy Policy</h1>
            <p class="mb-8 text-sm text-gray-400">Last updated: January 2026</p>

            <div class="space-y-8 text-sm leading-relaxed text-gray-700">
                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">1. Information We Collect</h2>
                    <p>
                        The PITX Commuter App collects personal information you provide during registration, including your full name, email address, phone number, and username. We also collect usage data to improve the app experience, such as route search queries and session information.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">2. How We Use Your Information</h2>
                    <p>We use the information we collect to:</p>
                    <ul class="mt-2 list-inside list-disc space-y-1 pl-2">
                        <li>Provide and maintain the PITX Commuter App and web system.</li>
                        <li>Process your reports and respond to your inquiries through the CRM system.</li>
                        <li>Improve route information and terminal services.</li>
                        <li>Send important service-related notifications.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">3. Data Sharing</h2>
                    <p>
                        We do not sell, trade, or otherwise transfer your personal information to third parties. Your information is only accessible to authorized PITX terminal staff for the purpose of managing your account and responding to reports.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">4. Data Retention</h2>
                    <p>
                        Your personal data is retained for as long as your account is active. You may request deletion of your account by contacting our support team. Report threads and messages may be retained for a period of up to 2 years for audit and compliance purposes.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">5. Security</h2>
                    <p>
                        We implement industry-standard security measures to protect your personal information. All API communications are encrypted in transit. Passwords are hashed and never stored in plain text.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">6. Your Rights</h2>
                    <p>
                        You have the right to access, correct, or delete your personal information. To exercise these rights, please contact us using the information on our
                        <Link href="/contact" class="text-[#1a3a6b] underline underline-offset-2 hover:text-[#c0392b]">Contact page</Link>.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">7. Changes to This Policy</h2>
                    <p>
                        We may update this Privacy Policy from time to time. Any changes will be posted on this page with an updated revision date. Continued use of our services after changes are posted constitutes your acceptance of the updated policy.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-base font-bold text-[#1a1a2e]">8. Contact</h2>
                    <p>
                        If you have questions about this Privacy Policy, please contact us via our
                        <Link href="/contact" class="text-[#1a3a6b] underline underline-offset-2 hover:text-[#c0392b]">Contact page</Link>.
                    </p>
                </section>
            </div>
        </main>

        <footer class="border-t border-gray-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5 text-xs text-gray-400">
                <span>© 2026 PITX System. All rights reserved.</span>
                <div class="flex gap-4">
                    <Link href="/terms" class="hover:text-[#1a3a6b]">Terms</Link>
                    <Link href="/contact" class="hover:text-[#1a3a6b]">Contact</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
