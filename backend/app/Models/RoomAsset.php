<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomAsset extends Model
{
    protected $fillable = ['room_id', 'category_id', 'quantity', 'condition'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
