<?php

namespace App\Http\Controllers\Application\ChatIA;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Services\ChatIA\GeminiService;

use App\Models\ChatIA\DocumentIA;

class ChatIAController extends Controller
{
    public $document;

    public function __construct(DocumentIA $document)
    {
        $this->document = $document;
    }

    public function view()
    {
        return view('app.chat_ia.chat.chat_index');
    }

    public function message(Request $request, GeminiService $gemini)
    {
        $data = $request->validate([
            'message'            => ['required', 'string'],
            'history'            => ['nullable', 'array'],
            'history.*.role'     => ['required_with:history', 'in:user,assistant'],
            'history.*.content'  => ['required_with:history', 'string'],
        ]);

        $question = $data['message'];
        $history  = $data['history'] ?? [];

        // =========================
        // 1) Extrair possíveis códigos numéricos da pergunta
        // =========================
        $codes = [];
        if (preg_match_all('/\b[0-9]{4,}\b/u', $question, $m)) {
            $codes = $m[0]; // ex.: ['6206088']
        }

        // =========================
        // 2) Extrair palavras-chave simples (tirar stopwords)
        // =========================
        $normalized = mb_strtolower($question, 'UTF-8');
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized); // remove pontuação
        $tokens = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        $stopwords = [
            'o','a','os','as','um','uma','de','da','do','das','dos',
            'que','qual','quais','quanto','quanto?','quanto?','quanto','quanto?',
            'custa','custam','preço','preco','é','eh','e','pra','para','no','na',
            'codigo','código','cod','do','da','em','sobre'
        ];

        $keywords = array_values(array_diff($tokens, $stopwords));

        // =========================
        // 3) Montar consulta de documentos
        // =========================
        $query = $this->document->query();

        // prioridade: bater códigos numéricos
        if (!empty($codes)) {
            $query->where(function ($q) use ($codes) {
                foreach ($codes as $code) {
                    $q->orWhere('content', 'LIKE', '%' . $code . '%')
                        ->orWhere('title', 'LIKE', '%' . $code . '%');
                }
            });
        }

        // se não teve código, ou além do código, usa algumas palavras-chave
        if (empty($codes) && !empty($keywords)) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    // evita palavras com 1 letra
                    if (mb_strlen($word, 'UTF-8') < 2) {
                        continue;
                    }
                    $q->orWhere('content', 'LIKE', '%' . $word . '%')
                        ->orWhere('title', 'LIKE', '%' . $word . '%');
                }
            });
        }

        $docs = $query->limit(5)->get();

        // =========================
        // 4) Montar contexto (ou vazio)
        // =========================
        $context = '';
        if ($docs->isNotEmpty()) {
            $context = $docs->map(function ($doc) {
                return "### {$doc->title}\n{$doc->content}";
            })->implode("\n\n");
        }

        // =========================
        // 5) Chamar Gemini com contexto + histórico
        // =========================
        $answer = $gemini->answerWithContext($question, $context, $history);

        return response()->json([
            'answer' => $answer,
        ]);
    }
}
