<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    public function current(Request $request)
    {
        $request->validate([
            'lat' => ['required','numeric'],
            'lon' => ['required','numeric'],
        ]);

        $key = config('services.openweather.key');
        if (!$key) {
            return response()->json(['error' => 'API key missing'], 500);
        }

        // OpenWeather Current Weather
        $res = Http::get('https://api.openweathermap.org/data/2.5/weather', [
            'lat'   => $request->lat,
            'lon'   => $request->lon,
            'appid' => $key,
            'units' => 'metric',
            'lang'  => 'ja',
        ]);

        if ($res->failed()) {
            return response()->json(['error' => 'fetch failed', 'detail' => $res->json()], 502);
        }

        $data = $res->json();
        // 例: "曇りがち", "小雨" など
        $desc = $data['weather'][0]['description'] ?? null;
        $temp = data_get($data, 'main.temp');

        return response()->json([
            'description' => $desc,
            'temp'        => $temp,
        ]);
    }
}