<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Post;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return [
            [
                'id' => 1,
                'title' => 'index title',
                'body' => 'index Post body'
            ]
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|min:2|max:255',
            'body' => 'required|string'
        ]);

        $data['author_id'] = 1;

        $post = Post::create($data);

        return response()->json($post, 201);
        ;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json([
            'message' => 'test',
            'data' => [
                'id' => 1,
                'title' => 'test title',
                'body' => 'Post body'
            ]
        ])
        // ->header('Test', 'Zura')
        ->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required|string|min:2|max:255',
            'body' => 'required|string'
        ]);

        return response()->json([
            'id' => 1,
            'title' => $request->title,
            'body' => $request->body
        ])
        ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return response()->json([
            'message' => 'Post deleted successfully'
        ])
        ->setStatusCode(204);
    }
}
