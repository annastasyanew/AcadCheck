<?php

namespace Tests\Unit;

use App\Services\TextExtractionService;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use ReflectionMethod;
use Tests\TestCase;

class TextExtractionServiceTest extends TestCase
{
    public function test_it_extracts_text_from_docx(): void
    {
        Storage::fake('local');
        $path = Storage::disk('local')->path('sample.docx');
        $phpWord = new PhpWord;
        $phpWord->addSection()->addText('Isi dokumen akademik DOCX.');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        $text = app(TextExtractionService::class)->extract($path, 'docx');

        $this->assertStringContainsString('Isi dokumen akademik DOCX.', $text);
    }

    public function test_it_extracts_text_from_pdf(): void
    {
        Storage::fake('local');
        $path = Storage::disk('local')->path('sample.pdf');
        $dompdf = new Dompdf;
        $dompdf->loadHtml('<p>Isi dokumen akademik PDF.</p>');
        $dompdf->render();
        file_put_contents($path, $dompdf->output());

        $text = app(TextExtractionService::class)->extract($path, 'pdf');

        $this->assertStringContainsString('Isi dokumen akademik PDF.', $text);
    }

    public function test_it_scrubs_invalid_unicode_from_extracted_text(): void
    {
        $method = new ReflectionMethod(TextExtractionService::class, 'normalize');

        $text = $method->invoke(
            app(TextExtractionService::class),
            "Teks sebelum \xED\xA0\xB5\xED\xB0\xBE teks sesudah.",
        );

        $this->assertTrue(mb_check_encoding($text, 'UTF-8'));
        $this->assertStringContainsString('Teks sebelum', $text);
        $this->assertStringContainsString('teks sesudah.', $text);
    }

    public function test_it_rejects_pdf_without_extractable_text(): void
    {
        Storage::fake('local');
        $path = Storage::disk('local')->path('empty.pdf');
        $dompdf = new Dompdf;
        $dompdf->loadHtml('<div></div>');
        $dompdf->render();
        file_put_contents($path, $dompdf->output());

        $this->expectExceptionMessage(
            'PDF tidak memiliki teks yang dapat dibaca. Jika PDF berupa hasil scan, jalankan OCR terlebih dahulu.',
        );

        app(TextExtractionService::class)->extract($path, 'pdf');
    }
}
