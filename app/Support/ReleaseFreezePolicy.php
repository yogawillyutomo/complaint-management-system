<?php

namespace App\Support;

class ReleaseFreezePolicy
{
    public function evaluate(
        array $config,
        array $changes,
        array $labels,
        string $title = ''
    ): array {
        $active = (bool) ($config['active'] ?? false);

        if (! $active) {
            return $this->result(
                true,
                'INACTIVE',
                'Release-candidate freeze is not active on the base branch.'
            );
        }

        $normalizedLabels = array_values(array_unique(array_map(
            static fn ($label): string => strtolower(trim((string) $label)),
            $labels
        )));

        $normalizedTitle = strtolower(trim($title));
        $adminTitlePrefix = strtolower((string) (
            $config['freeze_admin_title_prefix']
            ?? '[rc-admin]'
        ));
        $hasFreezeAdminReason = in_array(
            strtolower((string) ($config['freeze_admin_label'] ?? 'rc-freeze-admin')),
            $normalizedLabels,
            true
        ) || str_starts_with($normalizedTitle, $adminTitlePrefix);

        $freezeConfigPath = (string) (
            $config['freeze_config_path']
            ?? '.github/rc-freeze.json'
        );

        foreach ($changes as $change) {
            $status = strtoupper((string) ($change['status'] ?? ''));
            $path = (string) ($change['path'] ?? '');
            $oldPath = (string) ($change['old_path'] ?? '');

            if (
                ($path === $freezeConfigPath || $oldPath === $freezeConfigPath)
                && ! $hasFreezeAdminReason
            ) {
                return $this->result(
                    false,
                    'FREEZE_CONFIG_PROTECTED',
                    'Changing the freeze configuration requires the rc-freeze-admin label.'
                );
            }

            if (
                (
                    str_starts_with($path, 'database/migrations/')
                    || str_starts_with($oldPath, 'database/migrations/')
                )
                && in_array($status, ['M', 'D', 'R'], true)
            ) {
                return $this->result(
                    false,
                    'HISTORICAL_MIGRATION_REWRITE',
                    'Historical migration modification/deletion/rename is blocked during RC freeze.'
                );
            }
        }

        if ($this->isDocsOnly($config, $changes)) {
            return $this->result(
                true,
                'DOCS_ONLY',
                'Documentation-only change is allowed during RC freeze.'
            );
        }

        $allowedLabels = array_map(
            static fn ($label): string => strtolower((string) $label),
            $config['allowed_change_labels'] ?? []
        );

        $matchingLabels = array_values(array_intersect(
            $normalizedLabels,
            $allowedLabels
        ));

        $matchingPrefixes = array_values(array_filter(
            array_map(
                static fn ($prefix): string => strtolower((string) $prefix),
                $config['allowed_title_prefixes'] ?? []
            ),
            static fn (string $prefix): bool => $prefix !== ''
                && str_starts_with($normalizedTitle, $prefix)
        ));

        if ($matchingLabels === [] && $matchingPrefixes === []) {
            return $this->result(
                false,
                'RC_CHANGE_REASON_REQUIRED',
                'Non-documentation changes require an approved RC label or title prefix.'
            );
        }

        return $this->result(
            true,
            'RC_CHANGE_APPROVED',
            'Change is allowed under RC freeze and creates a replacement candidate.',
            [
                'matching_labels' => $matchingLabels,
                'matching_title_prefixes' => $matchingPrefixes,
                'replacement_candidate_required' => true,
            ]
        );
    }

    private function isDocsOnly(array $config, array $changes): bool
    {
        if ($changes === []) {
            return true;
        }

        $prefixes = $config['docs_only_prefixes'] ?? [];
        $files = $config['docs_only_files'] ?? [];

        foreach ($changes as $change) {
            $path = (string) ($change['path'] ?? '');

            if (in_array($path, $files, true)) {
                continue;
            }

            $matchesPrefix = false;

            foreach ($prefixes as $prefix) {
                if (str_starts_with($path, (string) $prefix)) {
                    $matchesPrefix = true;
                    break;
                }
            }

            if (! $matchesPrefix) {
                return false;
            }
        }

        return true;
    }

    private function result(
        bool $allowed,
        string $code,
        string $message,
        array $extra = []
    ): array {
        return array_merge([
            'allowed' => $allowed,
            'code' => $code,
            'message' => $message,
        ], $extra);
    }
}
