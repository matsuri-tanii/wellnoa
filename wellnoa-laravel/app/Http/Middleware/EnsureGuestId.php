<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AnonymousUser;

class EnsureGuestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $uuid = $request->cookie('guest_uuid');
        if (!$uuid) {
            $uuid = (string) Str::uuid();
        }
        
        $anon = AnonymousUser::firstOrCreate(
            ['anonymous_id' => $uuid],
            []
        );
        $numericId = (string) $anon->id;

        $request->cookies->set('guest_uuid', $uuid);
        $request->cookies->set('guest_numeric_id', $numericId);

        $response = $next($request);

        $minutes = 60 * 24 * 365; // 1年
        $response->headers->setCookie(cookie('guest_uuid', $uuid, $minutes));
        $response->headers->setCookie(cookie('guest_numeric_id', $numericId, $minutes));

        return $response;
    }
}