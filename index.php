<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<link rel="stylesheet" href="style.css">



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
//заполнение и чистка массива с ценой

function cleaningArr($connection, $select)
{
  
  $price = mysqli_query($connection, $select);
  $rows = mysqli_num_rows($price);
  for($i = 0 ; $i < $rows ; ++$i)                                                 //создаем одномерный массив со стоимосью
  {
       $arrPrice[] = mysqli_fetch_row($price)[0];
       
  }
  $numericArrPrice = preg_replace("/[^,.0-9]/", '', $arrPrice);                   // убираем из массива не числовые значения

  $clearArrPrice = array_diff($numericArrPrice, array(''));                       // убрал из массива пустые значения

  return($clearArrPrice);

}


//==============================================================================================
//поиск нужных значений в массиве

$clearArrMaxRetailPrice = cleaningArr($connection, "SELECT `Стоимость, руб` FROM `pricelisttable`");  // поиск самого дорогого товара (по рознице)
$finalMaxRetailPrice = max($clearArrMaxRetailPrice);


$clearArrMinTradePrice = cleaningArr($connection, "SELECT `Стоимость опт, руб` FROM `pricelisttable`");  // поиск самого дешевого товара (по опту)
$finalMinTradePrice = min($clearArrMinTradePrice);



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
              if($j===1 && $row[$j] === $finalMaxRetailPrice)                 //подсвечиваю красным ячейку с максимальной розничной ценой
              {
                echo "<td class='red'>$row[$j]</td>";
              }elseif ($j===2 && $row[$j] == $finalMinTradePrice){            //подсвечиваю зеленым ячейку с минимальной оптовой ценой
                echo "<td class='green'>$row[$j]</td>";
              } else {
                echo "<td >$row[$j]</td>";
              }
              
              if($row[3]<20 || $row[4]<20){                                   //ищу склад, в котором товара осталось меньше 20
                $row[6]="Осталось мало!! Срочно докупите!!!";         
              }
              
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








//====================================================================================
// вывод всякого под таблицей

 
mysqli_close($connection);


echo 'Общее количество товаров на Складе1 и на Складе2: ' . $allStore;
echo '<br>Средняя стоимость розничной цены товара: ' .  mysqli_fetch_row($avgRetailPrice)[0];
echo '<br>Средняя стоимость оптовой цены товара: ' . mysqli_fetch_row($avgTradePrice)[0];

?>


<!-- второе задание-->



<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
</head>
<body>


<div style="background-color: #212529;">ass</div>   <!-- разобраться со стилем -->


<div style="display: flex;">          <!-- разобраться со стилем -->

  <!-- Unnamed (Rectangle) -->
  <div id="u0">
      <p><span>Показать товары, у которых</span></p>
  </div>

  <!-- Unnamed (Droplist) -->
  <div id="u1">
    <select name = "typePrice" class = "typePrice">
      <option value="`Стоимость, руб`">Розничная цена</option>
      <option value="`Стоимость опт, руб`">Оптовая цена</option>
    </select>
  </div>

  <!-- Unnamed (Rectangle) -->
  <div id="u2">
    <p><span>от</span></p>
    
  </div>

  <!-- Unnamed (Text Field) -->
  <div id="u3">
    <input name = "lowPrice" class = "lowPrice" type="text" value="1000">
  </div>

  <!-- Unnamed (Rectangle) -->
  <div id="u4">
    <p><span>до</span></p>
  </div>

  <!-- Unnamed (Text Field) -->
  <div id="u5">
    <input name = "highPrice" class = "highPrice" type="text" value="3000">
  </div>

  <!-- Unnamed (Rectangle) -->
  <div id="u6">
    <p><span>рублей и на складе </span></p>
  </div>

  <!-- Unnamed (Droplist) -->
  <div id="u7">
    <select name = "route" class = "route">
      <option value=">">Более</option>
      <option value="<">Менее</option>
    </select>
  </div>

    <!-- Unnamed (Text Field) -->
    <div id="u8" >
    <input name = "zero" class = "zero" type="text" value="20">
  </div>

  <!-- Unnamed (Rectangle) -->
  <div id="u9">
    <p><span>штук.</span></p>
  </div>



  <!-- Unnamed (Rectangle) -->
  <div id="u10" > 
    <p><button class = "startButton">ПОКАЗАТЬ ТОВАРЫ</button></p>
  </div>

  

</div>
  
<div id="result_table"></div>


  <script>

    $(document).ready(function(){
      $('button.startButton').on('click', function(){
        var typePriceValue = $('select.typePrice').val();
        var lowPriceValue = $('input.lowPrice').val();
        var highPriceValue = $('input.highPrice').val();
        var routeValue = $('select.route').val();
        var zeroValue = $('input.zero').val();

        $.ajax({
          method: "POST",
          url: "filter.php",
          data: { typePrice: typePriceValue, lowPrice: lowPriceValue, highPrice: highPriceValue, route: routeValue, zero: zeroValue }
        })
          .done(function( tableFilter ) {
            // print_r( tableFilter );
            $('#result_table').html( tableFilter );
          });

      })
    });


 

  </script>

</body>
</html>