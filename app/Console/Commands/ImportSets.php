<?php

namespace App\Console\Commands;

use App\Models\Set;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImportSets extends Command
{
    protected $signature = 'sets:import
                          {file? : Path to local CSV file}
                          {--download : Download latest CSV from Rebrickable}';

    protected $description = 'Import LEGO sets from Rebrickable CSV';

    private const CSV_URL = 'https://cdn.rebrickable.com/media/downloads/sets.csv.gz';

    private int $inserted = 0;

    private int $updated = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $this->info('Importing sets from Rebrickable...');

        $csvPath = $this->getCsvFile();

        if (! $csvPath || ! file_exists($csvPath)) {
            $this->error('CSV file not found. Use --download or provide a file path.');

            return Command::FAILURE;
        }

        $this->importCsv($csvPath);

        $this->table(
            ['Status', 'Count'],
            [
                ['Inserted', $this->inserted],
                ['Updated', $this->updated],
                ['Skipped', $this->skipped],
                ['Errors', $this->errors],
                ['Total', $this->inserted + $this->updated + $this->skipped + $this->errors],
            ]
        );

        return $this->errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function getCsvFile(): ?string
    {
        if ($filePath = $this->argument('file')) {
            return str_starts_with($filePath, '/') ? $filePath : base_path($filePath);
        }

        if ($this->option('download')) {
            return $this->downloadCsv();
        }

        $defaultPath = storage_path('app/rebrickable/sets.csv');

        return file_exists($defaultPath) ? $defaultPath : null;
    }

    private function downloadCsv(): ?string
    {
        $this->info('Downloading sets CSV...');

        $response = Http::timeout(300)->get(self::CSV_URL);

        if (! $response->successful()) {
            $this->error('Download failed.');

            return null;
        }

        Storage::makeDirectory('rebrickable');

        $gzPath = storage_path('app/rebrickable/sets.csv.gz');
        $csvPath = storage_path('app/rebrickable/sets.csv');

        file_put_contents($gzPath, $response->body());

        $gz = gzopen($gzPath, 'rb');
        $csv = fopen($csvPath, 'wb');
        while (! gzeof($gz)) {
            fwrite($csv, gzread($gz, 4096));
        }
        gzclose($gz);
        fclose($csv);
        unlink($gzPath);

        $this->info("Downloaded to: {$csvPath}");

        return $csvPath;
    }

    private function importCsv(string $csvPath): void
    {
        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle);

        $columnMap = [
            'set_num' => array_search('set_num', $header),
            'name' => array_search('name', $header),
            'year' => array_search('year', $header),
            'theme_id' => array_search('theme_id', $header),
            'num_parts' => array_search('num_parts', $header),
            'img_url' => array_search('img_url', $header) !== false
                ? array_search('img_url', $header)
                : array_search('set_img_url', $header),
        ];

        // Count lines for progress bar
        $totalLines = 0;
        while (fgets($handle) !== false) {
            $totalLines++;
        }
        rewind($handle);
        fgetcsv($handle);

        $bar = $this->output->createProgressBar($totalLines);
        $bar->start();

        $batch = [];
        $batchSize = 500;

        while (($row = fgetcsv($handle)) !== false) {
            $data = $this->parseRow($row, $columnMap);

            if ($data) {
                $batch[] = $data;

                if (count($batch) >= $batchSize) {
                    $this->processBatch($batch);
                    $batch = [];
                }
            }

            $bar->advance();
        }

        if (! empty($batch)) {
            $this->processBatch($batch);
        }

        $bar->finish();
        $this->newLine(2);

        fclose($handle);
    }

    private function parseRow(array $row, array $columnMap): ?array
    {
        $setNum = $row[$columnMap['set_num']] ?? null;

        if (empty($setNum)) {
            return null;
        }

        return [
            'set_num' => $setNum,
            'name' => $row[$columnMap['name']] ?? 'Unknown',
            'year' => ($row[$columnMap['year']] ?? '') !== '' ? (int) $row[$columnMap['year']] : null,
            'theme_id' => ($row[$columnMap['theme_id']] ?? '') !== '' ? (int) $row[$columnMap['theme_id']] : null,
            'num_parts' => ($row[$columnMap['num_parts']] ?? '') !== '' ? (int) $row[$columnMap['num_parts']] : null,
            'img_url' => ($columnMap['img_url'] !== false && isset($row[$columnMap['img_url']]))
                ? ($row[$columnMap['img_url']] ?: null)
                : null,
        ];
    }

    private function processBatch(array $batch): void
    {
        DB::transaction(function () use ($batch) {
            foreach ($batch as $data) {
                try {
                    $existing = Set::where('set_num', $data['set_num'])->first();

                    if ($existing) {
                        if ($this->hasChanged($existing, $data)) {
                            $existing->update($data);
                            $this->updated++;
                        } else {
                            $this->skipped++;
                        }
                    } else {
                        Set::create($data);
                        $this->inserted++;
                    }
                } catch (\Exception $e) {
                    $this->errors++;

                    if ($this->errors <= 10) {
                        $this->warn("Error on set {$data['set_num']}: {$e->getMessage()}");
                    }
                }
            }
        });
    }

    private function hasChanged(Set $existing, array $data): bool
    {
        return $existing->name !== $data['name']
            || $existing->year !== $data['year']
            || $existing->theme_id !== $data['theme_id']
            || $existing->num_parts !== $data['num_parts']
            || $existing->img_url !== $data['img_url'];
    }
}
