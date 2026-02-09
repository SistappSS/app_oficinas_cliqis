<?php

namespace App\Services\ChatIA;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    public function answerWithContext(string $question, string $context = '', array $history = []): string
    {
        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            return 'GEMINI_API_KEY não configurada. Verifique o .env e config/services.php.';
        }

        $model = 'gemini-2.0-flash';
        $url   = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

        // monta histórico em texto simples
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

        $response = Http::post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ]);

        if (! $response->ok()) {
            Log::error('Erro Gemini', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return 'Erro ao falar com o modelo de IA. (HTTP '.$response->status().')';
        }

        $json = $response->json();

        return $json['candidates'][0]['content']['parts'][0]['text']
            ?? 'Sem resposta da IA.';
    }
}
