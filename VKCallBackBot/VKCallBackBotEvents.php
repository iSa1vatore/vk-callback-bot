<?php

class VKCallBackBotEvents
{
    private $bot;
    private $LongPollData;

    public function __construct($bot)
    {
        $this->bot = $bot;
        $this->LongPollData = $this->bot->api->getLongPollServer();
    }

    public function get()
    {
        $LongPollData = $this->bot->api->curlRequest($this->LongPollData['server'] . '?act=a_check&key=' . $this->LongPollData['key'] . '&ts=' . $this->LongPollData['ts'] . '&wait=30', []);

        $this->LongPollData['ts'] = $LongPollData['ts'];

        return $LongPollData['updates'];
    }
}