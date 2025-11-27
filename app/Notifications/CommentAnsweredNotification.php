<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CommentAnsweredNotification extends Notification
{
    use Queueable;

    private $recipe;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($recipe)
    {
        $this->recipe = $recipe;
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
        $message = (new MailMessage)
            ->subject('Han respondido a tu comentario')
            ->line('Alguien ha comentado una de las recetas.' . $this->recipe->titulo)
            ->salutation('Saludos, Healthy Martina')
            ->line('¡Gracias por elegirnos!')
            ->action('Ir a la receta', route('receta.show', [$this->recipe->slug]));

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
