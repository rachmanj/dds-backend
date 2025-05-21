<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the main test user with all required fields
        User::create([
            'name' => 'DDS Team',
            'email' => 'dadsdevteam@example.com',
            'username' => 'ddstest',
            'password' => Hash::make('dds2024'),
            'project' => '000H',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Assign department and role if needed
        $user = User::where('email', 'dadsdevteam@example.com')->first();

        // Assign the IT department to the test user
        $departmentId = DB::table('departments')->where('akronim', 'IT')->value('id');
        if ($departmentId) {
            $user->update(['department_id' => $departmentId]);
        }

        // Assign the superadmin role to the test user
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('superadmin');
        }

        $this->command->info('Test user created successfully.');
    }
}
