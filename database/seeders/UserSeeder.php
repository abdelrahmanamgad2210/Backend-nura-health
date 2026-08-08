<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'patient@nura.demo'], [
            'name' => 'Sara Al Noor',
            'password' => Hash::make('password'),
            'role' => 'patient',
            'emirates_id_verified' => true,
        ]);

        User::updateOrCreate(['email' => 'clinician@nura.demo'], [
            'name' => 'Dr Lina Rahman',
            'password' => Hash::make('password'),
            'role' => 'clinician',
            'emirates_id_verified' => true,
        ]);

        User::updateOrCreate(['email' => 'pharmacist@nura.demo'], [
            'name' => 'Crescent Pharmacy · Dispenser',
            'password' => Hash::make('password'),
            'role' => 'pharmacist',
            'emirates_id_verified' => true,
        ]);
    }
}
