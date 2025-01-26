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

    public function chat(Request $request) 
    {
        $userPrompt = $request->input('prompt', 'Zdravo, kako si?');
        $user = Auth::user();
    
        // Step 1: Kreiraj Thread (razgovor)
        try {
            $thread = OpenAI::beta()->threads()->create();
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create a thread: ' . $e->getMessage(),
            ], 500);
        }
    
        // Step 2: Dodaj korisničku poruku u thread
        try {
            $message = OpenAI::beta()->threads()->messages()->create(
                $thread->id, [
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
    
        // Step 3: Pokreni Asistenta sa kreiranim thread-om
        try {
            $assistantId = 'asst_mepEpGvVGZl2G6A9P0zZ7FPX'; // Postavi ID asistenta, mora biti prethodno kreiran
            $run = OpenAI::beta()->threads()->runs()->createAndPoll(
                $thread->id,
                [
                    'assistant_id' => $assistantId,
                    'instructions' => 'Please address the user as Jane Doe. The user has a premium account.',
                ]
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to run the assistant: ' . $e->getMessage(),
            ], 500);
        }
    
        // Step 4: Dobij odgovor asistenta i sačuvaj razgovor
        if ($run->status === 'completed') {
            $messages = OpenAI::beta()->threads()->messages()->list($run->thread_id);
            
            $generatedText = '';
            foreach ($messages->data as $message) {
                if ($message->role === 'assistant') {
                    $generatedText = $message->content[0]->text->value;
                    break;
                }
            }
    
            // Sačuvaj odgovor u bazu
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
