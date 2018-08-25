<?php

ini_set('error_reporting', E_ERROR);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__.'/VKCallBackBot/VKCallBackBot.php';

$bot = new VKCallBackBot([
	'access_token' => 'ACCESS_TOKEN',
	'confirmation_key' => 'CONFIRMATION',
	'callback_secret' => 'SECRET', //Секретный ключ проверяется, если указан.
	'fwdMessagesProcess' => true, //Берем команды из сообщений, которые переслал пользователь.
	'WebServer' => 1, // 1 - nginx; 2 - apache.
	'vAPI' => '5.80',
]);

switch ($bot->event) {
	case 'message_new':
		
		//Обрабатывает сообщение пользователя и вводит нужные переменные.
		$bot->MessageProcessing();

		//Создаю стандартную клавитуру и помещаю её в переменную.
		$defaultKeyboard = $bot->message
							->keyboard()
								->row()
									->button('Кнопка 1', 'primary', ['command' => 'clickbutton', 'parametr' => 1])
									->button('Кнопка 2', 'primary', ['command' => 'clickbutton', 'parametr' => 2])
								->row()
									->button('Кнопка 3', 'positive', ['thisbutton' => 3])
									->button('Кнопка 4', 'positive', ['thisbutton' => 4])
								->row()
									->button('Тест вложений', 'default', ['command' => 'attachmentsTest'])
								->row()
									->button('Тест загрузки вложений', 'default', ['command' => 'attachmentsTestUpload'])
								->row()
									->button('Удаление сообщения', 'default', ['command' => 'deleteMessage'])
									->button('Редактирование сообщения', 'default', ['command' => 'editMessage'])
							->one_time() //false by default
						->getKeyboard();

		switch ($bot->command) {
			case 'start':
			case 'начать':
				$bot->message
					->text("Добро пожаловать!")
					->setKeyboard($defaultKeyboard)
				->send();
				break;

			case 'clickbutton':
				$bot->message
					->keyboard()
						->row()
							->button('Главное меню', 'primary', ['command' => 'start'])
					->one_time() //false by default
					->text('Вызвана команда "clickbutton" с параметром "'.$bot->payload['parametr'].'"')
				->send();
				break;

			case 'кнопка':
				$bot->message->text('Вызвана команда "кнопка" с полем "thisbutton" равным "'.$bot->payload['thisbutton'].'"')->send();
				break;

			case 'attachmentsTest':
				$bot->message
					->keyboard()
						->row()
							->button('Изображение', 'positive', ['command' => 'sendAttach', 'parametr' => 1])
							->button('Видео', 'positive', ['command' => 'sendAttach', 'parametr' => 2])
						->row()
							->button('Документ', 'positive', ['command' => 'sendAttach', 'parametr' => 3])
							->button('Аудио', 'positive', ['command' => 'sendAttach', 'parametr' => 4])
						->row()
							->button('Запись на стене', 'positive', ['command' => 'sendAttach', 'parametr' => 5])
						->row()
							->button('Отмена', 'negative', ['command' => 'start'])
					->one_time() //false by default
					->text("Выберите тип.")
				->send();
				break;

			case 'sendAttach':
				switch ($bot->payload['parametr']) {
					case 1:
						$bot->message
							//->addPhoto('photo137371466_325103360') //1 метод
							->addPhoto(137371466, 456239044) // 2 метод
							->send();
						break;

					case 2:
						$bot->message
							//->addVideo('video277941697_456239037') //1 метод
							->addVideo(277941697, 456239037) // 2 метод
							->send();
						break;

					case 3:
						$bot->message
							//->addDoc('doc-166966945_472727937') //1 метод
							->addDoc(-166966945, 472727937) // 2 метод
							->send();
						break;

					case 4:
						$bot->message
							//->addAudio('audio137371466_456239655') //1 метод
							->addAudio(137371466, 456239655) // 2 метод
							->send();
						break;

					case 5:
						$bot->message
							//->addWall('wall137371466_2811') //1 метод
							->addWall(137371466, 2811) // 2 метод
							->send();
						break;
					
					default:
						$bot->message->text("Тип не определен.")->send();
						break;
				}
				break;

			case 'attachmentsTestUpload':
				$bot->message->text("Началась загрузка файлов!")->send();
				
				//Передаю параметр false, тем самым не прекращая работу скрипта, а VK получает ответ "ok".
				//Рекомендую использовать когда пользовалю нужно отправить большой фаил и нужно чтобы вк не выбил TimeOut.
				$bot->sendOK(false);

				$uploadPhoto = $bot->api->uploadPhoto(__DIR__.'/assets/image.jpg');
				
				if(isset($uploadPhoto['id'])) {
					$bot->message
						->addPhoto($uploadPhoto['oid'], $uploadPhoto['id'])
						->send();
				} else {
					$bot->message->text("Возникла ошибка при загрузке.")->send();
				}

				$uploadDoc = $bot->api->uploadDoc(__DIR__.'/assets/file.txt', 'Название');

				if(isset($uploadDoc['id'])) {
					$bot->message
						->addDoc($uploadDoc['oid'], $uploadDoc['id'])
						->send();
				} else {
					$bot->message->text("Возникла ошибка при загрузке.")->send();
				}

				$uploadVoice = $bot->api->uploadVoice(__DIR__.'/assets/click_audio.wav');

				if(isset($uploadVoice['id'])) {
					$bot->message
						->addDoc($uploadVoice['oid'], $uploadVoice['id'])
						->send();
				} else {
					$bot->message->text("Возникла ошибка при загрузке.")->send();
				}
				break;

			case 'deleteMessage':
				$mid = $bot->message->text("Это сообщение удалится через 2 секунды.")->send();

				sleep(2);

				$bot->message->delete($mid);
				break;

			case 'editMessage':
				$mid = $bot->message->text("Ghbdtn!")->send();

				sleep(1.5);

				$bot->message->text("Привет!")->edit($mid);
				break;
			
			default:
				$bot->message
					->text("Команда не найдена.")
					->setKeyboard($defaultKeyboard)
				->send();
				break;
		}

		break;

	case 'confirmation':
		exit($bot->confirmation_key);
		break;
}

$bot->sendOK();