<?php

namespace WebBestPractice\Posts;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PostsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/posts.php', 'posts');
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('_posts/create')
            ->name('create')
            ->group(__DIR__.'/../routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/posts.php' => config_path('posts.php'),
            ], 'posts-config');
        }
    }
}
