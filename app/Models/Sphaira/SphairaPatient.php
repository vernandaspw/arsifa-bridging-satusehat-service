<?php

namespace App\Models\Sphaira;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SphairaPatient extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_Sphaira';
    protected $table = 'Patient';
    protected $guarded = ['MedicalNo'];
    protected $primaryKey = 'MedicalNo';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    // protected static function booted()
    // {
    //     static::creating(function ($model) {
    //         $model->id = \Str::uuid()->toString();
    //     });
    // }
}
