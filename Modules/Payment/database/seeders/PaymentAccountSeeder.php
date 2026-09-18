<?php

namespace Modules\Payment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Payment\Models\PaymentAccount;

class PaymentAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name'         => 'Cash Counter',
                'account_type' => 'cash',
                'is_default'   => true,
                'is_active'    => true,
            ],
            [
                'name'             => 'bKash Business',
                'account_type'     => 'mobile_banking',
                'mobile_bank_name' => 'bKash',
                'is_active'        => true,
            ],
            [
                'name'             => 'Nagad Business',
                'account_type'     => 'mobile_banking',
                'mobile_bank_name' => 'Nagad',
                'is_active'        => true,
            ],
            [
                'name'             => 'Rocket',
                'account_type'     => 'mobile_banking',
                'mobile_bank_name' => 'Rocket',
                'is_active'        => true,
            ],
            [
                'name'              => 'DBBL Business Account',
                'account_type'      => 'bank',
                'bank_account_type' => 'current',
                'bank_account_name' => 'BizPOS Pro',
                'is_active'         => true,
            ],
            [
                'name'         => 'Visa/Mastercard POS',
                'account_type' => 'card',
                'card_type'    => 'visa_master',
                'is_active'    => true,
            ],
        ];

        foreach ($accounts as $acc) {
            PaymentAccount::firstOrCreate(['name' => $acc['name']], $acc);
        }
    }
}
