<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Chat;
use Http\Discovery\Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
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
    $threadId = $request->input('thread_id');  
    $user = Auth::user();

    if (!$threadId) {
        return response()->json([
            'success' => false,
            'error' => 'Thread ID is required.'
        ], 400);
    }

    try {
        $message = OpenAI::threads()->messages()->create(
            $threadId, [
                'role' => 'user',
                'content' =>  $userPrompt
            ]
        );
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to add message to thread: ' . $e->getMessage(),
        ], 500);
    }



    try {
        // 1) Create the run
        $assistantId = 'asst_mepEpGvVGZl2G6A9P0zZ7FPX';
        $run = OpenAI::threads()->runs()->create(
            threadId: $threadId,
            parameters: [
                'assistant_id' => $assistantId,
            ]
        );
    
        // 2) Poll for completion
        $maxAttempts = 20;
        $attempt = 0;
        $sleepSeconds = 1;
    
        while ($attempt < $maxAttempts) {
            $run = OpenAI::threads()->runs()->retrieve($threadId, $run->id);
    
            if ($run->status === 'completed') {
                break;
            }
    
            if ($run->status === 'failed') {
                throw new \Exception('Run failed to complete');
            }
    
            sleep($sleepSeconds);
            $attempt++;
        }
    
        // 3) If it's completed, get the messages
        if ($run->status === 'completed') {
            $messages = OpenAI::threads()->messages()->list($threadId);
    
            // Now you can find the assistant’s most recent response
            $generatedText = '';
            foreach ($messages->data as $msg) {
                if ($msg->role === 'assistant') {
                    // Adjust this to match how your response is structured
                    $generatedText = $msg->content[0]->text->value ?? '';
                    break;
                }
            }
    
            // Save to your database, return to user, etc.
            Chat::create([
                'user_id' => $user->id,
                'message' => $generatedText,
                'prompt'  => $userPrompt
            ]);
    
            return response()->json([
                'success' => true,
                'message' => $generatedText,
            ]);
        }
    
        // If we exit the loop and never hit 'completed', handle that gracefully
        return response()->json([
            'success' => false,
            'error' => 'Run is still queued after maximum attempts',
        ], 500);
    
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to run assistant: ' . $e->getMessage(),
        ], 500);
    }
}

public function streamAudio(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'input' => 'required|string',
        ]);

        // Retrieve input data
        $inputText = $request->input('input');

        try {
            // Initialize your TTS client
            // Replace with your actual client initialization

            // Initiate the speech stream
            $stream = OpenAI::audio()->speech([
                'model' => 'tts-1',
                'input' => $inputText,
                'voice' => 'alloy',
            ]);

            // Define response headers for audio streaming
            $headers = [
                'Content-Type' => 'audio/mpeg', // Update based on your audio format
                'Transfer-Encoding' => 'chunked',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
            ];

            // Stream the audio to the client
            return Response::json([
                'success'=> true,
                'audio' => $stream,
            ]);

        } catch (Exception $e) {
            // Log the error if necessary
            // Log::error('TTS Streaming Error: ' . $e->getMessage());

            // Return a JSON error response
            return response()->json([
                'success' => false,
                'error' => 'Failed to stream audio: ' . $e->getMessage(),
            ], 500);
        }
    }
}
