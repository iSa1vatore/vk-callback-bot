<?php

class VKCallBackBotSend
{
	private $text;
	private $keyboard = [];
	private $attachments = [];
	private $countRows = -1;
	private $forward_messages = [];
	private $bot;
	public $receiver;

	public function __construct($bot)
	{
		$this->bot = $bot;
	}

	public function setReceiver(int $uid)
	{
		$this->receiver = $uid;
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
		if(stristr($oID, '_') === FALSE) {
		    $this->attachments[] = $type.$oID.'_'.$iID;
		} else {
			$this->attachments[] = $oID;
		}
	}

	public function delete($mid)
	{
		if(is_int($mid)) {
			return $this->bot->api->messageDelete($mid);
		}

		return ['error' => ['code' => 1, 'message' => 'mid is not a number']];
	}

	public function send()
	{
		$result = $this->bot->api->sendMessage([
			'receiver' => $this->receiver,
			'text' => $this->text,
			'keyboard' => $this->getKeyboard(),
			'attachments' => $this->attachments,
			'forward_messages' => $this->forward_messages
		]);

		$this->text = '';
		$this->attachments = [];
		$this->forward_messages = [];

		return $result;
	}

	public function edit($mid)
	{
		if(!is_int($mid))
			return ['error' => ['code' => 1, 'message' => 'mID is not a number.']];

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
