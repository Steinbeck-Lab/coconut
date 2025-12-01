<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_profile_information_is_available(): void
    {
        $this->actingAs($user = User::factory()->create());

        $component = Livewire::test(UpdateProfileInformationForm::class);

        $this->assertEquals($user->name, $component->state['name']);
        $this->assertEquals($user->email, $component->state['email']);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(UpdateProfileInformationForm::class)
            ->set('state', [
                'first_name' => 'Test',
                'last_name' => 'Name',
                'username' => 'testuser',
                'email' => 'test@example.com',
                'affiliation' => 'Test University',
                'orcid_id' => '',
            ])
            ->call('updateProfileInformation');

        $user = $user->fresh();
        $this->assertEquals('Test', $user->first_name);
        $this->assertEquals('Name', $user->last_name);
        $this->assertEquals('test@example.com', $user->email);
    }
}
