<?php

namespace App\Support;

use Illuminate\Support\Str;

class CustomerAgreementStore
{
    private const BASE_DIR = 'customer-agreements';

    public function getRecords(int $customerId): array
    {
        $path = $this->recordsPath($customerId);
        if (!is_file($path)) {
            return [];
        }

        $json = file_get_contents($path);
        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        usort($decoded, function (array $a, array $b): int {
            return strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? ''));
        });

        return $decoded;
    }

    public function findRecord(int $customerId, string $recordId): ?array
    {
        foreach ($this->getRecords($customerId) as $record) {
            if ((string) ($record['id'] ?? '') === $recordId) {
                return $record;
            }
        }

        return null;
    }

    public function upsertRecord(int $customerId, array $record): array
    {
        $records = $this->getRecords($customerId);
        $recordId = (string) ($record['id'] ?? Str::uuid()->toString());
        $now = now()->toIso8601String();
        $record['id'] = $recordId;
        $record['updated_at'] = $now;
        if (!isset($record['created_at'])) {
            $record['created_at'] = $now;
        }

        $updated = false;
        foreach ($records as $idx => $existing) {
            if ((string) ($existing['id'] ?? '') === $recordId) {
                $record['created_at'] = (string) ($existing['created_at'] ?? $now);
                $records[$idx] = $record;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $records[] = $record;
        }

        $this->saveRecords($customerId, $records);

        return $record;
    }

    public function updateRecord(int $customerId, string $recordId, callable $mutator): ?array
    {
        $records = $this->getRecords($customerId);

        foreach ($records as $idx => $record) {
            if ((string) ($record['id'] ?? '') !== $recordId) {
                continue;
            }

            $next = $mutator($record);
            if (!is_array($next)) {
                return null;
            }

            $next['id'] = $recordId;
            $next['created_at'] = (string) ($record['created_at'] ?? now()->toIso8601String());
            $next['updated_at'] = now()->toIso8601String();
            $records[$idx] = $next;
            $this->saveRecords($customerId, $records);

            return $next;
        }

        return null;
    }

    public function writeDraftFile(int $customerId, string $agreementType, string $fileContent, string $extension = 'docx'): array
    {
        $safeBase = Str::of($agreementType)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9\-_]+/', '-')
            ->trim('-')
            ->toString();

        if ($safeBase === '') {
            $safeBase = 'agreement';
        }

        $normalizedExtension = ltrim(strtolower(trim($extension)), '.');
        if ($normalizedExtension === '') {
            $normalizedExtension = 'docx';
        }

        $fileName = $safeBase . '-' . now()->format('Ymd_His') . '.' . $normalizedExtension;
        $relativePublicPath = 'app/public/' . $customerId . '/' . $fileName;
        $absolutePath = storage_path($relativePublicPath);
        $dir = dirname($absolutePath);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create customer draft directory.');
        }

        $written = @file_put_contents($absolutePath, $fileContent);
        if ($written === false) {
            throw new \RuntimeException('Unable to save agreement draft file.');
        }

        return [
            'file_name' => $fileName,
            'relative_location' => str_replace('\\', '/', $relativePublicPath),
            'absolute_path' => $absolutePath,
        ];
    }

    private function saveRecords(int $customerId, array $records): void
    {
        $path = $this->recordsPath($customerId);
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create agreement storage directory.');
        }

        file_put_contents($path, json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function recordsPath(int $customerId): string
    {
        return storage_path('app/' . self::BASE_DIR . '/' . $customerId . '/index.json');
    }
}
