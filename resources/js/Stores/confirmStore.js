import { writable } from 'svelte/store';

export const confirmState = writable({
    isOpen: false,
    title: '',
    message: '',
    resolve: null,
});

export function showConfirm(message, title = 'Konfirmasi') {
    return new Promise((resolve) => {
        confirmState.set({
            isOpen: true,
            title,
            message,
            resolve,
        });
    });
}
