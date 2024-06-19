<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\PresentationFolder;

class PresentationFolderController extends Controller
{
    public function create(Request $request)
    {
        $user = auth()->user();
        $data = ['name' => $request->input('name'), 'user_id' => $user->id];
        if ($request->has('folder_id')) {
            $data['parent_folder_id'] = $request->input('folder_id');
        }
        $folder = PresentationFolder::create($data);
        
        $presentations = $folder->presentations()->with('user')->orderBy('created_at', 'desc')->get();
        $projects = (object) ['folders' => [], 'presentations' => $presentations];
        return Inertia::location(route('folder', ['id' => $folder->id]));
    }

    public function getPresentationsOfFolder(Request $request, $id)
    {
        $user = auth()->user();
        $folder = PresentationFolder::find($id);
        if (!$folder) {
            return response()->json(['error' => 'Folder not found.'], 404);
        }
        else {
            $presentations = $folder->presentations()->with('user')->orderBy('created_at', 'desc')->get();
            $folders = $folder->childFolders()->with(['presentations', 'user'])->get();
            $projects = (object) ['folders' => $folders, 'presentations' => $presentations];
            $parents = $folder->parents();
            return Inertia::render('User/Presentations', [
                'projects' => $projects,
                'folder' => $folder,
                'parents' => $parents
            ]);
        }
    }

    
    public function delete(Request $request)
    {
        $folder = PresentationFolder::find($request->input('id'));
        if ($folder) {
            $folder->delete();
            return response()->json(['message' => 'Folder deleted successfully'], 200);
        }
        return response()->json(['error' => 'Folder not found'], 404);
    }

}
