<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Chat;
use Illuminate\Support\Facades\Auth;

class OpenAIController extends Controller
{
    public function createEphemeralTokenME()
    {
        try {
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/realtime/sessions', [
                'model' => 'gpt-4o-mini-realtime-preview',
                'voice' => 'shimmer',
                "instructions"=> "Ti si psihoterapeut i strucnjak u oblasti fokusa kod ljudi.Zoves se Aurora i pricas na crnogorskom."
            ]);

            if ($response->successful()) {
                return response()->json($response->json(), 200);
            } else {
                return response()->json([
                    'error' => 'Failed to generate ephemeral token',
                    'toke' =>  env('OPENAI_API_KEY'),
                    'details' => $response->json(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while generating the token',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    public function createEphemeralTokenEN()
    {
        try {
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/realtime/sessions', [
                'model' => 'gpt-4o-realtime-preview',
                'voice' => 'shimmer',
                "instructions"=> "I am a psychotherapist and an expert in the area of focus for individuals. My name is Aurora."
            ]);

            if ($response->successful()) {
                return response()->json($response->json(), 200);
            } else {
                return response()->json([
                    'error' => 'Failed to generate ephemeral token',
                    'toke' =>  env('OPENAI_API_KEY'),
                    'details' => $response->json(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while generating the token',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function createThread(Request $request)
{
    try {
        // Kreiraj Thread
        $thread = OpenAI::threads()->create([]);

        return response()->json([
            'success' => true,
            'thread_id' => $thread->id
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to create thread: ' . $e->getMessage(),
        ], 500);
    }
}
    
public function sendMessage(Request $request)
{
    $userPrompt = $request->input('prompt', 'Zdravo, kako si?');
    $threadId = $request->input('thread_id');  // Uzimaš thread_id iz zahteva

    $user = Auth::user();

    if (!$threadId) {
        return response()->json([
            'success' => false,
            'error' => 'Thread ID is required.'
        ], 400);
    }

    // Dodaj korisničku poruku u thread
    try {
        $message = OpenAI::threads()->messages()->create(
            $threadId, [
                'role' => 'user',
                'content' => $userPrompt
            ]
        );
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to add message to thread: ' . $e->getMessage(),
        ], 500);
    }

    // Pokreni asistenta sa postojećim thread-om
    try {
        $assistantId = 'asst_mepEpGvVGZl2G6A9P0zZ7FPX';  // Postavi ID svog asistenta
        $run = OpenAI::threads()->createAndRun(
            $threadId,
            [
                'assistant_id' => $assistantId,
                'thread' => [
            'messages' =>
                [
                    [
                        'role' => 'user',
                        'content' => 'Explain deep learning to a 5 year old.',
                    ],
                ],
        ],
            ]
        );
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to run the assistant: ' . $e->getMessage(),
        ], 500);
    }

    // Dobij odgovor asistenta i sačuvaj ga u bazu
    if ($run->status === 'completed') {
        $messages = OpenAI::threads()->messages()->list($run->thread_id);
        
        $generatedText = '';
        foreach ($messages->data as $message) {
            if ($message->role === 'assistant') {
                $generatedText = $message->content[0]->text->value;
                break;
            }
        }

        // Sačuvaj odgovor u bazi
        Chat::create([
            'user_id' => $user->id,
            'message' => $generatedText,
            'prompt' => $userPrompt
        ]);

        return response()->json([
            'success' => true,
            'message' => $generatedText,
        ]);
    } else {
        return response()->json([
            'success' => false,
            'error' => 'Run failed or did not complete successfully',
        ], 500);
    }
}


}
