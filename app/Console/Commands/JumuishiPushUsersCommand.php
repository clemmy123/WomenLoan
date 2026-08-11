<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Jumuishi\JumuishiCentralUserSync;
use App\Services\JumuishiUrl;
use Illuminate\Console\Command;
use Throwable;

class JumuishiPushUsersCommand extends Command
{
    protected $signature = 'jumuishi:push-users
                            {--email= : Push only this email}
                            {--sync-password : Push local password hash to central (default true)}
                            {--no-sync-password : Do not overwrite central password}';

    protected $description = 'Push local WDF users to Jumuishi so they can sign in centrally (keeps local roles).';

    public function handle(JumuishiCentralUserSync $sync): int
    {
        if (! JumuishiUrl::enabled()) {
            $this->error('JUMUISHI_ENABLED is false.');

            return self::FAILURE;
        }

        if (! config('jumuishi.api_secret')) {
            $this->error('JUMUISHI_API_SECRET is empty.');

            return self::FAILURE;
        }

        $syncPassword = ! $this->option('no-sync-password');
        if ($this->option('sync-password')) {
            $syncPassword = true;
        }

        $query = User::query()->orderBy('id');
        if ($email = $this->option('email')) {
            $query->whereRaw('LOWER(email) = ?', [strtolower($email)]);
        }

        $users = $query->get();
        if ($users->isEmpty()) {
            $this->warn('No users found.');

            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $result = $sync->push($user, $syncPassword);
                $ok++;
                $this->line(sprintf(
                    'OK  %s → global_user_id=%d%s',
                    $user->email,
                    $result['global_user_id'],
                    $result['created'] ? ' (created)' : ' (updated)'
                ));
            } catch (Throwable $e) {
                $failed++;
                $this->error("FAIL {$user->email}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done. synced={$ok} failed={$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
