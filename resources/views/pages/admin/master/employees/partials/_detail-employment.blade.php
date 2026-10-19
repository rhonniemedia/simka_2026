{{-- File: resources/views/pages/admin/personnel/data/partials/_detail-employment.blade.php --}}
@php
$vault = $staff->vault ?? null;

// Format tanggal Indonesia; nilai kosong/tidak valid tidak boleh merusak modal.
$formatDate = function ($value) {
if (blank($value)) {
return '-';
}
try {
return \Carbon\Carbon::parse($value)->locale('id')->isoFormat('D MMMM Y');
} catch (\Throwable $e) {
return (string) $value;
}
};

$show = fn($v) => filled($v) ? $v : '-';

// Nama lengkap beserta gelar (dipakai di hero).
$fullName = trim(
(filled($staff->front_title) ? $staff->front_title . ' ' : '')
. $staff->name
. (filled($staff->back_title) ? ', ' . $staff->back_title : '')
);

$employment = $staff->employmentStatus;
$statusText = $employment?->name ?? $employment?->alias;

$priorEnabled = (string) $staff->prior_service_period === '1';

// Tiap item: [label, nilai, (opsional) keterangan kecil di samping nilai].
$sections = [
[
'title' => 'Status dan jabatan',
'icon' => 'briefcase',
'items' => [
['Status kepegawaian', $show($statusText)],
['Jenis pegawai', $show($staff->personnelType?->name)],
['Jabatan', $show($staff->position?->name)],
['Konsentrasi / jurusan', $show(($concentration ?? null)?->name)],
],
],
[
'title' => 'Nomor identitas kepegawaian',
'icon' => 'file-text',
'items' => [
['NIP', $show($vault?->nip)],
['NUPTK', $show($vault?->nuptk)],
],
],
[
'title' => 'Masa kerja',
'icon' => 'clock',
'items' => [
['Tanggal masuk / TMT status', $formatDate($staff->status_effective_date)],
['Peninjauan masa kerja', $priorEnabled ? 'Ya' : 'Tidak'],
['TMT masa kerja', $priorEnabled ? $formatDate($staff->prior_service_period_effective_date) : '-'],
],
],
];

$purpose = ['label' => 'Data kepegawaian', 'icon' => 'briefcase'];

$photoUrl = filled($staff->photo) ? \Storage::url($staff->photo) : null;
@endphp

@include('pages.admin.personnel.data.partials._detail-layout', compact('staff', 'fullName', 'purpose', 'sections', 'photoUrl'))