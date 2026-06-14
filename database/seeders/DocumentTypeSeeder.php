<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'article',
                'label' => 'Artikel Ilmiah',
                'description' => 'Dokumen artikel ilmiah untuk publikasi akademik.',
                'is_active' => true,
            ],
            [
                'name' => 'proposal',
                'label' => 'Proposal Penelitian/Project',
                'description' => 'Dokumen rencana penelitian atau project.',
                'is_active' => true,
            ],
            [
                'name' => 'report',
                'label' => 'Laporan Hasil Penelitian/Project',
                'description' => 'Dokumen laporan hasil penelitian atau project.',
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
