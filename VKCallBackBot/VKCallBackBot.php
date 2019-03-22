<?php

require __DIR__ . '/VKCallBackBotAPI.php';
require __DIR__ . '/VKCallBackBotEvents.php';
require __DIR__ . '/VKCallBackBotMessages.php';
require __DIR__ . '/VKCallBackBotConversation.php';

class VKCallBackBot
{
    public $access_token;
    public $vAPI;
    public $event;
    public $payload;
    public $confirmation_key;
    public $callback_secret;
    public $command;
    public $action;
    public $conversation_message_id;
    public $dialog_message_id;
    public $body;
    public $attachments = [];
    public $commandOptions;
    public $api;
    public $message;
    public $conversation;
    public $events;
    public $group_id;

    private $fwdMessagesProcess;
    private $CallBackData;
    private $WebServer;

    /** @var \Memcache */
    public $mc;

    public function __construct(array $config)
    {
        global $argv;

        if (is_array($config['access_token'])) {
            $this->access_token = $config['access_token'][array_rand($config['access_token'])];
        } else {
            $this->access_token = $config['access_token'];
        }

        $this->fwdMessagesProcess = $config['fwdMessagesProcess'];
        $this->WebServer = $config['WebServer'];
        $this->vAPI = $config['vAPI'];
        $this->confirmation_key = $config['confirmation_key'];
        $this->callback_secret = $config['callback_secret'];
        $this->group_id = $config['group_id'] ?? 0;

        $CallBackData = json_decode(file_get_contents('php://input'), 1);
        $PHParguments = $argv ?? [];

        if (class_exists('Memcache')) {
            $this->mc = new Memcache;
            $this->mc->connect('localhost', 11211);
        } else {
            $this->mc = null;
        }

        if (isset($CallBackData['type'])) {
            $this->CallBackData = $CallBackData;

            if (!empty($this->callback_secret) AND $this->CallBackData['secret'] != $this->callback_secret) {
                exit('ok');
            }
        } else {
            if (isset($PHParguments[1])) {
                $this->CallBackData = $this->mc->get($PHParguments[1]);
                $this->mc->delete($PHParguments[1]);
            }
        }

        $this->event = $this->CallBackData['type'];

        $this->api = new VKCallBackBotAPI($this);
        $this->events = new VKCallBackBotEvents($this);
        $this->message = new VKCallBackBotSend($this);
    }

    public function ConversationProcessing()
    {
        $this->conversation = new VKCallBackBotConversation($this);
    }

    public function ForwardedMessages()
    {
        return $this->CallBackData['object']['fwd_messages'] ?? [];
    }

    public function KeyboardEmulationDisable()
    {
        $this->message->alternative_keyboard = false;
    }

    public function KeyboardEmulation()
    {
        $this->message->alternative_keyboard = true;

        if (ctype_digit($this->body)) {
            $keyboard = $this->mc->get('uKeyboard_' . $this->api->receiver);

            if (isset($keyboard['buttons'])) {
                foreach ($keyboard['buttons'] as $buttonNumber => $buttonData) {
                    if ($buttonNumber == $this->body) {
                        $this->payload = json_decode($buttonData['payload'], 1);
                        $this->body = $buttonData['label'];

                        if (isset($this->payload['command'])) {
                            $this->command = $this->payload['command'];
                        } else {
                            $this->command = $this->body;
                        }

                        if ($keyboard['one_time']) {
                            $this->mc->delete('uKeyboard_' . $this->api->receiver);
                        }
                        break;
                    }
                }
            }
        }
    }

    public function MessageProcessing()
    {
        $this->payload = json_decode($this->CallBackData['object']['payload'], 1);
        $this->message->setReceiver($this->CallBackData['object']['peer_id']);
        $this->api->setReceiver($this->CallBackData['object']['peer_id']);
        $this->api->setFromID($this->CallBackData['object']['from_id']);

        if ($this->CallBackData['object']['conversation_message_id'] > 0) {
            $this->dialog_message_id = $this->api->getByConversationMessageId($this->CallBackData['object']['conversation_message_id']);
        } else {
            $this->dialog_message_id = $this->CallBackData['object']['id'];
        }

        if ($this->fwdMessagesProcess AND $this->CallBackData['object']['fwd_messages']) {
            $ForwardMessage = $this->GetLastForwardMessage($this->CallBackData['object']['fwd_messages']);
            $this->CallBackData['object']['text'] = $ForwardMessage['text'];
            $this->CallBackData['object']['attachments'] = $ForwardMessage['attachments'];
        }

        $MessageParts = explode(' ', $this->CallBackData['object']['text']);
        $this->body = $this->CallBackData['object']['text'];
        $this->attachments = $this->CallBackData['object']['attachments'];

        if (isset($this->payload['command'])) {
            $this->command = $this->payload['command'];
        } else {
            $this->command = mb_strtolower(array_shift($MessageParts));
        }

        $this->action = $this->CallBackData['object']['action'] ?? [];
        $this->commandOptions = $MessageParts;
    }

    private function GetLastForwardMessage(array $array)
    {
        while (1) {
            if (isset($array[0]['fwd_messages'])) {
                $array[0] = $array[0]['fwd_messages'][0];
            } else {
                break;
            }
        }

        return $array[0] ?? [];
    }

    public function sendOK(bool $exit = true)
    {
        if ($exit) {
            exit('ok');
        }

        switch ($this->WebServer) {
            case 1:
                echo "ok";
                fastcgi_finish_request();
                break;
            case 2:
                ob_end_clean();
                header("Connection: close\r\n");
                header("Content-Encoding: none\r\n");
                ob_start();

                echo "ok";

                header("Content-Length: " . ob_get_length());
                ob_end_flush();
                flush();
                ob_end_clean();
                break;
        }
    }
}
