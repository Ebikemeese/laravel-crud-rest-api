<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GeneratePromptRequest;
use App\Http\Resources\ImageGenerationResource;
use App\Services\OpenAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromptGenerationController extends Controller
{
    public function __construct(private OpenAiService $openAiService)
    {}

    /**
     * List Image Generations
     *
     * Retrieve a paginated list of all image generations created by the authenticated user.
     * Supports filtering by generated prompt and sorting by various fields.
     *
     * Query Parameters:
     * - search: Search term to filter by generated_prompt field
     * - sort: Field name with optional '-' prefix for descending order
     *   Examples: 'created_at', '-created_at', 'generated_prompt', '-file_size'
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = $user->imageGenerations();

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $query->where('generated_prompt', 'LIKE', '%' . $request->search . '%');
        }

        // Apply sorting
        $allowedSortFields = ['created_at', 'generated_prompt', 'original_filename', 'file_size'];
        $sortField = 'created_at';
        $sortDirection = 'desc';

        if ($request->has('sort') && !empty($request->sort)) {
            $sort = $request->sort;
            if (str_starts_with($sort, '-')) {
                $sortField = substr($sort, 1);
                $sortDirection = 'desc';
            } else {
                $sortField = $sort;
                $sortDirection = 'asc';
            }
        }

        // Validate sort field
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
            $sortDirection = 'desc';
        }

        $query->orderBy($sortField, $sortDirection);

        $imageGenerations = $query->paginate($request->get('per_page'));
        return ImageGenerationResource::collection($imageGenerations);
    }

    /**
     * Generate Prompt from Image
     *
     * Upload an image and generate a descriptive prompt using AI. The system will:
     * - Store the uploaded image securely
     * - Process it with OpenAI's vision model
     * - Generate a detailed descriptive prompt
     * - Save the generation history for the authenticated user
     *
     * @param \App\Http\Requests\GeneratePromptRequest $request
     * @return ImageGenerationResource
     */
    public function store(GeneratePromptRequest $request)
    {
        $user = $request->user();
        $image = $request->file('image');

        $originalName = $image->getClientOriginalName();
        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME)); // something.png -> something.png
        $extension = $image->getClientOriginalExtension();
        $safeFilename = $sanitizedName . '_' . Str::random(32) . '.' . $extension;

        $imagePath = $image->storeAs('uploads/images', $safeFilename, 'public');

        $generatedPrompt = $this->openAiService->generatePromptFromImage($image);

        $imageGeneration = $user->imageGenerations()->create([
            'image_path' => $imagePath,
            'generated_prompt' => $generatedPrompt,
            'original_filename' => $originalName,
            'file_size' => $image->getSize(),
            'mime_type' => $image->getMimeType(),
        ]);

        return new ImageGenerationResource($imageGeneration);

    }
}
