<?php

/*
 * JAWStats 0.6 Web Statistics
 *
 * Copyright (c) 2008 Jon Combe (jawstats.com)
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

  error_reporting(0);
  set_error_handler("ErrorHandler");

  // javascript caching
  $gc_sJavascriptVersion = "200808022050";

	// includes
	require_once "clsAWStats.php";
  require_once "config.php";
  ValidateConfig();

  // select configuraton
  $g_sConfig = GetConfig();
  $g_aConfig = $aConfig[$g_sConfig];

  // external include files
  if ((isset($g_aConfig["includes"]) == true) && (strlen($g_aConfig["includes"]) > 0)) {
    $aIncludes = explode(",", $g_aConfig["includes"]);
    foreach ($aIncludes as $sInclude) {
      include $sInclude;
    }
  }

  // get date range and valid log file
  $g_dtStatsMonth = ValidateDate($_GET["year"], $_GET["month"]);
  $g_aLogFiles = GetLogList($g_sConfig, $g_aConfig["statspath"]);
  $g_iThisLog = -1;
  for ($iIndex = 0; $iIndex < count($g_aLogFiles); $iIndex++) {
    if (($g_dtStatsMonth == $g_aLogFiles[$iIndex][0]) && ($g_aLogFiles[$iIndex][1] == true)) {
      $g_iThisLog = $iIndex;
      break;
    }
  }
  if ($g_iThisLog < 0) {
    if (count($g_aLogFiles) > 0) {
      $g_iThisLog = 0;
    } else {
      Error("NoLogsFound");
    }
  }

  // create change month picker
  $sChangeMonth = "<table width='100%'><tbody>" .
                  "<tr><td colspan='11'><h1 class='modal'>Pick the month you wish to view<\/h1><\/td><td colspan='2' onclick='$.unblockUI();' class='fauxlink right'>Cancel<\/td><\/tr>";
  for ($iYear = date("Y", $g_aLogFiles[0][0]); $iYear >= date("Y", $g_aLogFiles[count($g_aLogFiles) - 1][0]); $iYear--) {
    $sChangeMonth .= "<tr><td class='datepickerYear'>" . $iYear . ":<\/td>";
    for ($iMonth = 1; $iMonth < 13; $iMonth++) {
      $dtTemp = mktime(0, 0, 0, $iMonth, 1, $iYear);
      $bExists = false;
      foreach ($g_aLogFiles as $aLog) {
        if (($aLog[0] == $dtTemp) && ($aLog[1] == true)) {
          $bExists = true;
          break;
        }
      }
      if ($bExists == true) {
        $sAdditionalClasses = "";
        if ((date("n", $g_aLogFiles[0][0]) == $iMonth) && (date("Y", $g_aLogFiles[0][0]) == $iYear)) {
          $sAdditionalClasses = " datepickerThisMonth";
        }
        if ((date("n", $g_aLogFiles[$g_iThisLog][0]) == $iMonth) && (date("Y", $g_aLogFiles[$g_iThisLog][0]) == $iYear)) {
          $sAdditionalClasses .= " datepickerSelectedMonth";
        }
        $sChangeMonth .= "<td class='datepicker" . $sAdditionalClasses . "' onclick='ChangeMonth(" . date("Y,n", $dtTemp) . ")'>" . substr(date("F", $dtTemp), 0, 3) . "<\/td>";
      } else {
        $sChangeMonth .= "<td class='datepickerGrey'>" . substr(date("F", $dtTemp), 0, 3) . "<\/td>";
      }
    }
    $sChangeMonth .= "<\/tr>";
  }
  $sChangeMonth .= "<\/tbody><\/table>";

  // create change site picker
  if (($bConfigChangeSites == true) && (count($aConfig) > 1)) {
    $sChangeSite = "<div id='changesitecontainer'><table width='100%'><tbody>" .
                   "<tr><td><h1 class='modal'>Pick the site you wish to view<\/h1><\/td><td onclick='$.unblockUI();' class='fauxlink right'>Cancel<\/td><\/tr>";
    foreach ($aConfig as $sSiteCode => $aSite) {
      $sChangeSite .= "<tr><td><a href='?config=" . $sSiteCode . "'>" . $aSite["siteurl"] . "<\/a><\/td><\/tr>";
    }
    $sChangeSite .= "<\/tbody><\/table><\/div>";
  } else {
    $sChangeSite = "";
  }

  // validate current view
  if (ValidateView($_GET["view"]) == true) {
    $sCurrentView = $_GET["view"];
  } else {
    $sCurrentView = $sConfigDefaultView;
  }

  // create class
  $clsAWStats = new clsAWStats($g_sConfig,
                               $g_aConfig["statspath"],
                               date("Y", $g_aLogFiles[$g_iThisLog][0]),
                               date("n", $g_aLogFiles[$g_iThisLog][0]));
  if ($clsAWStats->bLoaded != true) {
    Error("CannotOpenLog");
  }

  // days in month
  if (($clsAWStats->iYear == date("Y")) && ($clsAWStats->iMonth == date("n"))) {
    $iDaysInMonth = abs(date("s", $clsAWStats->dtLastUpdate));
    $iDaysInMonth += (abs(date("i", $clsAWStats->dtLastUpdate)) * 60);
    $iDaysInMonth += (abs(date("H", $clsAWStats->dtLastUpdate)) * 60 * 60);
    $iDaysInMonth = abs(date("j", $clsAWStats->dtLastUpdate) - 1) + ($iDaysInMonth / (60 * 60 * 24));
  } else {
    $iDaysInMonth = date("d", mktime (0, 0, 0, date("n", $clsAWStats->dtLastUpdate), 0, date("Y", $clsAWStats->dtLastUpdate)));
  }

  // start of the month
  $dtStartOfMonth = mktime(0, 0, 0, $clsAWStats->iMonth, 1, $clsAWStats->iYear);
  $iDailyVisitAvg = ($clsAWStats->iTotalVisits / $iDaysInMonth);
  $iDailyUniqueAvg = ($clsAWStats->iTotalUnique / $iDaysInMonth);

  // output booleans for javascript
  function BooleanToText($bValue) {
    if ($bValue == true) {
      return "true";
    } else {
      return "false";
    }
  }

  // error display
  function Error($sReason, $sExtra="") {
    // echo "ERROR!<br />" . $sReason;
    switch ($sReason) {
      case "BadConfig":
        $sProblem     = "There is an error in <i>config.php</i>";
        $sResolution  = "<p>The variable <i>" . $sExtra . "</i> is missing or invalid.</p>";
        break;
      case "BadConfigNoSites":
        $sProblem     = "There is an error in <i>config.php</i>";
        $sResolution  = "<p>No individual AWStats configurations have been defined.</p>";
        break;
      case "CannotLoadClass":
        $sProblem     = "Cannot find required JAWStats file: \"clsAWStats.php\"";
        $sResolution  = "<p>At least one file required by JAWStats has been deleted, renamed or corrupted.</p>";
        break;
      case "CannotLoadConfig":
        $sProblem     = "Cannot find configuration file: \"config.php\"";
        $sResolution  = "<p>JAWStats cannot find it's configuration file, <i>config.php</i>.<br />\n" .
                        "Did you successfully copy and rename the <i>config.dist.php</i> file?</p>";
        break;
      case "CannotOpenLog":
        $sStatsPath = $GLOBALS["aConfig"][$GLOBALS["g_sConfig"]]["statspath"];
        $sProblem     = "JAWStats could not open an AWStats log file";
        $sResolution  = "<p>Is the specified AWStats log file directory correct? Does it have a trailing slash?<br />" .
                        "The problem is in a setting called <strong>\"statspath\"</strong> in your <i>config.php</i> file.</p>\n" .
                        "<p>The file being looked for is: <strong>awstats" . date("Yn") . "." . $GLOBALS["g_sConfig"] . ".txt</strong><br />" .
                        "The folder being looked in is: <strong>" . $sStatsPath . "</strong>\n";
        if (substr($sStatsPath, -1) != "/") {
          $sResolution  .= "<br />Try changing the folder to: <strong>" . $sStatsPath . "/</strong>";
        }
        $sResolution  .= "</p>";
        break;
      case "NoLogsFound":
        $sStatsPath = $GLOBALS["aConfig"][$GLOBALS["g_sConfig"]]["statspath"];
        $sProblem     = "No AWStats Log Files Found";
        $sResolution  = "<p>JAWStats cannot find any AWStats log files in the specified directory: <strong>" . $sStatsPath . "</strong><br />" .
                        "Is this the correct folder? Is your config name, <i>" . $GLOBALS["g_sConfig"] . "</i>, correct?</p>\n";
        break;
    }
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" " .
         "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n" .
         "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n" .
         "<head>\n" .
         "<title>JAWStats</title>\n" .
         "<style type=\"text/css\">\n" .
         "html, body { background: #33332d; border: 0; color: #eee; font-family: arial, helvetica, sans-serif; font-size: 15px; margin: 20px; padding: 0; }\n" .
         "a { color: #9fb4cc; text-decoration: none; }\n" .
         "a:hover { color: #fff; text-decoration: underline; }\n" .
         "h1 { border-bottom: 1px solid #cccc9f; color: #eee; font-size: 22px; font-weight: normal; } \n" .
         "h1 span { color: #cccc9f !important; font-size: 16px; } \n" .
         "p { margin: 20px 30px; }\n" .
         "</style>\n" .
         "</head>\n<body>\n" .
         "<h1><span>An error has occured:</span><br />" . $sProblem . "</h1>\n" . $sResolution .
         "<p>Please refer to the <a href=\"http://www.jawstats.com/manual/\">installation instructions</a> for more information.</p>\n" .
         "</body>\n</html>";
    exit;
  }

  // error handler
  function ErrorHandler ($errno, $errstr, $errfile, $errline, $errcontext) {
    switch ($errline) {
      case 7:
        Error("CannotLoadClass");
        break;
      case 8:
        Error("CannotLoadConfig");
        break;
    }
  }

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
  <title><?=date("F Y", $g_aLogFiles[$g_iThisLog][0])?> stats for <?=$g_aConfig["siteurl"]?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="stylesheet" href="themes/<?=$g_aConfig["theme"]?>/style.css" type="text/css" />
  <script type="text/javascript" src="js/packed.js?<?=$gc_sJavascriptVersion?>"></script>

  <!--
  <script type="text/javascript" src="js/jquery.js"></script>
  <script type="text/javascript" src="js/jquery.blockUI.js"></script>
  <script type="text/javascript" src="js/jquery.tablesorter.js"></script>
  <script type="text/javascript" src="js/swfobject.js"></script>
  -->

  <script type="text/javascript" src="js/constants.js?<?=$gc_sJavascriptVersion?>"></script>
  <script type="text/javascript" src="js/jawstats.js?<?=$gc_sJavascriptVersion?>"></script>
  <script type="text/javascript">
    var g_sConfig = "<?=$g_sConfig?>";
    var g_iYear = <?=date("Y", $g_aLogFiles[$g_iThisLog][0])?>;
    var g_iMonth = <?=date("n", $g_aLogFiles[$g_iThisLog][0])?>;
    var g_sCurrentView = "<?=$sCurrentView?>";
    var g_dtLastUpdate = <?=$clsAWStats->dtLastUpdate?>;
    var g_iFadeSpeed = <?=$g_aConfig["fadespeed"]?>;
    var g_bUseStaticXML = <?=BooleanToText($g_aConfig["staticxml"])?>;
    var sChangeMonth = "<?=$sChangeMonth?>";
    var sChangeSite  = "<?=$sChangeSite?>";
    var sUpdateFilename = "<?=$sUpdateSiteFilename?>";
    var sThemeDir = "<?=$g_aConfig["theme"]?>";
  </script>
  <script type="text/javascript" src="themes/<?=$g_aConfig["theme"]?>/style.js?<?=$gc_sJavascriptVersion?>"></script>
  <script type="text/javascript" src="http://version.jawstats.com/version.js"></script>
</head>

<body>
  <div id="header">
    <div class="container">
      <h1>
      <span>Statistics for </span><?=$g_aConfig["siteurl"]?><a href="<?=$g_aConfig["siteurl"]?>" target="_blank"><img src="themes/<?=$g_aConfig["theme"]?>/images/external_link.png" class="externallink" /></a>
        <span> in </span><?=date("F Y", $g_aLogFiles[$g_iThisLog][0])?>
<?php
  // prev / first month links
  if ($g_iThisLog < (count($g_aLogFiles) - 1)) {
    echo " <img src=\"themes/" . $g_aConfig["theme"] . "/changemonth/first.gif\" onmouseover=\"this.src='themes/" . $g_aConfig["theme"] . "/changemonth/first_on.gif'\" onmouseout=\"this.src='themes/" . $g_aConfig["theme"] . "/changemonth/first.gif'\" class=\"changemonth\" onclick=\"ChangeMonth(" . date("Y,n", $g_aLogFiles[count($g_aLogFiles) - 1][0]) . ")\" />" .
         "<img src=\"themes/" . $g_aConfig["theme"] . "/changemonth/prev.gif\" onmouseover=\"this.src='themes/" . $g_aConfig["theme"] . "/changemonth/prev_on.gif'\" onmouseout=\"this.src='themes/" . $g_aConfig["theme"] . "/changemonth/prev.gif'\" class=\"changemonth\" onclick=\"ChangeMonth(" . date("Y,n", $g_aLogFiles[$g_iThisLog + 1][0]) . ")\" /> ";
  } else {
    echo " <img src=\"themes/" . $g_aConfig["theme"] . "/changemonth/first_off.gif\" class=\"changemonthOff\" />" .
         "<img src=\"themes/" . $g_aConfig["theme"] . "/changemonth/prev_off.gif\" class=\"changemonthOff\" /> ";
  }
?>
        <span id="changemonth" class="change">change month</span>
<?php
  // next / last month links
  if ($g_iThisLog > 0) {
    echo " <img src=\"themes/" . $g_aConfig["theme"] . "/changemonth/next.gif\" onmouseover=\"this.src='themes/" . $g_aConfig["theme"] . "/changemonth/next_on.gif'\" onmouseout=\"this.src='themes/" . $g_aConfig["theme"] . "/changemonth/next.gif'\" class=\"changemonth\" onclick=\"ChangeMonth(" . date("Y,n", $g_aLogFiles[$g_iThisLog - 1][0]) . ")\" />" .
         "<img src=\"themes/" . $g_aConfig["theme"] . "/changemonth/last.gif\" onmouseover=\"this.src='themes/" . $g_aConfig["theme"] . "/changemonth/last_on.gif'\" onmouseout=\"this.src='themes/" . $g_aConfig["theme"] . "/changemonth/last.gif'\" class=\"changemonth\" onclick=\"ChangeMonth(" . date("Y,n", $g_aLogFiles[0][0]) . ")\" /> ";
  } else {
    echo " <img src=\"themes/" . $g_aConfig["theme"] . "/changemonth/next_off.gif\" class=\"changemonthOff\" />" .
         "<img src=\"themes/" . $g_aConfig["theme"] . "/changemonth/last_off.gif\" class=\"changemonthOff\" /> ";
  }

  // change site link
  if (($bConfigChangeSites == true) && (count($aConfig) > 1)) {
    echo "<span class=\"changedivider\">|</span> " .
         "<span id=\"changesite\" class=\"change\">change site</span> ";
  }

  // update stats link
  if ($bConfigUpdateSites == true) {
    echo "<span class=\"changedivider\">|</span> " .
         "<span id=\"updatesite\" class=\"change\">update site</span> ";
  }
?>
      </h1>
      <div id="summary">Stats last updated:
        <span><?=date("l, jS F Y", $clsAWStats->dtLastUpdate)?> at <?=date("H:i", $clsAWStats->dtLastUpdate)?></span><?=ElapsedTime(time() - $clsAWStats->dtLastUpdate)?>.
        A total of <span><?=number_format($clsAWStats->iTotalVisits)?></span> visitors
        (<?=number_format($clsAWStats->iTotalUnique)?> unique) this month, an average of <span><?=number_format($iDailyVisitAvg, 1)?></span> per day (<?=number_format($iDailyUniqueAvg, 1)?> unique).
      </div>
      <div id="menu">
        <ul>
          <li id="tabthismonth"><span onclick="ChangeTab(this, 'thismonth.all')">This Month</span></li>
          <li id="taballmonths"><span onclick="ChangeTab(this, 'allmonths.all')">All Months</span></li>
          <li id="tabtime" style="margin-right: 5px;"><span onclick="ChangeTab(this, 'time')">Hours</span></li>
          <li id="tabbrowser"><span onclick="ChangeTab(this, 'browser.family')">Browsers</span></li>
          <li id="tabcountry"><span onclick="ChangeTab(this, 'country.all')">Countries</span></li>
          <li id="tabfiletypes"><span onclick="ChangeTab(this, 'filetypes')">Filetypes</span></li>
          <li id="tabkeyphrases"><span onclick="ChangeTab(this, 'keyphrases.top10')">Keyphrases</span></li>
          <li id="tabkeywords"><span onclick="ChangeTab(this, 'keywords.top10')">Keywords</span></li>
          <li id="tabpages"><span onclick="ChangeTab(this, 'pages.topPages')">Pages</span></li>
          <li id="tabos"><span onclick="ChangeTab(this, 'os.family')">Operating Systems</span></li>
          <li id="tabpagerefs"><span onclick="ChangeTab(this, 'pagerefs.se')">Referrers</span></li>
          <li id="tabrobots"><span onclick="ChangeTab(this, 'robots')">Spiders</span></li>
          <li id="tabsession"><span onclick="ChangeTab(this, 'session')">Session</span></li>
          <li id="tabstatus"><span onclick="ChangeTab(this, 'status')">Status</span></li>
        </ul>
      </div>
      <br style="clear: both" />
      <div id="loading">&nbsp;</div>
    </div>
  </div>
  <div id="main">
    <div class="container">
      <div id="content">&nbsp;</div>
      <div id="footer">
      <span>Powered by</span> <a href="http://www.awstats.org/" target="_blank">AWStats</a><span>.
      Made beautiful by</span> <a href="http://www.jawstats.com/" target="_blank">JAWStats Web Statistics &amp; Analytics</a>.
      <span id="version">&nbsp;</span>
    </div>
  </div>
  </div>
</body>

</html>

