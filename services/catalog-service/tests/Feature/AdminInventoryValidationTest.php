<?php

namespace Tests\Feature;

// Comentario de mantenimiento: Esta prueba protege el comportamiento esperado del servicio.

use App\Http\Middleware\EnsureAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Esta prueba protege el comportamiento esperado del servicio.
 */
class AdminInventoryValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Explica la intención de test_adjust_stock_rejects_decimal_quantity_with_spanish_message dentro del flujo del servicio.
     */

    public function test_adjust_stock_rejects_decimal_quantity_with_spanish_message(): void
    {
        $this->withoutMiddleware(EnsureAdmin::class);

        $this->patchJson('/api/admin/inventory/1/stock', [
            'action' => 'add',
            'quantity' => 0.5,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('quantity')
            ->assertJsonPath('errors.quantity.0', 'La cantidad debe ser un número entero mayor o igual a 1.');
    }
}
