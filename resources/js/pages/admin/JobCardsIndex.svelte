<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { Accordion } from '@skeletonlabs/skeleton-svelte';
    import AppHead from '@/components/AppHead.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import { index, store } from '@/routes/admin/job-cards';
    import type { BreadcrumbItem } from '@/types';

    let {
        jobCards,
        clients,
        vehicles,
        stages,
        mechanics,
        filters,
    }: {
        jobCards: { data: Array<Record<string, string | number>> };
        clients: Array<{ id: number; name: string }>;
        vehicles: Array<{ id: number; registration_number: string; make: string; model: string }>;
        stages: Array<{ id: number; name: string }>;
        mechanics: Array<{ id: number; name: string }>;
        filters: {
            status?: string | null;
            client_id?: number | null;
            vehicle_registration?: string | null;
            overdue_only?: boolean;
        };
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Job Cards', href: '/admin/job-cards' }];
</script>

<AppHead title="Job Cards" />

<AdminLayout {breadcrumbs}>
    <section class="space-y-3 rounded-lg border p-4">
        <h2 class="text-lg font-semibold">Filters</h2>
        <form method="GET" action={index().url} class="grid gap-3 md:grid-cols-4">
            <label class="space-y-1 text-sm">
                <span>Status</span>
                <select name="status" class="w-full rounded-md border px-3 py-2">
                    <option value="">All</option>
                    <option value="OPEN" selected={filters.status === 'OPEN'}>OPEN</option>
                    <option value="IN_PROGRESS" selected={filters.status === 'IN_PROGRESS'}>IN_PROGRESS</option>
                    <option value="COMPLETED" selected={filters.status === 'COMPLETED'}>COMPLETED</option>
                    <option value="CANCELLED" selected={filters.status === 'CANCELLED'}>CANCELLED</option>
                </select>
            </label>
            <label class="space-y-1 text-sm">
                <span>Client</span>
                <select name="client_id" class="w-full rounded-md border px-3 py-2">
                    <option value="">All clients</option>
                    {#each clients as client (client.id)}
                        <option value={client.id} selected={filters.client_id === client.id}>{client.name}</option>
                    {/each}
                </select>
            </label>
            <label class="space-y-1 text-sm">
                <span>Vehicle registration</span>
                <input
                    type="text"
                    name="vehicle_registration"
                    class="w-full rounded-md border px-3 py-2"
                    value={filters.vehicle_registration ?? ''}
                />
            </label>
            <label class="flex items-center gap-2 text-sm pt-6">
                <input type="checkbox" name="overdue_only" value="1" checked={filters.overdue_only === true} />
                Overdue only
            </label>
            <div class="md:col-span-4 flex items-center gap-2">
                <button type="submit" class="btn preset-filled">Apply</button>
                <a href={index().url} class="btn preset-tonal">Reset</a>
            </div>
        </form>
    </section>

    <section class="space-y-3 rounded-lg border p-4">
        <h2 class="text-lg font-semibold">Create job card</h2>
        <Form {...store.form()} class="grid gap-3 md:grid-cols-2">
            <label class="space-y-1 text-sm">
                <span>Client</span>
                <select name="client_id" class="w-full rounded-md border px-3 py-2">
                    <option value="">Select client</option>
                    {#each clients as client (client.id)}
                        <option value={client.id}>{client.name}</option>
                    {/each}
                </select>
            </label>
            <label class="space-y-1 text-sm">
                <span>Vehicle</span>
                <select name="vehicle_id" class="w-full rounded-md border px-3 py-2">
                    <option value="">Select vehicle</option>
                    {#each vehicles as vehicle (vehicle.id)}
                        <option value={vehicle.id}>{vehicle.registration_number} ({vehicle.make} {vehicle.model})</option>
                    {/each}
                </select>
            </label>
            <label class="space-y-1 text-sm md:col-span-2">
                <span>Customer complaint</span>
                <textarea name="customer_complaint" class="min-h-20 w-full rounded-md border px-3 py-2" required></textarea>
            </label>
            <label class="space-y-1 text-sm md:col-span-2">
                <span>Diagnosis notes</span>
                <textarea name="diagnosis_notes" class="min-h-20 w-full rounded-md border px-3 py-2"></textarea>
            </label>
            <label class="space-y-1 text-sm">
                <span>Promised delivery</span>
                <input type="datetime-local" name="promised_delivery_at" class="w-full rounded-md border px-3 py-2" />
            </label>

            {#each stages as stage (stage.id)}
                <label class="space-y-1 text-sm">
                    <span>{stage.name} mechanic</span>
                    <select name={`assigned_mechanics[${stage.id}]`} class="w-full rounded-md border px-3 py-2">
                        <option value="">Unassigned</option>
                        {#each mechanics as mechanic (mechanic.id)}
                            <option value={mechanic.id}>{mechanic.name}</option>
                        {/each}
                    </select>
                </label>
            {/each}
            <button type="submit" class="btn preset-filled md:col-span-2">Create job card</button>
        </Form>
    </section>

    <section class="mt-6 space-y-3">
        <h2 class="text-lg font-semibold">Job cards</h2>
        <Accordion multiple>
            {#each jobCards.data as jobCard (jobCard.id)}
                <Accordion.Item value={String(jobCard.id)} class="rounded-lg border px-3 py-2">
                    <Accordion.ItemTrigger class="flex w-full items-center justify-between text-left">
                        <span class="font-medium">{jobCard.job_number}</span>
                        <span class="text-sm text-muted-foreground">{jobCard.status}</span>
                    </Accordion.ItemTrigger>
                    <Accordion.ItemContent class="space-y-1 text-sm text-muted-foreground">
                        <p>Vehicle: {jobCard.vehicle}</p>
                        <p>Client: {jobCard.client_name}</p>
                        <a href={`/admin/job-cards/${jobCard.id}`} class="underline">Open job card</a>
                    </Accordion.ItemContent>
                </Accordion.Item>
            {/each}
        </Accordion>
    </section>
</AdminLayout>
