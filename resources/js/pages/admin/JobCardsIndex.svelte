<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import { index, show, store } from '@/routes/admin/job-cards';
    import type { BreadcrumbItem } from '@/types';

    let {
        jobCards,
        clients,
        vehicles,
        stages,
        mechanics,
        filters,
    }: {
        jobCards: { data: Array<Record<string, string | number | null>> };
        clients: Array<{ id: number; name: string }>;
        vehicles: Array<{ id: number; registration_number: string; make: string; model: string }>;
        stages: Array<{ id: number; name: string; sla_value: number; sla_unit: string }>;
        mechanics: Array<{ id: number; name: string }>;
        filters: {
            status?: string | null;
            client_id?: number | null;
            vehicle_registration?: string | null;
            overdue_only?: boolean;
        };
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Job Cards', href: '/admin/job-cards' }];

    let showCreateForm = $state(false);
</script>

<AppHead title="Job Cards" />

<AdminLayout {breadcrumbs}>
    <!-- Filters -->
    <div class="rounded-xl border bg-white p-5 shadow-sm dark:bg-slate-900">
        <h2 class="mb-3 text-base font-semibold">Filters</h2>
        <form method="GET" action={index().url} class="grid gap-3 md:grid-cols-4">
            <label class="space-y-1 text-sm">
                <span class="font-medium">Status</span>
                <select name="status" class="w-full rounded-md border px-3 py-2">
                    <option value="">All statuses</option>
                    <option value="OPEN" selected={filters.status === 'OPEN'}>Open</option>
                    <option value="IN_PROGRESS" selected={filters.status === 'IN_PROGRESS'}>In Progress</option>
                    <option value="COMPLETED" selected={filters.status === 'COMPLETED'}>Completed</option>
                    <option value="CANCELLED" selected={filters.status === 'CANCELLED'}>Cancelled</option>
                </select>
            </label>
            <label class="space-y-1 text-sm">
                <span class="font-medium">Client</span>
                <select name="client_id" class="w-full rounded-md border px-3 py-2">
                    <option value="">All clients</option>
                    {#each clients as client (client.id)}
                        <option value={client.id} selected={filters.client_id === client.id}>{client.name}</option>
                    {/each}
                </select>
            </label>
            <label class="space-y-1 text-sm">
                <span class="font-medium">Registration</span>
                <input
                    type="text"
                    name="vehicle_registration"
                    placeholder="Search..."
                    class="w-full rounded-md border px-3 py-2"
                    value={filters.vehicle_registration ?? ''}
                />
            </label>
            <label class="flex items-center gap-2 pt-6 text-sm">
                <input type="checkbox" name="overdue_only" value="1" checked={filters.overdue_only === true} />
                <span class="font-medium">Overdue only</span>
            </label>
            <div class="flex items-center gap-2 md:col-span-4">
                <button type="submit" class="btn preset-filled">Apply filters</button>
                <a href={index().url} class="btn preset-tonal">Reset</a>
            </div>
        </form>
    </div>

    <!-- Job cards list -->
    <div class="mt-5">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-semibold">Job Cards ({jobCards.data.length})</h2>
            <button
                type="button"
                class="btn preset-filled"
                onclick={() => (showCreateForm = !showCreateForm)}
            >
                {showCreateForm ? '✕ Cancel' : '+ New job card'}
            </button>
        </div>

        <!-- Create form (collapsible) -->
        {#if showCreateForm}
            <div class="mb-5 rounded-xl border bg-white p-5 shadow-sm dark:bg-slate-900">
                <h3 class="mb-4 text-base font-semibold">Create job card</h3>
                <Form {...store.form()} class="grid gap-3 md:grid-cols-2">
                    <label class="space-y-1 text-sm">
                        <span class="font-medium">Client</span>
                        <select name="client_id" class="w-full rounded-md border px-3 py-2">
                            <option value="">Select client</option>
                            {#each clients as client (client.id)}
                                <option value={client.id}>{client.name}</option>
                            {/each}
                        </select>
                    </label>
                    <label class="space-y-1 text-sm">
                        <span class="font-medium">Vehicle</span>
                        <select name="vehicle_id" class="w-full rounded-md border px-3 py-2">
                            <option value="">Select vehicle</option>
                            {#each vehicles as vehicle (vehicle.id)}
                                <option value={vehicle.id}>{vehicle.registration_number} ({vehicle.make} {vehicle.model})</option>
                            {/each}
                        </select>
                    </label>
                    <label class="space-y-1 text-sm md:col-span-2">
                        <span class="font-medium">Customer complaint</span>
                        <textarea name="customer_complaint" class="min-h-20 w-full rounded-md border px-3 py-2" required></textarea>
                    </label>
                    <label class="space-y-1 text-sm md:col-span-2">
                        <span class="font-medium">Diagnosis notes</span>
                        <textarea name="diagnosis_notes" class="min-h-20 w-full rounded-md border px-3 py-2"></textarea>
                    </label>
                    <label class="space-y-1 text-sm">
                        <span class="font-medium">Promised delivery</span>
                        <input type="datetime-local" name="promised_delivery_at" class="w-full rounded-md border px-3 py-2" />
                    </label>

                    <div class="space-y-3 md:col-span-2">
                        <p class="text-sm font-medium">Repair route</p>
                        {#each stages as stage, index (stage.id)}
                            <div class="grid gap-3 rounded-lg border p-3 md:grid-cols-[minmax(0,1fr)_180px_140px_120px]">
                                <input type="hidden" name={`selected_stages[${index}][stage_id]`} value={stage.id} />
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name={`selected_stages[${index}][enabled]`} value="1" />
                                    <span>{stage.name}</span>
                                </label>
                                <label class="space-y-1 text-sm">
                                    <span>Technician</span>
                                    <select name={`selected_stages[${index}][assigned_mechanic_id]`} class="w-full rounded-md border px-3 py-2">
                                        <option value="">Unassigned</option>
                                        {#each mechanics as mechanic (mechanic.id)}
                                            <option value={mechanic.id}>{mechanic.name}</option>
                                        {/each}
                                    </select>
                                </label>
                                <label class="space-y-1 text-sm">
                                    <span>Duration</span>
                                    <input
                                        type="number"
                                        min="1"
                                        name={`selected_stages[${index}][planned_duration_value]`}
                                        class="w-full rounded-md border px-3 py-2"
                                        value={stage.sla_value}
                                    />
                                </label>
                                <label class="space-y-1 text-sm">
                                    <span>Unit</span>
                                    <select name={`selected_stages[${index}][planned_duration_unit]`} class="w-full rounded-md border px-3 py-2">
                                        <option value="hours" selected={stage.sla_unit === 'hours'}>Hours</option>
                                        <option value="days" selected={stage.sla_unit === 'days'}>Days</option>
                                    </select>
                                </label>
                            </div>
                        {/each}
                    </div>
                    <button type="submit" class="btn preset-filled md:col-span-2">Create job card</button>
                </Form>
            </div>
        {/if}

        {#if jobCards.data.length === 0}
            <div class="rounded-xl border bg-white p-8 text-center shadow-sm dark:bg-slate-900">
                <p class="text-muted-foreground">No job cards match the current filters.</p>
            </div>
        {:else}
            <div class="space-y-3">
                {#each jobCards.data as jobCard (jobCard.id)}
                    <a
                        href={show({ jobCard: String(jobCard.uuid) }).url}
                        class="block rounded-xl border bg-white p-4 shadow-sm transition hover:shadow-md dark:bg-slate-900
                            {jobCard.status === 'OVERDUE' ? 'border-red-300 dark:border-red-700' : ''}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold">{jobCard.job_number}</p>
                                <p class="mt-0.5 truncate text-sm text-muted-foreground">
                                    {jobCard.client_name} — {jobCard.vehicle}
                                </p>
                            </div>
                            <StatusBadge status={String(jobCard.status)} />
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                            <span>Stage: {jobCard.current_stage ?? 'All complete'}</span>
                            <span class="font-medium text-primary">Open →</span>
                        </div>
                    </a>
                {/each}
            </div>
        {/if}
    </div>
</AdminLayout>
