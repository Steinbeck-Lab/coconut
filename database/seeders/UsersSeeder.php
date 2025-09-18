<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Str::random();
        $email = 'superadmin@email.com';

        $admin = User::create([
            'name' => 'Super admin User',
            'first_name' => 'Super admin',
            'last_name' => 'User',
            'username' => 'superadmin',
            'email' => $email,
            'password' => bcrypt($password),
            'email_verified_at' => Carbon::now(),
        ]);

        // Teams feature is disabled in Jetstream configuration
        // No team creation needed

        $admin->assignRole('super_admin');

        $this->command->alert('COCONUT: Users table seed successfully');
        $this->command->line('You may log in to admin console using <info>'.$email.'</info> and password: <info>'.$password.'</info>');
        $this->command->line('');
    }
}
