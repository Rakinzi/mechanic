<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import { store, update } from '@/routes/admin/stages';
    import type { BreadcrumbItem } from '@/types';

    let { stages }: { stages: any[] } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Stage Configuration', href: '/admin/stages' }];
</script>

<AppHead title="Stage Configuration" />

<AdminLayout {breadcrumbs}>
    <section class="rounded-xl border bg-card p-5 shadow-sm">
        <h2 class="mb-4 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Add stage</h2>
        <Form {...store.form()} class="grid gap-3 sm:grid-cols-[1fr_100px_100px_120px_auto]">
            <input name="name" placeholder="Stage name" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" required />
            <input name="sequence" type="number" placeholder="Order" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" required />
            <input name="sla_value" type="number" placeholder="Duration" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" required />
            <select name="sla_unit" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                <option value="hours">Hours</option>
                <option value="days">Days</option>
            </select>
            <button type="submit" class="btn preset-filled whitespace-nowrap">Add stage</button>
        </Form>
    </section>

    <section class="mt-6 space-y-2">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Configured stages</h2>
        {#each stages as stage (stage.id)}
            <Form {...update.form({ stage: stage.id })} class="grid items-center gap-3 rounded-xl border bg-card px-4 py-3 shadow-sm sm:grid-cols-[1fr_100px_100px_120px_auto]">
                <input name="name" value={stage.name} class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" required />
                <input name="sequence" type="number" value={stage.sequence} class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" required />
                <input name="sla_value" type="number" value={stage.sla_value} class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" required />
                <select name="sla_unit" class="rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                    <option value="hours" selected={stage.sla_unit === 'hours'}>Hours</option>
                    <option value="days" selected={stage.sla_unit === 'days'}>Days</option>
                </select>
                <button type="submit" class="btn preset-tonal whitespace-nowrap">Save</button>
            </Form>
        {/each}
        {#if stages.length === 0}
            <div class="rounded-xl border border-dashed px-4 py-10 text-center">
                <p class="text-sm text-muted-foreground">No stages configured yet.</p>
            </div>
        {/if}
    </section>
</AdminLayout>
