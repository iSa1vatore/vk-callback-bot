# CallBack-бот для VK.

В этой библиотке есть все что вам потребуется для автоматического взаимодействия с пользователем в чате сообщества.

Для настройки вам понадобится:
1. Создать объект.
```php
require __DIR__.'/VKCallBackBot/VKCallBackBot.php';

$bot = new VKCallBackBot([
	'access_token' => 'ACCESS_TOKEN',
	'confirmation_key' => 'CONFIRMATION',
	'callback_secret' => 'SECRET', //Секретный ключ проверяется, если указан.
	'fwdMessagesProcess' => true, //Берем команды из сообщений, которые переслал пользователь.
	'WebServer' => 1, // 1 - nginx; 2 - apache.
	'vAPI' => '5.80',
]);
```
access_token - Ключ доступа к сообществу, получается в настройках>Работа c API>Ключи доступа. Создаем и выдаем все нужные права после чего придет смс на телефон, вводим и получаем токен.

confirmation_key - Строка, которую должен вернуть сервер, находится в разделе "CallBack API".

callback_secret - Секретный ключ, в разделе "CallBack API" под него есть поле, вводим любые символы и сохраняем. Те же символы вводим и в конфиг объекта.

WebServer - обязательно нужно указать, используется для того, чтобы отправить VK ответ и продолжить выполнение скрипта. Например если вы будете грузить большой фаил пользователю, то VK вывалит TimeOut на запрос и пошлет его повторно.

В разделе "CallBack API" выбираем версию 5.80 и указываем адрес, например https://example.com/index.php, но не нажимаем подтвердить :)

Добавляем обработку событий после объекта:
```php
switch ($bot->event) {
	case 'confirmation':
    exit($bot->confirmation_key);
    break;
}
```

Теперь вы можете нажать кнопку "Подтвердить" и если все верно, то сервер установится.

По итогу у вас должен получиться примерно такой код:
```php
require __DIR__.'/VKCallBackBot/VKCallBackBot.php';

$bot = new VKCallBackBot([
	'access_token' => '632130721aa8da3183a02e885eef468947a1ac51139d3bee30c546ce118f23ab77f58e0918e08306bf00k',
	'confirmation_key' => '7c5478f7',
	'callback_secret' => '7c7c5478f7547c5478f778f7', //Секретный ключ проверяется, если указан.
	'fwdMessagesProcess' => true, //Берем команды из сообщений, которые переслал пользователь.
	'WebServer' => 1, // 1 - nginx; 2 - apache.
	'vAPI' => '5.80',
]);

switch ($bot->event) {
	case 'confirmation':
		exit($bot->confirmation_key);
		break;
}
```

Теперь включим обработку сообщений от пользователя, для этого добавим обработку типа "message_new", так же не забудьте поставить обработку входящих сообщений во вкладке "Типы событий".
Обязательно добавляем в конец файла функцию, которая отправит строку "OK", так VK поймет, что мы смогли ответить на событие и не будет отправлять его повторно.

```php
switch ($bot->event) {
	case 'message_new':
		
		//Обрабатывает сообщение пользователя и вводит нужные переменные.
		$bot->MessageProcessing();

		switch ($bot->command) {
			case 'start':
			case 'начать':
				$bot->message
					->text("Добро пожаловать!")
				->send();
				break;
			default:
				$bot->message
					->text("Команда не найдена.")
				->send();
				break;
		}

		break;

	case 'confirmation':
		exit($bot->confirmation_key);
		break;
}

$bot->sendOK();
```

Команда пользователя находится в переменной $bot->command она формируется из первого слова в сообщении, остальные слова помещаются по одному в массив $bot->commandOptions.

Далее мы видим обращение к объекту $bot->message он нужен для отправки/удаления/редактирования сообщений.
Чтобы отправить простое текстовое сообщение нужно выполнить след. команду:

```php
$bot->message
  ->text("Текст")
->send();
```

Также можно прикрепить вложения:

Прикрепление изображения:
```php
$bot->message
   ->addPhoto('photo137371466_325103360') //1 метод
   ->addPhoto(137371466, 456239044) // 2 метод
   ->text("Текст")
->send();
```
Прикрепление документа:
```php
$bot->message
  ->addDoc('doc-166966945_472727937') //1 метод
  ->addDoc(-166966945, 472727937) // 2 метод
  ->text("Текст")
->send();
```
Прикрепление видео:
```php
$bot->message
  ->addVideo('video277941697_456239037') //1 метод
  ->addVideo(277941697, 456239037) // 2 метод
  ->text("Текст")
->send();
```
Прикрепление аудиозаписи:
```php
$bot->message
  ->addAudio('audio137371466_456239655') //1 метод
  ->addAudio(137371466, 456239655) // 2 метод
  ->text("Текст")
->send();
```
Прикрепление записи:
```php
$bot->message
  ->addWall('wall137371466_2811') //1 метод
  ->addWall(137371466, 2811) // 2 метод
  ->text("Текст")
->send();
```

