<?php

class VKCallBackBotConversation
{
    private $bot;

	public function __construct($bot)
	{
		$this->bot = $bot;
	}

	public function members()
	{
		return $this->bot->api->getConversationMembers([
			'peer_id' => $this->bot->api->receiver
		]);
	}

	public function administrators()
	{
		$data = $this->members();

        $admins = [];

		foreach ($data['items'] as $member) {
			if($member['is_admin']) {
				$admins[] = $member['member_id'];
			}
		}

		return $admins;
	}

	public function kick(int $uid)
	{
		return $this->bot->api->removeChatUser([
			'chat_id' => $this->bot->api->receiver,
			'user_id' => $uid,
			'member_id' => $uid,
		]);
	}
}
