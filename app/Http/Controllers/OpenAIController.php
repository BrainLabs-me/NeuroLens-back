<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

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

    public function chatStream(Request $request)
    {
        // Preuzimamo prompt koji nam stiže iz body-a POST zahtjeva.
        $userPrompt = $request->input('prompt', 'Zdravo, kako si?');

        // Postavljamo defaultne parametre za Chat completions
        $messages = [
            [
                'role' => 'system',
                'content' => 'Ti si asistent. Odgovaraj kratko i jasno.' 
            ],
            [
                'role' => 'user',
                'content' => $userPrompt
            ],
        ];

        // Pozivamo createStream za Chat API
        $stream = OpenAI::chat()->createStreamed([
            'model' => 'gpt-4o-mini', // ili 'gpt-4' ako imate pristup
            'messages' => $messages,
            'stream' => true,
        ]);

        // Vraćamo SSE (Server-Sent Events) response
        return response()->stream(function () use ($stream) {
            // Stream je iterabilan objekat. Čim dobije nove podatke, vraća ih.
            foreach ($stream as $response) {
                // Svaki $response je segment ChatCompletionChunk objekta
                $delta = $response->choices[0]->delta->content ?? '';

                // Ako postoji neki text u delta, šaljemo ga klijentu
                if (!empty($delta)) {
                    echo "data: {$delta}\n\n";
                    // 'data:' je SSE format, '\n\n' označava kraj 
                    ob_flush();
                    flush();
                }
            }

            // Kada je streaming gotovo, šaljemo specijalni event 'done'
            echo "event: done\n";
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type'  => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection'    => 'keep-alive',
        ]);
    }
}
