<?php

namespace App\Console\Commands;

use App\Models\Theme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImportThemes extends Command
{
    protected $signature = 'themes:import
                          {file? : Path to local CSV file}
                          {--download : Download latest CSV from Rebrickable}';

    protected $description = 'Import LEGO themes from Rebrickable CSV';

    private const CSV_URL = 'https://cdn.rebrickable.com/media/downloads/themes.csv.gz';

    private int $inserted = 0;

    private int $updated = 0;

    private int $skipped = 0;

    public function handle(): int
    {
        $this->info('Importing themes from Rebrickable...');

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
            ]
        );

        return Command::SUCCESS;
    }

    private function getCsvFile(): ?string
    {
        if ($filePath = $this->argument('file')) {
            return str_starts_with($filePath, '/') ? $filePath : base_path($filePath);
        }

        if ($this->option('download')) {
            return $this->downloadCsv();
        }

        $defaultPath = storage_path('app/rebrickable/themes.csv');

        return file_exists($defaultPath) ? $defaultPath : null;
    }

    private function downloadCsv(): ?string
    {
        $this->info('Downloading themes CSV...');

        $response = Http::timeout(120)->get(self::CSV_URL);

        if (! $response->successful()) {
            $this->error('Download failed.');

            return null;
        }

        Storage::makeDirectory('rebrickable');

        $gzPath = storage_path('app/rebrickable/themes.csv.gz');
        $csvPath = storage_path('app/rebrickable/themes.csv');

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

        $idIdx = array_search('id', $header);
        $nameIdx = array_search('name', $header);
        $parentIdx = array_search('parent_id', $header);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = [
                'id' => (int) $row[$idIdx],
                'name' => $row[$nameIdx],
                'parent_id' => $row[$parentIdx] !== '' ? (int) $row[$parentIdx] : null,
            ];
        }
        fclose($handle);

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        // Pass 1: upsert all themes without parent_id to avoid FK issues
        DB::transaction(function () use ($rows, $bar) {
            foreach ($rows as $data) {
                $this->upsertTheme(array_merge($data, ['parent_id' => null]));
                $bar->advance();
            }
        });

        // Pass 2: set parent_id now that all themes exist
        DB::transaction(function () use ($rows) {
            foreach ($rows as $data) {
                if ($data['parent_id'] !== null) {
                    Theme::where('id', $data['id'])->update(['parent_id' => $data['parent_id']]);
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
    }

    private function upsertTheme(array $data): void
    {
        $existing = Theme::find($data['id']);

        if ($existing) {
            if ($existing->name !== $data['name'] || $existing->parent_id !== $data['parent_id']) {
                $existing->update($data);
                $this->updated++;
            } else {
                $this->skipped++;
            }
        } else {
            Theme::create($data);
            $this->inserted++;
        }
    }
}
