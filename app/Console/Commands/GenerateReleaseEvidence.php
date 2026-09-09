<?php

namespace App\Console\Commands;

use App\Services\ReleaseEvidenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateReleaseEvidence extends Command
{
    protected $signature = 'release:evidence
        {--commit= : Commit SHA associated with this evidence}
        {--output=storage/app/release-evidence.json : Output path relative to project root}
        {--stdout : Print JSON to stdout instead of writing a file}';

    protected $description = 'Generate a non-secret release evidence manifest for UAT and thesis traceability';

    public function handle(ReleaseEvidenceService $service): int
    {
        $commitSha = $this->option('commit');

        if (is_string($commitSha) && $commitSha !== '') {
            if (! preg_match('/^[a-f0-9]{7,40}$/i', $commitSha)) {
                $this->error('Commit SHA must be a 7-40 character hexadecimal Git SHA.');

                return self::FAILURE;
            }
        } else {
            $commitSha = null;
        }

        $manifest = $service->build($commitSha);
        $json = json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ) . PHP_EOL;

        if ($this->option('stdout')) {
            $this->line($json);

            return self::SUCCESS;
        }

        $relative = trim((string) $this->option('output'));

        if ($relative === '') {
            $this->error('Output path cannot be empty.');

            return self::FAILURE;
        }

        $path = base_path($relative);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $json);

        $this->info("Release evidence written to {$relative}");

        return self::SUCCESS;
    }
}
