<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller {
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers, ThrottlesLogins;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::RANDOM_POEM;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        // Remove guest middleware for API - we want to allow repeated login requests
        // The guest middleware would redirect authenticated users, which breaks API functionality
    }

    /**
     * Handle a login request to the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|Response|void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request) {
        // For API: Allow authenticated users to login again for token refresh
        // This is different from web apps where guest middleware prevents re-login
        // API behavior: generate new token and revoke old ones (implemented in sendLoginResponse)
        
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            $user = $this->guard()->user();
            if ($user->password !== '' && !$user->hasVerifiedEmail()) {
                $this->guard()->logout();
                return $this->responseError('Email not verified', 403, ['error' => 'email_not_verified']);
            }
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function sendLoginResponse(Request $request) {
        /** @var \App\User $user */
        $user = $this->guard()->user();
        // Use DB transaction to ensure single active token invariant under concurrent logins
        $tokenResult = null;
        $oldTokenIds = [];
        DB::transaction(function () use ($user, &$tokenResult, &$oldTokenIds) {
            // Create new token first (Passport persists it)
            $tokenResult = $user->createToken('openapi');
            $newTokenId  = $tokenResult->token->id;

            // Collect currently active openapi tokens (excluding the freshly created one)
            $oldTokenIds = $user->tokens()
                ->where('name', 'openapi')
                ->where('revoked', false)
                ->where('id', '!=', $newTokenId)
                ->pluck('id')
                ->all();

            if ($oldTokenIds) {
                try {
                    $user->tokens()
                        ->whereIn('id', $oldTokenIds)
                        ->update(['revoked' => true]);
                } catch (\Throwable $e) {
                    // Report but do not rollback successful new token issuance
                    report($e); // English comment: log revocation failure for investigation
                }
            }
        });

        if (!$tokenResult) {
            // Defensive: should never happen, but avoid fatal error
            return $this->responseError('Token creation failed', 500);
        }
        $accessToken = $tokenResult->accessToken;
        $issuedAt    = now();
        $expiresAt   = $tokenResult->token->expires_at; // may be null if no expiry configured
        $expiresIn   = $expiresAt ? $expiresAt->diffInSeconds($issuedAt) : null;

        $this->clearLoginAttempts($request);

        return $this->responseSuccess([
            'access_token' => $accessToken,
            'token_type'   => 'Bearer',
            'issued_at'    => $issuedAt->toIso8601String(),
            'expires_in'   => $expiresIn,
            'expires_at'   => $expiresAt ? $expiresAt->toIso8601String() : null,
        ]);
    }

    /**
     * Validate the user login request.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request) {
        $request->validate([
            $this->username() => 'required|string',
            'password'        => 'required|string'
        ]);
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username() {
        return 'email';
    }

    /**
     * Get the response for a failed login attempt.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendFailedLoginResponse(Request $request) {
        return response()->json([
            'message' => '用户名或密码错误。',
            'errors'  => [
                $this->username() => ['用户名或密码错误。']
            ]
        ], 422);
    }

    /**
     * Redirect the user after determining they are locked out.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendLockoutResponse(Request $request) {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        return response()->json([
            'message' => '登录尝试次数过多，请等待 ' . $seconds . ' 秒后重试。'
        ], 429);
    }

    /**
     * The user has been authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed                    $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user) {
        // Return null to continue with sendLoginResponse
        return null;
    }

    /**
     * Get the post-registration redirect path.
     *
     * @return string
     */
    public function redirectPath() {
        // This should not be called in API context, but return a safe path
        return '/';
    }
}
