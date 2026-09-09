<?php

namespace App\Console\Commands;

use App\Services\UatReadinessService;
use Illuminate\Console\Command;

class CheckUatReadiness extends Command
{
    protected $signature = 'uat:readiness
        {--json : Output the readiness result as JSON}';

    protected $description = 'Verify that the controlled manual-UAT environment is ready without exposing credentials';

    public function handle(UatReadinessService $service): int
    {
        $result = $service->evaluate();

        if ($this->option('json')) {
            $this->line(json_encode(
                $result,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ));

            return $result['ready']
                ? self::SUCCESS
                : self::FAILURE;
        }

        $this->components->info(
            sprintf(
                'UAT readiness: %d/%d checks passed',
                $result['summary']['pass'],
                $result['summary']['total']
            )
        );

        $rows = collect($result['checks'])
            ->map(fn (array $check): array => [
                $check['status'],
                $check['id'],
                $check['message'],
            ])
            ->all();

        $this->table(
            ['Result', 'Check', 'Detail'],
            $rows
        );

        if (! $result['ready']) {
            $this->components->error(
                'UAT environment is not ready. Fix failing checks before manual execution.'
            );

            return self::FAILURE;
        }

        $this->components->info(
            'UAT environment is ready for manual scenario execution.'
        );

        return self::SUCCESS;
    }
}
