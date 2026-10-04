<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {email? : Admin email address}';

    protected $description = 'Create (or promote) an administrator who can sign in to /admin';

    public function handle(): int
    {
        $email = $this->argument('email') ?: text('Email', required: true);
        $user = User::where('email', $email)->first();

        if (! $user) {
            $name = text('Full name', required: true);
            $secret = password('Password (min 8 characters, letters and numbers)', required: true);

            $validator = Validator::make(
                ['email' => $email, 'password' => $secret],
                ['email' => ['email'], 'password' => [Password::min(8)->letters()->numbers()]],
            );

            if ($validator->fails()) {
                $this->error($validator->errors()->first());

                return self::FAILURE;
            }

            [$first, $last] = array_pad(explode(' ', $name, 2), 2, null);
            $user = new User(['first_name' => $first, 'last_name' => $last, 'email' => $email, 'password' => $secret, 'roles' => ['reader']]);
            $user->username = 'admin_'.substr(md5($email), 0, 6);
        }

        $user->is_admin = true;
        $user->email_verified_at ??= now();
        $user->save();

        $this->info("{$user->email} is now an administrator. Sign in at ".route('login').' (then open the admin panel from the profile menu or '.url('/admin').')');

        return self::SUCCESS;
    }
}
