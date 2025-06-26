<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CheckUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check existing users and test credentials';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking users in the system...');

        $users = User::all();

        if ($users->isEmpty()) {
            $this->error('No users found in the system!');
            return;
        }

        $this->info('Found ' . $users->count() . ' users:');
        $this->table(
            ['ID', 'Name', 'Email', 'Username', 'Active'],
            $users->map(function ($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->username,
                    $user->is_active ? 'Yes' : 'No'
                ];
            })->toArray()
        );

        // Test login with default credentials
        $this->info('Testing login with common credentials...');

        $testCredentials = [
            ['email' => 'oman@gmail.com', 'password' => '123456'],
            ['email' => 'prana@gmail.com', 'password' => '123456'],
            ['email' => 'logistic@gmail.com', 'password' => '123456'],
            ['email' => 'dadsdevteam@example.com', 'password' => 'dds2024'],
        ];

        foreach ($testCredentials as $creds) {
            $user = User::where('email', $creds['email'])->first();
            if ($user) {
                $this->info("Found user: {$user->email}");
                $this->info("Active: " . ($user->is_active ? 'Yes' : 'No'));
                if (Hash::check($creds['password'], $user->password)) {
                    $this->info("✓ Password '{$creds['password']}' is correct for {$creds['email']}");
                } else {
                    $this->error("✗ Password '{$creds['password']}' is incorrect for {$creds['email']}");
                }
            } else {
                $this->error("User {$creds['email']} not found");
            }
            $this->line('');
        }
    }
}
