<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAIService
{
    public function generateFeedbackSummary(array $promptData, string $fallbackSummary): array
    {
        $apiKey = (string) config('services.openai.api_key', '');

        if ($apiKey === '') {
            return [
                'summary' => $fallbackSummary,
                'source' => 'fallback',
                'error' => 'OPENAI_API_KEY is not configured. Rule-based summary was used.',
            ];
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->acceptJson()
                ->post('https://api.openai.com/v1/responses', [
                    'model' => config('services.openai.model', 'gpt-5.1-mini'),
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an academic course evaluation assistant. Use only the aggregated data provided. Do not invent student identities, names, emails, matric numbers, or personal details. Write concise, professional output for teaching improvement and research reporting.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Generate a course feedback AI summary for MY Penguin-LAB/ShellFix.\n\nRequired sections:\n- Overall interpretation\n- Strengths\n- Weaknesses\n- Suggested CQI actions\n- Research paper paragraph draft\n- Possible discussion points\n\nAggregated feedback data only:\n" . json_encode($promptData, JSON_PRETTY_PRINT),
                        ],
                    ],
                    'max_output_tokens' => 1400,
                ]);

            if (! $response->successful()) {
                return [
                    'summary' => $fallbackSummary,
                    'source' => 'fallback',
                    'error' => 'OpenAI request failed with HTTP ' . $response->status() . '. Rule-based summary was used.',
                ];
            }

            $summary = $this->extractText($response->json());

            if ($summary === '') {
                return [
                    'summary' => $fallbackSummary,
                    'source' => 'fallback',
                    'error' => 'OpenAI returned no readable summary. Rule-based summary was used.',
                ];
            }

            return [
                'summary' => $summary,
                'source' => 'openai',
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'summary' => $fallbackSummary,
                'source' => 'fallback',
                'error' => 'OpenAI request could not be completed. Rule-based summary was used.',
            ];
        }
    }

    private function extractText(array $payload): string
    {
        if (! empty($payload['output_text']) && is_string($payload['output_text'])) {
            return trim($payload['output_text']);
        }

        $chunks = [];

        foreach (($payload['output'] ?? []) as $output) {
            foreach (($output['content'] ?? []) as $content) {
                if (isset($content['text']) && is_string($content['text'])) {
                    $chunks[] = $content['text'];
                }
            }
        }

        return trim(implode("\n", $chunks));
    }
}
