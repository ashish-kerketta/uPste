<?php

namespace App\Http\Controllers;

use App\Upload;
use Auth;
use File;
use Illuminate\Http\Request;

use App\Http\Requests;
use Response;

class ApiController extends Controller
{
    public function upload(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['upload_file_not_found'], 400);
        }

        $file = $request->file('file');
        if (!$file->isValid()) {
            return response()->json(['invalid_file_upload'], 400);
        }

        $path = storage_path() . '/uploads/';
        $ext = $file->getClientOriginalExtension();
        if (!$ext) {
            $ext = 'txt';
        }

        $randomLen = 4;
        do {
            $newname = str_random($randomLen++) . ".$ext";
        } while (File::exists($path . $newname));

        $upload = Upload::create([
            'user_id' => Auth::user()->id,
            'hash' => sha1_file($file),
            'name' => $newname,
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName()
        ]);

        $upload->save();
        $file->move($path, $newname);

        $result = [
            'code' => 200,
            'hash' => $upload->getAttribute('hash'),
            'url' => env('UPLOAD_URL') . '/' . $newname
        ];

        $response = Response::make(json_encode($result, JSON_UNESCAPED_SLASHES), 200);
        $response->header('Content-Type', 'application/json');
        return $response;
    }
}
