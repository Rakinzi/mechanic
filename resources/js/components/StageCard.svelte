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
</script>

<div class="card rounded-lg border bg-white p-4 shadow-sm dark:bg-slate-900">
    <div class="mb-2 flex items-start justify-between gap-2">
        <h3 class="text-sm font-semibold">{title}</h3>
        <span class="badge rounded-md bg-slate-100 px-2 py-1 text-[10px] font-medium dark:bg-slate-800">{status}</span>
    </div>

    {#if description}
        <p class="text-sm text-muted-foreground">{description}</p>
    {/if}
</div>
