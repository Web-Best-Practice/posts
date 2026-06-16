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
- `400` — unmapped request field, missing database column, persistence error, or image processing failure

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
 
    // Maps database columns to request fields and mapping rules
    'map' => [
        'title'            => 'title',
        'content'          => 'content',
        'meta_title'       => 'meta_title',
        'meta_description' => 'meta_description',
        'meta_keywords'    => 'meta_keywords',
        'published_at'     => null, // or 'date_now' to set current timestamp
        'summary'          => [
            'column'  => 'content',
            'options' => ['no-html', 'max_characters:100'],
        ],
        // Map API `title` to a database `name` column on another site:
        // 'name' => 'title',
        // Static value example:
        // 'custom_field' => ['value' => 'value1'],
        // Callable example:
        // 'category_id' => function(): ?int {
        //     return App\Models\Blog\Category::first()?->id;
        // },
    ],
 
    // Image upload handlers (supports multiple entries)
    'images' => [
        [
            'column'   => 'filename',
            'callback' => function (UploadedFile $image, string $column): string {
                return ImageHelper::make($image)
                    ->uniqueName(new App\Models\Blog\Item, $column)
                    ->addDirectory('upload/blog', 2000)
                    ->addDirectory('upload/blog/thumbs', 500)
                    ->save();
            },
        ],
        // Additional image columns are supported:
        // [
        //     'column'   => 'thumbnail_filename',
        //     'callback' => function (UploadedFile $image, string $column): string {
        //         return ImageHelper::make($image)
        //             ->uniqueName(new App\Models\Blog\Item, $column)
        //             ->addDirectory('upload/blog/thumbnails', 2000)
        //             ->addDirectory('upload/blog/thumbnails/thumbs', 500)
        //             ->save();
        //     },
        // ],
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

Each entry maps a **database column** (array key) to a **request field or mapping rule** (array value).

The API always sends these request fields:

- `title`
- `content`
- `meta_title`
- `meta_keywords`
- `meta_description`

Each of them must appear as a mapping **source** in `map`. The corresponding **database column** (the map key) must exist in your table.

For example, if your table uses `name` instead of `title`, map the API field like this:

```php
'name' => 'title',
```

#### Mapping formats

**Direct mapping** — copy a request field to a database column:

```php
'title' => 'title',
```

When the request and database column names differ:

```php
'name' => 'title',
```

**Skip a mapping** — set to `null` to ignore an entry entirely:

```php
'published_at' => null,
```

**Current datetime** — set a column to the current timestamp:

```php
'published_at' => 'date_now',
```

**Static value** — always write a fixed value to a column:

```php
'custom_field' => ['value' => 'value1'],
```

**Callable** — compute the value at creation time using a closure:

```php
'category_id' => function(): ?int {
    return App\Models\Blog\Category::first()?->id;
},
```

**Derived field with options** — build a database column from another request field with transformations applied:

```php
'summary' => [
    'column'  => 'content',
    'options' => ['no-html', 'max_characters:100'],
],
```

The array key is the target database column. `column` refers to the source request field. Options are applied in order.

Supported options:

| Option | Example | Description |
|--------|---------|-------------|
| `no-html` | `'no-html'` | Strips HTML tags |
| `max_characters` | `'max_characters:100'` | Limits string length |

### `images`

An array of image handlers. Each entry defines:

| Key | Description |
|-----|-------------|
| `column` | Database column to store the processed image path |
| `callback` | Callable receiving `UploadedFile $image` and `string $column` |

The callback runs only when:

- an `image` file is uploaded with the request
- the target `column` exists in the database table
- the `callback` is callable
  Multiple handlers are supported, each writing to a different column.

## Package structure

```
src/
├── Http/Controllers/IndexController.php   # Request validation and JSON responses
├── Services/PostCreationService.php       # Post creation logic
└── PostsServiceProvider.php               # Config and route registration
```

`IndexController` handles HTTP concerns. `PostCreationService` handles secret verification, column mapping, image processing, and model persistence.
