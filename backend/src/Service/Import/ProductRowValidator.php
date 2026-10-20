<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Exception\InvalidRowException;
use Brick\Math\BigDecimal;

final readonly class ProductRowValidator
{
    private const string MAX_PRICE = '9999999999.99';

    public function __construct(
        private PriceParser $priceParser,
        private DiscountCalculator $discountCalculator,
    ) {}

    public function validate(ProductRow $row): ProductData
    {
        $errors = [];
        $warnings = [];

        if ($row->externalCode === '') {
            $errors[] = 'Не заполнен внешний код';
        } elseif (mb_strlen($row->externalCode) > 64) {
            $errors[] = 'Внешний код длиннее 64 символов';
        }

        if ($row->name === '') {
            $errors[] = 'Не заполнено наименование';
        } elseif (mb_strlen($row->name) > 255) {
            $errors[] = 'Наименование длиннее 255 символов';
        }

        $price = $this->price($row->price, 'Цена продажи', $errors);

        if ($price !== null && !$price->isPositive()) {
            $errors[] = 'Цена продажи должна быть больше нуля';
            $price = null;
        }

        $purchasePrice = $row->purchasePrice === '' ? null : $this->price($row->purchasePrice, 'Закупочная цена', $errors);

        if ($purchasePrice !== null && $purchasePrice->isNegative()) {
            $errors[] = 'Закупочная цена не может быть отрицательной';
            $purchasePrice = null;
        }

        foreach (array_keys($row->attributes) as $key) {
            if (mb_strlen($key) > 255) {
                $errors[] = sprintf('Название доп. поля «%s…» длиннее 255 символов', mb_substr($key, 0, 30));
            }
        }

        if ($errors !== [] || $price === null) {
            throw new InvalidRowException($errors);
        }

        $discount = null;

        if ($purchasePrice !== null && $purchasePrice->isGreaterThan($price)) {
            $warnings[] = 'Закупочная цена выше цены продажи, скидка не рассчитана';
        } elseif ($purchasePrice !== null) {
            $discount = (string) $this->discountCalculator->calculate($price, $purchasePrice);
        }

        $imageUrls = [];

        foreach ($row->imageUrls as $url) {
            if ($this->isValidUrl($url)) {
                $imageUrls[] = $url;
            } else {
                $warnings[] = sprintf('Некорректная ссылка на изображение: %s', mb_substr($url, 0, 200));
            }
        }

        return new ProductData(
            externalCode: $row->externalCode,
            name: $row->name,
            description: $row->description === '' ? null : $row->description,
            price: (string) $price,
            discount: $discount,
            attributes: $row->attributes,
            imageUrls: $imageUrls,
            warnings: $warnings,
        );
    }

    /**
     * @param list<string> $errors
     */
    private function price(string $value, string $label, array &$errors): ?BigDecimal
    {
        if ($value === '') {
            $errors[] = sprintf('Не заполнена %s', mb_strtolower($label));

            return null;
        }

        $price = $this->priceParser->parse($value);

        if ($price === null) {
            $errors[] = sprintf('%s «%s» не является числом', $label, mb_substr($value, 0, 50));
        } elseif ($price->isGreaterThan(self::MAX_PRICE)) {
            $errors[] = sprintf('%s «%s» слишком большая', $label, $value);
            $price = null;
        }

        return $price;
    }

    private function isValidUrl(string $url): bool
    {
        if (strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
