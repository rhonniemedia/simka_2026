import Alpine from 'alpinejs'
import collapse from '@alpinejs/collapse'
import htmx from 'htmx.org' // 1. Import sebagai objek modul

// 2. Expose ke global window agar bisa diakses Alpine.js & inline script
window.htmx = htmx

// Alpine
window.Alpine = Alpine
Alpine.plugin(collapse)
Alpine.start()

// Lucide
import { createIcons, icons } from 'lucide'

// Simpan referensi agar bisa dipanggil ulang dari mana saja (misal dari x-init Alpine)
window.renderIcons = () => createIcons({ icons })

// Event listeners untuk Lucide icons
document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons })
})

document.addEventListener('htmx:afterSwap', () => {
    createIcons({ icons })
})

document.addEventListener('htmx:afterSettle', () => {
    createIcons({ icons })
})

document.addEventListener('htmx:oobAfterSwap', () => {
    createIcons({ icons })
})