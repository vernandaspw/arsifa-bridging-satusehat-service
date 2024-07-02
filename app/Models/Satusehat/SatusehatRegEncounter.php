<?php

namespace App\Models\Satusehat;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SatusehatRegEncounter extends Model
{
    use HasFactory;
    protected $connection = 'mysql_satusehat';
    protected $table = 'reg_encounters';
    protected $guarded = ['id'];
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->id = Str::uuid()->toString();
        });
    }
}
