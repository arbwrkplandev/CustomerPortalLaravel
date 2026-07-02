<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Plan::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOL));
        }

        $plans = $query->orderBy('sort_order')->orderBy('name')->paginate($request->integer('per_page', 20));

        return $this->paginated($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120|unique:plans,name',
            'description' => 'nullable|string|max:1000',
            'monthly_price' => 'required|numeric|min:0',
            'quarterly_price' => 'required|numeric|min:0',
            'annual_price' => 'required|numeric|min:0',
            'max_users' => 'nullable|integer|min:1|max:100000',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $payload = $validator->validated();
        $name = $payload['name'];

        $plan = Plan::create([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(4)),
            'description' => $payload['description'] ?? null,
            'monthly_price' => $payload['monthly_price'],
            'quarterly_price' => $payload['quarterly_price'],
            'annual_price' => $payload['annual_price'],
            'features' => $payload['features'] ?? null,
            'max_users' => $payload['max_users'] ?? 1,
            'is_active' => $payload['is_active'] ?? true,
            'sort_order' => $payload['sort_order'] ?? 0,
        ]);

        return $this->created($plan, 'Plan created successfully');
    }

    public function show(Plan $plan): JsonResponse
    {
        return $this->success($plan);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:120|unique:plans,name,' . $plan->id,
            'description' => 'nullable|string|max:1000',
            'monthly_price' => 'sometimes|required|numeric|min:0',
            'quarterly_price' => 'sometimes|required|numeric|min:0',
            'annual_price' => 'sometimes|required|numeric|min:0',
            'max_users' => 'nullable|integer|min:1|max:100000',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $payload = $validator->validated();

        if (array_key_exists('name', $payload)) {
            $payload['slug'] = Str::slug($payload['name']) . '-' . $plan->id;
        }

        $plan->update($payload);

        return $this->success($plan->fresh(), 'Plan updated successfully');
    }

    public function toggleStatus(Plan $plan): JsonResponse
    {
        $plan->update(['is_active' => !$plan->is_active]);

        return $this->success($plan->fresh(), 'Plan status updated');
    }
}
