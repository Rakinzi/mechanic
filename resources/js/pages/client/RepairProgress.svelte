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
            assigned_mechanic: stage.assigned_mechanic,
        }))} />
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
