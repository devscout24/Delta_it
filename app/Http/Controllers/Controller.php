<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

abstract class Controller
{
    public function uploadImage($image, $oldImage = null, $folder = 'uploads', $width = 150, $height = 150, $customName = 'image')
    {
        if ($image && $image->isValid()) {
            // Delete old image if exists
            if ($oldImage && File::exists(public_path($oldImage))) {
                File::delete(public_path($oldImage));
            }

            // Ensure the folder exists, create if not
            $folderPath = public_path($folder);
            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true); // recursive = true
            }

            // Generate new image name with custom name + timestamp
            $extension = $image->getClientOriginalExtension();
            $image_name = $customName . '-' . time() . '.' . $extension;
            $image_path = $folder . '/' . $image_name;

            // Resize and save the image
            Image::make($image)->resize($width, $height)->save(public_path($image_path));

            return $image_path; // Return new image path
        }

        return $oldImage; // Return old image if no new image is uploaded
    }
    public static function fileUpload($file, $folder)
    {
        try {
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $targetPath = public_path('uploads/' . $folder);

            if (!File::exists($targetPath)) {
                File::makeDirectory($targetPath, 0777, true, true);
            }

            $file->move($targetPath, $fileName);

            return 'uploads/' . $folder . '/' . $fileName;
        } catch (\Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage());
            return null;
        }
    }
}
