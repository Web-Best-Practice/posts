<?php

namespace WebBestPractice\Posts\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class IndexController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'secret' => ['required', 'string', 'max:191'],
            'title' => ['required', 'string', 'max:191'],
            'content' => ['required', 'string', 'max:60000'],
            'meta_title' => ['required', 'max:2000'],
            'meta_keywords' => ['required', 'max:2000'],
            'meta_description' => ['required', 'max:2000'],
            'image' => ['image'],
        ]);

        $expectedSecret = (string) config('posts.secret', '');

        if ($expectedSecret === '' || ! hash_equals($expectedSecret, $validated['secret'])) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        unset($validated['secret']);

        $class = config('posts.class');

        if(!class_exists($class)) {
            return response()->json([
                'message' => 'Post class not found.',
            ], 401);
        }

        $model = new $class;

        $data = [];

        $titleColumn = config('posts.map.title');
        if ($titleColumn && Schema::hasColumn($model->getTable(), $titleColumn)) {
            $data[$titleColumn] = $validated['title'];
        } else {
            return response()->json([
                'message' => 'Title column not found.',
            ], 400);
        }

        $contentColumn = config('posts.map.content');
        if ($contentColumn && Schema::hasColumn($model->getTable(), $contentColumn)) {
            $data[$contentColumn] = $validated['content'];
        } else {
            return response()->json([
                'message' => 'Content column not found.',
            ], 400);
        }

        $metaTitleColumn = config('posts.map.meta_title');
        if ($metaTitleColumn && Schema::hasColumn($model->getTable(), $metaTitleColumn)) {
            $data[$metaTitleColumn] = $validated['meta_title'];
        }

        $metaKeywordsColumn = config('posts.map.meta_keywords');
        if ($metaKeywordsColumn && Schema::hasColumn($model->getTable(), $metaKeywordsColumn)) {
            $data[$metaKeywordsColumn] = $validated['meta_keywords'];
        }

        $metaDescriptionColumn = config('posts.map.meta_description');
        if ($metaDescriptionColumn && Schema::hasColumn($model->getTable(), $metaDescriptionColumn)) {
            $data[$metaDescriptionColumn] = $validated['meta_description'];
        }

        $publishedAtColumn = config('posts.map.published_at');
        if ($publishedAtColumn && Schema::hasColumn($model->getTable(), $publishedAtColumn)) {
            $data[$publishedAtColumn] = now();
        }

        $imagesSection = config('posts.images');

        $image = array_key_exists('image', $validated)
            ? $validated['image'] : null;

        if($imagesSection && is_array($imagesSection)) {

            foreach($imagesSection as $imageSection) {
                $imageColumn = $imageSection['column'];
                $imageCallback = $imageSection['callback'];

                if ($imageColumn
                    && Schema::hasColumn($model->getTable(), $imageColumn)
                    && $imageCallback
                    && is_callable($imageCallback)
                    && $image)
                {
                    try {
                        $data[$imageColumn] = call_user_func($imageCallback, $image, $imageColumn);
                    } catch (\Throwable $e) {
                        return response()->json([
                            'message' => $e->getMessage(),
                        ], 400);
                    }
                }
            }
        }

        $extraSection = config('posts.extra');

        if($extraSection && is_array($extraSection)) {

            foreach ($extraSection as $column => $mapTo) {

                if(!Schema::hasColumn($model->getTable(), $column)) {
                    continue;
                }

                [$main, $switches] = array_pad(explode(',', $mapTo, 2), 2, null);

                [$mapToColumn, $maxCharacters] = array_pad(explode(':', $main, 2), 2, null);

                $value = $validated[$mapToColumn];

                if ($switches) {
                    $switches = explode(',', $switches);

                    foreach ($switches as $switch) {
                        switch ($switch) {
                            case 'no-html':
                                $value = strip_tags($value);
                                break;
                        }
                    }
                }

                if ($maxCharacters !== null) {
                    $value = Str::limit($value, (int) $maxCharacters);
                }

                $data[$column] = $value;
            }
        }

        try {
            $item = $model->create($data);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }

        return response()->json([
            'data' => $item->toArray(),
        ]);
    }
}
