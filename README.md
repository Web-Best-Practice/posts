

### Add a Custom repository:

    composer config repositories.repo-name vcs https://github.com/Web-Best-Practice/posts

### Install package:

    composer require web-best-practice/posts

### Publish the config file:

    php artisan vendor:publish --tag=posts-config

### Configure the Package:

```php
<?php

use App\Helpers\Image as ImageHelper;
use Illuminate\Http\UploadedFile;

return [

    'secret' => 'key_CubvNVabgnB9kO8CKD9LQTCYZJFLEv64SCq1XT3xKCDWyVvO',
    'class' => App\Models\Blog\Item::class,
    'map' => [
        'title' => 'title',
        'content' => 'content',
        'meta_title' => 'meta_title',
        'meta_description' => 'meta_description',
        'meta_keywords' => 'meta_keywords',
        'created_at' => 'created_at',
    ],
    'image' => [
        'column' => 'filename',
        'callback' => function (UploadedFile $image, string $column) {
            return ImageHelper::make($image)
                ->uniqueName(new App\Models\Blog\Item, $column)
                ->addDirectory('upload/blog', 2000)
                ->addDirectory('upload/blog/thumbs', 500)
                ->save();
        }
    ],
    'extra' => ['summary' => 'content:100']
];
```


