<?php

$data = [
    "typePrice" => $_POST['typePrice'],
    "lowPrice" => $_POST['lowPrice'],
    "highPrice" => $_POST['highPrice'],
    "route" => $_POST['route'],
    "zero" => $_POST['zero']
];
echo $data[typePrice];
print_r($data);
// Соединение с базой MySQL
$connection = mysqli_connect('127.0.0.1', 'root', 'root', 'mydatabasetest1');
// Выбираем кодировку UTF-8
$connection->set_charset("utf8");
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

$clearArrMaxRetailPrice = cleaningArr($connection, "SELECT `Стоимость, руб` FROM `pricelisttable` WHERE $data[typePrice] > $data[lowPrice] AND $data[typePrice] < $data[highPrice] AND (`Наличие на складе 1, шт` $data[route] $data[zero] OR `Наличие на складе 2, шт` $data[route] $data[zero])");  // поиск самого дорогого товара (по рознице)
$finalMaxRetailPrice = max($clearArrMaxRetailPrice);


$clearArrMinTradePrice = cleaningArr($connection, "SELECT `Стоимость опт, руб` FROM `pricelisttable` WHERE $data[typePrice] > $data[lowPrice] AND $data[typePrice] < $data[highPrice] AND (`Наличие на складе 1, шт` $data[route] $data[zero] OR `Наличие на складе 2, шт` $data[route] $data[zero])");  // поиск самого дешевого товара (по опту)
$finalMinTradePrice = min($clearArrMinTradePrice);



//==============================================================================================
// вывод данных из базы на страницу


$query2 = "SELECT * FROM `pricelisttable` WHERE $data[typePrice] > $data[lowPrice] AND $data[typePrice] < $data[highPrice] AND (`Наличие на складе 1, шт` $data[route] $data[zero] OR `Наличие на складе 2, шт` $data[route] $data[zero])"; // запрос для вывода всех данных таблицы на страницу

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

?>