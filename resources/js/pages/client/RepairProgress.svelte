<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import PhotoGallery from '@/components/PhotoGallery.svelte';
    import StageTimeline from '@/components/StageTimeline.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';
    import ClientLayout from '@/layouts/ClientLayout.svelte';
    import type { BreadcrumbItem } from '@/types';

    let {
        jobCard,
        summaryUrl,
    }: {
        jobCard: any;
        summaryUrl: string;
    } = $props();

    const breadcrumbs = $derived<BreadcrumbItem[]>([
        { title: 'My Repairs', href: '/client/repairs' },
        { title: jobCard.job_number, href: `/client/repairs/${jobCard.uuid}` },
    ]);

    const allMedia = $derived(
        jobCard.job_stages.flatMap((stage: any) =>
            (stage.media ?? []).map((m: any) => ({ ...m, _stageName: stage.stage.name })),
        ),
    );

    const delayedStages = $derived(
        jobCard.job_stages.filter((stage: any) => (stage.delay_reports ?? []).length > 0),
    );
</script>

<AppHead title={`Repair ${jobCard.job_number}`} />

<ClientLayout {breadcrumbs}>
    <!-- Summary card -->
    <div class="rounded-xl border bg-white p-5 shadow-sm dark:bg-slate-900">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold">{jobCard.job_number}</h2>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    {jobCard.vehicle.make} {jobCard.vehicle.model}
                    <span class="font-mono text-xs">({jobCard.vehicle.registration_number})</span>
                </p>
            </div>
            <StatusBadge status={jobCard.status} />
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div class="rounded-lg bg-muted/40 px-3 py-2">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Current stage</p>
                <p class="mt-0.5 font-medium">{jobCard.current_job_stage?.stage?.name ?? 'All done'}</p>
            </div>
            <div class="rounded-lg bg-muted/40 px-3 py-2">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Est. completion</p>
                <p class="mt-0.5 font-medium">
                    {jobCard.promised_delivery_at
                        ? new Date(jobCard.promised_delivery_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
                        : 'To be confirmed'}
                </p>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2 border-t pt-4">
            <a class="btn preset-tonal" href={summaryUrl} target="_blank" rel="noopener noreferrer">View summary</a>
            <a class="btn preset-tonal" href={`${summaryUrl}?download=1`}>Download PDF</a>
        </div>
    </div>

    <!-- Repair timeline -->
    <div class="mt-5 rounded-xl border bg-white p-5 shadow-sm dark:bg-slate-900">
        <h3 class="mb-4 text-base font-semibold">Repair timeline</h3>
        <StageTimeline stages={jobCard.job_stages.map((stage: any) => ({
            id: stage.id,
            sequence: stage.sequence,
            name: stage.stage.name,
            status: stage.status,
            due_at: stage.due_at,
            planned_due_at: stage.planned_due_at,
            planned_duration_value: stage.planned_duration_value,
            planned_duration_unit: stage.planned_duration_unit,
            assigned_technician: stage.assigned_technician,
        }))} />
    </div>

    <!-- Delay updates -->
    {#if delayedStages.length > 0}
        <div class="mt-5 rounded-xl border bg-white p-5 shadow-sm dark:bg-slate-900">
            <h3 class="mb-4 text-base font-semibold">Delay updates</h3>
            <div class="space-y-3">
                {#each delayedStages as stage (stage.id)}
                    <div class="rounded-lg border p-3">
                        <p class="mb-2 text-sm font-medium">{stage.stage.name}</p>
                        {#each stage.delay_reports as report (report.id)}
                            <div class="mt-2 space-y-1 rounded-md bg-muted/30 px-3 py-2 text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-muted-foreground">{report.reason_category.replaceAll('_', ' ')}</span>
                                    <StatusBadge status={report.status} />
                                </div>
                                {#if report.proposed_eta}
                                    <p class="text-xs text-muted-foreground">
                                        New ETA: {new Date(report.proposed_eta).toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                    </p>
                                {/if}
                            </div>
                        {/each}
                    </div>
                {/each}
            </div>
        </div>
    {/if}

    <!-- Progress photos -->
    {#if allMedia.length > 0}
        <div class="mt-5 rounded-xl border bg-white p-5 shadow-sm dark:bg-slate-900">
            <h3 class="mb-4 text-base font-semibold">Progress photos</h3>
            {#each jobCard.job_stages.filter((s: any) => (s.media ?? []).length > 0) as stage (stage.id)}
                <div class="mb-4">
                    <p class="mb-2 text-sm font-medium text-muted-foreground">{stage.stage.name}</p>
                    <PhotoGallery media={stage.media ?? []} />
                </div>
            {/each}
        </div>
    {/if}
</ClientLayout>
