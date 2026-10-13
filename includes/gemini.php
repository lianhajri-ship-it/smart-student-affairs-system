<?php
// ===================================================
// تكامل Gemini API
// ===================================================

class GeminiAPI {
    private string $apiKey;
    private string $model;
    private string $embeddingModel;
    private string $baseUrl      = 'https://generativelanguage.googleapis.com/v1beta';
    private string $embeddingUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(string $apiKey, string $model = 'gemini-2.5-flash', string $embeddingModel = 'gemini-embedding-001') {
        $this->apiKey         = $apiKey;
        $this->model          = $model;
        $this->embeddingModel = $embeddingModel;
    }

    // إنشاء embedding لنص معين
    public function generateEmbedding(string $text): ?array {
        $url = "{$this->embeddingUrl}/models/{$this->embeddingModel}:embedContent?key={$this->apiKey}";

        $data = [
            'model'   => "models/{$this->embeddingModel}",
            'content' => ['parts' => [['text' => $text]]],
        ];

        $response = $this->callApi($url, $data);

        if (isset($response['embedding']['values'])) {
            return $response['embedding']['values'];
        }

        // حفظ رسالة الخطأ للاطلاع عليها
        if (isset($response['error']['message'])) {
            $this->lastError = $response['error']['message'];
        } elseif (isset($response['error'])) {
            $this->lastError = is_string($response['error']) ? $response['error'] : json_encode($response['error']);
        }
        return null;
    }

    public string $lastError = '';

    // توليد إجابة من Gemini
    public function generateAnswer(string $userQuestion, array $contextChunks, string $systemPrompt = ''): string {
        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        // بناء السياق من الـ chunks
        $context = '';
        foreach ($contextChunks as $item) {
            $context .= "- " . $item['chunk']['chunk_text'] . "\n";
        }

        if (empty($systemPrompt)) {
            $systemPrompt = "أنت مساعد ذكي لعمادة شؤون الطلاب في الجامعة. مهمتك الإجابة على أسئلة الطلاب بشكل واضح ومفيد باللغة العربية. استخدم المعلومات المتوفرة للإجابة، وإذا لم تجد إجابة واضحة، اقترح على الطالب التواصل مع عمادة شؤون الطلاب مباشرة أو تقديم طلب يدوي.\n\nقواعد التنسيق:\n- إذا وُجد رابط تحميل لنموذج أو دليل في المعلومات المتوفرة، أدرجه في إجابتك بصيغة ماركداون هكذا: [اسم الملف](الرابط)\n- استخدم **نص** للعناوين والنقاط المهمة\n- استخدم • لعرض القوائم والخطوات";
        }

        $prompt = $systemPrompt . "\n\nالمعلومات المتوفرة:\n" . $context . "\n\nسؤال الطالب: " . $userQuestion . "\n\nالإجابة:";

        $data = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature'    => 0.7,
                'maxOutputTokens'=> 8192,
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ],
        ];

        $response = $this->callApi($url, $data);

        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        }

        return 'عذراً، لم أتمكن من الإجابة على سؤالك في الوقت الحالي. يمكنك تقديم طلب يدوي أو التواصل مع عمادة شؤون الطلاب مباشرة.';
    }

    // استدعاء الـ API
    private function callApi(string $url, array $data): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $result = curl_exec($ch);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($error) return ['error' => $error];

        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : [];
    }
}
