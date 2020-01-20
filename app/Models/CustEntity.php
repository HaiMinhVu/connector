<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CustEntity extends Model {
    protected $connection = 'custom_entity_fields';

    protected $table = 'custom_entity_fields';

    public $timestamps = false;

    public static function getDescById($id)
    {
        return Cache::remember("cust-entity_{$id}", 15, function () use ($id) {
            return optional(self::where('ID', $id)->first())->Description;
        });
    }
}
