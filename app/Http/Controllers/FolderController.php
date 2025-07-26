<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Models\Folder;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FolderController extends Controller
{
    use ApiResponses;

    public function index(Folder $folder)
    {
        $this->authorize($folder);

        $folder_contents = $folder->children()->get();
        return FileResource::collection($folder_contents);
    }

    public function store(StoreFolderRequest $request)
    {
        $attributes = $request->validated();
        $folder = Folder::findOrFail($attributes['parent_folder_id']);

        $this->authorize($folder);

        $attributes['owner_id'] = auth()->id();
        $attributes['name'] = Str::afterLast($attributes['path'], '/');

        if (Folder::wherePath($attributes['path'])->exists()) {
            return $this->error('Folder already exists!', 409);
        }

        $folder = Folder::create($attributes);
        Storage::createDirectory(auth()->id() . $folder->path);

        return $this->success('Folder created!', $attributes, 201);
    }

    public function show(Folder $folder)
    {
        $this->authorize($folder);
        return FileResource::make($folder);
    }

    public function destroy(Folder $folder)
    {
        $this->authorize($folder);

        Storage::deleteDirectory(auth()->id() . $folder->path);

        File::whereLike('path', "{$folder->path}%", true)->delete();

        return $this->ok('Folder deleted!');
    }

    public function authorize($folder)
    {
        if ($folder->owner_id != auth()->id()) {
            throw new AuthorizationException("You aren't authorized to access this folder!");
        }
    }
}
