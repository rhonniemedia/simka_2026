<?php

namespace App\Http\Controllers\Admin\Career;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\PeriodicSalaryHistory;
use App\Services\PdfService;
use App\Services\SchoolProfile;
use App\Services\Staff\StaffDocumentService;
use App\Support\TableSort;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalariesHistoryController extends Controller
{
    /** Kode kategori dokumen untuk SK berkala (tabel staff_document_categories). */
    private const SK_CATEGORY = 'sk_berkala';

    public function index(Request $request)
    {
        $input = function (string $key) use ($request): string {
            $value = $request->input($key, '');

            return is_string($value) ? trim($value) : '';
        };

        $search       = $input('search');
        $filterStatus = $input('filter_verification_status');
        $filterYear   = $input('filter_year');

        $filterStatus = in_array($filterStatus, ['verified', 'draft', 'rejected'], true) ? $filterStatus : '';
        $filterYear   = ctype_digit($filterYear) ? $filterYear : '';

        $model = new PeriodicSalaryHistory;
        $table = $model->getTable();

        $sorter = TableSort::make($request, [
            'name'           => ['relation' => 'staff', 'column' => 'name'],
            'effective_date' => 'effective_date',
            'next_date'      => function (Builder $q, string $dir) use ($model) {
                [$start, $limit, $effective] = $this->periodicLimitSql($q);

                // Tanpa berkala berikutnya: tanpa tanggal mulai, tanpa golongan/batas, atau masa kerja + 2 tahun > batas
                $q->orderByRaw(
                    "case when {$start} is null or {$limit} is null"
                        . " or timestampdiff(month, {$start}, {$effective}) >= ({$limit} - 1) * 12"
                        . " then 1 else 0 end"
                );

                $q->orderBy($model->qualifyColumn('effective_date'), $dir);
            },
        ], default: 'name', defaultDirection: 'asc');

        $statusColumn = $model->qualifyColumn('verification_status');
        $effectiveCol = $model->qualifyColumn('effective_date');

        $latestId = DB::table("{$table} as latest")
            ->select('latest.id')
            ->whereColumn('latest.staff_id', "{$table}.staff_id")
            ->orderByDesc('latest.effective_date')
            ->orderByDesc('latest.id')
            ->limit(1);

        $query = PeriodicSalaryHistory::with(['staff.vault', 'staff.gradeHistories.grade.group', 'documents.category'])
            ->whereHas('staff', fn($s) => $s->completed()->active())
            ->where($model->qualifyColumn('id'), '=', $latestId)
            ->tap(fn($q) => $this->withinServiceLimit($q))
            ->when($search !== '', fn($q) => $q->whereHas(
                'staff',
                fn($s) => $s->where('name', 'like', "%{$search}%")
            ))
            ->when($filterStatus === 'draft', fn($q) => $q->where(
                fn($w) => $w->whereNull($statusColumn)->orWhereNotIn($statusColumn, ['verified', 'rejected'])
            ))
            ->when(in_array($filterStatus, ['verified', 'rejected'], true), fn($q) => $q->where($statusColumn, $filterStatus))
            ->when($filterYear !== '', function ($q) use ($effectiveCol, $filterYear) {
                $effective = $q->getQuery()->getGrammar()->wrap($effectiveCol);

                $q->whereRaw("year(date_add({$effective}, interval 2 year)) = ?", [$filterYear]);
            });

        $sorter->apply($query, then: $sorter->key() === 'name'
            ? ['staff_id', ['effective_date', 'desc']]
            : []);

        $histories = $query->paginate(10)->withQueryString();

        $filterVerificationStatus = $filterStatus;

        $yearOptions = PeriodicSalaryHistory::query()
            ->whereHas('staff', fn($s) => $s->completed()->active())
            ->where($model->qualifyColumn('id'), '=', $latestId)
            ->tap(fn($q) => $this->withinServiceLimit($q))
            ->selectRaw('year(date_add(' . $effectiveCol . ', interval 2 year)) as y')
            ->whereNotNull($effectiveCol)
            ->distinct()
            ->orderByDesc('y')
            ->pluck('y');

        if ($request->header('HX-Request')) {
            return view('pages.admin.career.salaries.partials._table', compact('histories') + $sorter->viewData());
        }

        return view(
            'pages.admin.career.salaries.index',
            compact('histories', 'search', 'filterVerificationStatus', 'filterYear', 'yearOptions') + $sorter->viewData()
        );
    }

    public function show(Request $request, $id)
    {
        $staff = Data::completed()->active()->with(['vault', 'employmentStatus', 'gradeHistories.grade.group'])->findOrFail($id);

        $histories = PeriodicSalaryHistory::with('documents.category')
            ->where('staff_id', $id)
            ->latest('effective_date')
            ->paginate(10);

        $histories->getCollection()->each->setRelation('staff', $staff);

        if ($request->header('HX-Request')) {
            return view('pages.admin.career.salaries.show.partials._table', compact('staff', 'histories'));
        }

        return view('pages.admin.career.salaries.show.index', compact('staff', 'histories'));
    }

    public function create($id)
    {
        $staff = Data::completed()->active()->findOrFail($id);

        return view('pages.admin.career.salaries.show.partials._modal-form', compact('staff'));
    }

    public function store(Request $request, $id)
    {
        $staff = Data::completed()->active()->findOrFail($id);

        try {
            $validated = $this->validated($request, $staff);

            DB::transaction(function () use ($request, $staff, $validated) {
                $history = $staff->periodicSalaries()->create(
                    Arr::except($validated, ['file', 'verification_status']) + ['created_by' => Auth::id()]
                );

                $this->storeSk($history, $request, $validated['verification_status'] ?? null);
            });
        } catch (ValidationException $e) {
            return $this->respondWithModalErrors($request, $e, $staff, null);
        }

        if ($request->header('HX-Request')) {
            return response($this->show($request, $id)->render())
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Data gaji berkala berhasil ditambahkan.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.periodic-salary.show', $id);
    }

    public function edit($staff_id, $history_id)
    {
        $staff = Data::completed()->active()->findOrFail($staff_id);
        $history = PeriodicSalaryHistory::where('staff_id', $staff_id)->findOrFail($history_id);

        return view('pages.admin.career.salaries.show.partials._modal-form', compact('staff', 'history'));
    }

    public function update(Request $request, $staff_id, $history_id)
    {
        $staff = Data::completed()->active()->findOrFail($staff_id);
        $history = PeriodicSalaryHistory::where('staff_id', $staff_id)->findOrFail($history_id);

        try {
            $validated = $this->validated($request, $staff, $history_id);

            DB::transaction(function () use ($request, $history, $validated) {
                $history->update(
                    Arr::except($validated, ['file', 'verification_status']) + ['updated_by' => Auth::id()]
                );

                $status = $validated['verification_status'] ?? null;

                $this->syncSkMeta($history, $status);
                $this->storeSk($history, $request, $status);
            });
        } catch (ValidationException $e) {
            return $this->respondWithModalErrors($request, $e, $staff, $history);
        }

        if ($request->header('HX-Request')) {
            return response($this->show($request, $staff_id)->render())
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Data gaji berkala berhasil diperbarui.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.periodic-salary.show', $staff_id);
    }

    public function destroy(Request $request, $staff_id, $history_id)
    {
        Data::completed()->active()->findOrFail($staff_id);

        try {
            $history = PeriodicSalaryHistory::where('staff_id', $staff_id)->findOrFail($history_id);
            $history->delete();
        } catch (\Throwable $e) {
            report($e);

            if ($request->header('HX-Request')) {
                return response($this->show($request, $staff_id)->render())
                    ->header('HX-Trigger', json_encode([
                        'showAlert' => [
                            'icon'  => 'error',
                            'title' => 'Gagal!',
                            'text'  => 'Riwayat gaji berkala gagal dihapus. Silakan coba lagi.',
                        ],
                    ]));
            }

            return redirect()->route('admin.personnel.periodic-salary.show', $staff_id)
                ->with('error', 'Riwayat gaji berkala gagal dihapus.');
        }

        if ($request->header('HX-Request')) {
            return response($this->show($request, $staff_id)->render())
                ->header('HX-Trigger', json_encode([
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Riwayat gaji berkala berhasil dihapus.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.periodic-salary.show', $staff_id);
    }

    public function generatePdf(Request $request, PdfService $pdf)
    {
        $year = $request->input('year');
        $year = is_string($year) && preg_match('/^\d{4}$/', $year) ? $year : null;
        $table = (new PeriodicSalaryHistory)->getTable();

        $latestId = DB::table("{$table} as latest")
            ->select('latest.id')
            ->whereColumn('latest.staff_id', "{$table}.staff_id")
            ->when($year, fn($q) => $q->whereRaw('year(date_add(latest.effective_date, interval 2 year)) = ?', [$year]))
            ->orderByDesc('latest.effective_date')
            ->limit(1);

        $query = PeriodicSalaryHistory::with(['staff.vault', 'staff.gradeHistories.grade.group'])
            ->whereHas('staff', fn($s) => $s->completed()->active())
            ->where("{$table}.id", '=', $latestId)
            ->tap(fn($q) => $this->withinServiceLimit($q));

        $rows = $query->get()->map(function ($h) {
            return [
                'name'        => $h->staff?->name ?? '-',
                'nip'         => $h->staff?->vault?->nip ?: '-',
                'tmt'         => $this->indonesianDate($h->effective_date),
                'masa_kerja'  => $h->masa_kerja,
                'tmt_berikut' => $this->indonesianDate($h->tmt_berikut),
                'raw_next'    => $h->tmt_berikut,
                'tahun_ke'    => $h->tahun_ke ?? '-',
            ];
        });

        $rows = $rows->sort(function ($a, $b) {
            if ($a['raw_next'] === $b['raw_next']) return 0;
            if ($a['raw_next'] === null) return 1;
            if ($b['raw_next'] === null) return -1;
            return $a['raw_next'] <=> $b['raw_next'];
        })->values();

        return $pdf->output(
            'pages.admin.pdf.periodic_salary',
            [
                'title'   => 'Daftar Kenaikan Gaji Berkala',
                'periode' => $year ?? 'Semua Tahun',
                'school'  => SchoolProfile::get()['name'],
                'rows'    => $rows,
            ],
            'kgb-periode-' . ($year ?? 'semua') . '-' . now()->format('Ymd') . '.pdf',
            $request->boolean('download'),
        );
    }

    /**
     * Potongan SQL untuk perhitungan batas masa kerja berkala:
     * [tanggal mulai kerja pegawai, batas masa kerja golongan pada TMT berkala, TMT berkala].
     * Batas diambil dari riwayat pangkat terbaru dengan TMT <= TMT berkala.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function periodicLimitSql(Builder $q): array
    {
        $g     = $q->getQuery()->getGrammar();
        $model = new PeriodicSalaryHistory;
        $staff = new Data;

        $effective = $g->wrap($model->qualifyColumn('effective_date'));
        $staffFk   = $g->wrap($model->qualifyColumn('staff_id'));

        $start = '(select ' . $g->wrap($staff->qualifyColumn('prior_service_period_effective_date'))
            . ' from ' . $g->wrapTable($staff->getTable())
            . ' where ' . $g->wrap($staff->getQualifiedKeyName()) . ' = ' . $staffFk
            . ' limit 1)';

        $limit = '(select gg.max_periodic_service_years'
            . ' from staff_grade_histories gh'
            . ' join staff_grades g on g.id = gh.grade_id'
            . ' join staff_grade_groups gg on gg.id = g.grade_group_id'
            . ' where gh.staff_id = ' . $staffFk
            . ' and gh.effective_date <= ' . $effective
            . ' order by gh.effective_date desc, gh.created_at desc limit 1)';

        return [$start, $limit, $effective];
    }

    /**
     * Sembunyikan pegawai yang sudah melampaui batas masa kerja berkala golongannya
     * (masa kerja + 2 tahun > batas). Pegawai yang belum punya riwayat pangkat / batas
     * atau tanggal mulai kerja tetap tampil, karena belum bisa dipastikan melampaui batas.
     */
    private function withinServiceLimit(Builder $q): Builder
    {
        [$start, $limit, $effective] = $this->periodicLimitSql($q);

        return $q->whereRaw(
            "not ({$start} is not null and {$limit} is not null"
                . " and timestampdiff(month, {$start}, {$effective}) >= ({$limit} - 1) * 12)"
        );
    }

    private function indonesianDate($value): string
    {
        return filled($value)
            ? Carbon::parse($value)->locale('id')->translatedFormat('j F Y')
            : '-';
    }

    private function validated(Request $request, Data $staff, ?string $ignoreId = null): array
    {
        $unique = Rule::unique('staff_periodic_salary_histories')
            ->where(fn($query) => $query->where('staff_id', $staff->id));

        if ($ignoreId) {
            $unique->ignore($ignoreId);
        }

        return $request->validate([
            'effective_date' => ['required', 'date', $unique],
            'decree_number'  => ['nullable', 'string', 'max:255', 'required_with:file'],
            'decree_date'    => ['required', 'date'],
            'base_salary'    => 'nullable|numeric',
            'verification_status' => 'nullable|in:draft,verified,rejected',
            'file'           => ['nullable', 'file', 'mimes:pdf', 'max:' . StaffDocumentService::MAX_SIZE_KB],
        ], [
            'effective_date.required'     => 'TMT Berkala (Terhitung Mulai Tanggal) wajib diisi.',
            'effective_date.unique'       => 'Riwayat gaji berkala pada TMT (Tanggal) tersebut sudah ada untuk pegawai ini.',
            'decree_date.required'        => 'Tanggal SK wajib diisi.',
            'decree_number.required_with' => 'Nomor SK wajib diisi jika mengunggah berkas SK.',
            'verification_status.in'      => 'Status dokumen tidak valid.',
            'file.mimes'                  => 'Berkas SK harus berformat PDF.',
            'file.max'                    => 'Ukuran berkas SK maksimal 1 MB.',
            'file.uploaded'               => 'Berkas SK gagal diunggah. Pastikan ukurannya maksimal 1 MB.',
        ]);
    }

    private function skDocuments(PeriodicSalaryHistory $history)
    {
        return $history->documents()
            ->whereHas('category', fn($c) => $c->where('code', self::SK_CATEGORY));
    }

    private function resolveDocumentName(PeriodicSalaryHistory $history): ?string
    {
        if (filled($history->effective_date)) {
            $year = Carbon::parse($history->effective_date)->format('Y');
            return 'Berkala ' . $year;
        }
        return null;
    }

    private function syncSkMeta(PeriodicSalaryHistory $history, ?string $status = null): void
    {
        $documentName = $this->resolveDocumentName($history);

        $meta = array_filter([
            'document_name'       => $documentName,
            'document_number'     => $history->decree_number,
            'document_date'       => $history->decree_date,
            'verification_status' => $status,
        ], fn($value) => filled($value));

        if ($meta) {
            $this->skDocuments($history)->update($meta + ['updated_by' => Auth::id()]);
        }
    }

    private function storeSk(PeriodicSalaryHistory $history, Request $request, ?string $status = null): void
    {
        if (! $request->hasFile('file')) {
            return;
        }

        $this->skDocuments($history)->get()->each->delete();

        $documentName = $this->resolveDocumentName($history);

        $history->attachDocument(
            $request->file('file'),
            self::SK_CATEGORY,
            array_merge(
                $documentName ? ['document_name' => $documentName] : [],
                array_filter(['verification_status' => $status], fn($value) => filled($value))
            )
        );
    }

    protected function respondWithModalErrors(Request $request, ValidationException $e, Data $staff, ?PeriodicSalaryHistory $history)
    {
        if ($request->header('HX-Request')) {
            $request->flash();

            $errorBag = new ViewErrorBag();
            $errorBag->put('default', $e->validator->errors());

            return response()
                ->view('pages.admin.career.salaries.show.partials._modal-form', [
                    'staff'   => $staff,
                    'history' => $history,
                    'errors'  => $errorBag,
                ], 200)
                ->header('HX-Retarget', '#modal-container')
                ->header('HX-Reswap', 'innerHTML');
        }

        throw $e;
    }
}
