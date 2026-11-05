<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PdfService
{
    public function output(
        string $view,
        array $data,
        string $filename,
        bool $download = false,
        string $paper = 'a4',
        string $orientation = 'portrait'
    ): Response {
        $pdf = Pdf::loadView($view, $data)->setPaper($paper, $orientation);

        return $download ? $pdf->download($filename) : $pdf->stream($filename);
    }
}
