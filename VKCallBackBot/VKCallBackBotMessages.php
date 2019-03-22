<?php

class VKCallBackBotSend
{
    private $text;
    private $keyboard = [];
    private $attachments = [];
    private $countRows = -1;
    private $forward_messages = [];
    private $reply_to_mid = '';
    private $bot;
    public $alternative_keyboard = false;
    public $receiver = 0;

    public function __construct($bot)
    {
        $this->bot = $bot;
    }

    public function setReceiver(int $uid)
    {
        $this->receiver = $uid;
        return $this;
    }

    public function setReceivers(array $uids)
    {
        $this->receiver = $uids;
        return $this;
    }

    public function text(string $text)
    {
        $this->text = $text;
        return $this;
    }

    public function keyboard()
    {
        $this->keyboard = [
            'one_time' => null,
            'buttons' => null
        ];
        return $this;
    }

    public function row()
    {
        $this->countRows += 1;
        return $this;
    }

    public function button(string $title, string $color, array $payload)
    {
        $this->keyboard['buttons'][$this->countRows][] = [
            'action' => [
                'type' => 'text',
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'label' => mb_strimwidth($title, 0, 35, "...")
            ],
            'color' => $color
        ];
        return $this;
    }

    public function one_time(bool $parametr = false)
    {
        $this->keyboard['one_time'] = $parametr;

        if ($this->alternative_keyboard AND is_int($this->receiver)) {
            $c = 0;
            $this->text .= "\n\n";

            $keyboardData = [
                'one_time' => $parametr
            ];

            foreach ($this->keyboard['buttons'] as $Rows) {
                foreach ($Rows as $buttons) {
                    $c++;
                    $this->text .= $c . '. ' . $buttons['action']['label'] . "\n";

                    $keyboardData['buttons'][$c] = [
                        'label' => $buttons['action']['label'],
                        'payload' => $buttons['action']['payload']
                    ];
                }
            }

            $this->bot->mc->set('uKeyboard_' . $this->receiver, $keyboardData);
        }

        $this->keyboard = ['keyboard' => json_encode($this->keyboard, JSON_UNESCAPED_UNICODE)];

        return $this;
    }

    public function getKeyboard()
    {
        $this->countRows = -1;
        $keyboard = $this->keyboard;
        $this->keyboard = [];
        return $keyboard;
    }

    public function setKeyboard(array $keyboard)
    {
        $this->keyboard = $keyboard;
        return $this;
    }

    public function addPhoto(string $oID = '', string $iID = '')
    {
        $this->addAttachment('photo', $oID, $iID);
        return $this;
    }

    public function addVideo(string $oID = '', string $iID = '')
    {
        $this->addAttachment('video', $oID, $iID);
        return $this;
    }

    public function addAudio(string $oID = '', string $iID = '')
    {
        $this->addAttachment('audio', $oID, $iID);
        return $this;
    }

    public function addDoc(string $oID = '', string $iID = '')
    {
        $this->addAttachment('doc', $oID, $iID);
        return $this;
    }

    public function addWall(string $oID = '', string $iID = '')
    {
        $this->addAttachment('wall', $oID, $iID);
        return $this;
    }

    private function addAttachment(string $type, $oID = '', $iID = '')
    {
        if (stristr($oID, '_') === false) {
            $this->attachments[] = $type . $oID . '_' . $iID;
        } else {
            $this->attachments[] = $oID;
        }
    }

    public function delete($mid)
    {
        if (is_int($mid)) {
            return $this->bot->api->messageDelete($mid);
        }

        return ['error' => ['code' => 1, 'message' => 'mid is not a number']];
    }

    public function reply(int $id)
    {
        $this->reply_to_mid = $id;

        return $this;
    }

    public function forward(int $id)
    {
        $this->forward_messages[] = $id;

        return $this;
    }

    public function send()
    {
        $result = $this->bot->api->sendMessage([
            'receiver' => $this->receiver,
            'text' => $this->text,
            'keyboard' => $this->getKeyboard(),
            'attachments' => $this->attachments,
            'forward_messages' => $this->forward_messages,
            'reply_to' => $this->reply_to_mid,
        ]);

        $this->text = '';
        $this->attachments = [];
        $this->forward_messages = [];

        return $result;
    }

    public function edit($mid)
    {
        if (!is_int($mid)) {
            return ['error' => ['code' => 1, 'message' => 'mID is not a number.']];
        }

        $result = $this->bot->api->editMessage([
            'receiver' => $this->receiver,
            'message_id' => $mid,
            'text' => $this->text,
            'attachments' => $this->attachments,
        ]);

        $this->text = '';
        $this->attachments = [];
        $this->forward_messages = [];

        return $result;
    }
}