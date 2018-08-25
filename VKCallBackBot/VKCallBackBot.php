<?php

require __DIR__.'/VKCallBackBotAPI.php';
require __DIR__.'/VKCallBackBotMessages.php';

class VKCallBackBot
{
	public $access_token;
	public $vAPI;
	public $event;
	public $payload;
	public $confirmation_key;
	public $callback_secret;
	private $fwdMessagesProcess;
	private $CallBackData;
	private $WebServer;

	public function __construct(array $config)
	{
		$this->access_token = $config['access_token'];
		$this->fwdMessagesProcess = $config['fwdMessagesProcess'];
		$this->WebServer = $config['WebServer'];
		$this->vAPI = $config['vAPI'];
		$this->confirmation_key = $config['confirmation_key'];
		$this->callback_secret = $config['callback_secret'];

		$this->CallBackData = json_decode(file_get_contents('php://input'), 1);

		if(!empty($this->callback_secret) AND $this->CallBackData['secret'] != $this->callback_secret) {
			exit('ok');
		}

		$this->event = $this->CallBackData['type'];

		$this->api = new VKCallBackBotAPI($this);
		$this->message = new VKCallBackBotSend($this);
	}

	public function MessageProcessing()
	{
		$this->payload = json_decode($this->CallBackData['object']['payload'], 1);
		$this->message->setReceiver($this->CallBackData['object']['peer_id']);
		$this->api->setReceiver($this->CallBackData['object']['peer_id']);

		if($this->fwdMessagesProcess AND $this->CallBackData['object']['fwd_messages']) {
			$ForwardMessage = $this->GetLastForwardMessage($this->CallBackData['object']['fwd_messages']);
			$this->CallBackData['object']['text'] = $ForwardMessage['text'];
			$this->CallBackData['object']['attachments'] = $ForwardMessage['attachments'];
		}

		$MessageParts = explode(' ', $this->CallBackData['object']['text']);
		$this->body = $this->CallBackData['object']['text'];
		$this->attachments = $this->CallBackData['object']['attachments'];

		if(isset($this->payload['command'])) {
			$this->command = $this->payload['command'];
		} else {
			$this->command = mb_strtolower(array_shift($MessageParts));
		}

		$this->commandOptions = $MessageParts;
		$this->text = $this->CallBackData['object']['text'];
	}

	private function GetLastForwardMessage($array)
	{
		while (1) {
			if(isset($array[0]['fwd_messages'])) {
				$array[0] = $array[0]['fwd_messages'][0];
			} else break;
		}

		return $array[0] ?? [];
	}

	public function sendOK(bool $exit = true)
	{
		if($exit) {
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

				header("Content-Length: ".ob_get_length());
				ob_end_flush();
				flush();
				ob_end_clean();
			break;
		}
	}
}
