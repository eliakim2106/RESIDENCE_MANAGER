<?php

namespace App\Support;

use DateTimeInterface;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Export Excel (.xlsx) aux couleurs DS Holding, commun à toutes les listes de l'administration.
 *
 * Une ligne de titre, la date d'export, puis un tableau : en-tête figé avec filtres, colonnes dimensionnées,
 * montants en FCFA, dates au format français. Les lignes sont fournies au fil de l'eau (lazy) pour les gros volumes.
 *
 *   return ExcelExport::download('reservations', 'Réservations', [
 *       ['label' => 'Référence', 'width' => 16],
 *       ['label' => 'Montant', 'type' => 'money'],
 *       ['label' => 'Arrivée', 'type' => 'date'],
 *   ], $rows);
 */
final class ExcelExport
{
    private const NAVY = '0A1F44';

    private const GOLD = 'D4A72C';

    private const STRIPE = 'F5F7FB';

    /**
     * Format et largeur par défaut de chaque type de colonne.
     *
     * @var array<string, array{0: ?string, 1: float}>
     */
    private const TYPES = [
        'text' => [null, 22],
        'money' => ['#,##0 "FCFA"', 16],
        'number' => ['#,##0', 12],
        'decimal' => ['#,##0.0', 12],
        'percent' => ['0.0 "%"', 12],
        'date' => ['dd/mm/yyyy', 13],
        'datetime' => ['dd/mm/yyyy hh:mm', 17],
    ];

    /**
     * @param  list<array{label: string, type?: string, width?: float}>  $columns
     * @param  iterable<int, list<mixed>>  $rows  une liste de valeurs par ligne, dans l'ordre des colonnes
     */
    public static function download(string $filename, string $title, array $columns, iterable $rows): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer = new Writer(new Options);
        $writer->openToFile($path);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $title), 0, 31));
        $count = count($columns);

        foreach ($columns as $index => $column) {
            $sheet->setColumnWidth($column['width'] ?? self::TYPES[$column['type'] ?? 'text'][1], $index + 1);
        }

        // Titre et date d'export
        $writer->addRow(Row::fromValuesWithStyle(['DS HOLDING · '.$title], new Style(fontBold: true, fontSize: 15, fontColor: self::NAVY), 24));
        $writer->addRow(Row::fromValuesWithStyle(['Exporté le '.now()->translatedFormat('d F Y à H:i')], new Style(fontItalic: true, fontSize: 10, fontColor: '64748B')));
        $writer->addRow(Row::fromValues([]));

        // En-tête
        $border = new Border(new BorderPart(BorderName::BOTTOM, self::GOLD));
        $header = new Style(fontBold: true, fontColor: 'FFFFFF', backgroundColor: self::NAVY, border: $border, cellVerticalAlignment: CellVerticalAlignment::CENTER, shouldWrapText: true);
        $writer->addRow(Row::fromValuesWithStyle(array_column($columns, 'label'), $header, 22));

        // Lignes : style de la colonne (format), ligne sur deux légèrement teintée
        $formats = array_map(fn (array $column): ?string => self::TYPES[$column['type'] ?? 'text'][0], $columns);
        $written = 0;

        foreach ($rows as $values) {
            $striped = $written % 2 === 1;
            $cells = [];

            foreach (array_values($formats) as $index => $format) {
                $style = new Style(backgroundColor: $striped ? self::STRIPE : null, format: $format, cellVerticalAlignment: CellVerticalAlignment::CENTER);
                $cells[] = Cell::fromValue(self::value($values[$index] ?? null), $style);
            }

            $writer->addRow(new Row($cells));
            $written++;
        }

        if ($written === 0) {
            $writer->addRow(Row::fromValuesWithStyle(['Aucune donnée pour ces critères.'], new Style(fontItalic: true, fontColor: '64748B')));
        }

        // Ligne d'en-tête figée et filtres automatiques
        $sheet->setSheetView((new SheetView)->withFreezeRow(5));
        $sheet->setAutoFilter(new AutoFilter(0, 4, $count - 1, 4 + max(1, $written)));

        $writer->close();

        return response()
            ->download($path, $filename.'-'.now()->format('Y-m-d').'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }

    /**
     * Valeur écrite dans la cellule : les énumérations deviennent leur libellé, les booléens « Oui / Non ».
     */
    private static function value(mixed $value): mixed
    {
        return match (true) {
            $value === null => '',
            $value instanceof DateTimeInterface => $value,
            $value instanceof \BackedEnum && method_exists($value, 'label') => $value->label(),
            $value instanceof \BackedEnum => $value->value,
            is_bool($value) => $value ? 'Oui' : 'Non',
            is_int($value), is_float($value) => $value,
            default => (string) $value,
        };
    }
}
