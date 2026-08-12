<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documento extends Model
{
    use HasFactory;

    protected $fillable = [
        'obra_id',
        'tipo',
        'url',
    ];

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }
}
