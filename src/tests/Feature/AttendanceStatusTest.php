<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Timestamp;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser()
    {
        return User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
    }

    /** @test */
    public function current_datetime_is_displayed_in_ui_format()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 28, 14, 30, 0));

        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/attendance');

        $dateString = Carbon::now()->isoFormat('YYYY年MM月DD日（ddd）');
        $timeString = Carbon::now()->format('H:i');

        $response->assertStatus(200);
        $response->assertSee($dateString);
        $response->assertSee($timeString);
    }

    /** @test */
    public function shows_status_as_off_work_when_no_timestamp()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /** @test */
    public function shows_status_as_working_when_punched_in()
    {
        $user = $this->createUser();

        // Use the application flow to create a timestamp
        $this->actingAs($user)->post('/attendance/punchin')->assertRedirect();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /** @test */
    public function shows_status_as_on_break_when_break_in_exists()
    {
        $user = $this->createUser();
        // create via controller then create a break record directly to simulate break-in
        $this->actingAs($user)->post('/attendance/punchin')->assertRedirect();

        $ts = \App\Models\Timestamp::where('user_id', $user->id)->first();
        $ts->breakTime()->create(['breakIn' => Carbon::now()]);

        // assert a break record exists with null breakOut (休憩中)
        $this->assertDatabaseHas('breaks', [
            'timestamp_id' => $ts->id,
            'breakOut' => null,
        ]);

        // also ensure UI shows 休憩中
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩中');
    }

    /** @test */
    public function shows_status_as_checked_out_when_punched_out()
    {
        $user = $this->createUser();

        // create via controller flow
        $this->actingAs($user)->post('/attendance/punchin')->assertRedirect();
        $this->actingAs($user)->post('/attendance/punchout')->assertRedirect();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

}
