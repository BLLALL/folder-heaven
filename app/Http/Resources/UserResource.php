<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public $token = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'user',
            'attributes' => [
                'name' => $this->name,
                'email' => $this->email,
                'avatar_url' => $this->avatar_url,
            ],
            $this->mergeWhen(
                isset($this->token),
                fn () => ['token' => $this->token]
            ),
        ];
    }

    public static function makeWithToken(User $user, string $token)
    {
        $resource = static::make($user);
        $resource->token = $token;

        return $resource;
    }
}
