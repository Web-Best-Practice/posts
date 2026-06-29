<?php

use App\Helpers\Image as ImageHelper;
use Illuminate\Http\UploadedFile;

return [

    'secret' => env('POSTS_SECRET'),
    'class' => App\Models\Blog\Item::class,
    'map' => [
        'title' => 'title',
        'content' => 'content',
        'meta_title' => 'meta_title',
        'meta_description' => 'meta_description',
        'meta_keywords' => 'meta_keywords',
        'published_at' => null, //'date_now'
        'summary' => ['column' => 'content', 'options' => ['no-html', 'max_characters:100']],
        /*'custom_field' => ['value' => 'value1'],
        'category_id' => function() : ?int {
            return App\Models\Blog\Category::first()?->id;
        }*/
    ],
    'images' => [
        [
            'column' => 'filename',
            'callback' => function (UploadedFile $image, string $column) : string {
                return ImageHelper::make($image)
                    ->uniqueName(new App\Models\Blog\Item, $column)
                    ->addDirectory('upload/blog', 2000)
                    ->addDirectory('upload/blog/thumbs', 500)
                    ->save();
            }
        ],
        /*[
            'column' => 'thumbnail_filename',
            'callback' => function (UploadedFile $image, string $column) : string {
                return ImageHelper::make($image)
                    ->uniqueName(new App\Models\Blog\Item, $column)
                    ->addDirectory('upload/blog/thumbnails', 2000)
                    ->addDirectory('upload/blog/thumbnails/thumbs', 500)
                    ->save();
            }
        ],*/
    ]
];
