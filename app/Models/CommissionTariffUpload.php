<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionTariffUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace',
        'file_name',
        'stored_path',
        'uploaded_by',
        'column_map',
        'status',
        'processed_rows',
        'matched_rows',
        'error_rows',
    ];

    protected $casts = [
        'column_map' => 'array',
        'processed_rows' => 'integer',
        'matched_rows' => 'integer',
        'error_rows' => 'integer',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function rows()
    {
        return $this->hasMany(CommissionTariffRow::class, 'upload_id');
    }
}
