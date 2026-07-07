<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\DatabaseSchemaExplorer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DocsController extends Controller
{
    public function apiPortal(): Response
    {
        return response()->view('docs.api-portal');
    }

    public function openApiYaml(): Response
    {
        $path = base_path('docs/openapi-v1.yaml');
        abort_unless(is_file($path), 404, 'OpenAPI specification not found.');

        return response(file_get_contents($path) ?: '', 200, [
            'Content-Type' => 'application/yaml; charset=utf-8',
        ]);
    }

    public function databasePortal(): Response
    {
        return response()->view('docs.database-portal');
    }

    public function databaseSchema(DatabaseSchemaExplorer $explorer): JsonResponse
    {
        return response()->json($explorer->inspect());
    }
}
