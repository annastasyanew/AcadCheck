<?php

namespace App\Services;

use App\Exceptions\TextExtractionException;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;
use Throwable;

class TextExtractionService
{
    public function extract(string $absolutePath, string $fileType): string
    {
        try {
            $text = match (strtolower($fileType)) {
                'pdf' => $this->extractPdf($absolutePath),
                'docx' => $this->extractDocx($absolutePath),
                default => throw new TextExtractionException('Jenis file tidak didukung untuk ekstraksi teks.'),
            };
        } catch (TextExtractionException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TextExtractionException(
                'Isi dokumen tidak dapat diekstrak. Pastikan file tidak rusak atau terkunci.',
                previous: $exception,
            );
        }

        $text = $this->normalize($text);

        if (blank($text)) {
            throw new TextExtractionException(match (strtolower($fileType)) {
                'pdf' => 'PDF tidak memiliki teks yang dapat dibaca. Jika PDF berupa hasil scan, jalankan OCR terlebih dahulu.',
                'docx' => 'DOCX tidak memiliki teks yang dapat dibaca.',
                default => 'Dokumen tidak memiliki teks yang dapat dibaca.',
            });
        }

        return $text;
    }

    private function extractPdf(string $path): string
    {
        return (new Parser)->parseFile($path)->getText();
    }

    private function extractDocx(string $path): string
    {
        $phpWord = IOFactory::load($path, 'Word2007');
        $parts = [];

        foreach ($phpWord->getSections() as $section) {
            $parts[] = $this->extractContainerText($section);
        }

        return implode("\n", $parts);
    }

    private function extractContainerText(AbstractContainer $container): string
    {
        $parts = [];

        foreach ($container->getElements() as $element) {
            if ($element instanceof AbstractContainer) {
                $parts[] = $this->extractContainerText($element);

                continue;
            }

            if (method_exists($element, 'getText')) {
                $text = $element->getText();

                if (is_string($text)) {
                    $parts[] = $text;
                }
            }
        }

        return implode("\n", $parts);
    }

    private function normalize(string $text): string
    {
        // Beberapa dokumen menyimpan surrogate Unicode yang tidak valid untuk MySQL utf8mb4.
        $text = mb_scrub($text, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
