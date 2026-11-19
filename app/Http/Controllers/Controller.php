<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Contrôleur de base de l'application.
 */
abstract class Controller
{
    // Autorise l'usage des policies via la méthode authorize().
    use AuthorizesRequests;
}
