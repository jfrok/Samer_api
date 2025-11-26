<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * The callback that should be used to create the reset password URL.
     *
     * @var \Closure|null
     */
    public static $createUrlCallback;

    /**
     * Create a new notification instance.
     *
     * @param  string  $token
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور')
            ->greeting('مرحباً!')
            ->line('أنت تتلقى هذا البريد الإلكتروني لأننا تلقينا طلب إعادة تعيين كلمة المرور لحسابك.')
            ->action('إعادة تعيين كلمة المرور', $url)
            ->line('ستنتهي صلاحية رابط إعادة تعيين كلمة المرور هذا خلال ' . config('auth.passwords.users.expire') . ' دقيقة.')
            ->line('إذا لم تطلب إعادة تعيين كلمة المرور، فلا داعي لاتخاذ أي إجراء.')
            ->salutation('مع أطيب التحيات، فريق ' . config('app.name'));
    }

    /**
     * Get the reset password URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function resetUrl($notifiable)
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
        }

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        return $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
    }

    /**
     * Set a callback that should be used when creating the reset password button URL.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function createUrlUsing($callback)
    {
        static::$createUrlCallback = $callback;
    }
}
