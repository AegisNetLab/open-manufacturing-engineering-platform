<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use PDO;
use RuntimeException;

final class DatabaseBackupService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{file:string,tables:int,rows:int,bytes:int}
     */
    public function createBackup(string $outputDirectory): array
    {
        if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
            throw new RuntimeException('Backup directory could not be created.');
        }

        if (!is_writable($outputDirectory)) {
            throw new RuntimeException('Backup directory is not writable.');
        }

        $timestamp = (new DateTimeImmutable())->format('Ymd-His');
        $file = rtrim($outputDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "openmep-backup-{$timestamp}.sql";
        $handle = fopen($file, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Backup file could not be opened for writing.');
        }

        $tables = $this->listTables();
        $rowCount = 0;

        fwrite($handle, "-- OpenMEP SQL backup\n");
        fwrite($handle, '-- Created at: ' . (new DateTimeImmutable())->format(DATE_ATOM) . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($tables as $table) {
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, $this->createTableStatement($table) . ";\n\n");
            $rowCount += $this->writeTableRows($handle, $table);
            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return [
            'file' => $file,
            'tables' => count($tables),
            'rows' => $rowCount,
            'bytes' => filesize($file) ?: 0,
        ];
    }

    /**
     * @return list<string>
     */
    public function listTables(): array
    {
        $statement = $this->pdo->query('SHOW TABLES');
        if ($statement === false) {
            return [];
        }

        return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function sqlLiteral(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $this->pdo->quote((string) $value) ?: "''";
    }

    private function createTableStatement(string $table): string
    {
        $statement = $this->pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`');
        if ($statement === false) {
            throw new RuntimeException("Could not read table definition for {$table}.");
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row) || !isset($row['Create Table'])) {
            throw new RuntimeException("Invalid CREATE TABLE metadata for {$table}.");
        }

        return (string) $row['Create Table'];
    }

    /**
     * @param resource $handle
     */
    private function writeTableRows(mixed $handle, string $table): int
    {
        $statement = $this->pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`');
        if ($statement === false) {
            return 0;
        }

        $count = 0;
        while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            $columns = array_map(
                static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`',
                array_keys($row)
            );
            $values = array_map(fn (mixed $value): string => $this->sqlLiteral($value), array_values($row));
            fwrite($handle, 'INSERT INTO `' . str_replace('`', '``', $table) . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n");
            $count++;
        }

        return $count;
    }
}
