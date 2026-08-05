<?php

namespace App\Http\Controllers;

use App\Models\CalificacionEntrega;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CalificacionEntregaPdfController extends Controller
{
    public function __invoke(CalificacionEntrega $entrega): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user?->is_admin || (int) $entrega->user_id === (int) $user?->id, 403);
        abort_unless($entrega->pdf_disk && $entrega->pdf_path, 404, 'El comprobante no está disponible.');
        abort_unless(Storage::disk($entrega->pdf_disk)->exists($entrega->pdf_path), 404, 'El archivo PDF no existe.');

        return Storage::disk($entrega->pdf_disk)->download(
            $entrega->pdf_path,
            $entrega->folio.'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}
