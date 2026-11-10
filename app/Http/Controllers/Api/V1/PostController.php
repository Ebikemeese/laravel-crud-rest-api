<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Post;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = request()->user();
        $posts = $user->posts()->paginate();
        return PostResource::collection($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validated();
        $data['author_id'] = $request->user()->id;

        $post = Post::create($data);

        return new PostResource($post)
        ;
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        abort_if(Auth::id() != $post->author_id, 403, 'Access Forbidden');

        return new PostResource($post)
        // ->header('Test', 'Zura')
        // ->setStatusCode(200)
        ;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        abort_if(Auth::id() != $post->author_id, 403, 'Access Forbidden');

        $data = $request->validate([
            'title' => 'required|string|min:2',
            'body' => ['required', 'string', 'min:2']
        ]);

        $post->update($data);

        return new PostResource($post);
        // ->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        abort_if(Auth::id() != $post->author_id, 403, 'Access Forbidden');
        $post->delete();
        return response()->noContent();
        
        // return response()->json([
        //     'message' => 'Post deleted successfully'
        // ])
        // ->setStatusCode(204);
    }
}
