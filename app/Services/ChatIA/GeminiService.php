<?php

namespace App\Services\ChatIA;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    public function answerWithContext(string $question, string $context = '', array $history = []): string
    {
        $apiKey = config('services.gemini.key');

        if (!$apiKey) {
            return 'A IA não está configurada. Avise o suporte.';
        }

        $model = 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

        // histórico em texto simples
        $historyText = '';
        if (!empty($history)) {
            $lines = [];
            foreach ($history as $msg) {
                $role = $msg['role'] === 'user' ? 'Usuário' : 'IA';
                $lines[] = "{$role}: {$msg['content']}";
            }
            $historyText = implode("\n", $lines);
        }

        $prompt = <<<TXT
Você é um assistente chamado Cliqis IA.

Estilo:
- Responda SEMPRE em português.
- Fale como um colega experiente, direto e educado, sem formalidade exagerada.
- Não repita saudação ("olá", "tudo bem") a cada mensagem. Só quando fizer sentido pelo contexto.
- Se a pergunta estiver confusa, peça mais detalhes de forma objetiva.

Memória da conversa:
- Abaixo tem um histórico recente da conversa. Use isso para manter o contexto e NÃO ficar pedindo as mesmas informações toda hora.
- Evite perguntar de novo coisas que o usuário já informou, a menos que realmente precise confirmar.

Histórico recente:
$historyText

Uso da base de conhecimento (RAG):
- Abaixo existe um CONHECIMENTO OPCIONAL, que vem de documentos do usuário.
- Se o CONHECIMENTO OPCIONAL tiver informações claramente ligadas à pergunta (ex: código numérico 6206088, nomes de peças, planos, regras, etc.), use isso como base principal.
- Atenção especial a códigos numéricos (ex: 6206088, 3404069): se o contexto mencionar esse código com preço ou descrição, responda direto com essas informações, sem pedir mais detalhes desnecessários.
- Se o CONHECIMENTO OPCIONAL não tiver nada relevante ou vier vazio, ignore e responda com seu conhecimento geral.
- Não fale termos como "contexto", "RAG" ou "documento". Só responda normalmente.

CONHECIMENTO OPCIONAL:
$context

Pergunta do usuário:
$question
TXT;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        $maxAttempts = 3;
        $delayMs = 500; // 0.5s, depois 1s, 2s...

        $response = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(25)->post($url, $payload);
            } catch (ConnectionException $e) {
                Log::warning('Erro de conexão com Gemini', [
                    'attempt' => $attempt,
                    'msg' => $e->getMessage(),
                ]);

                if ($attempt === $maxAttempts) {
                    return 'A IA está temporariamente indisponível. Tente novamente em alguns segundos.';
                }

                usleep($delayMs * 1000);
                $delayMs *= 2;
                continue;
            }

            // sucesso -> sai do loop
            if ($response->ok()) {
                break;
            }

            // 429: rate limit -> tenta de novo com backoff
            if ($response->status() === 429 && $attempt < $maxAttempts) {
                Log::warning('Rate limit Gemini (429), tentando novamente', [
                    'attempt' => $attempt,
                    'body' => $response->body(),
                ]);

                usleep($delayMs * 1000);
                $delayMs *= 2;
                continue;
            }

            // outros erros não vale insistir
            break;
        }

        if (!$response || !$response->ok()) {
            $status = $response ? $response->status() : null;

            Log::error('Erro Gemini após tentativas', [
                'status' => $status,
                'body' => $response ? $response->body() : null,
            ]);

            if ($status === 429) {
                // mensagem amigável pro usuário, sem expor "HTTP 429"
                return 'Muitas pessoas estão usando a IA ao mesmo tempo. Aguarde alguns instantes e tente novamente.';
            }

            return 'A IA teve um erro ao responder agora. Tente novamente em alguns segundos.';
        }

        $json = $response->json();

        return $json['candidates'][0]['content']['parts'][0]['text']
            ?? 'Sem resposta da IA.';
    }
}
