<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SystemSettings
{
    private const TABLES = [
        'business_settings',
        'pos_settings',
        'invoice_settings',
        'barcode_settings',
        'tax_settings',
        'notification_settings',
        'developer_settings',
    ];

    private static array $cache = [];

    public static function get(string $key, mixed $default = null, ?string $table = null): mixed
    {
        if ($table) {
            return self::tableValue($table, $key, $default);
        }

        foreach (self::TABLES as $settingsTable) {
            $value = self::tableValue($settingsTable, $key);

            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }

    public static function bool(string $key, bool $default = false, ?string $table = null): bool
    {
        return filter_var(self::get($key, $default ? '1' : '0', $table), FILTER_VALIDATE_BOOLEAN);
    }

    public static function number(string $key, int|float $default = 0, ?string $table = null): int|float
    {
        $value = self::get($key, $default, $table);

        return is_numeric($value) ? $value + 0 : $default;
    }

    public static function money(float|int|null $amount): string
    {
        $symbol = self::get('currency_symbol', 'Rs.', 'business_settings');
        $decimals = (int) self::number('decimal_places', 2, 'business_settings');

        return trim($symbol . ' ' . number_format((float) $amount, $decimals));
    }

    public static function currencySymbol(): string
    {
        return (string) self::get('currency_symbol', 'Rs.', 'business_settings');
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    public static function posPayload(): array
    {
        return [
            'systemName' => self::get('system_name', 'Phone Shop POS', 'business_settings'),
            'businessName' => self::get('business_name', self::get('system_name', 'Phone Shop POS', 'business_settings'), 'business_settings'),
            'currencySymbol' => self::get('currency_symbol', 'Rs.', 'business_settings'),
            'decimalPlaces' => (int) self::number('decimal_places', 2, 'business_settings'),
            'taxPercentage' => (float) self::number('default_sale_tax', 15, 'tax_settings'),
            'taxMode' => self::get('default_tax_mode', 'exclusive', 'tax_settings'),
            'enableTax' => self::bool('enable_tax_system', true, 'tax_settings'),
            'enableDiscount' => self::bool('enable_discount', true, 'tax_settings'),
            'maximumDiscountPercentage' => (float) self::number('maximum_discount_percentage', 100, 'tax_settings'),
            'enableHoldSale' => self::bool('enable_hold_sale', true, 'pos_settings'),
            'enableMixedPayment' => self::bool('enable_mixed_payment', true, 'pos_settings'),
            'enableBarcodeScanSound' => self::bool('enable_barcode_scan_sound', true, 'pos_settings'),
            'allowPriceEdit' => self::bool('allow_price_edit', false, 'pos_settings'),
            'autoPrintReceipt' => self::bool('auto_print_receipt', true, 'pos_settings'),
            'invoicePaperSize' => self::get('paper_size', '80mm', 'invoice_settings'),
            'invoiceFooterMessage' => self::get('footer_message', 'Thank you for shopping with us.', 'invoice_settings'),
        ];
    }

    private static function tableValue(string $table, string $key, mixed $default = null): mixed
    {
        if (!isset(self::$cache[$table])) {
            self::$cache[$table] = DB::table($table)->pluck('value', 'key')->all();
        }

        return array_key_exists($key, self::$cache[$table]) ? self::$cache[$table][$key] : $default;
    }
}
