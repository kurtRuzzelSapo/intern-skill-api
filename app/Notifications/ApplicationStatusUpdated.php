<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusUpdated extends Notification
{
    use Queueable;

    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        try {
            $status = $this->application->status;
            $internshipTitle = $this->application->internship->title;
            $companyName = $this->application->internship->recruiter->company;

            return (new MailMessage)
                ->subject("Your Application Status Has Been Updated")
                ->greeting("Hello " . $notifiable->fullname)
                ->line("Your application for the position of {$internshipTitle} at {$companyName} has been {$status}.")
                ->line($this->getStatusMessage($status))
                ->line("Thank you for using our platform!");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Email Notification Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getStatusMessage($status): string
    {
        return match ($status) {
            'accepted' => "Congratulations! We look forward to having you join the team.",
            'rejected' => "We appreciate your interest and encourage you to apply for other opportunities.",
            default => "Please check your application dashboard for more details.",
        };
    }
}
