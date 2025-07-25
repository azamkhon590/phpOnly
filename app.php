<?php
echo '<!DOCTYPE html><html><head><title>Обработка файла</title><meta charset="UTF-8">
      <script>
      function enableEditing() {
          const rows = document.querySelectorAll("table tbody tr");
          rows.forEach((row, index) => {
              const cells = row.cells;
              for (let i = 0; i < cells.length - 1; i++) { 
                  const value = cells[i].textContent;
                  cells[i].innerHTML = `<input type="text" value="${value}" name="data[${index}][${["weight","color","type","brand"][i]}]">`;
              }
              // Добавляем кнопку удаления
              cells[cells.length - 1].innerHTML = `<button type="button" class="delete-btn" onclick="deleteRow(this)">Удалить</button>`;
          });
          document.getElementById("editBtn").style.display = "none";
          document.getElementById("saveBtn").style.display = "block";
          document.getElementById("addRowBtn").style.display = "block";
      }
      
      function addNewRow() {
          const table = document.querySelector("table tbody");
          const rowCount = table.rows.length;
          const newRow = table.insertRow(-1);
          
          const fields = ["weight", "color", "type", "brand"];
          for (let i = 0; i < fields.length; i++) {
              const cell = newRow.insertCell(i);
              cell.innerHTML = `<input type="text" name="data[${rowCount}][${fields[i]}]" placeholder="${fields[i]}">`;
          }
          
          const deleteCell = newRow.insertCell(fields.length);
          deleteCell.innerHTML = `<button type="button" class="delete-btn" onclick="deleteRow(this)">Удалить</button>`;
      }
      
      function deleteRow(btn) {
          const row = btn.parentNode.parentNode;
          row.parentNode.removeChild(row);
          
          // Перенумеровываем строки после удаления
          const rows = document.querySelectorAll("table tbody tr");
          rows.forEach((row, index) => {
              const inputs = row.querySelectorAll("input");
              inputs.forEach((input, i) => {
                  const field = ["weight","color","type","brand"][i];
                  input.name = `data[${index}][${field}]`;
              });
          });
      }
      </script>
      <style>
          table { border-collapse: collapse; margin: 20px 0; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          th { background-color: #f2f2f2; }
          input[type="text"] { width: 100%; box-sizing: border-box; }
          #saveBtn, #addRowBtn { display: none; margin-top: 10px; margin-right: 10px; }
          .delete-btn { background-color: #ff6b6b; color: white; border: none; padding: 5px 10px; cursor: pointer; }
          .btn-container { margin-top: 20px; }
      </style>
      </head><body>';

echo '<form method="post" enctype="multipart/form-data">
        <input type="file" name="upload_file" accept=".txt">
        <input type="submit" value="Загрузить">
      </form>';

// Инициализируем переменные
$data = [];
$showEditForm = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка сохранения отредактированных данных
    if (isset($_POST['save_data'])) {
        $data = [];
        if (isset($_POST['data']) && is_array($_POST['data'])) {
            foreach ($_POST['data'] as $row) {
                // Пропускаем пустые строки
                if (!empty($row['weight']) || !empty($row['color']) || 
                    !empty($row['type']) || !empty($row['brand'])) {
                    
                    $data[] = [
                        'weight' => $row['weight'],
                        'color' => $row['color'],
                        'type' => $row['type'],
                        'brand' => $row['brand']
                    ];
                }
            }
        }

        // Сортировка данных
        usort($data, function ($a, $b) {
            if ($a['weight'] !== $b['weight']) return $a['weight'] <=> $b['weight'];
            if ($a['color'] !== $b['color']) return strcmp($a['color'], $b['color']);
            if ($a['type'] !== $b['type']) return strcmp($a['type'], $b['type']);
            return strcmp($a['brand'], $b['brand']);
        });

        // Сохранение в файл
        $resultFilename = 'result_' . date('Ymd_His') . '.txt';
        $resultContent = '';
        foreach ($data as $item) {
            $resultContent .= $item['weight'] . '|' 
                           . $item['color'] . '|' 
                           . $item['type'] . '|' 
                           . $item['brand'] . "\n";
        }
        
        file_put_contents($resultFilename, $resultContent);
        
        echo "<h2>Результат сохранен</h2>";
        echo "<p>Файл: <a href='$resultFilename' download>$resultFilename</a></p>";
        echo '<p><a href="'.$_SERVER['PHP_SELF'].'">Загрузить новый файл</a></p>';
        $showEditForm = true;
    }
    
    // Обработка загрузки файла
    if (isset($_FILES['upload_file'])) {
        $file = $_FILES['upload_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            die("Ошибка загрузки файла.");
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'txt') {
            die("Разрешены только файлы с расширением .txt");
        }

        $lines = file($file['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
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

        // Сортировка
        usort($data, function ($a, $b) {
            if ($a['weight'] !== $b['weight']) return $a['weight'] <=> $b['weight'];
            if ($a['color'] !== $b['color']) return strcmp($a['color'], $b['color']);
            if ($a['type'] !== $b['type']) return strcmp($a['type'], $b['type']);
            return strcmp($a['brand'], $b['brand']);
        });
        
        $showEditForm = true;
    }
}

if ($showEditForm && !empty($data)) {
    echo "<h2>Результат</h2>";
    echo '<form method="post">';
    echo '<table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse;">';
    echo '<thead><tr><th>Вес</th><th>Цвет</th><th>Тип</th><th>Марка</th><th>Действие</th></tr></thead>';
    echo '<tbody>';
    foreach ($data as $item) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($item['weight']) . '</td>';
        echo '<td>' . htmlspecialchars($item['color']) . '</td>';
        echo '<td>' . htmlspecialchars($item['type']) . '</td>';
        echo '<td>' . htmlspecialchars($item['brand']) . '</td>';
        echo '<td></td>'; // Пустая ячейка для кнопки удаления
        echo '</tr>';
    }
    echo '</tbody></table>';
    
    echo '<div class="btn-container">';
    echo '<button type="button" id="editBtn" onclick="enableEditing()">Редактировать</button>';
    echo '<button type="button" id="addRowBtn" onclick="addNewRow()">Добавить строку</button>';
    echo '<button type="submit" name="save_data" id="saveBtn">Сохранить изменения</button>';
    echo '</div>';
    echo '</form>';
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' || empty($data)) {
    echo "<p>Загрузите файл для обработки или данные отсутствуют</p>";
}

echo '</body></html>';