<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'id' => 1,
            'username' => 'BigObadiah',
            'first_name' => 'Kelvin',
            'other_names' => 'Mwangi Wanjohi',
            'gender' => 'male',
            'mobile_phone' => '(+254) 740-252837',
            'email' => 'kelvinramsiel01@gmail.com',
            'email_verified_at' => null,
            'google_id' => '111693591448676730118',
            'account_status' => 'active',
            'terms_accepted' => true,
            'terms_accepted_count' => 1,
            'last_terms_accepted_at' => '2025-10-08 14:26:00',
            'current_terms_version' => '1.0',
            'points' => 50,
            'password' => '$2y$12$ULS46OY1s2IP0fiLoJ3/D.uEONgujthJdvSa1.Jn9RQ8MfKAvq.8u',
            'remember_token' => 'F6IySiAKzEMQFKPFYtXuBPjeLBmOLgAyEPIIv3n3kQvnUs2QRTjjHMhYMBHd',
            'created_at' => '2025-10-08 14:24:32',
            'updated_at' => '2025-10-08 14:26:00',
            'deleted_at' => null,
        ])->assignRole('developer');
        \App\Models\User::create([
            'id' => 2,
            'username' => 'Reviewer001',
            'first_name' => 'Reviewer',
            'other_names' => '001',
            'gender' => 'male',
            'mobile_phone' => '(+254) 712-345678',
            'email' => 'reviewer001@example.com',
            'email_verified_at' => null,
            'google_id' => '111693591448676730118',
            'account_status' => 'active',
            'terms_accepted' => true,
            'terms_accepted_count' => 1,
            'last_terms_accepted_at' => '2025-10-08 14:26:00',
            'current_terms_version' => '1.0',
            'points' => 50,
            'password' => '$2y$12$ULS46OY1s2IP0fiLoJ3/D.uEONgujthJdvSa1.Jn9RQ8MfKAvq.8u',
            'remember_token' => 'F6IySiAKzEMQFKPFYtXuBPjeLBmOLgAyEPIIv3n3kQvnUs2QRTjjHMhYMBHd',
            'created_at' => '2025-10-08 14:24:32',
            'updated_at' => '2025-10-08 14:26:00',
            'deleted_at' => null,
        ])->assignRole('idea_reviewer');
    }
}
