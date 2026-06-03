import { writable } from 'svelte/store';

export const toastMessage = writable(null);

export function showToast(message, type = 'success', duration = 3000) {
    toastMessage.set({ message, type });
    setTimeout(() => {
        toastMessage.set(null);
    }, duration);
}
