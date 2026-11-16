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
