<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'size' => $this->size,
            'mime_type' => $this->mime_type,
            'is_folder' => $this->is_folder,
            'path' => $this->when(! $this->is_folder, $this->path),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'owner_id' => $this->owner_id,
            'parent_folder_id' => $this->parent_folder_id,
            'children_count' => $this->when($this->is_folder, $this->children()->count()),
            'children' => FileResource::collection($this->whenLoaded('children')),
        ];
    }
}
