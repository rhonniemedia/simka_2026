<?php

namespace App\Http\Controllers\Admin\Master\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmploymentStatus;
use App\Services\PdfService;
use App\Services\Personnel\EmployeeList;
use App\Services\Personnel\SeniorityList;
use App\Services\SchoolProfile;
use Carbon\Carbon;
use DateTimeImmutable;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /** Daftar Urut Kepangkatan (DUK). */
    public function seniority(Request $request, PdfService $pdf)
    {
        $today  = new DateTimeImmutable('today');
        $school = SchoolProfile::get();

        return $pdf->output(
            'pages.admin.pdf.seniority_list',
            [
                'title'     => 'Daftar Urut Kepangkatan',
                'school'    => $school['name'],
                'city'      => $school['city'],
                'headName'  => $school['head_name'],
                'headNip'   => $school['head_nip'],
                'printedAt' => Carbon::instance($today)->locale('id')->translatedFormat('j F Y'),
                'rows'      => SeniorityList::build($today),
            ],
            'duk-' . now()->format('Ymd') . '.pdf',
            $request->boolean('download'),
            'a4',
            'landscape'
        );
    }

    /** Daftar Pegawai (per kelompok, terurut kepangkatan). */
    public function employees(Request $request, PdfService $pdf)
    {
        $today = new DateTimeImmutable('today');

        $employmentInput = $request->input('employment');
        $employment = is_string($employmentInput) && $employmentInput !== ''
            ? EmploymentStatus::find($employmentInput)
            : null;

        $groups = EmployeeList::build($today, $employment?->id);

        return $pdf->output(
            'pages.admin.pdf.employee_list',
            [
                'title'       => 'Daftar Pegawai',
                'school'      => SchoolProfile::get()['name'],
                'printedAt'   => Carbon::instance($today)->locale('id')->translatedFormat('j F Y'),
                'year'        => $today->format('Y'),
                'filterLabel' => $employment ? 'Status Kepegawaian: ' . $employment->alias : null,
                'groups'      => $groups,
                'total'       => array_sum(array_map(fn($g) => count($g['rows']), $groups)),
            ],
            'daftar-pegawai-' . ($employment?->slug ?? 'semua') . '-' . now()->format('Ymd') . '.pdf',
            $request->boolean('download'),
            'a4',
            'portrait'
        );
    }
}
