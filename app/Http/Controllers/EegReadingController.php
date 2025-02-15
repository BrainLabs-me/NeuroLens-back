<?php

namespace App\Http\Controllers;

use App\Models\EegRawReading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EegReadingController extends Controller
{
    public function store(Request $request)
    {
        // Validiraj podatke ( prilagodi potrebama )
        $validated = $request->validate([
            'value'       => 'required|numeric',
            'recorded_at' => 'required|date',
        ]);
    
        $userId = Auth::id();
    
        // Pošto više nemamo array raw_data, možemo ga preskočiti ili setovati na null
        // jer u bazi možeš ostaviti polje raw_data ako želiš, ili ga obrisati iz migracije.
        // Ovde snimam value u 'focus' polje, recimo:
        $reading = EegRawReading::create([
            'user_id'     => $userId,
            'raw_data'    => null, // ili izbaciti ako kolona nije obavezna
            'focus'       => $validated['value'], 
            'recorded_at' => $validated['recorded_at'],
        ]);

        return response()->json([
            'message' => 'EEG zapis uspešno snimljen.',
            'data' => $reading
        ], 201);
    }

    /**
     * Primer jednostavnog algoritma za racunanje fokusa:
     * Uzima sve vrednosti iz raw_data i racuna prosek.
     */
    private function calculateFocus(array $rawData)
    {
        $sum = 0;
        $count = 0;
        // Pretpostavljamo structure: [channel1 => [float, float...], channel2 => [float, ...], ...]
        foreach ($rawData as $channelValues) {
            if (!is_array($channelValues)) {
                continue;
            }
            foreach ($channelValues as $value) {
                $sum += (float)$value;
                $count++;
            }
        }

        if ($count === 0) {
            return 0.0;
        }

        return $sum / $count; // prosečna vrednost
    }
}
