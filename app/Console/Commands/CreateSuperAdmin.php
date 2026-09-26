<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates the first super admin on a fresh (production) installation over SSH.
 */
#[Signature('app:create-super-admin')]
#[Description('Vytvorí účet super administrátora')]
class CreateSuperAdmin extends Command
{
    public function handle(): int
    {
        $data = [
            'first_name' => text('Meno', required: true),
            'last_name' => text('Priezvisko', required: true),
            'email' => text('E-mail', required: true),
            'password' => password('Heslo (min. 12 znakov)', required: true),
        ];

        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([...$validator->validated(), 'role' => UserRole::SuperAdmin, 'school_id' => null]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->info("Super administrátor {$user->email} bol vytvorený.");

        return self::SUCCESS;
    }
}
