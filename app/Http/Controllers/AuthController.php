<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Folder;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    use ApiResponses;

    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated());
        $folder = Folder::create(['owner_id' => $user->id, 'name' => '', 'path' => '/']);
        Storage::createDirectory($user->id);

        $resource = UserResource::makeWithToken($user, $user->generateToken());

        return $this->success('Registered!', ['user' => $resource, 'root_id' => $folder->id], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::firstWhere('email', $request->email);

        if (! Hash::check($request->post('password'), $user->password)) {
            return $this->unauthorized('Invalid credentials!');
        }

        $resource = UserResource::makeWithToken($user, $user->generateToken());

        return $this->ok('Authenticated!', $resource);
    }

    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function currentUser()
    {
        return UserResource::make(auth()->user());
    }
}
