<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currencies = [
            ['code' => 'JPY', 'name' => 'Japanese Yen'],
            ['code' => 'THB', 'name' => 'Thai Baht'],
            ['code' => 'VND', 'name' => 'Vietnamese Dong']
        ];
        foreach($currencies as $currency){
            Currency::create($currency);
        }
    }
}
