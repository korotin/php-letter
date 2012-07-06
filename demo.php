<?php
require_once dirname(__FILE__).'/letter.php';

letter::create()->
	// адрес и имя отправителя, имя можно не указывать
	from('vasya@gmail.com', 'Вася')->
	// адрес и имя получателя, имя так же можно опустить
	to('petya@gmail.com', 'Петя')->
	// тема письма
	subject('Тестовое письмо')->
	// тело письма с html, для plain-text можно использовать text()
	html('Мы можем отправить письмо с <b>html-форматированием</b>.')->
	// вложение из файла demo.php
	file('demo.php')->
	// вложение из файла с именем renamed_demo.php
	file('demo.php', 'renamed_demo.php')->
	// вложение из строки с именем test.txt
	data('проверонька', 'test.txt')->
	// поехали! :)
	send();