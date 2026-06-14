<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class RubricSeeder extends Seeder
{
    public function run(): void
    {
        $rubrics = [
            'article' => [
                ['Judul', 10, 'Kejelasan, spesifik, dan sesuai isi artikel.'],
                ['Abstrak', 15, 'Memuat tujuan, metode, hasil, dan kesimpulan.'],
                ['Pendahuluan', 15, 'Masalah, gap, tujuan, dan kontribusi jelas.'],
                ['Metode', 20, 'Data, prosedur, metode, dan evaluasi dijelaskan.'],
                ['Hasil dan Pembahasan', 20, 'Hasil disajikan dan dibahas secara logis.'],
                ['Kesimpulan', 10, 'Kesimpulan menjawab tujuan dan sesuai hasil.'],
                ['Referensi', 5, 'Referensi relevan dan cukup baru.'],
                ['Bahasa Akademik', 5, 'Bahasa baku, jelas, dan konsisten.'],
            ],
            'proposal' => [
                ['Latar Belakang', 20, 'Masalah jelas, relevan, dan kuat.'],
                ['Rumusan Masalah', 15, 'Rumusan masalah selaras dengan latar belakang.'],
                ['Tujuan', 15, 'Tujuan spesifik dan dapat dicapai.'],
                ['Metode', 25, 'Tahapan, data, alat, dan evaluasi jelas.'],
                ['Kelayakan Scope', 15, 'Scope tidak terlalu luas atau sempit.'],
                ['Rencana Output', 10, 'Output jelas dan dapat diukur.'],
            ],
            'report' => [
                ['Pendahuluan', 15, 'Masalah dan tujuan laporan jelas.'],
                ['Metode Pelaksanaan', 20, 'Tahapan kerja runtut dan dapat dipahami.'],
                ['Hasil', 25, 'Hasil utama disajikan secara jelas.'],
                ['Evaluasi', 15, 'Ada pengujian, pengukuran, atau penilaian hasil.'],
                ['Pembahasan', 15, 'Hasil ditafsirkan secara analitis.'],
                ['Kesimpulan', 10, 'Kesimpulan sesuai hasil dan tidak berlebihan.'],
            ],
        ];

        foreach ($rubrics as $typeName => $items) {
            $documentType = DocumentType::where('name', $typeName)->first();

            if (! $documentType) {
                continue;
            }

            foreach ($items as [$aspect, $weight, $description]) {
                $documentType->rubrics()->updateOrCreate(
                    ['aspect_name' => $aspect],
                    [
                        'weight' => $weight,
                        'description' => $description,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