Добавление клавиатуры:
```php
$bot->message
  ->keyboard()
    ->row()
      ->button('Кнопка 1 в первом ряду', 'positive', ['command' => 'start'])
      ->button('Кнопка 2 в первом ряду', 'positive', ['iSa1vatore' => 'Sexy boy'])
    ->row()
      ->button('Кнопка 1 во втором ряду', 'positive', [])
      ->button('Кнопка 2 во втором ряду', 'positive', [])
    ->row()
      ->button('Кнопка 1 третьем ряду', 'positive', ['command' => 'sendAttach', 'parametr' => 5])
  ->one_time() //false by default
  ->text("Клацни на кнопошку")
->send();
```

Для создания массива клавиатуры вызовите ->keyboard()

Для создания ряда используйте ->row()

Для создания кнопки используйте ->button(Название, Цвет, массив который будет передан в payload)

У кнопок может быть один из 4 цветов: 
1. primary — синяя кнопка, обозначает основное действие. #5181B8 
2. default — обычная белая кнопка. #FFFFFF 
3. negative — опасное действие, или отрицательное действие (отклонить, удалить и тд). #E64646 
4. positive — согласиться, подтвердить. #4BB34B
Если передать "command" в payload, то именно эта команда попадет в переменную $bot->command. (Кнопка 1 в первом ряду)

После того как вы сформировали клавиатуру добавьте ->one_time() эта функция сформирует клавиатуру. В неё также можно передать параметр true, тогда клавиатура удалится у пользователя как только он ей воспользуется.

Если вам нужно передавать одинаковую клавиатуру сразу в нескольких условиях, то вы можете поместить её в переменную.

Помещение клавиатуры в переменную и отправка:
```php
$defaultKeyboard = $bot->message
          ->keyboard()
            ->row()
              ->button('Кнопка 1', 'primary', ['command' => 'clickbutton', 'parametr' => 1])
              ->button('Кнопка 2', 'primary', ['command' => 'clickbutton', 'parametr' => 2])
            ->row()
              ->button('Кнопка 3', 'positive', ['thisbutton' => 3])
              ->button('Кнопка 4', 'positive', ['thisbutton' => 4])
          ->one_time() //false by default
        ->getKeyboard(); //getKeyboard возвращает строку.
	
..........................

$bot->message
  ->text("Добро пожаловать!")
  ->setKeyboard($defaultKeyboard) //setKeyboard добавляет клавиатуру из переменной.
->send();
```
Чтобы удалить сообщение вызовите метод delete у объекта message передав в параметр ID сообщения.

```php
$mid = $bot->message->text("Меня удалят, вот же бля(")->send();

$bot->message->delete($mid);
```

Для редактирования сообщения используйте метод edit вместо send, но передав ID сообщения.
Также добавить вложения.

```php
$mid = $bot->message->text("Ghbdtn!")->send();

$bot->message->text("Привет!")->edit($mid);
```

Загрузка файлов (Думаю, примера кода будет достаточно):
```php
$bot->message->text("Началась загрузка файлов!")->send();
				
//Передаю параметр false, тем самым не прекращая работу скрипта, а VK получает ответ "ok".
//Рекомендую использовать когда пользователю нужно отправить большой фаил и нужно чтобы VK не выбил TimeOut.
$bot->sendOK(false);

//Загрузка фото
$uploadPhoto = $bot->api->uploadPhoto(__DIR__.'/assets/image.jpg');

if(isset($uploadPhoto['id'])) {
  $bot->message
    ->addPhoto($uploadPhoto['oid'], $uploadPhoto['id'])
    ->send();
} else {
  $bot->message->text("Возникла ошибка при загрузке.")->send();
}

//Загрузка документа
$uploadDoc = $bot->api->uploadDoc(__DIR__.'/assets/file.txt', 'Название');

if(isset($uploadDoc['id'])) {
  $bot->message
    ->addDoc($uploadDoc['oid'], $uploadDoc['id'])
    ->send();
} else {
  $bot->message->text("Возникла ошибка при загрузке.")->send();
}

//Загрузка голосового сообщения
$uploadVoice = $bot->api->uploadVoice(__DIR__.'/assets/click_audio.wav');

if(isset($uploadVoice['id'])) {
  $bot->message
    ->addDoc($uploadVoice['oid'], $uploadVoice['id'])
    ->send();
} else {
  $bot->message->text("Возникла ошибка при загрузке.")->send();
}
```

Пример бота созданного на этой основе: https://vk.com/vkbotbyisa1vatore

Исходный код этого бота: https://github.com/iSa1vatore/vk-callback-bot/tree/master/example

По вопросам и предложениям пишите мне в VK: https://vk.com/s9008
