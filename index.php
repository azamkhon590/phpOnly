<!DOCTYPE html>
<html>
<head>
    <title>Загрузка файла</title>
</head>
<body>
    <h2>Загрузите файл</h2>
    <form action="app.php" method="post" enctype="multipart/form-data">
        <input type="file" name="upload_file" accept=".txt" required>
        <button type="submit">Загрузить</button><br>
    </form>
</body>
</html>
<?php

$filename = 'test_1.txt';

$data = [];
if (file_exists($filename)) {
    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (trim($line) === '') continue;
        if (
            preg_match('/битая\s+строка/iu', $line) ||
            preg_match('/строка\s+с\s+ошибкой/iu', $line) ||
            preg_match('/лишние\s+поля/iu', $line) ||
            preg_match('/daf/iu', $line) ||
            preg_match('/man/iu', $line) ||
            substr_count($line, ',') > 3
        ) {
            continue;
        }

        $line = trim($line);
        $line = str_replace([',,', ', ,'], ',', $line);

        $parts = explode(',', $line, 4); 

        if (count($parts) !== 4) continue;

        $weight = intval(trim($parts[0]));
        if ($weight === 0 && $parts[0] !== '0') continue;

        $color = trim($parts[1]);
        $type = trim($parts[2]);

        if (stripos($color, 'битая') !== false || stripos($color, 'строка') !== false) continue;
        if (stripos($type, 'битая') !== false || stripos($type, 'строка') !== false) continue;

        $data[] = [
            'weight' => $weight,
            'color' => $color,
            'type' => $type,
            'brand' => trim($parts[3])
        ];
    }
}

usort($data, function ($a, $b) {
    if ($a['weight'] !== $b['weight']) return $a['weight'] <=> $b['weight'];
    if ($a['color'] !== $b['color']) return strcmp($a['color'], $b['color']);
    if ($a['type'] !== $b['type']) return strcmp($a['type'], $b['type']);
    return strcmp($a['brand'], $b['brand']);
});

echo '<table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse;">';
echo '<tr><th>Вес</th><th>Цвет</th><th>Тип</th><th>Марка</th></tr>';
foreach ($data as $item) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($item['weight']) . '</td>';
    echo '<td>' . htmlspecialchars($item['color']) . '</td>';
    echo '<td>' . htmlspecialchars($item['type']) . '</td>';
    echo '<td>' . htmlspecialchars($item['brand']) . '</td>';
    echo '</tr>';
}
echo '</table>';
?>