<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import ExternalSettingsLayout from '@/layouts/external/SettingsLayout.vue';
import ExternalLayout from '@/layouts/ExternalLayout.vue';
</script>

<template>
    <ExternalLayout>
        <Head title="Appearance settings" />

        <h1 class="sr-only">Appearance Settings</h1>

        <ExternalSettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Appearance settings"
                    description="Update your account's appearance settings"
                />
                <AppearanceTabs />
            </div>
        </ExternalSettingsLayout>
    </ExternalLayout>
</template>
