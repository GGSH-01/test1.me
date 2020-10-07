<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>




<?php


require_once "PHPExcel.php";

// Соединение с базой MySQL
$connection = mysqli_connect('127.0.0.1', 'root', 'root', 'mydatabasetest1');
// Выбираем кодировку UTF-8
$connection->set_charset("utf8");


//==============================================================================================
// Загружаем файл Excel в бд
$PHPExcel_file = PHPExcel_IOFactory::load("./pricelist.xls");

$query = mysqli_query($connection, "DELETE FROM pricelisttable"); // чистит таблицу перед заполнением

foreach ($PHPExcel_file->getWorksheetIterator() as $worksheet) // цикл обходит страницы файла
{
  $highestRow = $worksheet->getHighestRow(); // получаем количество строк
  
  for ($row = 1; $row <= $highestRow; ++ $row) // обходим все строки
  {
    $cell1 = $worksheet->getCellByColumnAndRow(0, $row); //Наименование товара
    $cell2 = $worksheet->getCellByColumnAndRow(1, $row); //Стоимость, руб
    $cell3 = $worksheet->getCellByColumnAndRow(2, $row); //Стоимость опт, руб
    $cell4 = $worksheet->getCellByColumnAndRow(3, $row); //Наличие на складе 1, шт
    $cell5 = $worksheet->getCellByColumnAndRow(4, $row); //Наличие на складе 2, шт
    $cell6 = $worksheet->getCellByColumnAndRow(5, $row); //Страна производства
    $sql = "INSERT INTO `pricelisttable` (`Наименование товара`,`Стоимость, руб`,`Стоимость опт, руб`,`Наличие на складе 1, шт`,`Наличие на складе 2, шт`,`Страна производства`) VALUES
('$cell1','$cell2','$cell3','$cell4','$cell5','$cell6')";
    $query = mysqli_query($connection, $sql);
  }
}



//==============================================================================================
// вывод данных из базы на страницу


$query2 = "SELECT * FROM `pricelisttable`"; // запрос для вывода всех данных таблицы на страницу

$result = mysqli_query($connection, $query2) or die("Ошибка " . mysqli_error($connection)); 




if($result)
{
    $rows = mysqli_num_rows($result); // количество полученных строк
     
    echo "<table class='table table-bordered'><tr>
      <th>Наименование товара</th>
      <th>Стоимость, руб</th>
      <th>Стоимость опт, руб</th>
      <th>Наличие на складе 1, шт</th>
      <th>Наличие на складе 2, шт</th>
      <th>Страна производства</th>
      <th>Примечание</th>
    </tr>";
    for ($i = 0 ; $i < $rows ; ++$i)
    {
        $row = mysqli_fetch_row($result);
        echo "<tr>";
            for ($j = 0 ; $j < 7 ; ++$j) 
            {
              // if($j = 3; && $row[$j] === )
              
              
              echo "<td>$row[$j]</td>";

            }
        echo "</tr>";
    }
    echo "</table>";
     
    // очищаем результат
    mysqli_free_result($result);
}

//====================================================================================
// считаю общее количество товара на складах

$queryStore1 ="SELECT SUM(`Наличие на складе 1, шт`) FROM `pricelisttable`"; // запрос для вывода количества товара на складе1
$queryStore2 ="SELECT SUM(`Наличие на складе 2, шт`) FROM `pricelisttable`"; // запрос для вывода количества товара на складе1

$store1 = mysqli_query($connection, $queryStore1) or die("Ошибка " . mysqli_error($connection)); 
$store2 = mysqli_query($connection, $queryStore2) or die("Ошибка " . mysqli_error($connection)); 

$allStore = mysqli_fetch_row($store1)[0] + mysqli_fetch_row($store2)[0];

//====================================================================================
// считаю среднюю цену товара

$queryRetailPrice ="SELECT AVG(`Стоимость, руб`) FROM `pricelisttable`"; // запрос для расчета средней розничной цены
$queryTradePrice ="SELECT AVG(`Стоимость опт, руб`) FROM `pricelisttable`"; // запрос для расчета средней оптовой цены

$avgRetailPrice = mysqli_query($connection, $queryRetailPrice) or die("Ошибка " . mysqli_error($connection)); 
$avgTradePrice = mysqli_query($connection, $queryTradePrice) or die("Ошибка " . mysqli_error($connection)); 

//==============================================================================================
// поиск самого дорогого товара (по рознице)

$queryMaxRetailPrice = "SELECT `Стоимость, руб` FROM `pricelisttable`";


$maxRetailPrice = mysqli_query($connection, $queryMaxRetailPrice);  
for($i = 0 ; $i < $rows ; ++$i)                                               //создаем одномерный массив со стоимосью в розницу
{                                   
     $arrMaxRetailPrice[] = mysqli_fetch_row($maxRetailPrice)[0];
     
}
$arrMaxRetailPrice2 = preg_replace("/[^,.0-9]/", '', $arrMaxRetailPrice); // убираем из массива не числовые значения

$finalMaxRetailPrice = max($arrMaxRetailPrice2);

//==============================================================================================
// поиск самого дешевого товара (по опту)


// НЕ ЗАКОНЧЕНО!

$queryMinTradePrice = "SELECT `Стоимость опт, руб` FROM `pricelisttable`";


$minTradePrice = mysqli_query($connection, $queryMinTradePrice);  
for($i = 0 ; $i < $rows ; ++$i)                                                 //создаем одномерный массив со стоимосью в розницу
{
     $arrMinTradePrice[] = mysqli_fetch_row($minTradePrice)[0];
     
}
$arrMinTradePrice2 = preg_replace("/[^,.0-9]/", '', $arrMinTradePrice); // убираем из массива не числовые значения

$finalMinTradePrice = max($arrMinTradePrice2); // ЭТО НЕ ПРАВИЛЬНО





// for($i = 0 ; $i < $rows ; ++$i){
//   echo '<li>' . $arrMinTradePrice[i];      // тут я значения массива выводил, это проверка
// }







//====================================================================================
// вывод всякого под таблицей
 
mysqli_close($connection);

echo 'Общее количество товаров на Складе1 и на Складе2: ' . $allStore;
echo '<br>Средняя стоимость розничной цены товара: ' .  mysqli_fetch_row($avgRetailPrice)[0];
echo '<br>Средняя стоимость оптовой цены товара: ' . mysqli_fetch_row($avgTradePrice)[0];
echo '<br>максималка: ' . $finalMaxRetailPrice;
echo '<br>минималочка: ' . max($arrMinTradePrice2);


?>