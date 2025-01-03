<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;
use Google_Client;
use Illuminate\Auth\Events\Registered;
class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        // Validate request inputs
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'name' => ['required', 'max:255'],
            'password' => ['required', 'min:8', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Create a new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        
        event(new Registered($user));
        // Generate token
        $token = $user->createToken(Str::random(40))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful!',
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Login user and return token.
     */
    public function login(Request $request): JsonResponse
    {
        // Validate request inputs
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Attempt to find the user and verify password
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate token
        $token = $user->createToken(Str::random(40))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful!',
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Handle Google login.
     */

    public function googleLogin(Request $request)
    {
        $idToken = $request->input('id_token');

        if (!$idToken) {
            return response()->json([
                'success' => false,
                'message' => 'ID token is required.',
            ], 400);
        }

        // Verify the Google ID token
        $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]); // Specify your Google client ID
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ID token.',
            ], 401);
        }

        // Extract user info from payload
        $googleId = $payload['sub']; // Google user ID
        $email = $payload['email'];
        $name = $payload['name'];
        $profilePicture = $payload['picture'];

        // Find or create the user
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'photo' => $profilePicture,
                'password' => bcrypt(Str::random(16)), // Dummy password since Google handles auth
            ]
        );
  

        // Log in the user
        Auth::login($user);

        // Optionally, generate a personal access token (if using Sanctum or Passport)
        $token = $user->createToken('GoogleAuthToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful!',
            'data' => [
                'token' => $token,
                'photo' => $profilePicture,
                'user' => $user,
            ],
        ]);
    }

    /**
     * Logout user and revoke tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke all tokens for the authenticated user
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful!',
        ], Response::HTTP_OK);
    }
}
