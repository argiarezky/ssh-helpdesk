<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

#[Signature('app:create-engineer')]
#[Description('Create or promote an engineer account interactively.')]
class CreateEngineer extends Command
{
    public function handle(): int
    {
        $email = mb_strtolower(trim($this->ask('Engineer email')));
        $name = trim($this->ask('Engineer name'));
        $existingUser = User::query()->where('email', $email)->first();

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($existingUser)],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        if ($existingUser && ! $this->confirm('This account exists. Assign it the engineer role and set a new password?', false)) {
            return self::FAILURE;
        }

        $password = $this->secret('Engineer password (5 to 12 characters)');
        $passwordConfirmation = $this->secret('Confirm password');
        $passwordValidator = Validator::make([
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'password' => ['required', 'confirmed', Password::min(5)->max(12)],
        ]);

        if ($passwordValidator->fails()) {
            $this->error($passwordValidator->errors()->first());

            return self::FAILURE;
        }

        $user = $existingUser ?? new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = 'engineer';
        $user->email_verified_at ??= now();
        $user->save();

        $this->info('Engineer account is ready.');

        return self::SUCCESS;
    }
}
