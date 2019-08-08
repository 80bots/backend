<?php

namespace App\Helpers;

use App\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailHelper
{
    /**
     * @param User $user
     * @return bool
     */
    public static function welcomeEmail(User $user): bool
    {
        try {
            Mail::send('mail.register', ['user' => $user], function($mail) use ($user) {
                $mail->to($user->email ?? '', $user->name ?? '');
                $mail->subject(__('mail.subject_welcome'));
                $mail->from(config('mail.from.address'), config('mail.from.name'));
            });
            return true;
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return false;
        }
    }

    /**
     * @param User $user
     * @return bool
     */
    public static function updateUserCreditSendEmail(User $user): bool
    {
        try {
            Mail::send('mail.update_user_credit', ['user' => $user], function($mail) use ($user) {
                $mail->to($user->email ?? '', $user->name ?? '');
                $mail->subject(__('mail.subject_admin_update_credit'));
                $mail->from(config('mail.from.address'), config('mail.from.name'));
            });
            return true;
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return false;
        }
    }

    /**
     * @param User $user
     * @return bool
     */
    public static function userCreditSendEmail(User $user): bool
    {
        try {
            Mail::send('mail.user_credit', ['user' => $user], function($mail) use ($user) {
                $mail->to($user->email ?? '', $user->name ?? '');
                $mail->subject(__('mail.subject_update_credit'));
                $mail->from(config('mail.from.address'), config('mail.from.name'));
            });
            return true;
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            return false;
        }
    }
}
