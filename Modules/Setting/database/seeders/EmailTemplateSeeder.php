<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setting\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug'    => 'sale-invoice',
                'name'    => 'Sale Invoice',
                'subject' => 'Invoice {{invoice_number}} from {{company_name}}',
                'body'    => "Dear {{customer_name}},\n\nPlease find attached your invoice {{invoice_number}} for BDT {{amount}}.\n\nThank you for your business.\n\n{{company_name}}",
            ],
            [
                'slug'    => 'quotation-send',
                'name'    => 'Quotation Send',
                'subject' => 'Quotation {{quotation_number}} from {{company_name}}',
                'body'    => "Dear {{customer_name}},\n\nPlease find attached quotation {{quotation_number}} totaling BDT {{amount}}.\n\nThis quotation is valid until {{valid_until}}.\n\n{{company_name}}",
            ],
            [
                'slug'    => 'payment-receipt',
                'name'    => 'Payment Receipt',
                'subject' => 'Payment Receipt — {{payment_number}}',
                'body'    => "Dear {{customer_name}},\n\nWe have received your payment of BDT {{amount}} on {{payment_date}}.\n\nPayment Reference: {{payment_number}}\n\nThank you.\n\n{{company_name}}",
            ],
            [
                'slug'    => 'low-stock-alert',
                'name'    => 'Low Stock Alert',
                'subject' => 'Low Stock Alert — {{product_count}} products below threshold',
                'body'    => "Hello,\n\n{{product_count}} products have fallen below their minimum stock threshold. Please review and reorder.\n\n{{company_name}}",
            ],
        ];

        foreach ($templates as $tpl) {
            EmailTemplate::firstOrCreate(
                ['slug' => $tpl['slug']],
                array_merge($tpl, ['is_active' => true])
            );
        }
    }
}
