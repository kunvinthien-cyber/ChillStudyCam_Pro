<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    private function geminiRequest(int $timeout): PendingRequest
    {
        $request = Http::timeout($timeout)
            ->connectTimeout(min($timeout, 10));
        $caBundle = config('ai.ca_bundle');

        if (! is_string($caBundle) || trim($caBundle) === '') {
            return $request;
        }

        $caBundle = trim($caBundle);
        if (! is_file($caBundle) || ! is_readable($caBundle)) {
            throw new \RuntimeException('AI_CA_BUNDLE must point to a readable CA certificate bundle.');
        }

        return $request->withOptions(['verify' => $caBundle]);
    }

    public function getConversations(Request $request)
    {
        $user = $request->user() ?? User::first();
        if (!$user) return response()->json([]);

        $conversations = AiConversation::where('user_id', $user->id)
            ->latest('updated_at')
            ->take(30)
            ->get();

        return response()->json($conversations);
    }

    public function getConversationMessages(Request $request, $id)
    {
        $user = $request->user() ?? User::first();
        $conversation = AiConversation::where('user_id', $user->id)->findOrFail($id);

        $messages = $conversation->messages()->get()->map(fn($m) => [
            'id' => $m->id,
            'role' => $m->role,
            'text' => $m->message,
            'image' => $m->image_data ?? null,
            'is_voice' => (bool)$m->is_voice,
            'is_starred' => (bool)$m->is_starred,
            'created_at' => $m->created_at->toIso8601String(),
        ]);

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages
        ]);
    }

    public function deleteConversation(Request $request, $id)
    {
        $user = $request->user() ?? User::first();
        AiConversation::where('user_id', $user->id)->where('id', $id)->delete();
        return response()->json(['message' => 'បានលុបការសន្ទនាជោគជ័យ']);
    }

    public function toggleStar(Request $request, $id)
    {
        $user = $request->user() ?? User::first();
        $message = AiMessage::where('user_id', $user->id)->findOrFail($id);
        $message->is_starred = !$message->is_starred;
        $message->save();

        return response()->json([
            'message' => $message->is_starred ? 'បានចំណាំទុកក្នុងបញ្ជីសំខាន់ ⭐' : 'បានដកការចំណាំចេញ',
            'is_starred' => $message->is_starred
        ]);
    }

    public function getHistory(Request $request)
    {
        $user = $request->user() ?? User::first();
        if (!$user) return response()->json([]);

        $messages = AiMessage::where('user_id', $user->id)
            ->latest()
            ->take(40)
            ->get()
            ->reverse()
            ->map(fn($m) => [
                'id' => $m->id,
                'role' => $m->role,
                'text' => $m->message,
                'is_voice' => (bool)$m->is_voice,
                'is_starred' => (bool)$m->is_starred,
                'created_at' => $m->created_at->toIso8601String(),
            ])
            ->values();

        return response()->json($messages);
    }

    public function clearHistory(Request $request)
    {
        $user = $request->user() ?? User::first();
        if ($user) {
            AiMessage::where('user_id', $user->id)->where('is_starred', false)->delete();
        }
        return response()->json(['message' => 'ប្រវត្តិសារត្រូវបានសម្អាត']);
    }

    /**
     * 🚀 សួរសំណួរ AI (គាំទ្រទាំង Text, Voice, និង Multimodal IMAGE 📸)
     */
    public function ask(Request $request)
    { set_time_limit(120);
        try {
            $user = $request->user() ?? User::first();
            $geminiKey = trim(config('ai.supported_models.gemini.api_key') ?? env('GEMINI_API_KEY', ''));
            $groqKey = trim(config('ai.groq_api_key') ?? env('GROQ_API_KEY', ''));

            $messages = $request->input('messages', []);
            $conversationId = $request->input('conversation_id');
            $isVoiceMode = $request->boolean('voice_mode', false);
            $attachedImage = $request->input('image'); // Base64 Image

            if (empty($messages) && !$attachedImage) {
                return response()->json(['reply' => '⚠️ សូមវាយបញ្ចូលសំណួរ ឬដាក់រូបភាពលំហាត់!']);
            }

            $lastUserText = end($messages)['text'] ?? ($attachedImage ? 'សូមជួយមើល និងដោះស្រាយលំហាត់ក្នុងរូបភាពនេះ' : '');

            // បង្កើត Conversation ថ្មី
            $conversation = null;
            if ($user) {
                if ($conversationId) {
                    $conversation = AiConversation::where('user_id', $user->id)->find($conversationId);
                }

                if (!$conversation && $lastUserText) {
                    $title = mb_substr(trim($lastUserText), 0, 40, 'UTF-8');
                    $conversation = AiConversation::create([
                        'user_id' => $user->id,
                        'title' => $title ?: ($attachedImage ? '📷 លំហាត់រូបភាព' : 'ការសន្ទនាថ្មី'),
                    ]);
                }
            }

            // System Instruction
            if ($isVoiceMode) {
                $systemInstruction = "You are 'ChillAI' in a LIVE VOICE PHONE CALL. Keep your answer EXTREMELY SHORT (1-2 sentences only). Speak naturally in spoken Khmer or English. No markdown, no emojis.";
            } else {
                $systemInstruction = "You are 'ChillAI Tutor', the coolest, funniest, and most supportive study buddy for Cambodian students on ChillStudy.
                You are MULTIMODAL: You can analyze images of homework, math equations, chemistry formulas, and handwritten notes.
                When shown an image of a math problem or homework, solve it step-by-step clearly in fluent Khmer.
                Format your answers cleanly using bullet points, bold text, and code/math blocks. Be encouraging and friendly ✨.";
            }

            $recentMessages = array_slice($messages, -5);
            $replyText = '';
            $geminiError = '';
            $groqError = '';

            // ==============================================
            // 📸 ជំហានទី ១៖ ហៅ Google Gemini (ពូកែខាងមើលរូបភាព Vision) env('GROQ_API_KEY', '')
            // ==============================================
            if ($geminiKey && $geminiKey !== 'ដាក់_API_KEY_របស់អ្នកត្រង់នេះ') {
                $geminiRes = $this->callGeminiWithLog($recentMessages, $geminiKey, $systemInstruction, $isVoiceMode, $attachedImage);
                $replyText = $geminiRes['reply'];
                $geminiError = $geminiRes['error'];
            } else {
                $geminiError = 'មិនទាន់កំណត់ GEMINI_API_KEY';
            }

            // ==============================================
            // 🛡️ ជំហានទី ២៖ បើគ្មានរូបភាព ហើយ Gemini រវល់ -> ហៅ Groq Llama 3
            // ==============================================
            if (!$replyText && !$attachedImage && $groqKey) {
                $groqRes = $this->callGroqWithLog($recentMessages, $groqKey, $systemInstruction, $isVoiceMode);
                $replyText = $groqRes['reply'];
                $groqError = $groqRes['error'];
            }

            if (!$replyText) {
                return response()->json([
                    'reply' => "⚠️ មិនអាចទាញយកចម្លើយបានទេ:\n• Gemini: {$geminiError}" . ($groqError ? "\n• Groq: {$groqError}" : "")
                ]);
            }

            // 💾 រក្សាទុកក្នុង Database
            $savedModelMsg = null;
            try {
                if ($user && $conversation) {
                    AiMessage::create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $user->id,
                        'role' => 'user',
                        'message' => $lastUserText,
                        'is_voice' => $isVoiceMode,
                    ]);

                    $savedModelMsg = AiMessage::create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $user->id,
                        'role' => 'model',
                        'message' => $replyText,
                        'is_voice' => $isVoiceMode,
                    ]);

                    $conversation->touch();
                }
            } catch (\Exception $dbEx) {
                Log::error('Cannot save AI message: ' . $dbEx->getMessage());
            }

            return response()->json([
                'reply' => $replyText,
                'conversation_id' => $conversation ? $conversation->id : null,
                'conversation_title' => $conversation ? $conversation->title : null,
                'message_id' => $savedModelMsg ? $savedModelMsg->id : null
            ]);

        } catch (\Throwable $e) {
            Log::error('AI Error: ' . $e->getMessage());
            return response()->json(['reply' => '⚠️ Server Error: ' . $e->getMessage()]);
        }
    }

    /**
     * 🤖 Method ហៅ Google Gemini (គាំទ្រ MULTIMODAL VISION BASE64)
     */
    private function callGeminiWithLog($messages, $apiKey, $systemInstruction, $isVoiceMode, $attachedImage = null)
    {
        $geminiContents = [];
        $hasUserStarted = false;

        foreach ($messages as $index => $msg) {
            $role = ($msg['role'] === 'user') ? 'user' : 'model';
            if (!$hasUserStarted && $role !== 'user') continue;
            $hasUserStarted = true;
            $text = $msg['text'] ?? '';

            if (count($geminiContents) === 0) {
                $text = "[Instruction: {$systemInstruction}]\n\n" . $text;
            }

            $parts = [['text' => (string)$text]];

            // 📸 បញ្ចូលរូបភាព Base64 ទៅក្នុងសារចុងក្រោយរបស់ User
            if ($attachedImage && $role === 'user' && $index === count($messages) - 1) {
                if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64,(.+)$/', $attachedImage, $m)) {
                    $mimeType = $m[1];
                    $base64Data = $m[2];
                } else {
                    $mimeType = 'image/jpeg';
                    $base64Data = $attachedImage;
                }

                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $mimeType,
                        'data' => $base64Data
                    ]
                ];
            }

            $geminiContents[] = ['role' => $role, 'parts' => $parts];
        }

        // បើសារទទេ តែមានរូបភាព
        if (empty($geminiContents) && $attachedImage) {
            if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64,(.+)$/', $attachedImage, $m)) {
                $mimeType = $m[1];
                $base64Data = $m[2];
            } else {
                $mimeType = 'image/jpeg';
                $base64Data = $attachedImage;
            }

            $geminiContents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => "[Instruction: {$systemInstruction}]\n\nសូមជួយមើល និងដោះស្រាយលំហាត់ក្នុងរូបភាពនេះមួយ"],
                    ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64Data]]
                ]
            ];
        }

        if (empty($geminiContents)) return ['reply' => '', 'error' => 'No contents'];

        return $this->fetchReplyText($apiKey, $geminiContents, $isVoiceMode ? 70 : 450);
    }

    protected function fetchReplyText(string $apiKey, array $contents, int $maxOutputTokens = 450): array
    {
        $configuredEndpoint = config('ai.supported_models.gemini.api_endpoint');
        if (!is_string($configuredEndpoint)
            || !preg_match('#^(https://[^/]+/.*/models/)([^/:]+):generateContent$#', $configuredEndpoint, $endpointMatch)) {
            return ['reply' => '', 'error' => 'Invalid Gemini API endpoint configuration.', 'status' => null];
        }

        $configuredModel = $endpointMatch[2];
        $modelNames = $this->discoverGeminiModels($apiKey);
        $models = array_values(array_unique([
            $configuredModel,
            ...$modelNames,
            'gemini-2.0-flash',
        ]));
        $lastError = 'No available Gemini models support generateContent.';
        $lastStatus = null;

        foreach ($models as $model) {
            try {
                $response = $this->geminiRequest(25)
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->post($endpointMatch[1] . $model . ':generateContent', [
                        'contents' => $contents,
                        'generationConfig' => [
                            'maxOutputTokens' => $maxOutputTokens,
                            'temperature' => 0.7,
                        ],
                    ]);

                if ($response->status() === 429) {
                    return [
                        'reply' => '',
                        'error' => $response->json('error.message', 'Gemini rate limit exceeded.'),
                        'status' => 429,
                    ];
                }

                if (!$response->successful()) {
                    $lastStatus = $response->status();
                    $lastError = $response->json('error.message', 'Unknown Gemini API error.');
                    continue;
                }

                $reply = collect($response->json('candidates.0.content.parts', []))
                    ->pluck('text')
                    ->filter(fn ($text) => is_string($text) && trim($text) !== '')
                    ->implode('');

                if (trim($reply) !== '') {
                    return ['reply' => $reply, 'error' => null, 'status' => null];
                }

                $lastStatus = $response->status();
                $lastError = 'Gemini returned a successful response without reply text.';
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        return [
            'reply' => '',
            'error' => $lastStatus ? "Status {$lastStatus}: {$lastError}" : $lastError,
            'status' => $lastStatus,
        ];
    }

    private function discoverGeminiModels(string $apiKey): array
    {
        $cacheKey = 'ai.gemini.models.' . hash('sha256', $apiKey);
        $cachedModels = Cache::get($cacheKey);
        if (is_array($cachedModels)) {
            return $cachedModels;
        }

        try {
            $response = $this->geminiRequest(10)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->get('https://generativelanguage.googleapis.com/v1beta/models');

            if (!$response->successful()) {
                return [];
            }

            $models = collect($response->json('models', []))
                ->filter(fn ($model) => in_array('generateContent', $model['supportedGenerationMethods'] ?? [], true))
                ->pluck('name')
                ->map(fn ($name) => preg_replace('#^models/#', '', (string) $name))
                ->filter(fn ($name) => str_contains($name, 'flash') && !str_contains($name, 'tts'))
                ->values()
                ->all();

            Cache::put($cacheKey, $models, now()->addMinutes(30));

            return $models;
        } catch (\Throwable $e) {
            Log::warning('Gemini model discovery failed: ' . $e->getMessage());

            return [];
        }
    }

    private function callGroqWithLog($messages, $apiKey, $systemInstruction, $isVoiceMode)
    {
        try {
            $groqMessages = [['role' => 'system', 'content' => $systemInstruction]];
            foreach ($messages as $msg) {
                $role = ($msg['role'] === 'model') ? 'assistant' : 'user';
                $groqMessages[] = ['role' => $role, 'content' => $msg['text'] ?? ''];
            }

           $res = Http::withoutVerifying()->timeout(7)->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.1-8b-instant', // 👈 ម៉ូដែល Free ផ្លូវការរបស់ Groq (១៤,៤០០ ដង/ថ្ងៃ)
                'messages' => $groqMessages,
                'max_tokens' => $isVoiceMode ? 80 : 400,
                'temperature' => 0.7,
            ]);

            if ($res->successful()) {
                return ['reply' => $res->json('choices.0.message.content', ''), 'error' => ''];
            }
            return ['reply' => '', 'error' => "Status {$res->status()}: " . $res->json('error.message', 'Unknown')];
        } catch (\Exception $e) {
            return ['reply' => '', 'error' => $e->getMessage()];
        }
    }

    public function tts(Request $request)
    {
        $text = $request->query('text', '');
        if (!$text) return response()->json(['error' => 'No text'], 400);

        $cleanText = mb_substr(trim($text), 0, 90, 'UTF-8');
        $encodedText = rawurlencode($cleanText);

        try {
            $url = "https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&tl=km&q={$encodedText}";
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'stagefright/1.2 (Linux;Android 5.0)',
                    'Referer' => 'http://translate.google.com/',
                ])
                ->get($url);

            if ($response->successful()) {
                return response($response->body(), 200, [
                    'Content-Type' => 'audio/mpeg',
                    'Cache-Control' => 'no-cache',
                ]);
            }
            return response()->json(['error' => 'TTS failed'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
