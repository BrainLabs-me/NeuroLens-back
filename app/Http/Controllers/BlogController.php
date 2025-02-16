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

    public function store(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
        ]);

        // Kreiranje OpenAI klijenta
        $client = OpenAI::client( env('OPENAI_API_KEY'));

        // Prompt za generisanje bloga
        $prompt = "Napiši detaljan blog na temu: {$request->topic}. Blog treba da ima naslov, kratak rezime i sadržaj.";

        // OpenAI API poziv
        $response = $client->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'Ti si iskusni blog pisac.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        // Dobijanje generisanog teksta
        $generatedText = $response->choices[0]->message->content;

        // Parsiranje generisanog teksta
        $title = strtok($generatedText, "\n"); // Prva linija kao naslov
        $content = substr($generatedText, strlen($title)); // Ostatak kao sadržaj
        $summary = substr($content, 0, 200) . "..."; // Prvih 200 karaktera kao sažetak

        // Kreiranje bloga u bazi podataka
        $blog = Blog::create([
            'title' => trim($title),
            'summary' => trim($summary),
            'content' => trim($content),
            'photo' => null, // Možeš kasnije dodati generisanje slika ako želiš
        ]);

        return response()->json([
            'message' => 'Blog je uspešno generisan!',
            'blog' => $blog
        ], 201);
    }
}
