<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function name_is_required_and_shows_validation_message()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('name');

        $errors = session('errors');
        $this->assertEquals('お名前を入力してください。', $errors->first('name'));
    }

    /** @test */
    public function email_is_required_and_shows_validation_message()
    {
        $response = $this->post('/register', [
            'name' => 'Taro',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $errors = session('errors');
    $this->assertEquals('メールアドレスを入力してください。', $errors->first('email'));
    }

    /** @test */
    public function password_minimum_length_validation_shows_message()
    {
        $response = $this->post('/register', [
            'name' => 'Taro',
            'email' => 'user2@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
        $errors = session('errors');
        // custom min message is defined under 'min.string' in ja validation
        $this->assertStringContainsString('8文字以上', $errors->first('password'));
    }

    /** @test */
    public function password_confirmation_must_match_and_shows_message()
    {
        $response = $this->post('/register', [
            'name' => 'Taro',
            'email' => 'user3@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors('password');
        $errors = session('errors');
        $this->assertEquals('パスワードと一致しません', $errors->first('password'));
    }

    /** @test */
    public function password_is_required_and_shows_validation_message()
    {
        $response = $this->post('/register', [
            'name' => 'Taro',
            'email' => 'user4@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('password');
        $errors = session('errors');
    $this->assertEquals('パスワードを入力してください。', $errors->first('password'));
    }

    /** @test */
    public function valid_form_saves_user_to_database()
    {
        $this->post('/register', [
            'name' => 'Taro Yamada',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'taro@example.com',
            'name' => 'Taro Yamada',
        ]);
    }
}
