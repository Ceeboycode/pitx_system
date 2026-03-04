<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index } from '@/routes/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Plus, Upload } from 'lucide-vue-next';
import { computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: index().url }];

interface Role {
    id: number;
    name: string;
    type: string;
}

interface Company {
    id: number;
    company_name: string;
    company_code: string;
}

interface User {
    id: number;
    username: string;
    name: string;
    email: string;
    phone_number: string | null;
    company_id: number | null;
    company: Company | null;
    roles: Role[];
}

const props = defineProps<{
    users: {
        data: User[];
        links: any;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search?: string | null;
        type?: string | null;
    };
}>();

function changeType(type: string | null) {
    router.get(
        index().url,
        { search: props.filters.search ?? '', type },
        { preserveScroll: true, only: ['users', 'filters', 'flash'] },
    );
}

/**
 *  Hide company column when filtering strictly internal
 */
const showCompanyColumn = computed(() => props.filters.type !== 'internal');

function roleBadgeClass(role: Role) {
    switch (role.type) {
        case 'internal':
            return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'external':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        default:
            return 'bg-muted text-muted-foreground';
    }
}
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-5">
                <CardHeader>
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <CardTitle>Users</CardTitle>
                            <CardDescription>
                                Manage users, assign roles, and control access.
                            </CardDescription>
                        </div>

                        <div class="flex items-center gap-2">
                            <Select
                                :model-value="props.filters.type ?? 'all'"
                                @update:model-value="
                                    (value) => {
                                        const v = String(value);
                                        changeType(v === 'all' ? null : v);
                                    }
                                "
                            >
                                <SelectTrigger class="w-32">
                                    <SelectValue placeholder="Filter Users" />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    <SelectItem value="internal"
                                        >Internal</SelectItem
                                    >
                                    <SelectItem value="external"
                                        >External</SelectItem
                                    >
                                </SelectContent>
                            </Select>

                            <Button size="sm" as-child>
                                <Link :href="create().url">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Create User
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="
                                    props.filters.type
                                        ? `${index().url}?type=${props.filters.type}`
                                        : index().url
                                "
                                :initial-value="props.filters.search"
                                placeholder="Search users..."
                                :only="['users', 'filters', 'flash']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex gap-2 sm:justify-end">
                            <Button size="sm" variant="outline">
                                <Upload class="mr-2 h-4 w-4" />
                                Import
                            </Button>

                            <Button size="sm" variant="outline">
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </div>
                    </div>

                    <Table>
                        <TableCaption>List of Users</TableCaption>

                        <TableHeader>
                            <TableRow>
                                <TableHead>Username</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>

                                <!--  Conditionally show company column -->
                                <TableHead v-if="showCompanyColumn">
                                    Company
                                </TableHead>

                                <TableHead>Roles</TableHead>
                                <TableHead class="text-right"
                                    >Actions</TableHead
                                >
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="user in props.users.data"
                                :key="user.id"
                            >
                                <TableCell class="font-medium">
                                    {{ user.username }}
                                </TableCell>

                                <TableCell>{{ user.name }}</TableCell>

                                <TableCell>{{ user.email }}</TableCell>

                                <!--  Show company only when allowed -->
                                <TableCell v-if="showCompanyColumn">
                                    {{
                                        user.roles.some(
                                            (r) => r.type === 'external',
                                        )
                                            ? (user.company?.company_name ??
                                              '-')
                                            : '-'
                                    }}
                                </TableCell>

                                <TableCell>
                                    <div
                                        class="flex flex-wrap gap-1 capitalize"
                                    >
                                        <Badge
                                            v-for="role in user.roles"
                                            :key="role.id"
                                            :class="roleBadgeClass(role)"
                                            class="border"
                                        >
                                            {{ role.name }}
                                        </Badge>
                                    </div>
                                </TableCell>

                                <TableCell class="text-right"> — </TableCell>
                            </TableRow>

                            <TableRow v-if="props.users.data.length === 0">
                                <TableCell
                                    :colspan="showCompanyColumn ? 6 : 5"
                                    class="py-8 text-center text-sm text-muted-foreground"
                                >
                                    No users found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <InertiaPagination
                        :links="props.users.links"
                        :meta="{
                            from: props.users.from,
                            to: props.users.to,
                            total: props.users.total,
                        }"
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
