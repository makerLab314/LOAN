<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

/**
 * Speichert hochgeladene Bilder neu kodiert (WebP): entfernt EXIF/GPS-Daten und
 * eventuell eingebetteten Schadcode, begrenzt die Größe, erzeugt ein Thumbnail.
 */
class ImageStore
{
    public function store(UploadedFile $file, string $dir = 'images', int $max = 1600, int $thumb = 400): string
    {
        $manager = ImageManager::gd();
        $name = Str::uuid()->toString().'.webp';
        $disk = Storage::disk('public');

        // Nur einmal dekodieren: ein großes Foto belegt unkomprimiert schnell >100 MB,
        // ein zweites read() würde den Speicherbedarf verdoppeln. scaleDown() ändert das
        // Bild in place – das Thumbnail entsteht aus der bereits verkleinerten Fassung.
        $image = $manager->read($file->getRealPath())->orient()->scaleDown($max, $max);
        $disk->put($dir.'/'.$name, (string) $image->toWebp(82));

        if ($thumb > 0) {
            $disk->put($dir.'/thumbs/'.$name, (string) $image->scaleDown($thumb, $thumb)->toWebp(78));
        }

        return $dir.'/'.$name;
    }

    /**
     * Logos/Favicons: PNG behalten (Transparenz), aber ebenfalls neu kodieren.
     */
    public function storePng(UploadedFile $file, string $dir, int $max): string
    {
        $name = Str::uuid()->toString().'.png';
        $image = ImageManager::gd()->read($file->getRealPath())->scaleDown($max, $max);
        Storage::disk('public')->put($dir.'/'.$name, (string) $image->toPng());

        return $dir.'/'.$name;
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        $disk = Storage::disk('public');
        $disk->delete($path);
        $disk->delete(dirname($path).'/thumbs/'.basename($path));
    }
}
