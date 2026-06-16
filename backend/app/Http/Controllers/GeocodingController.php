<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeocodingController extends Controller
{
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:500',
            'limit' => 'sometimes|integer|min:1|max:20',
            'addressdetails' => 'sometimes|boolean',
            'extratags' => 'sometimes|boolean',
        ]);

        $response = Http::withHeaders([
            'User-Agent' => config('app.name', 'CarpoolApp') . ' GeocodingProxy',
        ])->timeout(10)->get('https://nominatim.openstreetmap.org/search', [
            'format' => 'json',
            'q' => $validated['q'],
            'limit' => $validated['limit'] ?? 5,
            'addressdetails' => ($validated['addressdetails'] ?? true) ? 1 : 0,
            'extratags' => ($validated['extratags'] ?? false) ? 1 : 0,
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Geocoding service unavailable'], 502);
        }

        return response()->json($response->json());
    }
}
