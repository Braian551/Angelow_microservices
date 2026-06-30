<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo ORM para direcciones de envío en la base distribuida (shipping-db).
 *
 * Almacena las direcciones de los usuarios en la base de datos del microservicio
 * de envíos. Es la tabla destino de la migración; eventualmente reemplazará
 * completamente a LegacyUserAddress cuando la migración desde Angelow legacy
 * esté completa.
 *
 * Contiene campos de geolocalización GPS opcionales (latitud, longitud, precisión,
 * marca de tiempo) que permiten al frontend capturar la ubicación exacta del
 * usuario durante el registro de dirección.
 *
 * @property int $id
 * @property string $user_id
 * @property string $address_type
 * @property string $alias
 * @property string $recipient_name
 * @property string $recipient_phone
 * @property string $address
 * @property string|null $complement
 * @property string $neighborhood
 * @property string $building_type
 * @property string|null $building_name
 * @property string|null $apartment_number
 * @property string|null $delivery_instructions
 * @property bool $is_default
 * @property bool $is_active
 * @property float|null $gps_latitude
 * @property float|null $gps_longitude
 * @property float|null $gps_accuracy
 * @property string|null $gps_timestamp
 * @property bool $gps_used
 */
class UserAddress extends Model
{
    /** Tabla asociada en la base de datos distribuida */
    protected $table = 'user_addresses';

    /** Campos asignables de forma masiva (mass assignment) */
    protected $fillable = [
        'user_id',
        'address_type',
        'alias',
        'recipient_name',
        'recipient_phone',
        'address',
        'complement',
        'neighborhood',
        'building_type',
        'building_name',
        'apartment_number',
        'delivery_instructions',
        'is_default',
        'is_active',
        'gps_latitude',
        'gps_longitude',
        'gps_accuracy',
        'gps_timestamp',
        'gps_used',
    ];

    /** Conversión de tipos nativos al acceder a atributos del modelo */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'gps_used' => 'boolean',
            'gps_latitude' => 'float',
            'gps_longitude' => 'float',
            'gps_accuracy' => 'float',
            'gps_timestamp' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}