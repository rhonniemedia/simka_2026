<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\AsnPosition;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AsnPositionController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->header('HX-Request')) {
            // Kirim identitas tab langsung dari Controller
            return view('pages.admin.master.index', [
                'activeTab' => 'asn_positions',
                'fetchUrl'  => $request->fullUrl()
            ]);
        }

        // Menerapkan paginasi 10 data per halaman
        $asnPositions = AsnPosition::latest()->paginate(10);

        return view('pages.admin.master.partials.asn-positions', compact('asnPositions'));
    }

    public function create()
    {
        $item = null;
        return view('pages.admin.master.partials._asn-position-form', compact('item'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $this->validated($request);
        } catch (ValidationException $e) {
            return $this->respondWithError($e->errors());
        }

        AsnPosition::create($validated);

        return $this->respondWithSuccess('Jabatan Kepegawaian berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $item = AsnPosition::findOrFail($id);
        return view('pages.admin.master.partials._asn-position-form', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = AsnPosition::findOrFail($id);

        try {
            $validated = $this->validated($request, $id);
        } catch (ValidationException $e) {
            return $this->respondWithError($e->errors());
        }

        $item->update($validated);

        return $this->respondWithSuccess('Jabatan Kepegawaian berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = AsnPosition::findOrFail($id);
        $item->delete();

        return $this->respondWithSuccess('Jabatan Kepegawaian berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------
    */

    /**
     * Validasi input. $id diisi saat update, agar validasi unique
     * mengabaikan record yang sedang diedit.
     */
    private function validated(Request $request, $id = null): array
    {
        // Rule::unique(AsnPosition::class, ...) otomatis memakai nama tabel
        // yang benar-benar dipakai Model (staff_asn_positions), bukan
        // ditebak dari nama Model. Ini juga aman untuk primary key UUID.
        $nameRule = Rule::unique(AsnPosition::class, 'name');

        if ($id) {
            $nameRule = $nameRule->ignore($id);
        }

        return $request->validate([
            'position_type' => 'required|in:fungsional_keahlian,fungsional_keterampilan,pelaksana',
            'eligibility'   => 'required|in:pns,pppk,both',
            'name'          => ['required', 'string', 'max:255', $nameRule],
        ], [
            'name.unique' => 'Nama jabatan ini sudah terdaftar, gunakan nama lain.',
        ]);
    }

    private function respondWithSuccess(string $message)
    {
        return response('')
            ->header('HX-Trigger', json_encode([
                'close-modal' => true,
                'refreshAsnPositions' => true, // Trigger untuk memuat ulang tab
                'showAlert' => [
                    'icon'  => 'success',
                    'title' => 'Berhasil!',
                    'text'  => $message,
                ],
            ]));
    }

    /**
     * Response saat validasi gagal. Modal TIDAK ditutup (tidak ada
     * 'close-modal') supaya user bisa memperbaiki inputnya, tapi
     * tetap menampilkan alert error lewat mekanisme HX-Trigger yang
     * sama dengan pesan sukses.
     */
    private function respondWithError(array $errors)
    {
        $message = collect($errors)->flatten()->first()
            ?? 'Terjadi kesalahan, silakan periksa kembali data Anda.';

        return response('', 422)
            ->header('HX-Trigger', json_encode([
                'showAlert' => [
                    'icon'  => 'error',
                    'title' => 'Gagal!',
                    'text'  => $message,
                ],
            ]));
    }
}
