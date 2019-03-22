<?php

class VKCallBackBotAPI
{
    private $bot;
    public $receiver;
    public $from_id;

    private $curlProgressFunction = [
        'use' => false,
        'object' => null,
        'function' => null
    ];

    public function __construct($bot)
    {
        $this->bot = $bot;
        $this->receiver = $bot;
    }

    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;
    }

    public function setFromID($from_id)
    {
        $this->from_id = $from_id;
    }

    public function getLongPollServer()
    {
        return $this->call('groups.getLongPollServer', [
            'group_id' => $this->bot->group_id
        ]);
    }

    public function messageDelete(int $mid)
    {
        return $this->call('messages.delete', [
            'message_ids' => $mid,
            'delete_for_all' => 1
        ]);
    }

    public function messagesGetHistory(array $fields)
    {
        if (!isset($fields['uid'])) {
            $fields['uid'] = $this->receiver;
        }

        return $this->call('messages.getHistory', [
            'count' => $fields['count'],
            'peer_id' => $fields['uid'],
            'offset' => $fields['offset'],
        ]);
    }

    public function groupsIsMember(int $user_id)
    {
        return $this->call('groups.isMember', [
            'group_id' => $this->bot->group_id,
            'user_id' => $user_id,
            'extended' => 0,
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

        if (is_null($savePhoto[0]['owner_id'])) {
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
            'file' => $uploadDoc['file'],
            'title' => $title,
        ]);

        if (is_null($saveDoc['doc']['owner_id'])) {
            return ['error_upload' => $saveDoc];
        }

        return [
            'oid' => $saveDoc['doc']['owner_id'],
            'id' => $saveDoc['doc']['id']
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
            'file' => $uploadDoc['file'],
            'title' => "Voice Message",
        ]);

        if (is_null($saveDoc[0]['owner_id'])) {
            return ['error_upload' => $saveDoc];
        }

        return [
            'oid' => $saveDoc[0]['owner_id'],
            'id' => $saveDoc[0]['id']
        ];
    }

    public function getByConversationMessageId($ids)
    {
        return $this->call('messages.getByConversationMessageId', [
            'peer_id' => $this->receiver,
            'conversation_message_ids' => $ids,
        ]);
    }

    public function sendMessage(array $fields)
    {
        if (is_array($fields['receiver'])) {
            $receiverField['user_ids'] = implode(',', $fields['receiver']);
        } else {
            $receiverField['peer_id'] = $fields['receiver'];
        }

        $Data = [
                'dont_parse_links' => 1,
                'random_id' => mt_rand(),
                'message' => $fields['text'],
                'attachment' => implode(',', $fields['attachments']),
                'forward_messages' => implode(',', $fields['forward_messages']),
            ] + $fields['keyboard'] + $receiverField;

        return $this->call('messages.send', $Data);
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

    public function getConversationMembers(array $fields)
    {
        return $this->call('messages.getConversationMembers', [
            'peer_id' => $fields['peer_id'],
        ]);
    }

    public function removeChatUser(array $fields)
    {
        return $this->call('messages.removeChatUser', [
            'chat_id' => $fields['chat_id'] - 2000000000,
            'user_id' => $fields['user_id'],
            'member_id' => $fields['member_id'],
        ]);
    }

    public function usersGet($uids)
    {
        if (!is_array($uids)) {
            $uids = [$uids];
        }

        return $this->call('users.get', [
            'user_ids' => implode(',', $uids)
        ]);
    }

    public function curlRequest(string $server, array $parameters)
    {
        $curlOptions = [
            CURLOPT_URL => $server,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $parameters,
            CURLOPT_RETURNTRANSFER => true
        ];

        if ($this->curlProgressFunction['use']) {
            $curlOptions[CURLOPT_PROGRESSFUNCTION] = [
                $this->curlProgressFunction['object'],
                $this->curlProgressFunction['function']
            ];
            $curlOptions[CURLOPT_NOPROGRESS] = false;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curlOptions);
        $result = json_decode(curl_exec($ch), 1);
        curl_close($ch);
        return $result;
    }

    public function setCurlRequestProgressFunction(array $params)
    {
        $this->curlProgressFunction = [
            'use' => true,
            'object' => $params['object'],
            'function' => $params['function']
        ];
    }

    public function call(string $method, array $fields)
    {
        $fields['access_token'] = $this->bot->access_token;
        $fields['v'] = $this->bot->vAPI;

        $ch = curl_init('https://api.vk.com/method/' . $method);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $result = json_decode(curl_exec($ch), 1);
        curl_close($ch);

        if (isset($result['error'])) {
            return $result['error'];
        } else {
            return $result['response'];
        }
    }
}
