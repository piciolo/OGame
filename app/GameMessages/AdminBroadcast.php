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

    public function getFrom(): string
    {
        return __('t_messages.admin_broadcast.from');
    }
}
