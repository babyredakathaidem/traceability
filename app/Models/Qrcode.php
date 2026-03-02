<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Qrcode extends Model
{
    use BelongsToTenant;    
    protected $fillable = [
        'enterprise_id','batch_id','type','token',
        'place_name','allowed_lat','allowed_lng','allowed_radius_m',
        'first_scanned_at','expires_at',
    ];

    protected $casts = [
        'first_scanned_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function batch() { return $this->belongsTo(Batch::class); }
}
