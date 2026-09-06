<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function index()
    {
        return view('pages.admin.master.index', [
            'activeTab' => 'personnel_types',
            'fetchUrl'  => route('admin.master.personnel-types')
        ]);
    }
}
