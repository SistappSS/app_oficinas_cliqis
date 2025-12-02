<?php

namespace App\Http\Controllers\Application\ChatIA;

use App\Http\Controllers\Controller;
use App\Models\ChatIA\DocumentIA;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class KnowLedgeBaseController extends Controller
{
    public $document;

    public function __construct(DocumentIA $document)
    {
        $this->document = $document;
    }

    public function view()
    {
        $documents = $this->document->latest()->get();

        return view('app.chat_ia.know_ledge.know_ledge_index', compact('documents'));
    }

    /**
     * Salva conhecimento: texto e/ou arquivo (PDF / Excel / CSV).
     */
    public function store(Request $request)
    {

        $data = $request->validate([
            'title'   => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'file'    => ['nullable', 'file', 'mimes:pdf,xlsx,xls,csv', 'max:20480'], // ~20MB
        ], [
            'file.file' => 'Por favor, insira um arquivo válido.',
            'file.max' => 'O arquivo precisa ter no máximo 20mbs.'
        ]);

        if (empty($data['content']) && ! $request->hasFile('file')) {
            return back()
                ->withErrors(['content' => 'Informe um texto ou envie um arquivo.'])
                ->withInput();
        }

        $file       = $request->file('file');
        $fileName   = null;
        $filePath   = null;
        $fileMime   = null;
        $sourceType = 'text';

        $finalContent = trim($data['content'] ?? '');

        if ($file instanceof UploadedFile) {
            $fileName = $file->getClientOriginalName();
            $fileMime = $file->getMimeType();
            $ext      = strtolower($file->getClientOriginalExtension());

            // salva o arquivo em storage/app/public/ai_kb
            $filePath = $file->store('ai_kb', 'private');

            $extracted = $this->extractTextFromFile($file);

            if ($ext === 'pdf') {
                $sourceType = 'pdf';
            } elseif (in_array($ext, ['xlsx', 'xls'])) {
                $sourceType = 'excel';
            } elseif ($ext === 'csv') {
                $sourceType = 'csv';
            }

            if (! empty($extracted)) {
                if ($finalContent !== '') {
                    $finalContent .= "\n\n---\n\n" . $extracted;
                } else {
                    $finalContent = $extracted;
                }
            }
        }

        if ($finalContent === '') {
            return back()
                ->withErrors(['content' => 'Não foi possível extrair texto do arquivo.'])
                ->withInput();
        }

        $title = $data['title'] ?? null;
        if (! $title) {
            $title = $fileName
                ? pathinfo($fileName, PATHINFO_FILENAME)
                : 'Documento sem título';
        }

        $this->document->create([
            'title'       => $title,
            'content'     => $finalContent,
            'file_name'   => $fileName,
            'file_path'   => $filePath,
            'file_mime'   => $fileMime,
            'source_type' => $sourceType,
        ]);

        return redirect()
            ->route('knowledge.view')
            ->with('success', 'Documento adicionado à base de conhecimento.');
    }

    /**
     * Exclui um documento da base (e o arquivo, se existir).
     */
    public function destroy(DocumentIA $document)
    {
        if ($document->file_path) {
            Storage::disk('private')->delete($document->file_path);
        }

        $document->delete();

        return redirect()
            ->route('knowledge.view')
            ->with('success', 'Documento removido da base de conhecimento.');
    }

    /**
     * Extrai texto de PDF / Excel / CSV.
     */
    protected function extractTextFromFile(UploadedFile $file): string
    {
        $ext  = strtolower($file->getClientOriginalExtension());
        $path = $file->getPathname();

        try {
            // PDF
            if ($ext === 'pdf') {
                $parser = new PdfParser();

                $binary = @file_get_contents($path);
                if ($binary === false) {

                    Log::error('AI KB: não conseguiu ler conteúdo do PDF', [
                        'path' => $path,
                        'name' => $file->getClientOriginalName(),
                    ]);

                    return '';
                }

                $pdf  = $parser->parseContent($binary);
                $text = trim($pdf->getText());

                if ($text === '') {
                    Log::warning('AI KB: PDF sem texto extraível (talvez só imagem)', [
                        'name' => $file->getClientOriginalName(),
                    ]);
                }

                return $text;
            }

            // Excel / CSV
            if (in_array($ext, ['xlsx', 'xls', 'csv'])) {
                $reader = IOFactory::createReaderForFile($path);

                if (method_exists($reader, 'setReadDataOnly')) {
                    $reader->setReadDataOnly(true);
                }

                $spreadsheet = $reader->load($path);
                $sheet       = $spreadsheet->getActiveSheet();

                $rows = [];

                foreach ($sheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    $cells = [];
                    foreach ($cellIterator as $cell) {
                        $cells[] = trim((string) $cell->getValue());
                    }

                    if (implode('', $cells) === '') {
                        continue;
                    }

                    $rows[] = implode(' | ', $cells);
                }

                $text = trim(implode("\n", $rows));

                if ($text === '') {
                    Log::warning('AI KB: planilha sem texto extraível', [
                        'name' => $file->getClientOriginalName(),
                    ]);
                }

                return $text;
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao extrair texto de arquivo para AI KB', [
                'ext'  => $ext,
                'name' => $file->getClientOriginalName(),
                'msg'  => $e->getMessage(),
            ]);
        }

        return '';
    }
}
