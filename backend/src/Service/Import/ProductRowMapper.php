<?php

declare(strict_types=1);

namespace App\Service\Import;

final readonly class ProductRowMapper
{
    public const string EXTERNAL_CODE = 'Внешний код';
    public const string NAME = 'Наименование';
    public const string DESCRIPTION = 'Описание';
    public const string PRICE = 'Цена: Цена продажи';
    public const string PURCHASE_PRICE = 'Закупочная цена';
    public const string PHOTOS = 'Доп. поле: Ссылки на фото';
    public const string PACKAGE = 'Доп. поле: Ссылка на упаковку';

    private const string ATTRIBUTE_PREFIX = 'Доп. поле:';
    private const array REQUIRED = [self::EXTERNAL_CODE, self::NAME, self::PRICE];

    /**
     * @param array<array-key, string> $headers
     *
     * @return list<string>
     */
    public function missingColumns(array $headers): array
    {
        return array_values(array_diff(self::REQUIRED, $headers));
    }

    /**
     * @param array<string, string> $cells
     */
    public function map(array $cells): ProductRow
    {
        $attributes = [];

        foreach ($cells as $header => $value) {
            if ($value === '' || $header === self::PHOTOS || $header === self::PACKAGE || !str_starts_with($header, self::ATTRIBUTE_PREFIX)) {
                continue;
            }

            $key = trim(substr($header, strlen(self::ATTRIBUTE_PREFIX)));

            if ($key !== '') {
                $attributes[$key] = $value;
            }
        }

        $imageUrls = array_merge(
            $this->splitUrls($cells[self::PHOTOS] ?? ''),
            $this->splitUrls($cells[self::PACKAGE] ?? ''),
        );

        return new ProductRow(
            externalCode: $cells[self::EXTERNAL_CODE] ?? '',
            name: $cells[self::NAME] ?? '',
            description: $cells[self::DESCRIPTION] ?? '',
            price: $cells[self::PRICE] ?? '',
            purchasePrice: $cells[self::PURCHASE_PRICE] ?? '',
            attributes: $attributes,
            imageUrls: array_values(array_unique($imageUrls)),
        );
    }

    /**
     * @return list<string>
     */
    private function splitUrls(string $value): array
    {
        return preg_split('/[\s,;]+/u', $value, flags: PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
