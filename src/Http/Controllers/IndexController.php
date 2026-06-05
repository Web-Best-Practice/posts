<?php

namespace WebBestPractice\Posts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;
use WebBestPractice\Posts\Services\PostCreationService;

class IndexController extends Controller
{
    public function index(Request $request, PostCreationService $postCreationService): JsonResponse
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

        try {
            $postCreationService->verifySecret($validated['secret']);
            unset($validated['secret']);

            $item = $postCreationService->create($validated);

            return response()->json([
                'data' => $item->toArray(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() === 401 ? 401 : 400);
        }
    }
}
