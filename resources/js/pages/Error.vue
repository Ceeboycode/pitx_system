<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { dashboard, home } from '@/routes';
import { dashboard as companyDashboard } from '@/routes/company';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BusStopRafikiUrl from '@/components/assets/Bus-Stop-rafiki.svg';
import MaintenanceRafikiUrl from '@/components/assets/Maintenance-rafiki.svg';
import ServerRafikiUrl from '@/components/assets/Server-rafiki.svg';
import BugFixingRafikiUrl from '@/components/assets/Bug-Fixing-rafiki.svg';

import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/button/Button.vue';

const props = defineProps<{
    status: number;
}>();

const page = usePage();

const authUser = computed(() => page.props.auth?.user ?? null);

const dashboardHref = computed(() =>
    authUser.value?.role_type === 'external' ? companyDashboard() : dashboard(),
);

// const log = computed(
//     () =>
//         ({
//             403: '404 — Page Not Found',
//             404: '404 — Page Not Found',
//             500: '500 — Server Error',
//             503: '503 — Service Unavailable',
//         })[props.status] ?? `${props.status} — Something Went Wrong`,
// );

const title = computed(
    () =>
        ({
            // 403: '403 — Forbidden',
            // it is bad practice to tell users theyve hit a match but they're not authorized to access.
            403: 'Oops! You look a little lost.',
            404: 'Oops! You look a little lost.',
            500: 'Whoops, something went wrong on our servers.',
            503: 'Maintenance is ongoing.',
        })[props.status] ?? `It's not you, it's us.`,
);

const description = computed(
    () =>
        ({
            // 403: 'You do not have permission to access this page.',
            // it is bad practice to tell users theyve hit a match but they're not authorized to access.
            403: 'The page you are looking for does not exist. Check the address or head back to a known page.',
            404: 'The page you are looking for does not exist. Check the address or head back to a known page.',
            500: 'Please try again later.',
            503: 'The system is down for maintenance. Please check back soon.',
        })[props.status] ?? 'An unexpected error occurred. Please try again or contact admin for help.',
);

const svg = computed(
    () =>
        ({
            403: BusStopRafikiUrl,
            404: BusStopRafikiUrl,
            500: ServerRafikiUrl,
            503: MaintenanceRafikiUrl,
        })[props.status] ?? BugFixingRafikiUrl,
);
</script>

<template>
    <Head :title="`${title} — PITX`" />
    
    <!-- styled similar sa authbase without using THAT component -->
    <AuthLayout>
        <Card class="mx-auto w-full max-w-sm">
            <CardHeader class="py-3 flex flex-col items-center justify-center relative gap-y-2">
                <img
                    :src="svg"
                    alt=""
                    class="w-2/3 object-contain opacity-90"
                    aria-hidden="true"
                />
                <CardTitle class="text-2xl font-semibold">{{ title }}</CardTitle>
                <CardDescription class="text-sm text-center">{{ description }}</CardDescription>
            </CardHeader>

            <CardContent class="flex py-3 w-full gap-2 flex-col lg:flex-row">
                <!-- TODO: make sure that these buttons work. -->
                <Button
                    as-child
                    variant="float-primary"
                    class="w-full"
                >
                    <Link :href="home()">
                        Go back home
                    </Link>
                </Button>
                <Button
                    v-if="authUser"
                    as-child
                    variant="float-primary"
                    class="w-full"
                >
                    <Link :href="dashboardHref">
                        Dashboard
                    </Link>
                </Button>
            </CardContent>

            <CardFooter class="flex flex-row gap-x-1 justify-center text-xs py-3">
                <span>
                    Need help?
                </span>
                <TextLink href="/contact" class="text-xs text-custom-accent-3 font-semibold">
                    Contact support here.
                </TextLink>
            </CardFooter>
        </Card>
    </AuthLayout>
</template>
