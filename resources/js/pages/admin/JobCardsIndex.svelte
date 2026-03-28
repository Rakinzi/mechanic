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
        mechanics,
        filters,
    }: {
        jobCards: { data: Array<Record<string, string | number | null>> };
        clients: Array<{ id: number; name: string }>;
        vehicles: Array<{ id: number; client_id: number; registration_number: string; make: string; model: string }>;
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
            <div class="mb-5 rounded-xl border bg-white p-5 shadow-sm dark:bg-slate-900">
                <h3 class="mb-4 text-base font-semibold">Create job card</h3>
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
                                    <span>Technicians</span>
                                    <select name={`selected_stages[${index}][mechanic_ids][]`} multiple class="w-full rounded-md border px-3 py-2 min-h-[80px]">
                                        {#each mechanics as mechanic (mechanic.id)}
                                            <option value={mechanic.id}>{mechanic.name}</option>
                                        {/each}
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
                        <div class="mt-2 flex items-center justify-between gap-4 text-xs text-muted-foreground">
                            <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
                                <span>
                                    Stage: <span class="font-medium text-foreground">{jobCard.current_stage ?? 'All complete'}</span>
                                </span>
                                {#if jobCard.current_stage_status && jobCard.current_stage_status !== 'NOT_STARTED'}
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold
                                        {jobCard.current_stage_status === 'IN_PROGRESS' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' :
                                         jobCard.current_stage_status === 'OVERDUE'     ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' :
                                         'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'}">
                                        {String(jobCard.current_stage_status).replace('_', ' ')}
                                    </span>
                                {/if}
                                {#if jobCard.current_mechanics}
                                    <span class="flex items-center gap-1 truncate">
                                        <User class="size-3.5 shrink-0" />
                                        <span class="truncate">{jobCard.current_mechanics}</span>
                                    </span>
                                {:else if jobCard.current_stage}
                                    <span class="italic text-amber-600 dark:text-amber-400">Unassigned</span>
                                {/if}
                                {#if jobCard.pending_delay_reports_count > 0}
                                    <span class="flex items-center gap-1 rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-semibold text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">
                                        {jobCard.pending_delay_reports_count} delay report{Number(jobCard.pending_delay_reports_count) !== 1 ? 's' : ''}
                                    </span>
                                {/if}
                            </div>
                            <span class="shrink-0 flex items-center gap-1 font-medium text-primary">
                                <span>Open</span>
                                <ChevronRight class="size-3.5" />
                            </span>
                        </div>
                    </a>
                {/each}
            </div>
        {/if}
    </div>
</AdminLayout>
