<script lang="ts">
    let {
        title,
        status,
        description,
    }: {
        title: string;
        status: string;
        description?: string;
    } = $props();

    const borderColor = $derived.by(() => {
        if (status === 'COMPLETED' || status === 'APPROVED') return 'border-l-green-500';
        if (status === 'OVERDUE' || status === 'REJECTED') return 'border-l-red-500';
        if (status === 'IN_PROGRESS') return 'border-l-blue-500';
        if (status === 'BLOCKED') return 'border-l-amber-500';
        if (status === 'OPEN') return 'border-l-primary';
        return 'border-l-slate-300 dark:border-l-slate-600';
    });

    const numericValue = $derived(description && !isNaN(Number(description)) ? Number(description) : null);
</script>

<div class="relative flex flex-col justify-between overflow-hidden rounded-xl border border-l-4 bg-card p-5 shadow-sm {borderColor}">
    <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">{title}</p>
    {#if numericValue !== null}
        <p class="mt-2 text-4xl font-black tracking-tight text-foreground">{numericValue}</p>
    {:else if description}
        <p class="mt-2 text-sm text-muted-foreground">{description}</p>
    {/if}
</div>
