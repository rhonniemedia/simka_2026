{{--
    Ini "shell" modal: cuma bertanggung jawab atas wrapper Alpine
    (x-data open/close + animasi buka) dan komponen <x-ui.modal>
    (backdrop, ukuran panel). Dipakai HANYA saat modal PERTAMA KALI
    dibuka (tombol "+ Tambah" / "Edit").

    Isi sebenarnya (header, form, footer) ada di
    _modal-form-content.blade.php dan dibungkus <div id="family-modal-content">.
    Tombol "Isi Otomatis dari Data Staff Ini" & "Tautkan" di
    _nik-check-result.blade.php me-reload ULANG HANYA div itu
    (hx-target="#family-modal-content"), BUKAN shell ini.

    Kenapa dipisah: kalau reload menimpa innerHTML #modal-container
    (shell ini beserta x-data & <x-ui.modal>-nya), maka setiap klik
    "Isi Otomatis"/"Tautkan" akan menghancurkan & membangun ulang
    instance Alpine modalnya dari nol - komponen lama tidak sempat
    dibersihkan dengan benar (terutama kalau <x-ui.modal> memindahkan
    elemennya ke tempat lain di DOM lewat x-teleport) sebelum node-nya
    dicabut duluan oleh htmx. Efeknya: backdrop lama menumpuk jadi makin
    gelap, dan ikon loading yang sempat aktif nyangkut permanen karena
    instance-nya sudah yatim piatu (orphan), tidak lagi diurus Alpine.
    Reload yang ditarget ke #family-modal-content saja tidak menyentuh
    shell/backdrop sama sekali, jadi masalah itu tidak terjadi.
--}}
<div x-data="{ open: false }"
    x-init="setTimeout(() => open = true, 10)"
    @close-modal.window="open = false; setTimeout(() => $el.closest('#modal-container').innerHTML = '', 150)">

    <x-ui.modal show="open" maxWidth="2xl">
        <div id="family-modal-content">
            @include('pages.admin.personnel.family.show.partials._modal-form-content', [
            'staff' => $staff,
            'relation' => $relation ?? null,
            'prefill' => $prefill ?? null,
            'linkToFamilyMemberId' => $linkToFamilyMemberId ?? null,
            ])
        </div>
    </x-ui.modal>
</div>