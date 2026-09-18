<?php

namespace Modules\Customer\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Customer\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Seed sample Bangladesh customers.
     */
    public function run(): void
    {
        $customers = [
            [
                'name'      => 'Rahim Khan',
                'phone'     => '01712345678',
                'email'     => 'rahim.khan@example.com',
                'district'  => 'Dhaka',
                'upazila'   => 'Dhanmondi',
                'address'   => 'House 12, Road 5, Dhanmondi, Dhaka-1205',
                'is_active' => true,
                'created_by'=> 1,
            ],
            [
                'name'      => 'Fatima Begum',
                'phone'     => '01898765432',
                'email'     => 'fatima.begum@example.com',
                'district'  => 'Gazipur',
                'address'   => 'Tongi Bazar, Gazipur Sadar, Gazipur',
                'is_active' => true,
                'created_by'=> 1,
            ],
            [
                'name'      => 'Kamal Hossain',
                'phone'     => '01655112233',
                'email'     => 'kamal.hossain@example.com',
                'district'  => 'Chattogram',
                'address'   => 'Agrabad C/A, Chittagong-4100',
                'is_active' => true,
                'created_by'=> 1,
            ],
            [
                'name'         => 'Jamal Uddin',
                'phone'        => '01911998877',
                'email'        => 'jamal.uddin@juenterprise.com',
                'company_name' => 'JU Enterprises',
                'district'     => 'Dhaka',
                'upazila'      => 'Gulshan',
                'address'      => 'Gulshan Avenue, Plot 23, Gulshan-1, Dhaka-1212',
                'is_active'    => true,
                'created_by'   => 1,
            ],
            [
                'name'      => 'Nasreen Akter',
                'phone'     => '01777445566',
                'email'     => 'nasreen.akter@example.com',
                'district'  => 'Narayanganj',
                'address'   => 'Shiddhirganj, Narayanganj',
                'is_active' => true,
                'created_by'=> 1,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
