import { createInertiaApp } from '@inertiajs/svelte'

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.svelte', { eager: true })
        return pages[`./Pages/${name}.svelte`]
    },
    setup({ el, App, props }) {
        new App({ target: el, props })
    },
    progress: {
        delay: 0, // Segera tampilkan
        color: '#FF0000', // Warna merah cerah yang sangat kontras
        includeCSS: true,
        showSpinner: true, // Tampilkan spinner loading juga
    },
})