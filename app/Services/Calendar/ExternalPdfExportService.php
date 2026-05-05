<?php

namespace App\Services\Calendar;

use Illuminate\Support\Facades\Http;

class ExternalPdfExportService
{
    public function enqueue(array $payload): array
    {
        $baseUrl = rtrim((string) config('pdf_export.base_url'), '/');
        $secret = (string) config('pdf_export.shared_secret');
        $timestamp = (string) time();
        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $signature = hash_hmac('sha256', $timestamp . '.' . $jsonBody, $secret);

        $response = Http::timeout((int) config('pdf_export.http_timeout_seconds', 20))
            ->withHeaders([
                'X-Export-Timestamp' => $timestamp,
                'X-Export-Signature' => $signature,
                'Content-Type' => 'application/json',
            ])
            ->withBody($jsonBody, 'application/json')
            ->post($baseUrl . '/jobs');

        $response->throw();

        return $response->json() ?? [];
    }

    public function status(string $externalJobId): array
    {
        $baseUrl = rtrim((string) config('pdf_export.base_url'), '/');

        $response = Http::timeout((int) config('pdf_export.http_timeout_seconds', 20))
            ->get($baseUrl . '/jobs/' . urlencode($externalJobId));

        $response->throw();

        return $response->json() ?? [];
    }

    public function downloadUrl(string $externalJobId): string
    {
        $baseUrl = rtrim((string) config('pdf_export.base_url'), '/');

        return $baseUrl . '/jobs/' . urlencode($externalJobId) . '/download';
    }
}
