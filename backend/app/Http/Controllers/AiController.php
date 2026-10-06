<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    /**
     * ១. ទាញយកបញ្ជីប្រធានបទសន្ទនាទាំងអស់
     */
    public function getConversations(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated.'], 401);

        $conversations = AiConversation::where('user_id', $user->id)
            ->latest('updated_at')
            ->take(30)
            ->get();

        return response()->json($conversations);
    }

    /**
     * ២. ទាញយកសារទាំងអស់នៃកិច្ចសន្ទនាណាមួយមកឆាតបន្ត
     */
    public function getConversationMessages(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated.'], 401);
        $conversation = AiConversation::where('user_id', $user->id)->findOrFail($id);

        $messages = $conversation->messages()->get()->map(fn($m) => [
            'id' => $m->id,
            'role' => $m->role,
            'text' => $m->message,
            'is_voice' => (bool)$m->is_voice,
            'is_starred' => (bool)$m->is_starred,
            'image' => $m->image,
            'created_at' => $m->created_at->toIso8601String(),
        ]);

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages
        ]);
    }

    /**
     * ៣. លុបកិច្ចសន្ទនាណាមួយចោល
     */
    public function deleteConversation(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated.'], 401);
        AiConversation::where('user_id', $user->id)->where('id', $id)->delete();
        return response()->json(['message' => 'បានលុបការសន្ទនាជោគជ័យ']);
    }

    /**
     * ៤. ចុចផ្កាយចំណាំសារសំខាន់ៗ
     */
    public function toggleStar(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated.'], 401);
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
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated.'], 401);

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
            'image' => $m->image,
                'created_at' => $m->created_at->toIso8601String(),
            ])
            ->values();

        return response()->json($messages);
    }

    public function clearHistory(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated.'], 401);
        if ($user) {
            AiMessage::where('user_id', $user->id)->where('is_starred', false)->delete();
        }
        return response()->json(['message' => 'ប្រវត្តិសារត្រូវបានសម្អាត']);
    }

    /**
     * ៥. សួរសំណួរ AI (Timeout ២០ វិនាទី & Groq Backup)
     */
    public function ask(Request $request)
    {
        // បង្កើនពេលឱ្យ PHP រង់ចាំដល់ ១២០ វិនាទី
        set_time_limit(120);

        try {
            $user = $request->user();
            if (! $user) return response()->json(['message' => 'Unauthenticated.'], 401);

            $request->validate([
                'messages' => ['required', 'array', 'min:1', 'max:30'],
                'messages.*.role' => ['required', 'in:user,model,assistant'],
                'messages.*.text' => ['nullable', 'string', 'max:20000'],
                'image' => ['nullable', 'string', 'max:7000000'],
                'conversation_id' => ['nullable', 'integer'],
                'voice_mode' => ['nullable', 'boolean'],
            ]);

            $geminiKey = trim(config('ai.supported_models.gemini.api_key') ?? env('GEMINI_API_KEY', ''));
            $groqKey = trim(config('ai.groq_api_key') ?? env('GROQ_API_KEY', ''));

            $messages = $request->input('messages', []);
            $imageData = $request->input('image');
            $imageParts = null;
            if ($imageData !== null) {
                if (! preg_match('/^data:(image\/(?:jpeg|png|webp|gif));base64,([A-Za-z0-9+\/=\r\n]+)$/i', $imageData, $imageMatch)) {
                    return response()->json(['message' => 'Image must be a valid JPEG, PNG, WEBP, or GIF data URL.'], 422);
                }
                $decodedImage = base64_decode($imageMatch[2], true);
                if ($decodedImage === false || strlen($decodedImage) > 5 * 1024 * 1024) {
                    return response()->json(['message' => 'Image must be 5 MB or smaller.'], 422);
                }
                $imageParts = ['mime_type' => strtolower($imageMatch[1]), 'data' => $imageMatch[2]];
            }

            $conversationId = $request->input('conversation_id');
            $isVoiceMode = $request->boolean('voice_mode', false);

            if (empty($messages)) {
                return response()->json(['reply' => '⚠️ សូមវាយបញ្ចូលសំណួររបស់អ្នក!']);
            }

            $lastUserMessage = collect($messages)->reverse()->first(fn ($message) => ($message['role'] ?? null) === 'user') ?? [];
            $lastUserText = trim((string) ($lastUserMessage['text'] ?? ''));
            if ($lastUserText === '' && $imageParts) $lastUserText = '[Image attachment]';

            // បង្កើត Conversation ថ្មីបើមិនទាន់មាន
            $conversation = null;
            if ($user) {
                if ($conversationId) {
                    $conversation = AiConversation::where('user_id', $user->id)->find($conversationId);
                }

                if (!$conversation && $lastUserText) {
                    $title = mb_substr(trim($lastUserText), 0, 40, 'UTF-8');
                    $conversation = AiConversation::create([
                        'user_id' => $user->id,
                        'title' => $title ?: 'ការសន្ទនាថ្មី',
                    ]);
                }
            }

            // System Instruction
            if ($isVoiceMode) {
                $systemInstruction = "You are 'ChillAI' in a LIVE VOICE PHONE CALL. Keep your answer EXTREMELY SHORT (1-2 sentences only). Speak naturally in polite spoken Khmer or English. No markdown, no bullet points.";
            } else {
                $systemInstruction = "You are 'ChillAI Tutor', an intelligent, respectful, and highly competent academic study mentor for Cambodian students.
                Write in grammatically correct, natural, standard Cambodian Khmer.
                Format your answers cleanly using bullet points, bold text, and line breaks. Break down explanations into clear steps.";
            }

            $recentMessages = array_slice($messages, -5);
            $replyText = '';
            $geminiError = '';
            $groqError = '';

            // ==============================================
            // 🚀 ជំហានទី ១៖ ហៅ Google Gemini (Timeout ២០ វិនាទី)
            // ==============================================
            if ($geminiKey && $geminiKey !== 'ដាក់_API_KEY_របស់អ្នកត្រង់នេះ') {
                $geminiRes = $this->callGeminiWithLog($recentMessages, $geminiKey, $systemInstruction, $isVoiceMode, $imageParts);
                $replyText = $geminiRes['reply'];
                $geminiError = $geminiRes['error'];
            } else {
                $geminiError = 'មិនទាន់កំណត់ GEMINI_API_KEY';
            }

            // ==============================================
            // 🛡️ ជំហានទី ២៖ បើ Gemini បរាជ័យ -> រត់ទៅ Groq ភ្លាម
            // ==============================================
            if (!$replyText && ! $imageParts) {
                if ($groqKey) {
                    $groqRes = $this->callGroqWithLog($recentMessages, $groqKey, $systemInstruction, $isVoiceMode);
                    $replyText = $groqRes['reply'];
                    $groqError = $groqRes['error'];
                } else {
                    $groqError = 'មិនទាន់ឃើញ GROQ_API_KEY ក្នុង .env';
                }
            }

            if (!$replyText) {
                return response()->json([
                    'reply' => "⚠️ មិនអាចទាញយកចម្លើយបានទេ:\n• Gemini: {$geminiError}\n• Groq: {$groqError}"
                ]);
            }

            // 💾 រក្សាទុកក្នុង Database
            $savedModelMsg = null;
            try {
                if ($user && $lastUserText && $conversation) {
                    AiMessage::create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $user->id,
                        'role' => 'user',
                        'message' => $lastUserText,
                        'image' => $imageData,
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
     * 🤖 Method ហៅ Google Gemini (Timeout ២០ វិនាទី កុំឱ្យ cURL កាត់ផ្តាច់)
     */
    private function callGeminiWithLog($messages, $apiKey, $systemInstruction, $isVoiceMode, $imageParts = null)
    {
        $contents = [];
        $hasUserMessage = false;

        foreach ($messages as $message) {
            $role = ($message['role'] ?? 'user') === 'user' ? 'user' : 'model';
            if (! $hasUserMessage && $role !== 'user') continue;
            $hasUserMessage = true;
            $contents[] = ['role' => $role, 'parts' => [['text' => (string) ($message['text'] ?? '')]]];
        }

        if (empty($contents)) return ['reply' => '', 'error' => 'No user message was provided.', 'status' => null];

        if ($imageParts) {
            $lastUserIndex = null;
            foreach ($contents as $index => $content) {
                if ($content['role'] === 'user') $lastUserIndex = $index;
            }
            if ($lastUserIndex !== null) $contents[$lastUserIndex]['parts'][] = ['inline_data' => $imageParts];
        }

        return $this->fetchReplyText($apiKey, $contents, $systemInstruction, $isVoiceMode);
    }

    private function callGroqWithLog($messages, $apiKey, $systemInstruction, $isVoiceMode)
    {
        try {
            $groqMessages = [['role' => 'system', 'content' => $systemInstruction]];
            foreach ($messages as $msg) {
                $role = ($msg['role'] === 'model') ? 'assistant' : 'user';
                $groqMessages[] = ['role' => $role, 'content' => $msg['text'] ?? ''];
            }

            $res = Http::connectTimeout(3)->timeout(8)->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'openai/gpt-oss-20b', // 👈 ម៉ូដែល Free ផ្លូវការរបស់ Groq
                'messages' => $groqMessages,
                'max_tokens' => $isVoiceMode ? 250 : 800,
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

    /**
     * 🔊 ៦. Google Khmer TTS
     */
    protected function fetchReplyText(string $apiKey, array $contents, ?string $systemInstruction = null, bool $isVoiceMode = false): array
    {
        $endpoint = (string) config('ai.supported_models.gemini.api_endpoint', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent');
        preg_match('~/models/([^/:]+):generateContent~', $endpoint, $endpointMatch);
        $preferredModel = $endpointMatch[1] ?? 'gemini-flash-latest';
        $models = ['models/' . $preferredModel];
        $fallbacksLoaded = false;
        $lastError = 'Gemini returned no usable response.';

        for ($index = 0; $index < count($models); $index++) {
            $model = $models[$index];
            try {
                $request = Http::connectTimeout(3)->timeout(8)
                    ->withHeaders(['x-goog-api-key' => $apiKey]);
                $body = [
                    'contents' => $contents,
                    'generationConfig' => [
                        'maxOutputTokens' => $isVoiceMode ? 320 : 1400,
                        'temperature' => 0.7,
                    ],
                ];
                if ($systemInstruction) $body['systemInstruction'] = ['parts' => [['text' => $systemInstruction]]];

                $response = $request->post("https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent", $body);
                if ($response->status() === 429) {
                    return ['reply' => '', 'error' => $response->json('error.message', 'Gemini quota exceeded.'), 'status' => 429];
                }

                $text = collect($response->json('candidates.0.content.parts', []))
                    ->pluck('text')->filter()->implode("\n");
                if ($response->successful() && trim($text) !== '') {
                    return ['reply' => trim($text), 'error' => null, 'status' => $response->status()];
                }

                $lastError = $response->json('error.message')
                    ?? ($response->successful() ? 'Gemini returned an empty response.' : "Status {$response->status()}");
            } catch (\Throwable $error) {
                $lastError = $error->getMessage();
            }

            if (! $fallbacksLoaded && $index === 0) {
                $fallbacksLoaded = true;
                $discoveredModels = Cache::remember('ai.gemini.models.v1', now()->addHour(), function () use ($apiKey) {
                    try {
                        $response = Http::connectTimeout(3)->timeout(5)
                            ->withHeaders(['x-goog-api-key' => $apiKey])
                            ->get('https://generativelanguage.googleapis.com/v1beta/models');

                        if (! $response->successful()) return [];

                        return collect($response->json('models', []))
                            ->filter(fn ($candidate) => in_array('generateContent', $candidate['supportedGenerationMethods'] ?? [], true))
                            ->pluck('name')
                            ->filter(fn ($name) => str_contains(strtolower($name), 'flash') && ! str_contains(strtolower($name), 'tts'))
                            ->unique()
                            ->values()
                            ->all();
                    } catch (\Throwable) {
                        return [];
                    }
                });

                $fallbackModels = collect($discoveredModels)
                    ->reject(fn ($name) => basename($name) === $preferredModel)
                    ->sortByDesc(fn ($name) => preg_match('/(\\d+(?:\\.\\d+)?)/', $name, $matches) ? (float) $matches[1] : 0)
                    ->take(2)
                    ->values()
                    ->all();
                array_push($models, ...$fallbackModels);
            }
        }

        return ['reply' => '', 'error' => $lastError, 'status' => null];
    }

    public function tts(Request $request)
    {
        $text = $request->query('text', '');
        if (!$text) return response()->json(['error' => 'No text'], 400);

        $cleanText = mb_substr(trim($text), 0, 180, 'UTF-8');
        $lastPeriod = mb_strrpos($cleanText, '។', 0, 'UTF-8');
        if ($lastPeriod !== false && $lastPeriod > 40) {
            $cleanText = mb_substr($cleanText, 0, $lastPeriod + 1, 'UTF-8');
        }

        $encodedText = rawurlencode($cleanText);

        try {
            $url = "https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&tl=km&q={$encodedText}";
            $response = Http::connectTimeout(3)->timeout(10)
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
