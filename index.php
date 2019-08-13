<html>
<head>
<link rel="stylesheet" href="https://unpkg.com/purecss@1.0.1/build/pure-min.css" integrity="sha384-oAOxQR6DkCoMliIh8yFnu25d7Eq/PHS21PClpwjOTeU2jRSq11vu66rf90/cZr47" crossorigin="anonymous">
   </head>
<body background="./assets/bg_vichy.png">
   <div class="pure-g" style="margin: 40px 0px 0px 0px"><div class="pure-u-1-5"></div><div class="pure-u-3-5" style="padding: 30px 50px 50px 50px; background-image: url('./assets/bg_whitewall.png')">
   <center><h1>Trademark Multifetch</h1></center>
   <p><b>Why would I use this?</b> If you want to quickly review some <a href="http://tmsearch.uspto.gov">TESS</a> search results and are frustrated that they do not include a "class" or "goods & services" column. 
   <p><b>How do I use this?</b> In the box below, provide multiple USPTO trademark serial nubmers separated by a space, newlines, or commas. This page fetches data from <a href="https://www.uspto.gov/trademark/trademark-updates-and-announcements/deployment-trademark-status-and-document-retrieval">TSDRAPI</a>  and gives you a table with those marks and class, registration, and goods & services. Multi-class registrations show up on multiple lines, with one class per line. The query limit is 200 serial numbers per sumission.</p> <b>Example use</b>. One way to use this service, the one it was created for, is to be able to quickly scan the results of a trademark search on TESS with the goods and services listed (on TESS you have to click each one; on TSDR multi-search only partial G&S info is shown). To do this, copy the table of search results from TESS into an Excel spreadsheet (use "paste as text"), copy the serial number column and paste it here, and click submit. You will have to wait a few seconds for results to be retrieved. The results generated are a table that can be pasted into an Excel spreadsheet for review of the marks in association with their goods and services. 
<p><b>This is beta-quality software.</b> I'm just one guy that wrote this because I wanted it. What it is doing is fairly straightforward, just downloading information from the USPTO website; but it has not been extensively tested or vetted as would be the case if it were commerical software. Results are cached and so may not be entirely up-to-date with the USPTO database. Please use accordingly, and note that this service is provided AS-IS and with no warranty, and otherwise according to the <a href="./tmmf_tos.html">terms of service</a>. If you have comments, suggestions, or encounter errors, either <a href="https://morris.cloud/contact/">contact me</a> or open an issue <a href="https://github.com/xenotropic/uspto-multifetch/issues">on github</a>. 
<p>Serial numbers, separated by space, newline, or commas:

<form method=post action="./index.php">
   <textarea name="serials" cols=50 rows=8></textarea>
   <p>
   <input name="tos" type="checkbox" value="agreed"> I agree to the <a href="./tmmf_tos.html">terms of service</a> and <a href="tmmf_pp.html">privacy policy</a>. 
   <input type="submit" value="submit"></form>
   </form>

<?php 
$serials = $_POST['serials'];
if ( $serials != "" ) {
  if ( $_POST['tos'] != "agreed" ) {
    echo "<b>You need to agree to the Terms of Service.</b>";
    exit;
  }
  $serial_array =  preg_split("/(\r\n|\n|\r| |,)/",$serials);
  if ( count ( $serial_array) > 200 ) {
    echo "Query limit is 200 per submission."; exit;
  }
  require_once 'TsdrApi.php';
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

</div><div class="pure-u-1-5">
</div></div>   
<center><small><a href="https://github.com/xenotropic/uspto-multifetch">source code</a></center></small>
  </body>
  </html>