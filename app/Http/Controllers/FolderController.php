<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Models\Folder;
use App\Traits\ApiResponses;
use App\Traits\FolderHelpers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FolderController extends Controller
{
    use ApiResponses, FolderHelpers;

    public function index(Folder $folder)
    {
        $this->authorize($folder);

        $folder_contents = $folder->children()->get();

        return FileResource::collection($folder_contents);
    }

    public function store(StoreFolderRequest $request)
    {
        $attributes = $request->validated();
        $parent = Folder::find($attributes['parent_folder_id']);

        $this->authorize($parent);

        $attributes['owner_id'] = auth()->id();
        $attributes['name'] = Str::afterLast($attributes['path'], '/');
        if ("{$parent->path}/".$attributes['name'] != $attributes['path']) {
            return $this->error('No match between parent folder and path!', 409);
        }

        if (Folder::wherePath($attributes['path'])->exists()) {
            return $this->error('Folder already exists!', 409);
        }

        $folder = Folder::create($attributes);
        Storage::createDirectory(auth()->id().$folder->path);

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

        Storage::deleteDirectory(auth()->id().$folder->path);

        File::whereLike('path', "{$folder->path}%", true)->delete();

        return $this->ok('Folder deleted!');
    }

    public function update(Folder $folder, UpdateFolderRequest $request)
    {
        $this->authorize($folder);

        $attributes = $request->validated();

        $user_id = auth()->id();

        if (Str::beforeLast($attributes['path'], '/') == Str::beforeLast($folder->path, '/') && Storage::directoryMissing($user_id.$attributes['path'])) {
            $attributes['name'] = Str::afterLast($attributes['path'], '/'); // rename
        } else {
            $attributes['name'] = $folder->name; // move
            if (Folder::findOrFail($attributes['parent_folder_id'])->path != $attributes['path']) {
                return $this->error('No match between parent folder and path!', 409);
            }
            $attributes['path'] .= "/{$folder->name}";

        }

        $source = Storage::path($user_id.$folder->path);
        $destination = Storage::path($user_id.$attributes['path']);

        if ($this->moveFolder($source, $destination)) {
            $folder->update($attributes);

            return $this->ok('Folder moved!');
        }

        return $this->error("Couldn't move folder!", 500);
    }
}
