<?php

namespace App\Support;

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InternalApiGateway
{
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, [], $query);
    }

    public function post(string $path, array $payload = []): array
    {
        return $this->request('POST', $path, $payload);
    }

    public function postWithFiles(string $path, array $payload = [], array $files = []): array
    {
        return $this->request('POST', $path, $payload, [], $files, false);
    }

    public function put(string $path, array $payload = []): array
    {
        return $this->request('PUT', $path, $payload);
    }

    public function patch(string $path, array $payload = []): array
    {
        return $this->request('PATCH', $path, $payload);
    }

    public function delete(string $path, array $payload = []): array
    {
        return $this->request('DELETE', $path, $payload);
    }

    public function forward(string $method, string $path, array $payload = [], array $query = [], array $files = [], bool $asJson = true): Response
    {
        $uri = '/api/v1' . $path;
        $currentUser = auth()->user();
        $parentRequest = request();

        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        $server = [
            'HTTP_ACCEPT' => $asJson ? 'application/json' : '*/*',
            'HTTP_HOST'   => $parentRequest->getHttpHost(),
            'SERVER_PORT' => $parentRequest->getPort(),
        ];

        if ($asJson) {
            $server['CONTENT_TYPE'] = 'application/json';
        }

        $subRequest = HttpRequest::create(
            $uri,
            $method,
            $payload,
            $parentRequest->cookies->all(),
            $files,
            $server,
            $asJson && !in_array($method, ['GET', 'DELETE'], true) ? json_encode($payload) : null
        );

        $subRequest->setUserResolver(fn () => auth()->user());
        $flashSnapshot = null;
        if ($parentRequest->hasSession()) {
            $session = $parentRequest->session();
            $subRequest->setLaravelSession($session);
            $flashSnapshot = $this->snapshotFlash($session);
        }

        if ($currentUser) {
            Auth::setUser($currentUser);
        }

        $response = app()->handle($subRequest);

        if ($flashSnapshot !== null && $parentRequest->hasSession()) {
            $this->restoreFlash($parentRequest->session(), $flashSnapshot);
        }

        return $response;
    }

    public function extractErrors(array $response): array
    {
        $errors = $response['errors'] ?? null;
        if (is_array($errors) && !empty($errors)) {
            return $errors;
        }

        return ['api' => [$response['message'] ?? 'API request failed.']];
    }

    public function toEntities(mixed $value): mixed
    {
        return ApiEntity::from($value);
    }

    public function toPaginator(array $response, int $defaultPerPage = 15): LengthAwarePaginator
    {
        $items = $this->toEntities($response['data'] ?? []);
        $meta = $response['meta'] ?? [];

        $paginator = new LengthAwarePaginator(
            $items,
            (int) ($meta['total'] ?? count((array) $items)),
            (int) ($meta['per_page'] ?? $defaultPerPage),
            (int) ($meta['current_page'] ?? 1),
            [
                'path' => url()->current(),
                'query' => request()->query(),
            ]
        );

        return $paginator;
    }

    protected function request(
        string $method,
        string $path,
        array $payload = [],
        array $query = [],
        array $files = [],
        bool $asJson = true
    ): array
    {
        $uri = '/api/v1' . $path;
        $currentUser = auth()->user();
        $parentRequest = request();

        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_HOST'   => $parentRequest->getHttpHost(),
            'SERVER_PORT' => $parentRequest->getPort(),
        ];

        if ($asJson) {
            $server['CONTENT_TYPE'] = 'application/json';
        }

        $subRequest = HttpRequest::create(
            $uri,
            $method,
            $payload,
            $parentRequest->cookies->all(),
            $files,
            $server,
            $asJson && !in_array($method, ['GET', 'DELETE'], true) ? json_encode($payload) : null
        );

        $subRequest->setUserResolver(fn () => auth()->user());
        $flashSnapshot = null;
        if ($parentRequest->hasSession()) {
            $session = $parentRequest->session();
            $subRequest->setLaravelSession($session);
            $flashSnapshot = $this->snapshotFlash($session);
        }

        if ($currentUser) {
            Auth::setUser($currentUser);
        }

        $response = app()->handle($subRequest);

        if ($flashSnapshot !== null && $parentRequest->hasSession()) {
            $this->restoreFlash($parentRequest->session(), $flashSnapshot);
        }
        $decoded = json_decode($response->getContent(), true);

        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'Unexpected non-JSON response from API bridge.',
                'errors' => ['api' => ['Unexpected API response.']],
            ];
        }

        return $decoded;
    }

    /**
     * Snapshot flash bag + keys so a sub-request cannot age or discard the parent's flash data.
     */
    protected function snapshotFlash(\Illuminate\Contracts\Session\Session $session): array
    {
        $flash = $session->get('_flash', ['old' => [], 'new' => []]);
        $keys = array_unique(array_merge($flash['old'] ?? [], $flash['new'] ?? []));
        $values = [];
        foreach ($keys as $key) {
            if ($session->has($key)) {
                $values[$key] = $session->get($key);
            }
        }
        return ['flash' => $flash, 'values' => $values];
    }

    protected function restoreFlash(\Illuminate\Contracts\Session\Session $session, array $snapshot): void
    {
        $session->put('_flash', $snapshot['flash']);
        foreach ($snapshot['values'] as $key => $value) {
            $session->put($key, $value);
        }
    }

}
