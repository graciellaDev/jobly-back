<!DOCTYPE html>
<html>

<head>
    <title>{{  $data['subject'] }}</title>
</head>

<body>
<h1>Привет, {{ $data['name'] }}!</h1>
<p>Вы отправили запрос на восстановление пароля! Перейдите по ссылке, чтобы восстановить доступ.</p>
<a href="{{ $data['url'] }}">Ссылка на восстановление</a>
</body>

</html>
