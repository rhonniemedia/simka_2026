import Alpine from 'alpinejs'
import collapse from '@alpinejs/collapse'
import 'htmx.org'

// Alpine
window.Alpine = Alpine
Alpine.plugin(collapse)
Alpine.start()

// Lucide
import { createIcons, icons } from 'lucide'

// Simpan referensi agar bisa dipanggil ulang dari mana saja (misal dari x-init Alpine)
window.renderIcons = () => createIcons({ icons })

// 1. Jalankan sekali saat DOM siap (initial page load)
document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons })
})

// 2. Jalankan ulang setiap kali htmx selesai menukar konten
//    (misal: search/filter tabel, buka modal via hx-get, dsb)
document.addEventListener('htmx:afterSwap', () => {
    createIcons({ icons })
})

// 3. Jalankan ulang setelah htmx selesai "settle"
//    (menjaga-jaga untuk kasus swap ke innerHTML seperti #modal-container)
document.addEventListener('htmx:afterSettle', () => {
    createIcons({ icons })
})

// 4. Jalankan ulang setiap ada perubahan DOM oob-swap (out of band)
//    berguna kalau ada bagian lain di luar target utama yang ikut berubah
document.addEventListener('htmx:oobAfterSwap', () => {
    createIcons({ icons })
})