{{-- File: resources/views/pages/admin/master/employees/partials/_detail.blade.php

     Modal detail pegawai (gabungan data pribadi + data kepegawaian), dipisah dengan tab.

     Variabel:
       $staff         : model Data (relasi vault, employmentStatus, personnelType, position)
       $concentration : (opsional) model konsentrasi
       $initialTab    : (opsional) 'personal' | 'employment'  (default 'personal')
--}}
@php
$vault = $staff->vault ?? null;

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

// Nama lengkap beserta gelar.
$fullName = trim(
(filled($staff->front_title) ? $staff->front_title . ' ' : '')
. $staff->name
. (filled($staff->back_title) ? ', ' . $staff->back_title : '')
);

/* ------------------------------ DATA PRIBADI ------------------------------ */
$ageText = null;
if (filled($vault?->dob)) {
try {
$ageText = (int) \Carbon\Carbon::parse($vault->dob)->age . ' tahun';
} catch (\Throwable $e) {
$ageText = null;
}
}

$genderRaw = $staff->gender;
$gender = $genderRaw instanceof \BackedEnum ? $genderRaw : \App\Enums\Staff\Gender::tryFrom((string) $genderRaw);
$genderText = $gender?->label() ?? '-';

$religionText = \App\Enums\Staff\Religion::fromStored($vault?->religion)?->label()
?? (filled($vault?->religion) ? $vault->religion : '-');

$maritalText = match ((string) $staff->marital_dependents) {
'1' => 'Kawin dengan tanggungan',
'0' => 'Kawin/Belum tanpa tanggungan',
default => '-',
};

$rtRw = collect([
filled($vault?->rt) ? 'RT ' . $vault->rt : null,
filled($vault?->rw) ? 'RW ' . $vault->rw : null,
])->filter()->implode(' / ');
$addressText = collect([$vault?->address, $rtRw])->filter(fn($v) => filled($v))->implode(', ');

$salary = $vault?->base_salary;
$salaryText = is_numeric($salary) ? 'Rp ' . number_format((float) $salary, 0, ',', '.') : '-';

$personalSections = [
[
'title' => 'Data pribadi',
'icon' => 'user',
'items' => [
['Nama lengkap', $fullName],
['Jenis kelamin', $genderText],
['NIK', $show($vault?->nik)],
['Tempat lahir', $show($vault?->pob)],
['Tanggal lahir', $formatDate($vault?->dob), $ageText],
['Agama', $religionText],
['Status kawin & tanggungan', $maritalText],
],
],
[
'title' => 'Kontak dan alamat',
'icon' => 'map-pin',
'items' => [
['Nomor telepon', $show($vault?->phone_number)],
['Email', $show($vault?->email)],
['Alamat', $show($addressText)],
['Desa / kelurahan', $show($vault?->village)],
['Kecamatan', $show($vault?->district)],
['Kabupaten / kota', $show($vault?->regency)],
['Provinsi', $show($vault?->province)],
],
],
[
'title' => 'Finansial',
'icon' => 'wallet',
'items' => [
['NPWP', $show($vault?->npwp)],
['Nomor rekening', $show($vault?->bank_account)],
['Gaji pokok', $salaryText],
],
],
];

/* ---------------------------- DATA KEPEGAWAIAN ---------------------------- */
$employment = $staff->employmentStatus;
$statusText = $employment?->name ?? $employment?->alias;
$priorEnabled = (string) $staff->prior_service_period === '1';

$employmentSections = [
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

$tabs = [
['key' => 'personal', 'label' => 'Data pribadi', 'icon' => 'user', 'sections' => $personalSections],
['key' => 'employment', 'label' => 'Data kepegawaian', 'icon' => 'briefcase', 'sections' => $employmentSections],
];

$initialTab = in_array($initialTab ?? null, ['personal', 'employment'], true) ? $initialTab : 'personal';
$photoUrl = filled($staff->photo) ? \Storage::url($staff->photo) : null;
@endphp

@include('pages.admin.master.employees.partials._detail-layout', compact('staff', 'fullName', 'tabs', 'initialTab', 'photoUrl'))