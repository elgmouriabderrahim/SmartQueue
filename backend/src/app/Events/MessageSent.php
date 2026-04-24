<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Message $message)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->sender_id),
            new PrivateChannel('conversation.'.$this->message->recipient_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $payload = $this->message->loadMissing(['sender', 'recipient', 'appointment']);

        return [
            'message' => [
                'id' => $payload->id,
                'sender_id' => $payload->sender_id,
                'recipient_id' => $payload->recipient_id,
                'appointment_id' => $payload->appointment_id,
                'content' => $payload->content,
                'status' => $payload->status,
                'created_at' => optional($payload->created_at)?->toISOString(),
                'sender' => $payload->sender,
                'recipient' => $payload->recipient,
                'appointment' => $payload->appointment,
            ],
        ];
    }
}
