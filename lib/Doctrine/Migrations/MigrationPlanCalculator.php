<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Version;
use Doctrine\Migrations\Version\VersionInterface;
use function array_filter;
use function array_reverse;
use function count;
use function in_array;

/**
 * @internal
 */
final class MigrationPlanCalculator
{
    /** @var MigrationRepository */
    private $migrationRepository;

    public function __construct(MigrationRepository $migrationRepository)
    {
        $this->migrationRepository = $migrationRepository;
    }

    /** @return Version[] */
    public function getMigrationsToExecute(string $direction, string $to) : array
    {
        $allVersions = $this->migrationRepository->getMigrations();

        if ($direction === Direction::DOWN && count($allVersions) !== 0) {
            $allVersions = array_reverse($allVersions);
        }

        $migrated = $this->migrationRepository->getMigratedVersions();

        return array_filter($allVersions, function (VersionInterface $version) use (
            $migrated,
            $direction,
            $to
        ) {
            return $this->shouldExecuteMigration($direction, $version, $to, $migrated);
        });
    }

    /** @param string[] $migrated */
    private function shouldExecuteMigration(
        string $direction,
        VersionInterface $version,
        string $to,
        array $migrated
    ) : bool {
        $to = (int) $to;

        if ($direction === Direction::DOWN) {
            if (! in_array($version->getVersion(), $migrated, true)) {
                return false;
            }

            return (int) $version->getVersion() > $to;
        }

        if ($direction === Direction::UP) {
            if (in_array($version->getVersion(), $migrated, true)) {
                return false;
            }

            return (int) $version->getVersion() <= $to;
        }

        return false;
    }
}
