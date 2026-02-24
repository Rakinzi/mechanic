<script lang="ts">
    type GalleryMedia = {
        id: number;
        original_url?: string;
        file_name?: string;
    };

    let { media }: { media: GalleryMedia[] } = $props();
</script>

{#if media.length === 0}
    <p class="text-sm text-muted-foreground">No photos uploaded yet.</p>
{:else}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        {#each media as item (item.id)}
            <a href={item.original_url} target="_blank" class="overflow-hidden rounded border">
                {#if item.original_url}
                    <img src={item.original_url} alt={item.file_name ?? 'Stage media'} class="h-32 w-full object-cover" />
                {/if}
            </a>
        {/each}
    </div>
{/if}
