<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['building_id', 'room_number', 'qr_code_url'];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function assets()
    {
        return $this->hasMany(RoomAsset::class);
    }
}
