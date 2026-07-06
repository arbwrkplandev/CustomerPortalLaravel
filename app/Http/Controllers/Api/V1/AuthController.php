<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
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
        $normalizedLogin = trim((string) $loginValue);
        $normalizedCorpId = strtoupper(trim((string) $request->input('corp_id', '')));

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

        $candidate = User::query()
            ->with('tenant')
            ->where('is_active', true)
            ->where(function ($query) use ($normalizedLogin) {
                $query->where('email', $normalizedLogin)
                    ->orWhere('username', $normalizedLogin);
            })
            ->first();

        if (!$candidate) {
            return $this->notFound('This account is not registered. Please check your email or username, or contact support.');
        }

        if ($candidate->isCustomer()) {
            if ($normalizedCorpId === '') {
                return $this->validationError([
                    'corp_id' => ['Corp ID is required for customer login.'],
                ], 'Customer login requires a valid Corp ID.');
            }

            $tenantCorpId = strtoupper((string) optional($candidate->tenant)->corp_id);
            if ($tenantCorpId === '' || !hash_equals($tenantCorpId, $normalizedCorpId)) {
                return $this->unauthorized('Corp ID does not match this customer account.');
            }
        }

        $payload = $this->authService->attempt(
            $normalizedLogin,
            $request->password,
            $request->boolean('remember', false),
            $candidate->isCustomer() ? $normalizedCorpId : null
        );

        if (!$payload) {
            return $this->unauthorized(
                $candidate->isCustomer()
                    ? 'Wrong password. Please try again or reset your password.'
                    : 'Wrong email/username or password. Please try again.'
            );
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
