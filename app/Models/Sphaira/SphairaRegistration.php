<?php

namespace App\Models\Sphaira;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SphairaRegistration extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_Sphaira';
    protected $table = 'Registration';
    protected $guarded = ['RegistrationNo'];
    // protected $primaryKey = 'RegistrationNo';
    protected $primaryKey = 'RegistrationNo';

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

}
