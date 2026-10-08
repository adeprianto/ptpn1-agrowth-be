<?php

namespace App\Imports;

use App\Enums\CompetencyItemType;
use App\Models\Competencies;
use App\Models\CompetencyGroups;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class KompetensiImport
{
    private const SKIPPED_PREFIX = 'T&E ';

    private const GROUP_NAMES = [
        'Enginering & Infrastructure' => 'Engineering & Infrastructure',
        'Procurement & Supplier Manageme' => 'Procurement & Supplier Management',
        'Portfolio & Corporate Developme' => 'Portfolio & Corporate Development',
        'Corporate Affair & Goverment Re' => 'Corporate Affair & Government Relations',
        'Procurement & Strategic Sourcin' => 'Procurement & Strategic Sourcing',
        'Supply Chain, Logistics & Inven' => 'Supply Chain, Logistics & Inventory',
        'Agrowisata & Experience Busines' => 'Agrowisata & Experience Business',
    ];

    private const HEADERS = [
        'kode' => 'code',
        'technical competency' => 'name_en',
        'bahasa indonesia' => 'name',
        'definisi' => 'definition',
        'skill' => CompetencyItemType::Skill->value,
        'knowledge' => CompetencyItemType::Knowledge->value,
        'specialization' => CompetencyItemType::Specialization->value,
    ];

    public array $counts = ['groups' => 0, 'competencies' => 0, 'skipped_sheets' => 0];
    
    public array $warnings = [];

    private array $groups = [];

    public function __construct(private string $path)
    {
    }

    public function read(): void
    {
        $spreadsheet = IOFactory::load($this->path);

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $title = trim($sheet->getTitle());

            if (str_starts_with($title, self::SKIPPED_PREFIX)) {
                $this->warn($title, 0, 'sheet versi lain dari rumpun yang sudah ada, dilewati.');
                $this->counts['skipped_sheets']++;
                continue;
            }

            $this->readSheet(self::GROUP_NAMES[$title] ?? $title, $sheet);
        }
    }

    public function persist(): void
    {
        foreach ($this->groups as $name => $competencies) {
            $group = CompetencyGroups::firstOrCreate(['name' => $name]);
            $this->counts['groups']++;

            foreach ($competencies as $code => $competency) {
                $model = Competencies::updateOrCreate(
                    ['competency_group_id' => $group->id, 'code' => $code],
                    [
                        'name' => $competency['name'],
                        'name_en' => $competency['name_en'],
                        'definition' => $competency['definition'],
                    ],
                );
                $model->syncItems($competency['items']);
                $model->syncLevels($competency['levels']);
                $this->counts['competencies']++;
            }
        }
    }

    private function readSheet(string $groupName, Worksheet $sheet): void
    {
        $rows = $this->fillMergedCells($sheet->toArray(null, true, false, false), $sheet);
        $columns = $this->mapColumns($rows[0] ?? [], $groupName);
        $competencies = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $line = $index + 2;
            $value = fn (string $field) => isset($columns[$field]) ? $this->text($row[$columns[$field]] ?? null) : null;

            $code = $value('code');
            if ($code === null) {
                if (array_filter($row, fn ($cell) => $this->text($cell) !== null) !== []) {
                    $this->warn($groupName, $line, 'kode kosong, baris dilewati.');
                }
                continue;
            }

            $competencies[$code] ??= [
                // nama Indonesia jadi nama utama; kalau kosong pakai nama Inggrisnya
                'name' => $value('name') ?? $value('name_en') ?? $code,
                'name_en' => $value('name_en'),
                'definition' => $value('definition'),
                'levels' => array_map(fn (int $level) => $value("level_{$level}"), range(1, 6)),
                'items' => array_fill_keys(array_column(CompetencyItemType::cases(), 'value'), []),
            ];

            foreach (CompetencyItemType::cases() as $type) {
                if (($item = $value($type->value)) !== null) {
                    $competencies[$code]['items'][$type->value][] = $item;
                }
            }
        }

        if ($competencies === []) {
            $this->warn($groupName, 0, 'tidak ada kompetensi yang terbaca.');

            return;
        }

        $this->groups[$groupName] = $competencies;
    }

    private function fillMergedCells(array $rows, Worksheet $sheet): array
    {
        foreach ($sheet->getMergeCells() as $range) {
            [[$fromCol, $fromRow], [$toCol, $toRow]] = array_map(
                fn (array $cell) => [Coordinate::columnIndexFromString($cell[0]) - 1, (int) $cell[1] - 1],
                Coordinate::getRangeBoundaries($range),
            );
            $value = $rows[$fromRow][$fromCol] ?? null;

            for ($row = $fromRow; $row <= $toRow; $row++) {
                for ($col = $fromCol; $col <= $toCol; $col++) {
                    if (isset($rows[$row])) {
                        $rows[$row][$col] = $value;
                    }
                }
            }
        }

        return $rows;
    }

    private function mapColumns(array $header, string $groupName): array
    {
        $columns = [];

        foreach ($header as $index => $title) {
            $title = mb_strtolower((string) $this->text($title));

            if (isset(self::HEADERS[$title])) {
                $columns[self::HEADERS[$title]] = $index;
            } elseif (preg_match('/^level\s*([1-6])\b/', $title, $match)) {
                $columns["level_{$match[1]}"] = $index;
            }
        }

        if (! isset($columns['code'])) {
            throw new RuntimeException("Sheet \"{$groupName}\" tidak punya kolom 'Kode'.");
        }

        return $columns;
    }

    private function text($value): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value));

        return $value === '' || $value === '-' ? null : $value;
    }

    private function warn(string $sheet, int $line, string $message): void
    {
        $text = $line > 0 ? "[{$sheet}] baris {$line}: {$message}" : "[{$sheet}] {$message}";
        $this->warnings[] = $text;
        Log::warning("Import kompetensi {$text}");
    }
}
