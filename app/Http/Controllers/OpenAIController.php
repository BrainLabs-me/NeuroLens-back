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
                'content' => 'cao kkao si', // User message
            ]
        );
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to add message to thread: ' . $e->getMessage(),
        ], 500);
    }
    
    // Step 2: Run the assistant with the existing thread
    try {
        $assistantId = 'asst_mepEpGvVGZl2G6A9P0zZ7FPX';  // Set your assistant's ID
        $run = OpenAI::threads()->runs()->create(
            threadId: $threadId, 
            parameters: [
                'assistant_id' => $assistantId,
            ]
        );
    
        // Check the run status and wait for it to complete
        $counter = 0;
        while ($run->status !== 'completed') {
            // Retrieve current run status
            $run = OpenAI::threads()->runs()->retrieve([
                'thread_id' => $threadId, 
                'run_id' => $run->id
            ]);
    
            // Debugging print every 10 iterations to see the current run status
            if ($counter % 10 == 0) {
                // You can log or print the status for debugging purposes
                echo "Run status: " . $run->status . "\n";
            }
    
            // Increase counter and wait before retrying
            $counter++;
            sleep(5);
        }
    
        // Step 3: Once completed, retrieve the assistant's response
        if ($run->status === 'completed') {
            // Retrieve all messages in the thread
            $messages = OpenAI::threads()->messages()->list($run->thread_id);
    
            $generatedText = '';
    
            // Loop through the messages to find the assistant's response
            foreach ($messages->data as $message) {
                if ($message->role === 'assistant') {
                    // Extract assistant's response content
                    $generatedText = $message->content; // Make sure this field is correct
                    break;
                }
            }
    
            // If we have a valid generated response, save it to the database
            if (!empty($generatedText)) {
                Chat::create([
                    'user_id' => $user->id,
                    'message' => $generatedText,
                    'prompt' => 'cao kkao si', // Use the user prompt
                ]);
    
                // Return the response with the assistant's generated text
                return response()->json([
                    'success' => true,
                    'message' => $generatedText,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'No response from assistant.',
                ], 500);
            }
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Run failed or did not complete successfully.',
            ], 500);
        }
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to run the assistant: ' . $e->getMessage(),
        ], 500);
    }if ($run->status === 'completed') {
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
