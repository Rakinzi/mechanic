<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { onDestroy, onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import StatusBadge from '@/components/StatusBadge.svelte';
    import ClientLayout from '@/layouts/ClientLayout.svelte';
    import { show } from '@/routes/client/repairs';
    import type { BreadcrumbItem } from '@/types';

    let { jobCards }: { jobCards: any[] } = $props();

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'My Repairs', href: '/client/repairs' }];

    let pollInterval: ReturnType<typeof setInterval> | null = null;

    onMount(() => {
        pollInterval = setInterval(() => {
            router.reload({
                only: ['jobCards'],
            });
        }, 45000);
    });

    onDestroy(() => {
        if (pollInterval) {
            clearInterval(pollInterval);
        }
    });

    const activeRepairs = $derived(jobCards.filter((jc) => jc.status !== 'COMPLETED'));
    const completedRepairs = $derived(jobCards.filter((jc) => jc.status === 'COMPLETED'));
</script>

<AppHead title="My Repairs" />

<ClientLayout {breadcrumbs}>
    {#if jobCards.length === 0}
        <div class="rounded-xl border bg-white p-8 text-center shadow-sm dark:bg-slate-900">
            <p class="text-2xl">🔧</p>
            <p class="mt-2 font-medium">No repairs yet</p>
            <p class="text-sm text-muted-foreground">Your repair history will appear here.</p>
        </div>
    {:else}
        {#if activeRepairs.length > 0}
            <div class="space-y-3">
                {#each activeRepairs as jobCard (jobCard.id)}
                    <a
                        href={show({ jobCard: jobCard.uuid }).url}
                        class="block rounded-xl border bg-white p-4 shadow-sm transition hover:shadow-md dark:bg-slate-900"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{jobCard.job_number}</p>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    {jobCard.vehicle.make} {jobCard.vehicle.model}
                                </p>
                            </div>
                            <StatusBadge status={jobCard.status} />
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                            <span>Stage: {jobCard.current_job_stage?.stage?.name ?? 'Completed'}</span>
                            <span class="font-medium text-primary">View progress →</span>
                        </div>
                    </a>
                {/each}
            </div>
        {/if}

        {#if completedRepairs.length > 0}
            <details class="mt-6 group">
                <summary class="flex cursor-pointer items-center gap-2 text-sm font-medium text-muted-foreground list-none">
                    <span class="transition group-open:rotate-90">▶</span>
                    Completed repairs ({completedRepairs.length})
                </summary>
                <div class="mt-3 space-y-2">
                    {#each completedRepairs as jobCard (jobCard.id)}
                        <a
                            href={show({ jobCard: jobCard.uuid }).url}
                            class="flex items-center justify-between rounded-lg border bg-muted/30 px-4 py-3 text-sm hover:bg-muted/50"
                        >
                            <span class="text-muted-foreground">
                                {jobCard.job_number} — {jobCard.vehicle.make} {jobCard.vehicle.model}
                            </span>
                            <StatusBadge status={jobCard.status} />
                        </a>
                    {/each}
                </div>
            </details>
        {/if}
    {/if}
</ClientLayout>
