<?php

namespace App\Models\Satusehat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatusehatLocation extends Model
{
    use HasFactory;

    protected $connection = 'mysql_satusehat';
    protected $guarded = ['id'];

    protected $table = 'locations';

    public const STATUS = [
        'active',
        'suspended',
        'inactive',
    ];

    public static function cek($service_unit_id, $room_id, $room_code, $bed_id)
    {
        $locationLocal = SatusehatLocation::query();

        if ($service_unit_id) {
            $locationLocal->where('ServiceUnitID', $service_unit_id);
        }
        if ($room_id) {
            $locationLocal->where('RoomID', $room_id);
        }
        if ($room_code) {
            $locationLocal->where('RoomCode', $room_code);
        }
        if ($bed_id) {
            $locationLocal->where('BedID', $bed_id);
        }
        $data = $locationLocal->first();

        if ($data) {
            $ihs = $data->location_id;
        } else {
            $ihs = null;
        }

        return $ihs;
    }

}
