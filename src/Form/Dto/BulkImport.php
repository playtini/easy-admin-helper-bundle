<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Form\Dto;

use Luchaninov\CsvFileLoader\TsvStringLoader;

/**
 * Backing DTO for {@see \Playtini\EasyAdminHelperBundle\Form\BulkImportType}:
 * a pasted TSV blob plus the mode that decides what to do with each row.
 *
 * The modes are declared here rather than in the importing controller so every
 * project offers the same five, with the same stored values.
 */
class BulkImport
{
    public const string MODE_CREATE_OR_UPDATE = 'create_or_update';
    public const string MODE_CREATE_ONLY = 'create_only';
    public const string MODE_UPDATE_ONLY = 'update_only';
    public const string MODE_CREATE_SKIP_EXISTING = 'create_skip_existing';
    public const string MODE_UPDATE_SKIP_MISSING = 'update_skip_missing';

    private string $data = '';
    private string $mode = self::MODE_CREATE_OR_UPDATE;

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    /** @return list<array<array-key, mixed>> */
    public function getRows(): array
    {
        $result = [];

        $loader = new TsvStringLoader($this->data);
        foreach ($loader->getItems() as $row) {
            if ($row) {
                $result[] = self::normalizeKeys($row);
            }
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed> $row
     *
     * @return array<array-key, mixed>
     */
    private static function normalizeKeys(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[is_string($key) ? strtolower(trim($key)) : $key] = $value;
        }

        return $normalized;
    }
}
