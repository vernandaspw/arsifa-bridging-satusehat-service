<?php

namespace App\Models\Satusehat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatusehatOrganization extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $connection = 'mysql_satusehat';
    protected $table = 'organizations';


}
