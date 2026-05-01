<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@shellfix.test'],
            [
                'name' => 'ShellFix Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'approved',
            ]
        );

        User::updateOrCreate(
            ['email' => 'lecturer@shellfix.test'],
            [
                'name' => 'ShellFix Lecturer',
                'password' => Hash::make('password'),
                'role' => 'lecturer',
                'status' => 'approved',
            ]
        );

        User::updateOrCreate(
            ['email' => 'student@shellfix.test'],
            [
                'name' => 'ShellFix Student',
                'password' => Hash::make('password'),
                'role' => 'student',
                'matric_no' => 'SF001',
                'status' => 'approved',
            ]
        );
    }
}
