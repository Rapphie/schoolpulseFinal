<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PredictionClient
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        // Default to local dev server; can be moved to config later
        $this->baseUrl = $baseUrl ?: 'http://127.0.0.1:8001';
    }

    public function predictBatch(array $batchVectors): array
    {
        if (empty($batchVectors)) return [];
        try {
            $resp = Http::timeout(8)->post(rtrim($this->baseUrl, '/') . '/prediction_probability_batch', [
                'batch' => array_values($batchVectors),
            ]);
            if ($resp->successful()) {
                $data = $resp->json();
                return $data['predictions'] ?? [];
            }
        } catch (\Throwable $e) {

        }
        return [];
    }

    public function predictSingle(array $orderedFeatures): ?float
    {
        try {
            $resp = Http::timeout(5)->post(rtrim($this->baseUrl, '/') . '/prediction_probability', [
                'values' => $orderedFeatures,
            ]);
            if ($resp->successful()) {
                $data = $resp->json();
                return isset($data['prediction_confidence']) ? (float)$data['prediction_confidence'] : null;
            }
        } catch (\Throwable $e) {
        }
        return null;
    }
}
