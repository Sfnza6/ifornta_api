<?php
// صفحة بسيطة لصناعة هاش لأي كلمة مرور وإظهاره لاستخدامه داخل قاعدة البيانات
header('Content-Type: text/plain; charset=utf-8');
$pwd = $_GET['p'] ?? '123456';
echo password_hash($pwd, PASSWORD_BCRYPT);
