<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class GenerateDocs extends Command
{
    protected $signature   = 'docs:pdf';
    protected $description = 'Genera los archivos PDF de documentación en docs/';

    public function handle(): int
    {
        $destino = base_path('docs');

        if (! is_dir($destino)) {
            mkdir($destino, 0755, true);
        }

        $docs = [
            'docs.guia-instalacion' => 'guia-instalacion.pdf',
            'docs.manual-usuario'   => 'manual-usuario.pdf',
        ];

        foreach ($docs as $vista => $archivo) {
            $this->info("Generando {$archivo}…");

            Pdf::loadView($vista)
                ->setPaper('letter', 'portrait')
                ->save("{$destino}/{$archivo}");

            $this->line("  → docs/{$archivo}");
        }

        $this->newLine();
        $this->info('Documentación generada correctamente en docs/');

        return self::SUCCESS;
    }
}
