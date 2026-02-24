<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import StageTimeline from '@/components/StageTimeline.svelte';
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import { approve, reject } from '@/routes/admin/delay-reports';
    import { close } from '@/routes/admin/job-cards';
    import type { BreadcrumbItem } from '@/types';

    let {
        jobCard,
        summaryUrl,
    }: {
        jobCard: any;
        summaryUrl: string;
    } = $props();

    const breadcrumbs = $derived<BreadcrumbItem[]>([
        { title: 'Job Cards', href: '/admin/job-cards' },
        { title: jobCard.job_number, href: `/admin/job-cards/${jobCard.id}` },
    ]);
</script>

<AppHead title={`Job ${jobCard.job_number}`} />

<AdminLayout {breadcrumbs}>
    <section class="space-y-2 rounded-lg border p-4">
        <h2 class="text-lg font-semibold">{jobCard.job_number}</h2>
        <p class="text-sm text-muted-foreground">Vehicle: {jobCard.vehicle.make} {jobCard.vehicle.model} ({jobCard.vehicle.registration_number})</p>
        <p class="text-sm text-muted-foreground">Client: {jobCard.vehicle.client.name}</p>
        <p class="text-sm">Complaint: {jobCard.customer_complaint}</p>
        <div class="flex flex-wrap items-center gap-2">
            <a class="btn preset-tonal" href={summaryUrl} target="_blank" rel="noopener noreferrer">Open PDF-ready summary</a>
            <a class="btn preset-tonal" href={`${summaryUrl}?download=1`}>Download summary</a>
            <Form {...close.form({ jobCard: jobCard.id })}>
                <button type="submit" class="btn preset-tonal">Close job card</button>
            </Form>
        </div>
    </section>

    <section class="mt-6 grid gap-4 md:grid-cols-[220px_1fr]">
        <aside class="h-fit rounded-lg border p-3">
            <p class="mb-2 text-sm font-semibold">Sections</p>
            <nav class="flex flex-col gap-2 text-sm">
                <a href="#timeline" class="rounded px-2 py-1 hover:bg-muted">Stage Timeline</a>
                <a href="#delays" class="rounded px-2 py-1 hover:bg-muted">Delay Reports</a>
            </nav>
        </aside>

        <div class="space-y-6">
            <div id="timeline" class="scroll-mt-24 space-y-3">
                <h3 class="text-base font-semibold">Stage Timeline</h3>
                <StageTimeline
                    stages={jobCard.job_stages.map((stage: any) => ({
                        id: stage.id,
                        sequence: stage.sequence,
                        name: stage.stage.name,
                        status: stage.status,
                        due_at: stage.due_at,
                        assigned_mechanic: stage.assigned_mechanic,
                    }))}
                />
            </div>

            <div id="delays" class="scroll-mt-24 space-y-3">
                <h3 class="text-base font-semibold">Delay Reports</h3>
                {#each jobCard.job_stages as stage (stage.id)}
                    {#each stage.delay_reports as report (report.id)}
                        <div class="rounded-lg border p-3">
                            <p class="font-medium">{stage.stage.name} - {report.status}</p>
                            <p class="text-sm">{report.explanation}</p>
                            <div class="mt-2 flex gap-2">
                                <Form {...approve.form({ delayReport: report.id })}>
                                    <button type="submit" class="btn preset-filled">Approve</button>
                                </Form>
                                <Form {...reject.form({ delayReport: report.id })}>
                                    <button type="submit" class="btn bg-red-600 text-white">Reject</button>
                                </Form>
                            </div>
                        </div>
                    {/each}
                {/each}
            </div>
        </div>
    </section>
</AdminLayout>
