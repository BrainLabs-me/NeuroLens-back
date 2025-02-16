<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Message;
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
                "instructions"=> "Zoves se Aurora. Ti si psihoterapeut koji pomaze ljudima da poboljsaju svoj fokus tokom dana.Trebas da im dajes savjete vjezbe i preporuke za bolji fokus na svoje dnevne obaveze.Napravljena si od strane BrainLabs tima ucenika ETS 'Vaso Aligrudic'.Razgovaras sa Anom."

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
                'model' => 'gpt-4o-realtime-preview-2024-12-17',
                'voice' => 'shimmer',
                'temperature' => 0.3,
                "instructions"=> "I am a psychotherapist and an expert in the area of focus for individuals. My name is Aurora.You speaking with Ana."
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
    $user = Auth::user();

    try {
        // Kreiraj Thread
        $thread = OpenAI::threads()->create(['messages' =>
                [
                    [
                        'role' => 'user',
                        'content' => 'Moje ime je ' . $user->name,
                    ],
                ],
        ]);

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
            Message::create([
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
    // Validate the incoming request
    $validated = $request->validate([
        'input' => 'required|string',
    ], [
        'input.required' => 'The input text is required.',
        'input.string' => 'The input must be a string.',
    ]);

    // Retrieve input data
    $inputText = $validated['input'];

    try {
        // Generate speech using OpenAI's TTS
        $audioContent = OpenAI::audio()->speech([
            'model' => 'tts-1',
            'input' => $inputText,
            'voice' => 'alloy',
        ]);

        // Verify that audioContent is not empty
        if (!$audioContent) {
        }

       
        $contentType = 'audio/mpeg';

        // Optionally, determine the file extension based on content type
        $fileExtension = 'mp3';

        // Return the audio as a streamed response with appropriate headers
        return Response::make($audioContent, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="speech.' . $fileExtension . '"',
            'Content-Length' => strlen($audioContent),
        ]);

    } catch (Exception $e) {
        // Log the error for debugging

        // Return a JSON response with the error message
        return response()->json([
            'success' => false,
            'error' => 'Failed to stream audio: ' . $e->getMessage(),
        ], 500);
    }
}

public function createThreadGuest(Request $request)
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

public function sendMessageGuest(Request $request)
{
    $userPrompt = $request->input('prompt', 'Zdravo, kako si?');
    $threadId = $request->input('thread_id');  

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
        $assistantId = 'asst_mepEpGvVGZl2G6A9P0zZ7FPX';
        $run = OpenAI::threads()->runs()->create(
            threadId: $threadId,
            parameters: [
                'assistant_id' => $assistantId,
            ]
        );
    
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
    
            $generatedText = '';
            foreach ($messages->data as $msg) {
                if ($msg->role === 'assistant') {
                    // Adjust this to match how your response is structured
                    $generatedText = $msg->content[0]->text->value ?? '';
                    break;
                }
            }
    
            // Save to your database, return to user, etc.
            return response()->json([
                'success' => true,
                'message' => $generatedText,
            ]);
        }
    
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
}



