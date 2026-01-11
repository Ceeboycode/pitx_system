<script setup lang="ts">
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
    CardFooter,
} from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue';
</script>



<template>
    <Card class="w-full">
        <!-- Header -->
        <CardHeader class="flex flex-row items-center justify-between">
            <div>
                <CardTitle>Users</CardTitle>
                <CardDescription>
                    A list of all registered users
                </CardDescription>
            </div>

            <Button size="sm">
                Create New User
            </Button>
        </CardHeader>

        <!-- Table -->
        <CardContent>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>ID</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Roles</TableHead>
                        <TableHead>Created</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <!-- Example row -->
                    <TableRow>
                        <TableCell>1</TableCell>
                        <TableCell class="font-medium">
                            John Doe
                        </TableCell>
                        <TableCell>john@example.com</TableCell>
                        <TableCell>Admin</TableCell>
                        <TableCell>2024-01-01</TableCell>
                        <TableCell class="space-x-2 text-right">
                            <Button variant="outline" size="sm">
                                Edit
                            </Button>

                            <Button variant="destructive" size="sm">
                                Delete
                            </Button>
                        </TableCell>
                    </TableRow>

                    <!-- Empty state example -->
                    <!--
                    <TableRow>
                        <TableCell
                            colspan="6"
                            class="h-24 text-center text-muted-foreground"
                        >
                            No users found
                        </TableCell>
                    </TableRow>
                    -->
                </TableBody>
            </Table>
        </CardContent>

        <!-- Footer (UI only) -->
        <CardFooter class="flex items-center justify-between">
            <span class="text-sm text-muted-foreground">
                Page 1 of 1
            </span>

            <div class="flex items-center gap-1">
                <Button variant="outline" size="sm">Previous</Button>
                <Button variant="outline" size="sm">1</Button>
                <Button variant="outline" size="sm">Next</Button>
            </div>
        </CardFooter>
    </Card>
</template>
