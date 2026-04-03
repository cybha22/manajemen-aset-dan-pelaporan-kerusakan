import { ref, provide, inject } from 'vue'

const TOAST_KEY = Symbol('toast')

export function provideToast() {
    const toastRef = ref(null)

    function showToast(message, type = 'error') {
        if (toastRef.value) {
            toastRef.value.show(message, type)
        }
    }

    provide(TOAST_KEY, { showToast })
    return { toastRef, showToast }
}

export function useToast() {
    const ctx = inject(TOAST_KEY, null)
    if (ctx) return ctx
    return { showToast: () => { } }
}
