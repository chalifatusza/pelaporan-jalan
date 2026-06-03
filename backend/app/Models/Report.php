<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $table = 'reports';

    protected $fillable = [
        'user_id',
        'kategori_id',
        'judul_laporan',
        'lokasi_jalan',
        'kecamatan',
        'deskripsi_kerusakan',
        'foto_path',
        'tingkat_kerusakan',
        'status',
        'latitude',
        'longitude',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }
}
