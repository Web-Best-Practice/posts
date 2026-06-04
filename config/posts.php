<?php

use App\Helpers\Image as ImageHelper;
use Illuminate\Http\UploadedFile;

return [

    'secret' => 'key_QeMhaBpG7l9BNFHUoAUphV7ZaB9QxBxoHFCOZJgZ7QHfoyaU',
    'class' => App\Models\Blog\Item::class,
    'map' => [
        'title' => 'title',
        'content' => 'content',
        'meta_title' => 'meta_title',
        'meta_description' => 'meta_description',
        'meta_keywords' => 'meta_keywords',
        'published_at' => null //'published_at'
    ],
    'images' => [
        [
            'column' => 'filename',
            'callback' => function (UploadedFile $image, string $column) {
                return ImageHelper::make($image)
                    ->uniqueName(new App\Models\Blog\Item, $column)
                    ->addDirectory('upload/blog', 2000)
                    ->addDirectory('upload/blog/thumbs', 500)
                    ->save();
            }
        ],
        /*[
            'column' => 'thumbnail_filename',
            'callback' => function (UploadedFile $image, string $column) {
                return ImageHelper::make($image)
                    ->uniqueName(new App\Models\Blog\Item, $column)
                    ->addDirectory('upload/blog/thumbnails', 2000)
                    ->addDirectory('upload/blog/thumbnails/thumbs', 500)
                    ->save();
            }
        ],*/
    ],
    'extra' => null //['summary' => 'content:100,no-html']
];
