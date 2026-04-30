<?php
require_once 'includes/db.php';

if ($conn) {
    echo "Подключение успешно";
} else {
    echo "Ошибка: переменная \$conn не определена";
}
?>