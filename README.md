# webbestpractice/posts

Laravel API package (`Webbestpractice\Posts`).

## Requirements

- PHP 8.2+
- Laravel 12+

## Installation

### From GitHub (after publishing the repository)

Add the VCS repository to your Laravel app's `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/Web-Best-Practice/posts"
    }
],
"require": {
    "webbestpractice/posts": "^1.0"
}
```

Then run:

```bash
composer update webbestpractice/posts
php artisan vendor:publish --tag=posts-config
```

### Local development (path repository)

While developing inside this monorepo, Composer uses a path repository (see the host app's `composer.json`).

```bash
composer update webbestpractice/posts
```

## API routes

Routes are defined in `routes/api.php` and registered with these defaults (see `config/posts.php`):

| Setting | Default |
|---------|---------|
| URL prefix | `/api/posts` |
| Route name prefix | `posts.` |
| Middleware | `api` |

Disable package routes with `POSTS_ROUTES_ENABLED=false` in `.env`.

Example: `GET /api/posts` → `posts.index`

## License

MIT
