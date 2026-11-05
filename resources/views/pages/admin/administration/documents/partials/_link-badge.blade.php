{{--
    Keterangan tautan satu dokumen: "Terkait ..." / "Belum bertaut" / "Mandiri".
    Aturannya sama dengan scope Document::linkStatus() yang dipakai filter.
    Param: $item (Document)
--}}
@php
if ($item->linked_record_id) {
$label = null;

try {
$label = $item->linkedRecord?->documentLabel();
} catch (\Throwable $e) {
$label = null;
}

$info = $label
? ['text' => 'text-slate-600', 'icon' => 'link', 'label' => $label]
: ['text' => 'text-amber-600', 'icon' => 'unlink', 'label' => 'Data terkait tidak ditemukan'];
} elseif (filled($item->category?->linked_table)) {
$info = ['text' => 'text-amber-600', 'icon' => 'unlink', 'label' => 'Belum bertaut'];
} else {
$info = ['text' => 'text-secondary', 'icon' => 'file', 'label' => 'Mandiri'];
}
@endphp
<div class="flex items-center gap-1.5 text-xs {{ $info['text'] }} min-w-0">
    <i data-lucide="{{ $info['icon'] }}" class="size-3 shrink-0 pointer-events-none"></i>
    <span class="truncate">{{ $info['label'] }}</span>
</div>