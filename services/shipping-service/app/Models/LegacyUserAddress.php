<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo ORM para direcciones del esquema legacy (base de datos Angelow PHP original).
 *
 * Este modelo apunta a la tabla user_addresses en la base legacy (legacy_mysql)
 * y se usa durante la migración para mantener compatibilidad con los datos
 * existentes. El controlador ShippingController consulta primero esta tabla
 * (fuente primaria) y, si no encuentra datos, recurre a UserAddress (distribuida).
 *
 * Cuando la migración esté completa, este modelo dejará de ser necesario.
 *
 * @property string $connection Conexión a la base legacy ('legacy_mysql')
 * @property string $table Tabla física legacy ('user_addresses')
 */
class LegacyUserAddress extends Model
{
    /** Conexión a la base de datos legacy de Angelow PHP */
    protected $connection = 'legacy_mysql';

    /** Tabla en el esquema legacy */
    protected $table = 'user_addresses';

    /** Campos asignables de forma masiva */
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

    /** Conversión de tipos nativos */
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
