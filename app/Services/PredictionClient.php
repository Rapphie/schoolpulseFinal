<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PredictionClient
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
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
                $predictions = $data['predictions'] ?? [];

                // Python returns [{prediction_confidence: 0..1, risk_label: ...}, ...]
                // The rest of the PHP app expects a 0..100 percentage per student.
                return array_map(function ($item) {
                    if (!is_array($item) || !isset($item['prediction_confidence'])) {
                        return null;
                    }
                    return round(((float) $item['prediction_confidence']) * 100, 1);
                }, $predictions);
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
                return isset($data['prediction_confidence'])
                    ? round(((float) $data['prediction_confidence']) * 100, 1)
                    : null;
            }
        } catch (\Throwable $e) {
        }
        return null;
    }

    /**
     * Fetch the feature tables (Table 1, 2, 3) from the Python API.
     *
     * @return array|null
     */
    public function getFeatureTables(): ?array
    {
        try {
            $resp = Http::timeout(15)->get(rtrim($this->baseUrl, '/') . '/features/tables');
            if ($resp->successful()) {
                $data = $resp->json();
                if ($data['success'] ?? false) {
                    return $data;
                }
            }
        } catch (\Throwable $e) {
            // Log error if needed
        }
        return null;
    }
}
