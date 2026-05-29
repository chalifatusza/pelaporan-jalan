<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Laporan extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'laporan';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
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

    /**
     * Get the user that owns the report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
