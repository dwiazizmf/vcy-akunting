<script>
    import { confirmState } from '../Stores/confirmStore.js';
    import { Button } from '$lib/components/ui/button';
    import { AlertTriangle } from 'lucide-svelte';
    import { fade, scale } from 'svelte/transition';

    function close(confirmed) {
        if ($confirmState.resolve) {
            $confirmState.resolve(confirmed);
        }
        $confirmState.isOpen = false;
        setTimeout(() => {
            $confirmState.resolve = null;
        }, 300);
    }
</script>

{#if $confirmState.isOpen}
    <div class="fixed inset-0 z-[200] flex items-center justify-center p-4 sm:p-0">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" transition:fade={{ duration: 150 }} on:click={() => close(false)}></div>

        <!-- Modal -->
        <div 
            class="relative z-[201] w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden border border-slate-100" 
            transition:scale={{ start: 0.95, duration: 150 }}
        >
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <div class="flex-1 pt-1">
                        <h3 class="text-lg font-bold text-slate-900 mb-1">{$confirmState.title}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">
                            {$confirmState.message}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <Button variant="outline" class="bg-white border-slate-200 text-slate-700 hover:bg-slate-100 shadow-sm" on:click={() => close(false)}>
                    Batal
                </Button>
                <Button class="bg-red-600 hover:bg-red-700 text-white shadow-sm font-semibold" on:click={() => close(true)}>
                    Ya, Lanjutkan
                </Button>
            </div>
        </div>
    </div>
{/if}
