<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OpenAIController extends Controller
{
    public function createEphemeralToken()
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
}
