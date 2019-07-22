<html>
<head>
</head>
<body>

<p>

List multiple USPTO trademark serial nubmers separated by a space.
<form method=post action="./index.php">
   <textarea name="serials" cols=80 rows=10></textarea>
    <input type="submit" value="submit"></form>
   </form>

   
<?php 
$serials = $_POST['serials'];
if ( $serials != "" ) {
  require_once 'TsdrApi.php';
  $serial_array =  preg_split("/(\r\n|\n|\r| |,)/",$serials);
  $api = new TsdrApi();
  echo "<table border=1><tr><td>Serial</td><td>Reg No</td><td>Reg Date</td><td>Mark Literal Elements</td><td>Class</td><td>Goods and Services</td></tr>";
  foreach  ( $serial_array as $serial ) {
    $data = $api->getTrademarkData ($serial, $api::SERIAL_NUMBER);
    // var_dump ($data); echo "<p>";  // uncomment to see dump showing available fields
    foreach ($data["ClassNumber"] as $key => $class ) {
      //  if ( $key == 0 ) {  // key is zero when we are on first class of application
      echo "<tr><td>$serial</td><td>" . $data["RegistrationNumber"] . "</td> ";
      echo "<td>" . $data["RegistrationDate"] . "</td>";
      echo "<td>". $data["MarkLiteralElements"]."</td>";
      //   } else echo "<tr><td>$serial</td><td>------></td><td>--></td><td>------></td>";  // this prints when on subsequent classes of application
      echo "<td>$class</td><td>" . $data["ClassDescription"][$key] . "</td>";
      echo "</tr>";
    }
  }
  echo "</table>";
}


?>