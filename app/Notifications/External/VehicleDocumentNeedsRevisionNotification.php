<?php

namespace App\Notifications\External;

use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleDocumentNeedsRevisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Vehicle $vehicle,
        public VehicleDocument $document,
        public string $remarks,
    ) {
    }

    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'external_vehicle_document_needs_revision',
            'title' => 'Vehicle document needs revision',
            'message' => "{$this->document->document_type} for vehicle {$this->vehicle->plate_number} was marked invalid.",
            'vehicle_id' => $this->vehicle->id,
            'company_id' => $this->vehicle->company_id,
            'document_id' => $this->document->id,
            'plate_number' => $this->vehicle->plate_number,
            'document_type' => $this->document->document_type,
            'remarks' => $this->remarks,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vehicle Document Needs Revision')
            ->greeting('Hello,')
            ->line("A vehicle document for {$this->vehicle->plate_number} needs correction.")
            ->line("Document type: {$this->document->document_type}")
            ->line("Remarks: {$this->remarks}")
            ->action('Open Vehicle Record', route('company.vehicles.show', $this->vehicle->id))
            ->line('Please upload the corrected document as soon as possible.');
    }
}
