<?php

namespace App\Http\Controllers;

use App\Models\Foto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FotoDescargaController extends Controller
{
    public function __invoke(Foto $foto): StreamedResponse
    {
        abort_unless(auth()->check() && ! auth()->user()->hasRole('operario'), 403);

        abort_unless(Storage::disk('public')->exists($foto->url), 404);

        return Storage::disk('public')->download($foto->url, $foto->nombreDescriptivo());
    }
}
