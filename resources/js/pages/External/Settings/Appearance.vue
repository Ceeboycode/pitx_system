<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import ExternalSettingsLayout from '@/layouts/external/SettingsLayout.vue';
import ExternalLayout from '@/layouts/ExternalLayout.vue';
</script>

<template>
    <ExternalLayout>
        <Head title="Appearance settings" />
        <h1 class="sr-only">Appearance Settings</h1>

        <ExternalSettingsLayout>
            <Card>
                <CardHeader>
                    <CardTitle>Appearance settings</CardTitle>
                    <CardDescription>Update your account's appearance settings</CardDescription>
                </CardHeader>

                <CardContent>
                    <AppearanceTabs />
                </CardContent>
            </Card>
        </ExternalSettingsLayout>
    </ExternalLayout>
</template>