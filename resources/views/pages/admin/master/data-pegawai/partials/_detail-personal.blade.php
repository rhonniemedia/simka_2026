{{-- File: resources/views/pages/admin/personnel/data/partials/_detail-personal.blade.php --}}
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

// Umur dari tanggal lahir (keterangan di samping tanggal lahir).
$ageText = null;
if (filled($vault?->dob)) {
try {
$ageText = (int) \Carbon\Carbon::parse($vault->dob)->age . ' tahun';
} catch (\Throwable $e) {
$ageText = null;
}
}

// Nama lengkap beserta gelar.
$fullName = trim(
(filled($staff->front_title) ? $staff->front_title . ' ' : '')
. $staff->name
. (filled($staff->back_title) ? ', ' . $staff->back_title : '')
);

// Jenis kelamin bisa berupa enum atau string, tergantung cast model.
$genderRaw = $staff->gender;
$gender = $genderRaw instanceof \BackedEnum ? $genderRaw : \App\Enums\Staff\Gender::tryFrom((string) $genderRaw);
$genderText = $gender?->label() ?? '-';

// Agama: data lama bisa berupa kode angka ("1"), jadi dipetakan lewat enum.
$religionText = \App\Enums\Staff\Religion::fromStored($vault?->religion)?->label()
?? (filled($vault?->religion) ? $vault->religion : '-');

$maritalText = match ((string) $staff->marital_dependents) {
'1' => 'Kawin dengan tanggungan',
'0' => 'Kawin/Belum tanpa tanggungan',
default => '-',
};

// Alamat: jalan + RT/RW dalam satu baris.
$rtRw = collect([
filled($vault?->rt) ? 'RT ' . $vault->rt : null,
filled($vault?->rw) ? 'RW ' . $vault->rw : null,
])->filter()->implode(' / ');
$addressText = collect([$vault?->address, $rtRw])->filter(fn($v) => filled($v))->implode(', ');

$salary = $vault?->base_salary;
$salaryText = is_numeric($salary) ? 'Rp ' . number_format((float) $salary, 0, ',', '.') : '-';

$show = fn($v) => filled($v) ? $v : '-';

// Tiap item: [label, nilai, (opsional) keterangan kecil di samping nilai].
$sections = [
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

$purpose = ['label' => 'Data pribadi', 'icon' => 'user'];

$photoUrl = filled($staff->photo) ? \Storage::url($staff->photo) : null;
@endphp

@include('pages.admin.personnel.data.partials._detail-layout', compact('staff', 'fullName', 'purpose', 'sections', 'photoUrl'))