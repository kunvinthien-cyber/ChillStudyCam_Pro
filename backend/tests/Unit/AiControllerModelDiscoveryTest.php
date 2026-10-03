<?php

namespace Tests\Unit;

use App\Http\Controllers\AiController;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiControllerModelDiscoveryTest extends TestCase
{
    public function test_it_uses_a_discovered_model_when_an_older_model_is_not_available(): void
    {
        Cache::flush();
        config([
            'ai.supported_models.gemini.api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
        ]);
        Http::fake(function (Request $request) {
            if ($request->url() === 'https://generativelanguage.googleapis.com/v1beta/models') {
                return Http::response([
                    'models' => [
                        [
                            'name' => 'models/gemini-3.8-flash-tts',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                        [
                            'name' => 'models/gemini-2.5-flash',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                        [
                            'name' => 'models/gemini-2.0-flash',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                        [
                            'name' => 'models/gemini-1.5-pro',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                        [
                            'name' => 'models/gemini-embedding-001',
                            'supportedGenerationMethods' => ['embedContent'],
                        ],
                    ],
                ]);
            }

            if (str_contains($request->url(), 'models/gemini-2.5-flash:generateContent')) {
                return Http::response(['error' => ['message' => 'Model unavailable']], 404);
            }

            if (str_contains($request->url(), 'models/gemini-2.0-flash:generateContent')) {
                return Http::response([
                    'candidates' => [
                        ['content' => ['parts' => [['text' => 'AI reply']]]],
                    ],
                ]);
            }

            return Http::response([], 500);
        });

        $controller = new class extends AiController
        {
            public function askGemini(string $apiKey): array
            {
                return $this->fetchReplyText($apiKey, [
                    ['role' => 'user', 'parts' => [['text' => 'Hello']]],
                ]);
            }
        };

        $result = $controller->askGemini('test-api-key');

        $this->assertSame('AI reply', $result['reply']);
        $this->assertNull($result['error']);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v1beta/models')
            && $request->hasHeader('x-goog-api-key', 'test-api-key'));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'gemini-1.5-pro:generateContent'));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'gemini-3.8-flash-tts:generateContent'));
    }

    public function test_it_falls_back_when_model_does_not_support_multi_turn_chat(): void
    {
        Cache::flush();
        config([
            'ai.supported_models.gemini.api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent',
        ]);
        Http::fake(function (Request $request) {
            if ($request->url() === 'https://generativelanguage.googleapis.com/v1beta/models') {
                return Http::response([
                    'models' => [
                        [
                            'name' => 'models/gemini-3.8-flash',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                        [
                            'name' => 'models/gemini-2.5-flash',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                    ],
                ]);
            }

            if (str_contains($request->url(), 'models/gemini-3.8-flash:generateContent')) {
                return Http::response([
                    'error' => [
                        'message' => 'Multiturn chat is not enabled for this model',
                    ],
                ], 400);
            }

            if (str_contains($request->url(), 'models/gemini-2.5-flash:generateContent')) {
                return Http::response([
                    'candidates' => [
                        ['content' => ['parts' => [['text' => 'Fallback reply']]]],
                    ],
                ]);
            }

            return Http::response([], 500);
        });

        $controller = new class extends AiController
        {
            public function askGemini(string $apiKey): array
            {
                return $this->fetchReplyText($apiKey, [
                    ['role' => 'user', 'parts' => [['text' => 'Hello']]],
                    ['role' => 'model', 'parts' => [['text' => 'Hi']]],
                    ['role' => 'user', 'parts' => [['text' => 'Follow-up']]],
                ]);
            }
        };

        $result = $controller->askGemini('test-api-key');

        $this->assertSame('Fallback reply', $result['reply']);
        $this->assertNull($result['error']);
    }

    public function test_it_prioritizes_the_configured_gemini_flash_latest_model(): void
    {
        Cache::flush();
        config([
            'ai.supported_models.gemini.api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent',
        ]);
        Http::fake(function (Request $request) {
            if ($request->url() === 'https://generativelanguage.googleapis.com/v1beta/models') {
                return Http::response([
                    'models' => [
                        [
                            'name' => 'models/gemini-2.5-flash',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                    ],
                ]);
            }

            if (str_contains($request->url(), 'models/gemini-flash-latest:generateContent')) {
                return Http::response([
                    'candidates' => [
                        ['content' => ['parts' => [['text' => 'Original model reply']]]],
                    ],
                ]);
            }

            return Http::response([], 500);
        });

        $controller = new class extends AiController
        {
            public function askGemini(string $apiKey): array
            {
                return $this->fetchReplyText($apiKey, [
                    ['role' => 'user', 'parts' => [['text' => 'Hello']]],
                ]);
            }
        };

        $result = $controller->askGemini('test-api-key');

        $this->assertSame('Original model reply', $result['reply']);
        $this->assertNull($result['error']);
    }

    public function test_it_falls_back_when_a_model_returns_a_successful_but_malformed_response(): void
    {
        Cache::flush();
        config([
            'ai.supported_models.gemini.api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent',
        ]);
        Http::fake(function (Request $request) {
            if ($request->url() === 'https://generativelanguage.googleapis.com/v1beta/models') {
                return Http::response([
                    'models' => [
                        [
                            'name' => 'models/gemini-3.6-flash',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                        [
                            'name' => 'models/gemini-2.5-flash',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                    ],
                ]);
            }

            if (str_contains($request->url(), 'models/gemini-3.6-flash:generateContent')) {
                return Http::response([
                    'candidates' => [
                        ['content' => [], 'finishReason' => 'MALFORMED_RESPONSE'],
                    ],
                ]);
            }

            if (str_contains($request->url(), 'models/gemini-2.5-flash:generateContent')) {
                return Http::response([
                    'candidates' => [
                        ['content' => ['parts' => [['text' => 'Recovered reply']]]],
                    ],
                ]);
            }

            return Http::response([], 500);
        });

        $controller = new class extends AiController
        {
            public function askGemini(string $apiKey): array
            {
                return $this->fetchReplyText($apiKey, [
                    ['role' => 'user', 'parts' => [['text' => 'Hello']]],
                ]);
            }
        };

        $result = $controller->askGemini('test-api-key');

        $this->assertSame('Recovered reply', $result['reply']);
        $this->assertNull($result['error']);
    }

    public function test_it_does_not_retry_other_models_after_a_rate_limit_response(): void
    {
        Cache::flush();
        config([
            'ai.supported_models.gemini.api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent',
        ]);
        Http::fake(function (Request $request) {
            if ($request->url() === 'https://generativelanguage.googleapis.com/v1beta/models') {
                return Http::response([
                    'models' => [
                        [
                            'name' => 'models/gemini-3.8-flash',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                        [
                            'name' => 'models/gemini-2.5-flash',
                            'supportedGenerationMethods' => ['generateContent'],
                        ],
                    ],
                ]);
            }

            return Http::response([
                'error' => ['message' => 'Quota exceeded'],
            ], 429);
        });

        $controller = new class extends AiController
        {
            public function askGemini(string $apiKey): array
            {
                return $this->fetchReplyText($apiKey, [
                    ['role' => 'user', 'parts' => [['text' => 'Hello']]],
                ]);
            }
        };

        $result = $controller->askGemini('test-api-key');

        $this->assertSame(429, $result['status']);
        $this->assertStringContainsString('Quota exceeded', $result['error']);
        Http::assertSentCount(2);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'gemini-2.5-flash:generateContent'));
    }
}
