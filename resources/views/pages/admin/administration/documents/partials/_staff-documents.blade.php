@php
$badge = [
'draft' => ['Draft', 'bg-slate-100 text-slate-700'],
'verified' => ['Terverifikasi', 'bg-emerald-100 text-emerald-700'],
'rejected' => ['Ditolak', 'bg-red-100 text-red-700'],
];
@endphp

@if ($documents->isEmpty())
<p class="text-sm text-secondary py-3 text-center">Belum ada dokumen untuk pegawai ini.</p>
@else
<div class="overflow-x-auto bg-white rounded-xl border border-border">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs uppercase tracking-wide text-secondary border-b border-border">
                <th class="px-4 py-2.5">Dokumen</th>
                <th class="px-4 py-2.5">Kategori</th>
                <th class="px-4 py-2.5">Tanggal</th>
                <th class="px-4 py-2.5">Status</th>
                <th class="px-4 py-2.5 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            @foreach ($documents as $doc)
            @php [$label, $cls] = $badge[$doc->verification_status] ?? [$doc->verification_status, 'bg-slate-100 text-slate-700']; @endphp
            <tr>
                <td class="px-4 py-3">
                    <p class="font-medium text-foreground">{{ $doc->document_name }}</p>
                    @if ($doc->document_number)
                    <p class="text-xs text-secondary">{{ $doc->document_number }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-secondary">{{ $doc->category?->name ?? '-' }}</td>
                <td class="px-4 py-3 text-secondary whitespace-nowrap">{{ $doc->document_date?->format('d M Y') ?? '-' }}</td>
                <td class="px-4 py-3">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $cls }}">{{ $label }}</span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-1.5">
                        <a href="{{ route('admin.personnel.documents.preview', $doc->id) }}" target="_blank" rel="noopener" title="Lihat"
                            class="size-8 flex items-center justify-center rounded-lg border border-border hover:bg-muted">
                            <i data-lucide="eye" class="size-4 text-secondary"></i>
                        </a>
                        <a href="{{ route('admin.personnel.documents.download', $doc->id) }}" title="Unduh"
                            class="size-8 flex items-center justify-center rounded-lg border border-border hover:bg-muted">
                            <i data-lucide="download" class="size-4 text-secondary"></i>
                        </a>
                        <button type="button" title="Edit"
                            hx-get="{{ route('admin.personnel.documents.edit', $doc->id) }}"
                            hx-target="#modal-container" hx-swap="innerHTML"
                            class="size-8 flex items-center justify-center rounded-lg border border-border hover:bg-muted cursor-pointer">
                            <i data-lucide="pencil" class="size-4 text-secondary"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>