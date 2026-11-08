<?php

namespace App\Http\Controllers\Admin\Career;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\Grade;
use App\Models\GradeHistory;
use App\Services\Staff\StaffDocumentService;
use App\Support\TableSort;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GradeHistoryController extends Controller
{
    /** Kode kategori dokumen untuk SK pangkat (tabel staff_document_categories). */
    private const SK_CATEGORY = 'sk_pangkat';

    public function index(Request $request)
    {
        $search        = $request->search;
        $filterGradeId = $request->filter_grade_id;
        $filterYear    = $request->filter_year;

        $sorter = TableSort::make($request, [
            'name'           => ['relation' => 'staff', 'column' => 'name'],
            'grade'          => ['relation' => 'grade', 'column' => 'grade_code'],
            'effective_date' => 'effective_date',
        ], default: 'name', defaultDirection: 'asc');

        $model = new GradeHistory;
        $table = $model->getTable();

        $latestId = DB::table("{$table} as latest")
            ->select('latest.id')
            ->whereColumn('latest.staff_id', "{$table}.staff_id")
            ->orderByDesc('latest.effective_date')
            ->orderByDesc('latest.id')
            ->limit(1);

        $query = GradeHistory::with(['staff.vault', 'grade', 'documents.category'])
            ->whereHas('staff', fn($s) => $s->completed()->active())
            ->where($model->qualifyColumn('id'), '=', $latestId)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->whereHas('staff', fn($s) => $s->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('grade', function ($g) use ($search) {
                            $g->where('grade_name', 'like', "%{$search}%")
                                ->orWhere('grade_code', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filterGradeId, fn($q) => $q->where($model->qualifyColumn('grade_id'), $filterGradeId))
            ->when($filterYear, fn($q) => $q->whereYear($model->qualifyColumn('effective_date'), $filterYear));

        $sorter->apply($query, then: $sorter->key() === 'name'
            ? ['staff_id', ['effective_date', 'desc']]
            : [['effective_date', 'desc']]);

        $histories = $query->paginate(10)->withQueryString();

        $grades = Grade::orderBy('grade_code')->get();

        $yearOptions = [];
        $currentYear = date('Y');
        for ($i = 0; $i < 5; $i++) {
            $yearOptions[] = $currentYear - $i;
        }

        if ($request->header('HX-Request')) {
            return view('pages.admin.career.grades.partials._table', compact('histories') + $sorter->viewData());
        }

        return view(
            'pages.admin.career.grades.index',
            compact('histories', 'search', 'grades', 'filterGradeId', 'yearOptions', 'filterYear') + $sorter->viewData()
        );
    }

    public function show(Request $request, $id)
    {
        $staff = Data::completed()->active()->with(['vault', 'employmentStatus'])->findOrFail($id);

        $histories = GradeHistory::with(['grade', 'documents.category'])
            ->where('staff_id', $id)
            ->orderBy('effective_date', 'desc')
            ->paginate(10);

        if ($request->header('HX-Request')) {
            return view('pages.admin.career.grades.show.partials._table', compact('staff', 'histories'));
        }

        return view('pages.admin.career.grades.show.index', compact('staff', 'histories'));
    }

    public function create($id)
    {
        $staff = Data::completed()->active()->findOrFail($id);
        $grades = Grade::orderBy('grade_code')->get();

        return view('pages.admin.career.grades.show.partials._modal-form', compact('staff', 'grades'));
    }

    public function store(Request $request, $id)
    {
        $staff = Data::completed()->active()->findOrFail($id);

        try {
            $validated = $this->validated($request, $staff);

            DB::transaction(function () use ($request, $staff, $validated) {
                $history = GradeHistory::create(
                    Arr::except($validated, ['file', 'verification_status']) + [
                        'staff_id'   => $staff->id,
                        'created_by' => Auth::id(),
                    ]
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
                        'text'  => 'Data kepangkatan berhasil ditambahkan.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.promotions.show', $id);
    }

    public function edit($staff_id, $history_id)
    {
        $staff = Data::completed()->active()->findOrFail($staff_id);
        $history = GradeHistory::where('staff_id', $staff_id)->findOrFail($history_id);
        $grades = Grade::orderBy('grade_code')->get();

        return view('pages.admin.career.grades.show.partials._modal-form', compact('staff', 'history', 'grades'));
    }

    public function update(Request $request, $staff_id, $history_id)
    {
        $staff = Data::completed()->active()->findOrFail($staff_id);
        $history = GradeHistory::where('staff_id', $staff_id)->findOrFail($history_id);

        try {
            $validated = $this->validated($request, $staff, $history_id);

            DB::transaction(function () use ($request, $history, $validated) {
                $history->update(
                    Arr::except($validated, ['file', 'verification_status']) + ['updated_by' => Auth::id()]
                );

                $history->load('grade');
                $status = $validated['verification_status'] ?? null;
                $documentName = $this->resolveDocumentName($history);

                $this->skDocuments($history)->update(array_merge(
                    $documentName ? ['document_name' => $documentName] : [],
                    [
                        'document_number' => $history->decree_number,
                        'document_date'   => $history->decree_date,
                        'updated_by'      => Auth::id(),
                    ],
                    filled($status) ? ['verification_status' => $status] : []
                ));

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
                        'text'  => 'Data kepangkatan berhasil diperbarui.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.promotions.show', $staff_id);
    }

    public function destroy(Request $request, $staff_id, $history_id)
    {
        Data::completed()->active()->findOrFail($staff_id);

        try {
            $history = GradeHistory::where('staff_id', $staff_id)->findOrFail($history_id);
            $history->delete();
        } catch (\Throwable $e) {
            report($e);

            if ($request->header('HX-Request')) {
                return response($this->show($request, $staff_id)->render())
                    ->header('HX-Trigger', json_encode([
                        'showAlert' => [
                            'icon'  => 'error',
                            'title' => 'Gagal!',
                            'text'  => 'Riwayat kepangkatan gagal dihapus. Silakan coba lagi.',
                        ],
                    ]));
            }

            return redirect()->route('admin.personnel.promotions.show', $staff_id)
                ->with('error', 'Riwayat kepangkatan gagal dihapus.');
        }

        if ($request->header('HX-Request')) {
            return response($this->show($request, $staff_id)->render())
                ->header('HX-Trigger', json_encode([
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Riwayat kepangkatan berhasil dihapus.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.promotions.show', $staff_id);
    }

    private function validated(Request $request, Data $staff, ?string $ignoreId = null): array
    {
        $unique = Rule::unique('staff_grade_histories')
            ->where(fn($query) => $query->where('staff_id', $staff->id));

        if ($ignoreId) {
            $unique->ignore($ignoreId);
        }

        return $request->validate([
            'grade_id'             => 'required|exists:staff_grades,id',
            'effective_date'       => ['required', 'date', $unique],
            'decree_number'        => ['nullable', 'string', 'max:255', 'required_with:file'],
            'decree_date'          => 'nullable|date',
            'service_period_years' => 'nullable|integer|min:0',
            'service_period_months' => 'nullable|integer|min:0|max:11',
            'approval_reference'   => 'nullable|string|max:255',
            'approval_date'        => 'nullable|date',
            'position_at_time'     => 'nullable|string|max:255',
            'verification_status'  => 'nullable|in:draft,verified,rejected',
            'file'                 => ['nullable', 'file', 'mimes:pdf', 'max:' . StaffDocumentService::MAX_SIZE_KB],
        ], [
            'grade_id.required'              => 'Golongan wajib dipilih.',
            'grade_id.exists'                => 'Golongan yang dipilih tidak valid.',
            'effective_date.required'        => 'TMT Pangkat wajib diisi.',
            'effective_date.date'            => 'Format TMT Pangkat tidak valid.',
            'effective_date.unique'          => 'Riwayat kepangkatan pada TMT (Tanggal) tersebut sudah ada untuk pegawai ini.',
            'decree_number.required_with'    => 'Nomor SK wajib diisi jika mengunggah berkas SK.',
            'verification_status.in'         => 'Status dokumen tidak valid.',
            'file.mimes'                     => 'Berkas SK harus berformat PDF.',
            'file.max'                       => 'Ukuran berkas SK maksimal 1 MB.',
            'file.uploaded'                  => 'Berkas SK gagal diunggah. Pastikan ukurannya maksimal 1 MB.',
        ]);
    }

    private function skDocuments(GradeHistory $history)
    {
        return $history->documents()
            ->whereHas('category', fn($c) => $c->where('code', self::SK_CATEGORY));
    }

    private function resolveDocumentName(GradeHistory $history): ?string
    {
        $gradeCode = $history->grade?->grade_code;
        return filled($gradeCode) ? 'Pangkat ' . $gradeCode : null;
    }

    private function storeSk(GradeHistory $history, Request $request, ?string $status = null): void
    {
        if (! $request->hasFile('file')) {
            return;
        }

        $this->skDocuments($history)->get()->each->delete();

        $history->load('grade');
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

    protected function respondWithModalErrors(Request $request, ValidationException $e, Data $staff, ?GradeHistory $history)
    {
        if ($request->header('HX-Request')) {
            $request->flash();

            $errorBag = new ViewErrorBag();
            $errorBag->put('default', $e->validator->errors());

            return response()
                ->view('pages.admin.career.grades.show.partials._modal-form', [
                    'staff'   => $staff,
                    'history' => $history,
                    'grades'  => Grade::orderBy('grade_code')->get(),
                    'errors'  => $errorBag,
                ], 200)
                ->header('HX-Retarget', '#modal-container')
                ->header('HX-Reswap', 'innerHTML');
        }

        throw $e;
    }
}
