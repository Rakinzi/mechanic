<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import type { BreadcrumbItem } from '@/types';
    import { store, update } from '@/routes/admin/stages';

    let { stages }: { stages: any[] } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Stage Configuration', href: '/admin/stages' }];
</script>

<AppHead title="Stage Configuration" />

<AdminLayout {breadcrumbs}>
    <section class="rounded-lg border p-4">
        <h2 class="mb-3 text-lg font-semibold">Add stage</h2>
        <Form {...store.form()} class="grid gap-3 md:grid-cols-5">
            <input name="name" placeholder="Name" class="rounded-md border px-3 py-2" required />
            <input name="sequence" type="number" placeholder="Sequence" class="rounded-md border px-3 py-2" required />
            <input name="sla_value" type="number" placeholder="SLA value" class="rounded-md border px-3 py-2" required />
            <label>
                <select name="sla_unit" class="w-full rounded-md border px-3 py-2">
                    <option value="hours">hours</option>
                    <option value="days">days</option>
                </select>
            </label>
            <button type="submit" class="btn preset-filled">Create</button>
        </Form>
    </section>

    <section class="mt-6 space-y-3">
        {#each stages as stage (stage.id)}
            <Form {...update.form({ stage: stage.id })} class="grid gap-3 rounded-lg border p-3 md:grid-cols-5">
                <input name="name" value={stage.name} class="rounded-md border px-3 py-2" required />
                <input name="sequence" type="number" value={stage.sequence} class="rounded-md border px-3 py-2" required />
                <input name="sla_value" type="number" value={stage.sla_value} class="rounded-md border px-3 py-2" required />
                <select name="sla_unit" class="w-full rounded-md border px-3 py-2">
                    <option value="hours" selected={stage.sla_unit === 'hours'}>hours</option>
                    <option value="days" selected={stage.sla_unit === 'days'}>days</option>
                </select>
                <button type="submit" class="btn preset-tonal">Update</button>
            </Form>
        {/each}
    </section>
</AdminLayout>
