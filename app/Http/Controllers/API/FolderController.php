<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\FolderResource;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Tag;

class FolderController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->sendResponse(FolderResource::collection(Folder::where('idOwnerFolder', '=', Auth::user()->id)->get()), 'Folder retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'idParent' => 'integer'
        ]);
        if ($validator->fails()) {
            return $this->sendError("Validation Error.", $validator->errors());
        }
        $folder = new Folder();
        $idParents = (FolderController::getIdParent());
        $idP = true;
        foreach ($idParents as $idParent) {
            if (($idParent != $request->idParent)) {
                $idP = false;
            } else {
                $idP = true;
                break;
            }
        }
        if ($idP == false) {
            return $this->sendError("Validation Error.", $validator->errors());
        } else {
            $folder->idParent = $idParent;
        }
        $folder->owner()->associate(Auth::user());
        $folder->name = $request->name;
        $folder->save();
        return $this->sendResponse(new FolderResource($folder), 'Folder created successfully');
    }



    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $folder = Folder::findOrFail($id);
        if (!Gate::allows('folder_owned', $folder)) {
            return $this->sendError(null, 'Unauthorized resource.', 403);
        }
        return $this->sendResponse(new FolderResource($folder), 'Folder showed successfully');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Folder $folder)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'idParent' => 'integer|nullable'
        ]);

        if ($validator->fails()) {
            return $this->sendError("Validation Error.", $validator->errors());
        }

        if (!Gate::allows('folder_owned', $folder) && (FolderController::getRoot($request) === $request->id)) {
            return $this->sendError(null, 'Unauthorized resource.', 403);
        }
        $input = $request->all();
        if (isset($input['name'])) {
            $folder->name = $request->name;
        }
        if (isset($input['idParent'])) {
            $folder->idParent = $request->idParent;
        }
        $folder->save();
        return $this->sendResponse(new FolderResource($folder), 'Folder updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Http\Response
     */
    public function destroy(Folder $folder)
    {
        if (!Gate::allows('folder_owned', $folder) && ($folder->name == 'root')) {
            return $this->sendError(null, 'Unauthorized resource.', 403);
        }
        $folder->delete();
        return $this->sendResponse(null, 'Folder deleted successfully');
    }

    /**
     * Returns the folder corresponding to the ID sent in the request.
     *
     * @param Request $request
     * @return Folder
     */
    static public function getFolder(Request $request)
    {
        return Folder::findOrFail($request->folder_id);
    }

    /**
     * Returns the root folder ID of the user sent in the request. 
     *
     * @return int
     */
    static public function getRoot(Request $request)
    {
        return  DB::table('folders')
            ->where('idOwnerFolder', '=', Auth::user()->id)
            ->whereNull('idParent')
            ->get()->first()->id;
    }

    /**
     * Remove a tag from a folder.
     *
     * @param \App\Models\Folder $folder
     * @param \App\Models\Tag $tag
     * @return \Illuminate\Http\Response
     */
    public function destroyForFolder(Folder $folder, Tag $tag)
    {
        if (TagController::index()->contains($tag)) {
            $folder->tags()->detach($tag);
            return $this->sendResponse(null, 'Tag deleted successfully');
        };
        return $this->sendError(null, 'Unauthorized resource.', 403);
    }
    /**
     * Display a listing of the resource,
     * bookmarks in folders.
     *
     * @param \App\Models\Folder $folder
     * @return collection
     */
    public function bookmarkInFolder(Folder $folder)
    {
        return DB::table('bookmarks')
            ->where('idFolder', '=', $folder->id)
            ->get();
    }

    /**
     *  Display a listing of the resource,
     * folders in folders.
     *
     * @param \App\Models\Folder $folder
     * @return collection
     */
    public function folderInFolder(Folder $folder)
    {
        return DB::table('folders')
            ->where('idParent', '=', $folder->id)
            ->get();
    }

    public function content(Folder $folder)
    {
        return 'Folders :<br>' . FolderController::folderInFolder($folder) . '<br>Bookmarks :<br>' . (FolderController::bookmarkInFolder($folder));
    }

    /**
     * Return the idParent of all folders of the user
     *
     * @return collection
     */
    public function getIdParent()
    {
        $parents =  DB::table('folders')
            ->where('idOwnerFolder', '=', Auth::user()->id)
            ->get();

        $idParents = [];

        foreach ($parents as $parent) {
            array_push($idParents, $parent->id);
        }

        return $idParents;
    }
}
