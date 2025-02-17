<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Auth;

class FcmTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string|unique:fcm_tokens,token',
        ]);


        // Ako korisnik već ima token, ažuriraj ga
        FcmToken::updateOrCreate(
            ['token' => $request->token]
        );

        return response()->json(['message' => 'Token saved successfully'], 200);
    }
}
