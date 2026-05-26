<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnonymousUser
{
    private const COOKIE_NAME = 'anon_uid';
    private const COOKIE_TTL_MINUTES = 60 * 24 * 365 * 5; // 5 years

    public function handle(Request $request, Closure $next): Response
    {
        $uid = $request->cookie(self::COOKIE_NAME);

        $user = $uid ? User::find($uid) : null;

        if (! $user) {
            $preferred = $request->getPreferredLanguage(['ja-JP', 'en-US']) ?? 'ja-JP';
            $user = User::create([
                'locale' => str_replace('_', '-', $preferred),
                'region' => 'JP',
                'last_active_at' => now(),
            ]);
            Cookie::queue(self::COOKIE_NAME, $user->id, self::COOKIE_TTL_MINUTES);
        } else {
            $user->forceFill(['last_active_at' => now()])->saveQuietly();
        }

        Auth::setUser($user);

        return $next($request);
    }
}
