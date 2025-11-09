<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Place extends Model
{
    use HasFactory, HasSpatial;

    protected $fillable = ['name', 'location'];

    protected $spatialFields = [
        'location',
    ];
}
