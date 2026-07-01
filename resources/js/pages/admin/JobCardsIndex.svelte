<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import Plus from 'lucide-svelte/icons/plus';
    import User from 'lucide-svelte/icons/user';
    import X from 'lucide-svelte/icons/x';
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
        technicians,
        admins,
        filters,
    }: {
        jobCards: { data: Array<Record<string, string | number | null>> };
        clients: Array<{ id: number; name: string }>;
        vehicles: Array<{ id: number; client_id: number; registration_number: string; make: string; model: string }>;
        stages: Array<{ id: number; name: string; sla_value: number; sla_unit: string }>;
        technicians: Array<{ id: number; name: string }>;
        admins: Array<{ id: number; name: string }>;
        filters: {
            status?: string | null;
            client_id?: number | null;
            vehicle_registration?: string | null;
            overdue_only?: boolean;
        };
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Job Cards', href: '/admin/job-cards' }];

    let showCreateForm = $state(false);
    let selectedClientId = $state<number | null>(null);
    let useNewVehicle = $state(false);

    const clientVehicles = $derived(
        selectedClientId
            ? vehicles.filter((v) => v.client_id === selectedClientId)
            : [],
    );
</script>

<AppHead title="Job Cards" />

<AdminLayout {breadcrumbs}>
    <!-- Filters -->
    <div class="rounded-xl border bg-card p-5 shadow-sm">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Filters</h2>
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
                class="btn preset-filled gap-1.5"
                onclick={() => (showCreateForm = !showCreateForm)}
            >
                {#if showCreateForm}
                    <X class="size-4" />
                    <span>Cancel</span>
                {:else}
                    <Plus class="size-4" />
                    <span>New job card</span>
                {/if}
            </button>
        </div>

        <!-- Create form (collapsible) -->
        {#if showCreateForm}
            <div class="mb-5 rounded-xl border bg-card p-5 shadow-sm">
                <h3 class="mb-4 text-xs font-semibold uppercase tracking-widest text-muted-foreground">New job card</h3>
                <Form {...store.form()} class="grid gap-3 md:grid-cols-2">
                    <label class="space-y-1 text-sm">
                        <span class="font-medium">Client</span>
                        <select
                            name="client_id"
                            class="w-full rounded-md border px-3 py-2"
                            onchange={(e) => {
                                const val = (e.target as HTMLSelectElement).value;
                                selectedClientId = val ? Number(val) : null;
                                useNewVehicle = false;
                            }}
                        >
                            <option value="">Select client</option>
                            {#each clients as client (client.id)}
                                <option value={client.id}>{client.name}</option>
                            {/each}
                        </select>
                    </label>

                    {#if selectedClientId !== null && clientVehicles.length > 0}
                        <div class="space-y-3 md:col-span-2">
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-dashed px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium">Vehicle selection</p>
                                    <p class="text-xs text-muted-foreground">
                                        This client already has {clientVehicles.length} registered vehicle{clientVehicles.length !== 1 ? 's' : ''}.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="btn preset-tonal"
                                    onclick={() => (useNewVehicle = !useNewVehicle)}
                                >
                                    {useNewVehicle ? 'Use existing vehicle' : 'Register another vehicle'}
                                </button>
                            </div>
                        </div>
                    {/if}

                    {#if selectedClientId !== null && (clientVehicles.length === 0 || useNewVehicle)}
                        <!-- Client has no vehicles — register one inline -->
                        <p class="text-sm font-medium text-amber-600 dark:text-amber-400 md:col-span-2">
                            {clientVehicles.length === 0
                                ? 'This client has no registered vehicles. Fill in the details below to register one.'
                                : 'Register a new vehicle for this client, then the job card will be created against it.'}
                        </p>
                        <label class="space-y-1 text-sm">
                            <span class="font-medium">Registration number <span class="text-red-500">*</span></span>
                            <input type="text" name="vehicle[registration_number]" class="w-full rounded-md border px-3 py-2" placeholder="e.g. AB12CDE" required />
                        </label>
                        <label class="space-y-1 text-sm">
                            <span class="font-medium">Make <span class="text-red-500">*</span></span>
                            <input type="text" name="vehicle[make]" class="w-full rounded-md border px-3 py-2" placeholder="e.g. Toyota" required />
                        </label>
                        <label class="space-y-1 text-sm">
                            <span class="font-medium">Model <span class="text-red-500">*</span></span>
                            <input type="text" name="vehicle[model]" class="w-full rounded-md border px-3 py-2" placeholder="e.g. Corolla" required />
                        </label>
                        <label class="space-y-1 text-sm">
                            <span class="font-medium">Year</span>
                            <input type="number" name="vehicle[model_year]" class="w-full rounded-md border px-3 py-2" placeholder="e.g. 2021" min="1950" max="2100" />
                        </label>
                        <label class="space-y-1 text-sm">
                            <span class="font-medium">Colour</span>
                            <input type="text" name="vehicle[color]" class="w-full rounded-md border px-3 py-2" placeholder="e.g. White" />
                        </label>
                        <label class="space-y-1 text-sm">
                            <span class="font-medium">VIN</span>
                            <input type="text" name="vehicle[vin]" class="w-full rounded-md border px-3 py-2" placeholder="Optional" />
                        </label>
                    {:else}
                        <label class="space-y-1 text-sm">
                            <span class="font-medium">Vehicle</span>
                            <select name="vehicle_id" class="w-full rounded-md border px-3 py-2" required={selectedClientId !== null}>
                                <option value="">
                                    {selectedClientId ? 'Select vehicle' : 'Select a client first'}
                                </option>
                                {#each clientVehicles as vehicle (vehicle.id)}
                                    <option value={vehicle.id}>{vehicle.registration_number} ({vehicle.make} {vehicle.model})</option>
                                {/each}
                            </select>
                            {#if selectedClientId !== null && clientVehicles.length > 0}
                                <span class="text-xs text-muted-foreground">Choose an existing vehicle or use “Register another vehicle”.</span>
                            {/if}
                        </label>
                    {/if}
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
                            <div class="grid gap-3 rounded-lg border p-3 md:grid-cols-[minmax(0,1fr)_220px_140px_120px]">
                                <input type="hidden" name={`selected_stages[${index}][stage_id]`} value={stage.id} />
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name={`selected_stages[${index}][enabled]`} value="1" />
                                    <span>{stage.name}</span>
                                </label>
                                <label class="space-y-1 text-sm">
                                    <span>Technicians &amp; Admins</span>
                                    <select name={`selected_stages[${index}][technician_ids][]`} multiple class="w-full rounded-md border px-3 py-2 min-h-[80px]">
                                        {#if technicians.length > 0}
                                            <optgroup label="Technicians">
                                                {#each technicians as person (person.id)}
                                                    <option value={person.id}>{person.name}</option>
                                                {/each}
                                            </optgroup>
                                        {/if}
                                        {#if admins.length > 0}
                                            <optgroup label="Admins">
                                                {#each admins as person (person.id)}
                                                    <option value={person.id}>{person.name}</option>
                                                {/each}
                                            </optgroup>
                                        {/if}
                                    </select>
                                    <span class="text-xs text-muted-foreground">Hold Ctrl/Cmd to select multiple</span>
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
            <div class="rounded-xl border border-dashed px-4 py-12 text-center">
                <p class="text-sm text-muted-foreground">No job cards match the current filters.</p>
            </div>
        {:else}
            <div class="space-y-2">
                {#each jobCards.data as jobCard (jobCard.id)}
                    <a
                        href={show({ jobCard: String(jobCard.uuid) }).url}
                        class="group flex items-center gap-4 rounded-xl border bg-card px-4 py-3.5 shadow-sm transition hover:shadow-md
                            {jobCard.status === 'OVERDUE' ? 'border-l-4 border-l-red-500' : 'border-l-4 border-l-transparent hover:border-l-primary'}"
                    >
                        <!-- Job number + vehicle -->
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-bold tracking-tight">{jobCard.job_number}</span>
                                <StatusBadge status={String(jobCard.status)} />
                                {#if jobCard.pending_delay_reports_count > 0}
                                    <span class="rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-semibold text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">
                                        {jobCard.pending_delay_reports_count} delay{Number(jobCard.pending_delay_reports_count) !== 1 ? 's' : ''}
                                    </span>
                                {/if}
                            </div>
                            <p class="mt-0.5 truncate text-sm text-muted-foreground">
                                {jobCard.client_name} &mdash; <span class="font-medium text-foreground">{jobCard.vehicle}</span>
                                {#if jobCard.registration_number}
                                    &nbsp;<span class="rounded border border-slate-400 bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-bold tracking-widest text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{jobCard.registration_number}</span>
                                {/if}
                            </p>
                        </div>

                        <!-- Stage info -->
                        <div class="hidden shrink-0 text-right sm:block">
                            <p class="text-xs font-semibold text-foreground">{jobCard.current_stage ?? 'Complete'}</p>
                            {#if jobCard.current_technicians}
                                <p class="mt-0.5 flex items-center justify-end gap-1 text-xs text-muted-foreground">
                                    <User class="size-3" />
                                    {jobCard.current_technicians}
                                </p>
                            {:else if jobCard.current_stage}
                                <p class="mt-0.5 text-xs font-medium text-amber-600 dark:text-amber-400">Unassigned</p>
                            {/if}
                        </div>

                        <ChevronRight class="size-4 shrink-0 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-primary" />
                    </a>
                {/each}
            </div>
        {/if}
    </div>
</AdminLayout>
