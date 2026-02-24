<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { Accordion } from '@skeletonlabs/skeleton-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import type { BreadcrumbItem } from '@/types';
    import { index as usersIndex } from '@/routes/admin/users';

    let {
        users,
        roles,
        filters,
    }: {
        users: {
            data: any[];
            links: Array<{ url: string | null; label: string; active: boolean }>;
            current_page: number;
            last_page: number;
            total: number;
        };
        roles: string[];
        filters: {
            search?: string | null;
            role?: string | null;
            is_active?: string | null;
        };
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'User Management', href: '/admin/users' }];
</script>

<AppHead title="User Management" />

<AdminLayout {breadcrumbs}>
    <section class="space-y-3 rounded-lg border p-4">
        <h2 class="text-lg font-semibold">Search & Filters</h2>
        <form method="GET" action={usersIndex().url} class="grid gap-3 md:grid-cols-4">
            <label class="space-y-1 text-sm md:col-span-2">
                <span>Search (name/email/phone)</span>
                <input type="text" name="search" class="w-full rounded-md border px-3 py-2" value={filters.search ?? ''} />
            </label>
            <label class="space-y-1 text-sm">
                <span>Role</span>
                <select name="role" class="w-full rounded-md border px-3 py-2">
                    <option value="">All roles</option>
                    {#each roles as role (role)}
                        <option value={role} selected={filters.role === role}>{role}</option>
                    {/each}
                </select>
            </label>
            <label class="space-y-1 text-sm">
                <span>Status</span>
                <select name="is_active" class="w-full rounded-md border px-3 py-2">
                    <option value="">All</option>
                    <option value="1" selected={filters.is_active === '1'}>Active</option>
                    <option value="0" selected={filters.is_active === '0'}>Inactive</option>
                </select>
            </label>
            <div class="md:col-span-4 flex items-center gap-2">
                <button type="submit" class="btn preset-filled">Apply</button>
                <a href={usersIndex().url} class="btn preset-tonal">Reset</a>
            </div>
        </form>
    </section>

    <section class="space-y-3 rounded-lg border p-4">
        <h2 class="text-lg font-semibold">Create User</h2>
        <Form action="/admin/users" method="post" class="grid gap-3 md:grid-cols-2">
            <label class="space-y-1 text-sm">
                <span>Name</span>
                <input type="text" name="name" required class="w-full rounded-md border px-3 py-2" />
            </label>
            <label class="space-y-1 text-sm">
                <span>Email</span>
                <input type="email" name="email" required class="w-full rounded-md border px-3 py-2" />
            </label>
            <label class="space-y-1 text-sm">
                <span>Phone</span>
                <input type="text" name="phone" class="w-full rounded-md border px-3 py-2" />
            </label>
            <label class="space-y-1 text-sm">
                <span>Role</span>
                <select name="role" required class="w-full rounded-md border px-3 py-2">
                    {#each roles as role (role)}
                        <option value={role}>{role}</option>
                    {/each}
                </select>
            </label>
            <label class="space-y-1 text-sm md:col-span-2">
                <span>Password</span>
                <input type="password" name="password" required class="w-full rounded-md border px-3 py-2" />
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" checked />
                Active
            </label>
            <div class="md:col-span-2">
                <button type="submit" class="btn preset-filled">Create user</button>
            </div>
        </Form>
    </section>

    <section class="mt-6 space-y-3">
        <h2 class="text-lg font-semibold">Manage Users ({users.total})</h2>
        <Accordion multiple>
            {#each users.data as user (user.id)}
                <Accordion.Item value={String(user.id)} class="rounded-lg border px-3 py-2">
                    <Accordion.ItemTrigger class="flex w-full items-center justify-between text-left">
                        <span class="font-medium">{user.name} ({user.email})</span>
                        <span class="text-sm text-muted-foreground">{user.roles.join(', ')}</span>
                    </Accordion.ItemTrigger>
                    <Accordion.ItemContent class="space-y-3 pt-3">
                        <Form action={`/admin/users/${user.id}`} method="post" class="grid gap-3 md:grid-cols-2">
                            <input type="hidden" name="_method" value="PUT" />
                            <label class="space-y-1 text-sm">
                                <span>Name</span>
                                <input type="text" name="name" required class="w-full rounded-md border px-3 py-2" value={user.name} />
                            </label>
                            <label class="space-y-1 text-sm">
                                <span>Email</span>
                                <input type="email" name="email" required class="w-full rounded-md border px-3 py-2" value={user.email} />
                            </label>
                            <label class="space-y-1 text-sm">
                                <span>Phone</span>
                                <input type="text" name="phone" class="w-full rounded-md border px-3 py-2" value={user.phone ?? ''} />
                            </label>
                            <label class="space-y-1 text-sm">
                                <span>Role</span>
                                <select name="role" required class="w-full rounded-md border px-3 py-2">
                                    {#each roles as role (role)}
                                        <option value={role} selected={user.roles.includes(role)}>{role}</option>
                                    {/each}
                                </select>
                            </label>
                            <label class="space-y-1 text-sm md:col-span-2">
                                <span>New Password (optional)</span>
                                <input type="password" name="password" class="w-full rounded-md border px-3 py-2" />
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_active" value="1" checked={user.is_active} />
                                Active
                            </label>
                            <div class="md:col-span-2 flex items-center gap-2">
                                <button type="submit" class="btn preset-filled">Save</button>
                            </div>
                        </Form>

                        <Form action={`/admin/users/${user.id}`} method="post">
                            <input type="hidden" name="_method" value="DELETE" />
                            <button type="submit" class="btn bg-red-600 text-white">Delete user</button>
                        </Form>
                    </Accordion.ItemContent>
                </Accordion.Item>
            {/each}
        </Accordion>

        <div class="flex flex-wrap items-center gap-2 pt-2">
            {#each users.links as link (link.label)}
                {#if link.url}
                    <a
                        href={link.url}
                        class={`rounded-md border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : ''}`}
                    >
                        {@html link.label}
                    </a>
                {:else}
                    <span class="rounded-md border px-3 py-1 text-sm text-muted-foreground">{@html link.label}</span>
                {/if}
            {/each}
        </div>
    </section>
</AdminLayout>
