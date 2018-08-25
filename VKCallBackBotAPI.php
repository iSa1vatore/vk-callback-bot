<?php

class VKCallBackBotAPI
{
	private $bot;
	private $receiver;

	public function __construct($bot)
	{
		$this->bot = $bot;
		$this->receiver = $bot;
	}

	public function setReceiver($receiver)
	{
		$this->receiver = $receiver;
	}

	public function messageDelete(int $mid)
	{
		return $this->call('messages.delete', [
			'message_ids' => $mid,
			'delete_for_all' => 1
		]);
	}

	public function uploadPhoto(string $filename)
	{
		$uploadServer = $this->call('photos.getMessagesUploadServer', [
			'peer_id' => $this->receiver
		])['upload_url'];

		$uploadPhoto = $this->curlRequest($uploadServer, ['file' => new CURLfile($filename)]);

		$savePhoto = $this->call('photos.saveMessagesPhoto', [
			'photo' => $uploadPhoto['photo'],
			'server' => $uploadPhoto['server'],
			'hash' => $uploadPhoto['hash'],
		]);

		if(is_null($savePhoto[0]['owner_id'])) {
			return ['error_upload' => $savePhoto];
		}

		return [
			'oid' => $savePhoto[0]['owner_id'],
			'id' => $savePhoto[0]['id']
		];
	}

	public function uploadDoc(string $filename, $title = '')
	{
		$uploadServer = $this->call('docs.getMessagesUploadServer', [
			'peer_id' => $this->receiver,
			'type' => 'doc'
		])['upload_url'];

		$uploadDoc = $this->curlRequest($uploadServer, ['file' => new CURLfile($filename)]);

		$saveDoc = $this->call('docs.save', [
		    'file'  => $uploadDoc['file'],
			'title' => $title,
		]);

		if(is_null($saveDoc[0]['owner_id'])) {
			return ['error_upload' => $saveDoc];
		}

		return [
			'oid' => $saveDoc[0]['owner_id'],
			'id' => $saveDoc[0]['id']
		];
	}

	public function uploadVoice(string $filename)
	{
		$uploadServer = $this->call('docs.getMessagesUploadServer', [
			'peer_id' => $this->receiver,
			'type' => 'audio_message'
		])['upload_url'];

		$uploadDoc = $this->curlRequest($uploadServer, ['file' => new CURLfile($filename)]);

		$saveDoc = $this->call('docs.save', [
		    'file'  => $uploadDoc['file'],
			'title' => "Voice Message",
		]);

		if(is_null($saveDoc[0]['owner_id'])) {
			return ['error_upload' => $saveDoc];
		}

		return [
			'oid' => $saveDoc[0]['owner_id'],
			'id' => $saveDoc[0]['id']
		];
	}

	public function sendMessage(array $fields)
	{
		return $this->call('messages.send', [
			'random_id' => mt_rand(),
			'peer_id' => $fields['receiver'],
			'message' => $fields['text'],
			'attachment' => implode(',', $fields['attachments']),
			'forward_messages' => implode(',', $fields['forward_messages']),
		] + $fields['keyboard']);
	}

	public function editMessage(array $fields)
	{
		return $this->call('messages.edit', [
			'message_id' => $fields['message_id'],
			'peer_id' => $fields['receiver'],
			'message' => $fields['text'],
			'attachment' => implode(',', $fields['attachments']),
		]);
	}

	public function curlRequest($server, $parameters) 
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $server);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SAFE_UPLOAD, TRUE);
		$result = json_decode(curl_exec($ch), 1);
		curl_close($ch);
		return $result;
	}

	public function call(string $method, array $fields) 
	{
		$fields['access_token'] = $this->bot->access_token;
		$fields['v'] = $this->bot->vAPI;

		$ch = curl_init('https://api.vk.com/method/'.$method);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => $fields,
			CURLOPT_SSL_VERIFYPEER => false,
		]);
		$result = json_decode(curl_exec($ch), 1);
		curl_close($ch);

		if(isset($result['error'])) {
			return $result['error'];
		} else {
			return $result['response'];
		}
	}
}