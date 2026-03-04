<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        Admin::create([
            'name'     => '管理者',
            'email'    => 'admin@admin.admin',
            'password' => Hash::make('password'),
        ]);

        Admin::create([
            'name'     => '管理者2',
            'email'    => 'admin2@admin.admin', // メールアドレスは一意である必要があります
            'password' => Hash::make('password2'),
        ]);
    }
}
