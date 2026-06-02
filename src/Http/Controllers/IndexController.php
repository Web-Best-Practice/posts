<?php

namespace Webbestpractice\Posts\Http\Controllers;

use App\Helpers\Image as ImageHelper;
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
        }

        $contentColumn = config('posts.map.content');
        if ($contentColumn && Schema::hasColumn($model->getTable(), $contentColumn)) {
            $data['content'] = $validated['content'];
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

        $imageSection = config('posts.image');

        if($imageSection) {
            $imageColumn = config('posts.image.column');
            $imageCallback = config('posts.image.callback');

            if ($imageColumn
                && Schema::hasColumn($model->getTable(), $imageColumn)
                && $imageCallback
                && is_callable($imageCallback)
                && $validated['image']) {
                $image = $validated['image'];

                $data[$imageColumn] = call_user_func($imageCallback, $image, $imageColumn);
            }
        }

        $extraSection = config('posts.extra');

        if($extraSection && is_array($extraSection)) {

            foreach ($extraSection as $column => $mapTo) {

                if(!Schema::hasColumn($model->getTable(), $column)) {
                    continue;
                }

                list($mapToColumn, $maxCharacters) = explode(':', $mapTo);

                $data[$column] = Str::limit($validated[$mapToColumn], $maxCharacters);
            }
        }

        $item = $model->create($data);

        return response()->json([
            'data' => $item->toArray(),
        ]);
    }
}
