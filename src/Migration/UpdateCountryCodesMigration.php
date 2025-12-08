<?php

declare(strict_types=1);

namespace HeimrichHannot\LocationBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Symfony\Component\Intl\Countries;

class UpdateCountryCodesMigration extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'Heimrich & Hannot Location Bundle: Convert country codes to uppercase';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_location'])) {
            return false;
        }

        // Alle country-Werte laden und in PHP prüfen
        $countries = $this->connection->fetchFirstColumn(
            "SELECT DISTINCT country FROM tl_location WHERE country != ''"
        );

        foreach ($countries as $country) {
            if ($country !== strtoupper($country)) {
                return true;
            }
        }

        return false;
    }


    public function run(): MigrationResult
    {
        $validCountries = array_change_key_case(Countries::getNames(), CASE_UPPER);

        $allRows = $this->connection->fetchAllAssociative(
            "SELECT id, country FROM tl_location WHERE country != ''"
        );

        $rows = array_filter($allRows, function($row) {
            return $row['country'] !== strtoupper($row['country']);
        });


        $changed = 0;
        $warnings = [];

        foreach ($rows as $row) {
            $id = $row['id'];
            $orig = (string) $row['country'];
            $norm = strtoupper(trim($orig));

            if (!isset($validCountries[$norm])) {
                $warnings[] = sprintf('ID %s: Ungültiger Ländercode "%s" (normalisiert: "%s") - wird auf "DE" gesetzt', $id, $orig, $norm);
                $norm = 'DE';
            }

            $this->connection->update(
                'tl_location',
                ['country' => $norm],
                ['id' => $id]
            );

            $changed++;
        }

        $message = sprintf('%d Ländercode(s) auf Großbuchstaben konvertiert.', $changed);

        if (!empty($warnings)) {
            $message .= "\n\nWarnungen:\n" . implode("\n", $warnings);
        }

        return $this->createResult(true, $message);
    }
}
