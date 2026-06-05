# Posts

Laravel package for creating blog posts via a secured API endpoint. Request data is validated, mapped to your Eloquent model through config, and persisted by `PostCreationService`.

## Installation

### Add a custom repository

```bash
composer config repositories.repo-name vcs https://github.com/Web-Best-Practice/posts
```

### Install the package

```bash
composer require web-best-practice/posts
```

### Publish the config file

```bash
php artisan vendor:publish --tag=posts-config
```

## API

The package registers one route:

```
POST /_posts/create
```

### Request fields

| Field | Rules |
|-------|-------|
| `secret` | required, string, max 191 |
| `title` | required, string, max 191 |
| `content` | required, string, max 60000 |
| `meta_title` | required, max 2000 |
| `meta_keywords` | required, max 2000 |
| `meta_description` | required, max 2000 |
| `image` | optional, image file |

### Responses

Success (`200`):

```json
{
  "data": { }
}
```

Error (`401` or `400`):

```json
{
  "message": "Error description"
}
```

- `401` — invalid secret or missing post model class
- `400` — missing column mapping, database error, or image processing failure

## Configuration

Publish and edit `config/posts.php`:

```php
<?php

use App\Helpers\Image as ImageHelper;
use Illuminate\Http\UploadedFile;

return [

    // API authentication secret
    'secret' => 'your-secret-key',

    // Eloquent model used to create posts
    'class' => App\Models\Blog\Item::class,

    // Maps request/model fields to database columns
    'map' => [
        'title' => 'title',
        'content' => 'content',
        'meta_title' => 'meta_title',
        'meta_description' => 'meta_description',
        'meta_keywords' => 'meta_keywords',
        'published_at' => 'date_now',
        'summary' => [
            'column' => 'content',
            'options' => ['no-html', 'max_characters:100'],
        ],
    ],

    // Image upload handlers (supports multiple entries)
    'images' => [
        [
            'column' => 'filename',
            'callback' => function (UploadedFile $image, string $column) {
                return ImageHelper::make($image)
                    ->uniqueName(new App\Models\Blog\Item, $column)
                    ->addDirectory('upload/blog', 2000)
                    ->addDirectory('upload/blog/thumbs', 500)
                    ->save();
            },
        ],
    ],
];
```

## Config options

### `secret`

Shared key sent with each API request. Requests with a missing or incorrect secret are rejected with `401`.

### `class`

Fully qualified Eloquent model class. The model must exist and use a valid database table.

### `map`

Defines how incoming data is transformed before `create()` is called.

The following request fields are required in `map` and must resolve to existing database columns:

- `title`
- `content`
- `meta_title`
- `meta_keywords`
- `meta_description`

#### Mapping formats

**Direct mapping** — copy a request field to a model column:

```php
'title' => 'title',
```

**Current datetime** — set a column to the current timestamp:

```php
'published_at' => 'date_now',
```

**Skip a mapping** — ignore an entry:

```php
'published_at' => null,
```

**Derived field with options** — build a model column from another request field:

```php
'summary' => [
    'column' => 'content',
    'options' => ['no-html', 'max_characters:100'],
],
```

Supported options:

| Option | Example | Description |
|--------|---------|-------------|
| `no-html` | `'no-html'` | Strips HTML tags |
| `max_characters` | `'max_characters:100'` | Limits string length |

The array key is the model/database column name. The `column` value is the source request field.

### `images`

An array of image handlers. Each entry may define:

| Key | Description |
|-----|-------------|
| `column` | Database column to store the processed image path |
| `callback` | Callable receiving `UploadedFile $image` and `string $column` |

The callback runs only when:

- an `image` file is uploaded
- the target column exists in the database table
- the callback is callable

You can configure multiple image handlers for different columns.

## Package structure

```
src/
├── Http/Controllers/IndexController.php   # Request validation and JSON responses
├── Services/PostCreationService.php       # Post creation logic
└── PostsServiceProvider.php               # Config and route registration
```

`IndexController` handles HTTP concerns. `PostCreationService` handles secret verification, column mapping, image processing, and model persistence.
