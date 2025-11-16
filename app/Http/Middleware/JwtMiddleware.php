<?php 
namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use \Tymon\JWTAuth\Exceptions;
 use \Tymon\JWTAuth\Exceptions\TokenExpiredException;
use \Tymon\JWTAuth\Exceptions\TokenInvalidException;
use \Tymon\JWTAuth\Exceptions\JWTException;
class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // محاولة قراءة التوكين وتوثيق المستخدم
            $user = JWTAuth::parseToken()->authenticate();

        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired. Please login again.'], 401);

        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Invalid token. Please login again.'], 401);

        } catch (JWTException $e) {
            return response()->json(['error' => 'Token not found. Please login.'], 401);
        }

         if (!$user) {
            return response()->json(['error' => 'Unauthorized user'], 401);
        }

         if (is_null($user->email_verified_at)) {
            return response()->json(['message' => 'Email not verified'], 403);
        }

        return $next($request);
    }
}
