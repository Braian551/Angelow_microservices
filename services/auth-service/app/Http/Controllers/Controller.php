<?php

namespace App\Http\Controllers;

/**
 * Controlador base abstracto del servicio de autenticación.
 *
 * Todos los controladores concretos del auth-service extienden
 * esta clase. Actualmente no contiene lógica compartida, pero
 * se mantiene como punto de extensión para futuros middlewares
 * o utilidades comunes a todos los endpoints.
 */
abstract class Controller
{
    //
}
