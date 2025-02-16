<?php
namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    // Prikaz svih blogova
    public function index()
    {
        $blogs = Blog::latest()->get();
        return response()->json($blogs);
    }

    // Kreiranje novog bloga
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'required|string',
            'content' => 'required|string',
            'photo' => 'nullable|image|max:2048', // Maks 2MB slika
        ]);

        // Čuvanje slike ako je poslata
        $photoPath = $request->file('photo') ? $request->file('photo')->store('blog_photos', 'public') : null;

        $blog = Blog::create([
            'title' => $request->title,
            'summary' => $request->summary,
            'content' => $request->content,
            'photo' => $photoPath,
        ]);

        return response()->json(['message' => 'Blog created successfully', 'blog' => $blog], 201);
    }
}
