<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;
use OpenSpout\Reader\XLSX\Reader;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Lecture des exports Excel dans les tests : lignes du tableau (sans le titre ni la date d'export).
 */
trait ReadsExcelExports
{
    /**
     * @return list<list<mixed>> en-tête, puis une ligne par élément exporté
     */
    protected function excelRows(TestResponse $response): array
    {
        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $file = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $file);

        $reader = new Reader;
        $reader->open($file->getFile()->getPathname());
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }

            break;
        }

        $reader->close();

        // Lignes 1 à 3 : titre, date d'export, ligne vide
        return array_values(array_slice($rows, 2));
    }

    /**
     * Toutes les valeurs de l'export, en texte, pour des recherches simples.
     */
    protected function excelText(TestResponse $response): string
    {
        return collect($this->excelRows($response))
            ->map(fn (array $row): string => implode(' | ', array_map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('d/m/Y') : (string) $value, $row)))
            ->implode("\n");
    }
}
