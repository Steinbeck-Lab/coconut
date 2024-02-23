<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        $password = Str::random();
        $email = 'superadmin@email.com';

        $admin = User::create([
            'name' => 'Super admin',
            'email' => $email,
            'password' => bcrypt($password),
            'email_verified_at' => Carbon::now(),
        ]);

        $this->command->alert('nmrXiv: Users table seed successfully');
        $this->command->line('You may log in to admin console using <info>'.$email.'</info> and password: <info>'.$password.'</info>');
        $this->command->line('');

        $this->call(ShieldSeeder::class);
        $this->call(LicenseSeeder::class);
        $this->call(CitationSeeder::class);
        $this->call(CollectionSeeder::class);
    }
}
