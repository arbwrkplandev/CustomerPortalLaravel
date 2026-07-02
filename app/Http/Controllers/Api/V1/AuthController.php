<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(name="Authentication", description="Auth endpoints - provider-agnostic")
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $authService) {}

    /**
     * @OA\Post(
     *   path="/api/v1/auth/login",
     *   tags={"Authentication"},
     *   summary="Login user",
     *   @OA\RequestBody(required=true,
    *     @OA\JsonContent(required={"login","password"},
    *       @OA\Property(property="login", type="string", example="admin@wrkplan.com"),
     *       @OA\Property(property="password", type="string", example="password"),
    *       @OA\Property(property="corp_id", type="string", example="ACME-IND"),
     *       @OA\Property(property="remember", type="boolean", example=false)
     *     )
     *   ),
     *   @OA\Response(response=200, description="Login successful"),
     *   @OA\Response(response=401, description="Invalid credentials"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $loginValue = $request->input('login', $request->input('email'));

        $validator = Validator::make($request->all(), [
            'login'    => 'nullable|string|max:255',
            'email'    => 'nullable|string|max:255',
            'password' => 'required|string',
            'corp_id'  => 'nullable|string|max:30',
            'remember' => 'boolean',
        ]);

        if ($validator->fails() || empty($loginValue)) {
            if (empty($loginValue)) {
                $validator->errors()->add('login', 'The login field is required.');
            }
            return $this->validationError($validator->errors());
        }

        $payload = $this->authService->attempt(
            (string) $loginValue,
            $request->password,
            $request->boolean('remember', false),
            $request->input('corp_id')
        );

        if (!$payload) {
            return $this->unauthorized('Invalid credentials. Check Corp ID, username/email, and password.');
        }

        return $this->success($payload, 'Login successful');
    }

    /**
     * @OA\Post(path="/api/v1/auth/logout", tags={"Authentication"}, summary="Logout user",
     *   @OA\Response(response=200, description="Logged out")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();
        return $this->success(null, 'Logged out successfully');
    }

    /**
     * @OA\Get(path="/api/v1/auth/me", tags={"Authentication"}, summary="Get current user",
     *   @OA\Response(response=200, description="Current user payload"),
     *   @OA\Response(response=401, description="Not authenticated")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $payload = $this->authService->currentUserPayload();
        if (!$payload) {
            return $this->unauthorized();
        }
        return $this->success($payload);
    }
}
