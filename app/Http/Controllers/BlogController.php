<?php
namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use OpenAI;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::latest()->get();
        return response()->json($blogs);
    }

    public function show($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json(['message' => 'Blog nije pronađen'], 404);
        }

        return response()->json($blog);
    }

    public function store(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
        ]);

        $client = OpenAI::client(env('OPENAI_API_KEY'));

        $prompt = "Napiši detaljan blog na temu: {$request->topic}. Blog treba da ima naslov, kratak rezime i sadržaj.";

        $response = $client->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'Ti si iskusni blog pisac.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $generatedText = $response->choices[0]->message->content;

        $title = strtok($generatedText, "\n");
        $content = substr($generatedText, strlen($title));
        $summary = substr($content, 0, 200) . "...";

        $blog = Blog::create([
            'title' => trim($title),
            'summary' => trim($summary),
            'content' => trim($content),
            'photo' => null,
        ]);

        return response()->json([
            'message' => 'Blog je uspešno generisan!',
            'blog' => $blog
        ], 201);
    }
}
