<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<img
    src="/images/branding/logo-light.jpeg"
    alt="Mechanic logo"
    class={`block rounded-sm object-cover dark:hidden ${className}`}
    {...rest}
/>
<img src="/images/branding/logo-dark.jpeg" alt="Mechanic logo" class={`hidden rounded-sm object-cover dark:block ${className}`} />
