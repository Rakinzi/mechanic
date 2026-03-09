<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import PhotoGallery from '@/components/PhotoGallery.svelte';
    import StageTimeline from '@/components/StageTimeline.svelte';
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
        { title: jobCard.job_number, href: `/client/repairs/${jobCard.id}` },
    ]);
</script>

<AppHead title={`Repair ${jobCard.job_number}`} />

<ClientLayout {breadcrumbs}>
    <section class="space-y-3 rounded-lg border p-4">
        <h2 class="text-lg font-semibold">{jobCard.job_number}</h2>
        <p class="text-sm text-muted-foreground">Vehicle: {jobCard.vehicle.make} {jobCard.vehicle.model} ({jobCard.vehicle.registration_number})</p>
        <p class="text-sm text-muted-foreground">Current stage: {jobCard.current_job_stage?.stage?.name ?? 'Completed'}</p>
        <p class="text-sm text-muted-foreground">Estimated completion: {jobCard.promised_delivery_at ? new Date(jobCard.promised_delivery_at).toLocaleString() : 'To be confirmed'}</p>
        <div class="flex flex-wrap gap-2">
            <a class="btn preset-tonal" href={summaryUrl} target="_blank" rel="noopener noreferrer">Open PDF-ready summary</a>
            <a class="btn preset-tonal" href={`${summaryUrl}?download=1`}>Download summary</a>
        </div>
    </section>

    <section class="mt-6">
        <h3 class="mb-3 text-base font-semibold">Repair timeline</h3>
        <StageTimeline stages={jobCard.job_stages.map((stage: any) => ({
            id: stage.id,
            sequence: stage.sequence,
            name: stage.stage.name,
            status: stage.status,
            due_at: stage.due_at,
            planned_due_at: stage.planned_due_at,
            planned_duration_value: stage.planned_duration_value,
            planned_duration_unit: stage.planned_duration_unit,
            assigned_mechanic: stage.assigned_mechanic,
        }))} />
    </section>

    <section class="mt-6 space-y-3 rounded-lg border p-4">
        <h3 class="text-base font-semibold">Delay updates</h3>
        {#each jobCard.job_stages.filter((stage: any) => (stage.delay_reports ?? []).length > 0) as stage (stage.id)}
            <div class="space-y-2 rounded-md border p-3">
                <p class="font-medium">{stage.stage.name}</p>
                {#each stage.delay_reports as report (report.id)}
                    <div class="text-sm text-muted-foreground">
                        <p>Status: {report.status}</p>
                        <p>Reason: {report.reason_category}</p>
                        <p>Updated ETA: {report.proposed_eta ? new Date(report.proposed_eta).toLocaleString() : 'N/A'}</p>
                    </div>
                {/each}
            </div>
        {/each}
    </section>

    <section class="mt-6">
        <h3 class="mb-3 text-base font-semibold">Progress photos</h3>
        {#each jobCard.job_stages as stage (stage.id)}
            <div class="mb-4 space-y-2">
                <p class="font-medium">{stage.stage.name}</p>
                <PhotoGallery media={stage.media ?? []} />
            </div>
        {/each}
    </section>
</ClientLayout>
