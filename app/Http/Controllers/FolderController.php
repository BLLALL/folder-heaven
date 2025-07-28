<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Models\Folder;
use App\Traits\ApiResponses;
use App\Traits\FolderHelpers;
use Illuminate\Support\Facades\DB;
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
        DB::beginTransaction();
        
        try {
            $attributes = $request->validated();
            $parent = File::find($attributes['parent_folder_id']);

            if (!$parent || !$parent->is_folder) {
                return $this->error(['message' => 'Parent must be a folder'], 422);
            }

            $this->authorize($parent);

            $attributes['path'] = str_replace('//', '/', $parent->path . '/' . $attributes['name']);
            $attributes['owner_id'] = auth()->id();
            $attributes['is_folder'] = true;

            if (File::wherePath($attributes['path'])->exists()) {
                return $this->error('Folder already exists!', 409);
            }

            $folder = File::create($attributes);
            Storage::createDirectory(auth()->id() . $folder->path);

            DB::commit();

            return new FileResource($folder);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if (isset($attributes['path']) && Storage::exists(auth()->id() . $attributes['path'])) {
                Storage::deleteDirectory(auth()->id() . $attributes['path']);
            }

            return response()->json([
                'message' => 'Failed to create folder',
                'error' => $e->getMessage(),
            ], 500);
        }
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

        DB::beginTransaction();

        try {
            $attributes = $request->validated();
            $oldPath = $folder->path;

            if ($request->has('parent_folder_id') && $folder->parent_folder_id !== $request->parent_folder_id) {
                $newParentFolder = File::find($request->parent_folder_id);

                if (!$newParentFolder || !$newParentFolder->is_folder) {
                    return $this->error(['message' => 'The destination must be a folder'], 422);
                }

                $this->authorize($newParentFolder);

                $newPath = str_replace('//', '/', $newParentFolder->path . '/' . $folder->name);
                
                if (File::wherePath($newPath)->exists()) {
                    return $this->error('A folder with this name already exists in the destination folder.', 409);
                }

                $folder->parent_folder_id = $newParentFolder->id;
                $folder->path = $newPath;
            }

            // Handle name change (rename)
            if ($request->has('name') && $folder->name !== $request->name) {
                $newName = $request->name;
                $newPath = str_replace('//', '/', dirname($folder->path) . '/' . $newName);

                if (File::wherePath($newPath)->exists()) {
                    return $this->error('A folder with this name already exists.', 409);
                }

                $folder->name = $newName;
                $folder->path = $newPath;
            }

            if ($oldPath !== $folder->path) {
                $source = Storage::path(auth()->id() . $oldPath);
                $destination = Storage::path(auth()->id() . $folder->path);

                if (!$this->moveFolder($source, $destination)) {
                    return $this->error("Couldn't move folder!", 500);
                }

            }
            $folder->save();
            DB::commit();

            return new FileResource($folder);
            
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($oldPath) && $oldPath !== $folder->path) {
                $source = Storage::path(auth()->id() . $folder->path);
                $destination = Storage::path(auth()->id() . $oldPath);
                $this->moveFolder($source, $destination);
            }

            return response()->json([
                'message' => 'Failed to update folder',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function updateChildrenPaths($oldParentPath, $newParentPath)
    {
        $children = File::where('path', 'like', $oldParentPath . '/%')->get();
        
        foreach ($children as $child) {
            $child->path = str_replace($oldParentPath, $newParentPath, $child->path);
            $child->save();
        }
    }
}
