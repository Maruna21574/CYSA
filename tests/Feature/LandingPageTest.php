<?php

namespace Tests\Feature;

use App\Mail\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function message(array $overrides = []): array
    {
        return [
            'name' => 'Jana Učiteľová',
            'email' => 'jana@skola.sk',
            'school' => 'ZŠ Hlavná',
            'subject' => 'demo',
            'message' => 'Máme záujem o ukážku platformy pre 8. ročník.',
            'consent' => '1',
            ...$overrides,
        ];
    }

    public function test_landing_page_is_public_and_has_all_sections(): void
    {
        $response = $this->get(route('home'))->assertOk();

        foreach (['id="o-nas"', 'id="kurzy"', 'id="sluzby"', 'id="ako-to-funguje"', 'id="kontakt"'] as $anchor) {
            $response->assertSee($anchor, false);
        }

        $response->assertSee('Phishing')->assertSee(route('login'));
    }

    public function test_signed_in_user_gets_link_to_the_app(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('Do aplikácie'));
    }

    public function test_contact_form_sends_email_to_configured_address(): void
    {
        Mail::fake();
        config(['cysa.contact_email' => 'info@cysa.test']);

        $this->post(route('contact.store'), $this->message())
            ->assertRedirect(route('home').'#kontakt')
            ->assertSessionHas('contact_sent');

        Mail::assertQueued(ContactMessage::class, fn (ContactMessage $mail) => $mail->hasTo('info@cysa.test')
            && $mail->hasReplyTo('jana@skola.sk')
            && $mail->data['subject'] === 'Ukážka platformy pre školu');
    }

    public function test_contact_form_is_validated_and_requires_consent(): void
    {
        Mail::fake();
        config(['cysa.contact_email' => 'info@cysa.test']);

        $this->post(route('contact.store'), $this->message(['consent' => null, 'email' => 'zly', 'subject' => 'hacker']))
            ->assertSessionHasErrors(['consent', 'email', 'subject']);

        Mail::assertNothingQueued();
    }

    public function test_bots_filling_the_honeypot_are_ignored(): void
    {
        Mail::fake();
        config(['cysa.contact_email' => 'info@cysa.test']);

        $this->post(route('contact.store'), $this->message(['website' => 'http://spam.example']))
            ->assertSessionHas('contact_sent');

        Mail::assertNothingQueued();
    }

    public function test_contact_form_is_rate_limited(): void
    {
        Mail::fake();
        config(['cysa.contact_email' => 'info@cysa.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contact.store'), $this->message());
        }

        $this->post(route('contact.store'), $this->message())->assertStatus(429);
    }
}
