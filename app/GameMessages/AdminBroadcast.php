<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\GameMessage;

class AdminBroadcast extends GameMessage
{
    protected function initialize(): void
    {
        $this->key = 'admin_broadcast';
        $this->params = ['subject', 'body'];
        $this->tab = 'communication';
        $this->subtab = 'messages';
    }

    public function getSubject(): string
    {
        if (!empty($this->message->params['subject'])) {
            return $this->message->params['subject'];
        }
        // Fallback to the translated template (':subject' placeholder) when no
        // params are set — keeps the contract that every GameMessage returns a
        // non-empty subject (required by Tests\Feature\MessagesTest).
        return parent::getSubject();
    }

    public function getBody(): string
    {
        if (!empty($this->message->params['body'])) {
            return nl2br(e($this->message->params['body']));
        }
        // Fallback to the translated template (':body' placeholder) when no
        // params are set — same contract as above.
        return parent::getBody();
    }

    public function getFrom(): string
    {
        return __('t_messages.admin_broadcast.from');
    }
}
