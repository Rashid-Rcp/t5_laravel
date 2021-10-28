<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class discussionComment
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $discussionId;
    public $comments;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($comments, $discussionId)
    {
        //
        $this->comments = $comments;
        $this->discussionId = $discussionId;
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'discussion.comments';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('discussion.'.$this->discussionId);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return ['discussion' => $this->discussionId, 'comments'=>$this->comments];
    }
}
