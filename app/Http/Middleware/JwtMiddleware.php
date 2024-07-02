<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $app_id = env('APP_ID');
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            // $payload = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            // dd(env('URL_SIFA_APLIKASI_AKUN_SERVICE') .'/v1/me');
            $response = Http::withToken($token)->withHeaders([
                'appid' => $app_id
            ])->get(env('URL_SIFA_APLIKASI_AKUN_SERVICE') .'/v1/me');
            // dd($response->json());
            if ($response->successful()) {
                $user = $response->json();

                $request->attributes->set('user', $user);
            } else {
                $res = $response->json();
                return response()->json([
                    'msg' => $res['msg']
                ], $response->status());
            }
        } catch (ExpiredException $e) {
            return response()->json([
                'error' => 'Provided token is expired.',
            ], 403);
        } catch (SignatureInvalidException $e) {
            return response()->json([
                'error' => 'Wrong signature token or secret key.',
            ], 401);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 401);
        }

        return $next($request);
    }
}
