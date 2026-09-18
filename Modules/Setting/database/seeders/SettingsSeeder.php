<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Seed default application settings.
     */
    public function run(): void
    {
        $defaults = [
            // Business Profile
            ['group' => 'business', 'key' => 'company_name', 'value' => 'BizPOS Pro', 'type' => 'string'],
            ['group' => 'business', 'key' => 'address', 'value' => '', 'type' => 'string'],
            ['group' => 'business', 'key' => 'phone', 'value' => '', 'type' => 'string'],
            ['group' => 'business', 'key' => 'email', 'value' => '', 'type' => 'string'],
            ['group' => 'business', 'key' => 'website', 'value' => '', 'type' => 'string'],
            ['group' => 'business', 'key' => 'logo', 'value' => '', 'type' => 'string'],
            ['group' => 'business', 'key' => 'bin_number', 'value' => '', 'type' => 'string'],
            ['group' => 'business', 'key' => 'tin_number', 'value' => '', 'type' => 'string'],
            ['group' => 'business', 'key' => 'trade_license', 'value' => '', 'type' => 'string'],
            ['group' => 'business', 'key' => 'financial_year_start', 'value' => 'July', 'type' => 'string'],
            ['group' => 'business', 'key' => 'primary_currency', 'value' => 'BDT', 'type' => 'string'],
            ['group' => 'business', 'key' => 'secondary_currency', 'value' => '', 'type' => 'string'],
            ['group' => 'business', 'key' => 'exchange_rate', 'value' => '110.50', 'type' => 'string'],

            // Tax / VAT
            ['group' => 'tax', 'key' => 'vat_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'tax', 'key' => 'vat_rate', 'value' => '15', 'type' => 'integer'],
            ['group' => 'tax', 'key' => 'tax_inclusive', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'tax', 'key' => 'mushak_63', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'tax', 'key' => 'mushak_65', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'tax', 'key' => 'mushak_91', 'value' => '1', 'type' => 'boolean'],

            // Invoice & Receipt
            ['group' => 'invoice', 'key' => 'prefix', 'value' => 'INV-', 'type' => 'string'],
            ['group' => 'invoice', 'key' => 'next_invoice_number', 'value' => '1', 'type' => 'integer'],
            ['group' => 'invoice', 'key' => 'invoice_format', 'value' => 'INV-YYYY-NNNNN', 'type' => 'string'],
            ['group' => 'invoice', 'key' => 'receipt_width', 'value' => '80mm', 'type' => 'string'],
            ['group' => 'invoice', 'key' => 'footer_text', 'value' => 'Thank you for your business!', 'type' => 'string'],
            ['group' => 'invoice', 'key' => 'terms', 'value' => '', 'type' => 'string'],
            ['group' => 'invoice', 'key' => 'show_logo', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'invoice', 'key' => 'auto_print', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'invoice', 'key' => 'show_vat', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'invoice', 'key' => 'bangla_amount_words', 'value' => '0', 'type' => 'boolean'],

            // Notification — sensible defaults so the system is functional out of the box.
            ['group' => 'notification', 'key' => 'low_stock_alert', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'notification', 'key' => 'payment_reminder', 'value' => '1', 'type' => 'boolean'],
            // Per-event / per-channel toggles shown in the Notifications matrix.
            ['group' => 'notification', 'key' => 'notify_low_stock_email', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'notification', 'key' => 'notify_low_stock_inapp', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'notification', 'key' => 'notify_new_order_email', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'notification', 'key' => 'notify_new_order_inapp', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'notification', 'key' => 'notify_payment_due_email', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'notification', 'key' => 'notify_payment_due_inapp', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'notification', 'key' => 'notify_courier_inapp', 'value' => '1', 'type' => 'boolean'],

            // Localization
            ['group' => 'localization', 'key' => 'country', 'value' => 'BD', 'type' => 'string'],
            ['group' => 'localization', 'key' => 'currency', 'value' => 'BDT', 'type' => 'string'],
            ['group' => 'localization', 'key' => 'date_format', 'value' => 'd M Y', 'type' => 'string'],
            ['group' => 'localization', 'key' => 'timezone', 'value' => 'Asia/Dhaka', 'type' => 'string'],
            ['group' => 'localization', 'key' => 'language', 'value' => 'en', 'type' => 'string'],
            ['group' => 'localization', 'key' => 'number_format', 'value' => 'bd_lakh', 'type' => 'string'],
            ['group' => 'localization', 'key' => 'currency_display', 'value' => 'BDT', 'type' => 'string'],
            ['group' => 'localization', 'key' => 'currency_symbol', 'value' => '৳', 'type' => 'string'],
            ['group' => 'localization', 'key' => 'bangla_digits', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'localization', 'key' => 'bangla_receipt', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'localization', 'key' => 'bangla_invoice', 'value' => '0', 'type' => 'boolean'],

            // SMS Gateway
            ['group' => 'sms', 'key' => 'gateway', 'value' => 'bulksmsbd', 'type' => 'string'],
            ['group' => 'sms', 'key' => 'api_key', 'value' => '', 'type' => 'string'],
            ['group' => 'sms', 'key' => 'sender_id', 'value' => '', 'type' => 'string'],

            // Courier & Delivery
            ['group' => 'courier', 'key' => 'default_courier', 'value' => 'own_delivery', 'type' => 'string'],
            ['group' => 'courier', 'key' => 'auto_book', 'value' => 'manual', 'type' => 'string'],
            ['group' => 'courier', 'key' => 'delivery_type', 'value' => 'regular', 'type' => 'string'],
            ['group' => 'courier', 'key' => 'cod_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'courier', 'key' => 'tracking_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'courier', 'key' => 'courier_sms', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'courier', 'key' => 'return_window', 'value' => '7', 'type' => 'integer'],
            ['group' => 'courier', 'key' => 'return_courier', 'value' => 'same', 'type' => 'string'],
            ['group' => 'courier', 'key' => 'return_shipping_payer', 'value' => 'buyer_defective_exception', 'type' => 'string'],
            ['group' => 'courier', 'key' => 'return_policy', 'value' => 'Returns accepted within 7 days with original invoice.', 'type' => 'string'],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']]
            );
        }
    }
}
