<html lang="en">
 <head> 
   <title>BinGO BoArd #1</title>
<style>
table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 100%;
}

td, th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
    width: 8em;
    height: 8em;
    text-align: center;
    vertical-align: middle;
}
</style>
<h3>Refresh the page before printing another</h3>
<?php
	function Bingo() {
}
$freespace = "Free Space";
$buzzwords = array(
  "Slams desk",
  "Sabs a student",
  "Mentions mullets",
  "Mentions the Buddha",
  "Sings a Song",
  "Mentions another teacher in the school",
  "Talks about politics",
  "Mentions Steve Jobs",
  "Mentions another class",
  "Calls a student a narcissist",
  "Mentions Thomas Jefferson",
  "Does the Humpty Dance",
  "Mentions Sabatini.guru",
  "Mentions texting while driving",
  "Mentions a sports team",
  "Talks about fishing",
  "Mentions the global economic crisis",
  "Mentions a current blind spot",
  "Makes someone read ancient text",
  "Calls someone out for not paying attention",
  "Mentions Mark Zuckerberg",
  "Picks up his mac book",
  "Mentions the hidden value X ",
  "Says 'Let no one ignorant of geometry enter' ",
  "Mentions reptilian back skin"
);
shuffle($buzzwords);
$bingocard = "<table id='bingo'
summary='A random selection of 25 buzzwords
arranged in a bingo card'>";
$bingocard .= "<thead><tr>";
$bingocard .= "<th>B</th>
      <th>I</th><th>N</th>
      <th>G</th><th>O</th>";
$bingocard .= "</tr></thead>";
$bingocard .= "<tbody>";
$bingocard .= "<tr>";
for($cell=0; $cell<25; $cell++)
  {
    $rowend = ($cell + 1) % 5;
    $bingocard .= "<td>" 
     . $buzzwords[$cell] . "</td>";
    if($rowend == 0 && $cell < 24) {
      $bingocard .= "</tr>n<tr>";
}
  }
$bingocard .= "</tr>";
$bingocard .= "</tbody>";
$bingocard .= "</table>";

echo $bingocard;
?>
</html>
