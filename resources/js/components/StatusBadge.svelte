<script lang="ts">
    let { status }: { status: string } = $props();

    const toneClass = $derived.by(() => {
        if (status === 'COMPLETED') {
            return 'bg-green-100 text-green-800';
        }

        if (status === 'OVERDUE') {
            return 'bg-red-100 text-red-800';
        }

        if (status === 'IN_PROGRESS') {
            return 'bg-blue-100 text-blue-800';
        }

        if (status === 'BLOCKED') {
            return 'bg-amber-100 text-amber-800';
        }

        return 'bg-gray-100 text-gray-800';
    });
</script>

<span class={`badge rounded-md px-2 py-1 text-xs font-semibold ${toneClass}`}>{status.replaceAll('_', ' ')}</span>
