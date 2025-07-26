<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;

abstract class Controller
{
    public function authorize($folder)
    {
        if ($folder->owner_id != auth()->id()) {
            throw new AuthorizationException("You aren't authorized to access this folder!");
        }
    }
}
