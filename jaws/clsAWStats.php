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

  class clsAWStats {
    var $sAWStats       = "";
    var $bLoaded        = false;
    var $iYear          = 0;
    var $iMonth         = 0;
    var $dtLastUpdate   = 0;
    var $iTotalVisits   = 0;
    var $iUniqueVisits  = 0;
    var $arrLabel       = array();
    var $arrLogMonths   = array();

    function clsAWStats($sStatName, $sFilePath="", $iYear=0, $iMonth=0) {
      // validate dates
      $dtDate = ValidateDate($iYear, $iMonth);
      $this->iYear = date("Y", $dtDate);
      $this->iMonth = date("n", $dtDate);

      // load data
      $sFilePath .= "awstats";
      if ($this->iMonth < 10) {
        $sFilePath .= "0";
      }
      $sFilePath .= ($this->iMonth . $this->iYear . "." . $sStatName . ".txt");
      if (is_readable($sFilePath)) {
        $this->sAWStats = htmlspecialchars(file_get_contents($sFilePath));
        $this->bLoaded = true;
      }

      // get summary data
      $arrData = $this->GetSection("GENERAL");
      $sLastUpdate = $this->GetSummaryElement($arrData, "lastupdate", 1);
      $this->dtLastUpdate = strtotime($this->GetSummaryElement($arrData, "lastupdate", 1));
      $this->dtLastUpdate = mktime(abs(substr($sLastUpdate, 8, 2)),
                                   abs(substr($sLastUpdate, 10, 2)),
                                   abs(substr($sLastUpdate, 12, 2)),
                                   abs(substr($sLastUpdate, 4, 2)),
                                   abs(substr($sLastUpdate, 6, 2)),
                                   abs(substr($sLastUpdate, 0, 4)));
      $this->iTotalVisits = $this->GetSummaryElement($arrData, "totalvisits", 1);
      $this->iTotalUnique = $this->GetSummaryElement($arrData, "totalunique", 1);

  	  // populate label array
  	  $this->arrLabel["BROWSER"] 		  = array("id", "hits");
  	  $this->arrLabel["DAY"] 			    = array("date", "pages", "hits", "bw", "visits");
  	  $this->arrLabel["DOMAIN"] 		  = array("id", "pages", "hits", "bw");
  	  $this->arrLabel["ERRORS"] 		  = array("id", "hits", "bw");
  	  $this->arrLabel["FILETYPES"] 		= array("id", "hits", "bw", "noncompressedbw", "compressedbw");
  	  $this->arrLabel["KEYWORDS"] 		= array("word", "freq");
  	  $this->arrLabel["OS"] 			    = array("id", "hits");
  	  $this->arrLabel["PAGEREFS"] 		= array("url", "pages", "hits");
  	  $this->arrLabel["ROBOT"] 			  = array("id", "hits", "bw", "lastvisit", "robotstxt");
  	  $this->arrLabel["SEARCHWORDS"] 	= array("phrase", "freq");
  	  $this->arrLabel["SEREFERRALS"] 	= array("id", "pages", "hits");
  	  $this->arrLabel["SESSION"] 		  = array("range", "freq");
  	  $this->arrLabel["SIDER"] 	      = array("url", "pages", "bw", "entry", "exit");
  	  $this->arrLabel["SIDER_404"] 	  = array("url", "hits", "referrer");
  	  $this->arrLabel["TIME"]			    = array("hour", "pages", "hits", "bw", "notviewedpages", "notviewedhits", "notviewedbw");
    }

    function CreateJSON($sSection) {
      echo json_encode($this->GetSection($sSection));
    }

    function CreatePagesXMLString() {
      // produce xml
      $aXML = array();
      $aData = $this->GetSection("SIDER");

      // count totals
      $iTotalPages = 0;
      $iTotalBW = 0;
      $iTotalEntry = 0;
      $iTotalExit = 0;
      for ($iIndexItem = 0; $iIndexItem < count($aData); $iIndexItem++) {
        $aData[$iIndexItem][1] = abs($aData[$iIndexItem][1]);
        $aData[$iIndexItem][2] = abs($aData[$iIndexItem][2]);
        $aData[$iIndexItem][3] = abs($aData[$iIndexItem][3]);
        $aData[$iIndexItem][4] = abs($aData[$iIndexItem][4]);

        $iTotalPages += $aData[$iIndexItem][1];
        $iTotalBW += $aData[$iIndexItem][2];
        $iTotalEntry += $aData[$iIndexItem][3];
        $iTotalExit += $aData[$iIndexItem][4];
      }

      // define size
      $iSize = 50;

      // last update and totals
      $aXML[] = ("<info lastupdate=\"" . $this->dtLastUpdate . "\" />\n" .
                 "<totals pages=\"" . $iTotalPages . "\" bw=\"" . $iTotalBW . "\" entry=\"" .
                 $iTotalEntry . "\" exit=\"" . $iTotalExit . "\" />\n");

      // sort by page views
      usort($aData, "Sort1");
      $aXML[] = "<data_pages>";
      for ($iIndexItem = 0; $iIndexItem < count($aData); $iIndexItem++) {
        $sTemp = "";
        for ($iIndexAttr = 0; $iIndexAttr < count($aData[$iIndexItem]); $iIndexAttr++) {
          $sTemp .= $this->arrLabel["SIDER"][$iIndexAttr] . "=\"" . trim($aData[$iIndexItem][$iIndexAttr]) . "\" ";
        }
        $aXML[] = ("<item " . $sTemp . "/>\n");
        if ($iIndexItem > $iSize) {
          break;
        }
      }
      $aXML[] = "</data_pages>\n";

      // sort by bandwidth
      usort($aData, "Sort2");
      $aXML[] = "<data_bw>";
      for ($iIndexItem = 0; $iIndexItem < count($aData); $iIndexItem++) {
        $sTemp = "";
        for ($iIndexAttr = 0; $iIndexAttr < count($aData[$iIndexItem]); $iIndexAttr++) {
          $sTemp .= $this->arrLabel["SIDER"][$iIndexAttr] . "=\"" . trim($aData[$iIndexItem][$iIndexAttr]) . "\" ";
        }
        $aXML[] = ("<item " . $sTemp . "/>\n");
        if ($iIndexItem > $iSize) {
          break;
        }
      }
      $aXML[] = "</data_bw>\n";

      // sort by bandwidth
      usort($aData, "Sort3");
      $aXML[] = "<data_entry>";
      for ($iIndexItem = 0; $iIndexItem < count($aData); $iIndexItem++) {
        $sTemp = "";
        for ($iIndexAttr = 0; $iIndexAttr < count($aData[$iIndexItem]); $iIndexAttr++) {
          $sTemp .= $this->arrLabel["SIDER"][$iIndexAttr] . "=\"" . trim($aData[$iIndexItem][$iIndexAttr]) . "\" ";
        }
        $aXML[] = ("<item " . $sTemp . "/>\n");
        if ($iIndexItem > $iSize) {
          break;
        }
      }
      $aXML[] = "</data_entry>\n";

      // sort by bandwidth
      usort($aData, "Sort4");
      $aXML[] = "<data_exit>";
      for ($iIndexItem = 0; $iIndexItem < count($aData); $iIndexItem++) {
        $sTemp = "";
        for ($iIndexAttr = 0; $iIndexAttr < count($aData[$iIndexItem]); $iIndexAttr++) {
          $sTemp .= $this->arrLabel["SIDER"][$iIndexAttr] . "=\"" . trim($aData[$iIndexItem][$iIndexAttr]) . "\" ";
        }
        $aXML[] = ("<item " . $sTemp . "/>\n");
        if ($iIndexItem > $iSize) {
          break;
        }
      }
      $aXML[] = "</data_exit>\n";

      // return
      return implode($aXML, "");
    }

    function CreateXMLString($sSection) {
      // produce xml
      $aXML = array();
      $arrData = $this->GetSection($sSection);
      $aXML[] = "<info lastupdate=\"" . $this->dtLastUpdate . "\" />\n<data>\n";
      for ($iIndexItem = 0; $iIndexItem < count($arrData); $iIndexItem++) {
        $sTemp = "";
        for ($iIndexAttr = 0; $iIndexAttr < count($arrData[$iIndexItem]); $iIndexAttr++) {
          $sTemp .= $this->arrLabel[$sSection][$iIndexAttr] . "=\"" . trim($arrData[$iIndexItem][$iIndexAttr]) . "\" ";
        }
        $aXML[] = ("<item " . $sTemp . "/>\n");
      }
      $aXML[] = "</data>\n";
      return implode("", $aXML);
    }

    function GetSection($sSection) {
    	$iStartPos = strpos($this->sAWStats, ("\nBEGIN_" . $sSection . " "));
    	$iEndPos = strpos($this->sAWStats, ("\nEND_" . $sSection), $iStartPos);
    	$arrStat = explode("\n", substr($this->sAWStats, ($iStartPos + 1), ($iEndPos - $iStartPos - 1)));
  		for ($iIndex = 1; $iIndex < count($arrStat); $iIndex++) {
  			$arrData[] = split(' ', $arrStat[$iIndex]);
  		}
  		return $arrData;
    }

    function GetSummaryElement($arrData, $sLabel, $iElementID) {
      for ($iIndex = 1; $iIndex < count($arrData); $iIndex++) {
        if (strtolower($arrData[$iIndex][0]) == $sLabel) {
          return $arrData[$iIndex][$iElementID];
        }
      }
    }

    function OutputXML($sXML) {
      header("content-type: text/xml");
      echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n" .
           "<jawstats>\n" . $sXML . "</jawstats>";
    }
  }

  function ElapsedTime($iSeconds) {
    if ($GLOBALS["g_iThisLog"] == 0) {
      if ($iSeconds < 60) {
        return (" (<1 min ago)");
      }
      $iMinutes = floor($iSeconds / 60);
      if ($iMinutes < 60) {
        if ($iMinutes == 1) {
          return (" (" . $iMinutes . " min ago)");
        } else {
          return (" (" . $iMinutes . " mins ago)");
        }
      }
      $iHours = floor($iMinutes / 60);
      if ($iHours < 24) {
        $iMinutes = ($iMinutes - ($iHours * 60));
        return (" (" . $iHours . "h " . $iMinutes . "m ago)");
      }
      $iDays = floor($iHours / 24);
      if ($iDays > 1) {
        return (" (" . $iDays . " days ago)");
      } else {
        return (" (" . $iDays . " day ago)");
      }
    }
  }

  function GetConfig() {
    // check config(s) exists
    if (count($GLOBALS["aConfig"]) < 1) {
      Error("BadConfigNoSites");
    }

    // check this site config exists
    if ((isset($_GET["config"]) == true) && (isset($GLOBALS["aConfig"][$_GET["config"]]) == true)) {
      $sConfig = $_GET["config"];
    } else {
      $sConfig = key($GLOBALS["aConfig"]);
    }

    // validate settings
    if ((isset($GLOBALS["aConfig"][$sConfig]["staticxml"]) != true) || (is_bool($GLOBALS["aConfig"][$sConfig]["staticxml"]) != true)) {
      $GLOBALS["aConfig"][$sConfig]["staticxml"] = false;
    }

    // return
    return $sConfig;
  }

  function GetLogList($sStatsName, $sFilePath) {
    if ($oDir = opendir($sFilePath)) {
      // load available dates into array and sort by date
      while (($oItem = readdir($oDir)) !==  false) {
        if ((substr($oItem, 0, 7) == "awstats") &&
            (substr($oItem, 14) == ($sStatsName . ".txt"))) {
          $aTemp[] = mktime(0, 0, 0, intval(substr($oItem, 7, 2)), 1, intval(substr($oItem, 9, 4)));
        }
      }
      if (count($aTemp) < 1) {
        Error("NoLogsFound", $GLOBALS["g_sConfig"]);
      }
      if (count($aTemp) > 1) {
        rsort($aTemp);
      }

      // find first & last dates
      $dtLatest = $aTemp[0];
      $dtEarliest = $aTemp[count($aTemp) - 1];

      // create full array of all potential dates
      $aMonths = array();
      $dtLoop = $dtLatest;
      while ($dtLoop >= $dtEarliest) {
        $bFound = false;
        for ($iIndex = 0; $iIndex < count($aTemp); $iIndex++) {
          if ($aTemp[$iIndex] == $dtLoop) {
            $bFound = true;
            array_splice($aTemp, $iIndex, 1);
            break;
          }
        }
        array_push($aMonths, array($dtLoop, $bFound));
        $dtLoop = mktime(0, 0, 0, (date("n", $dtLoop) - 1), 1, date("Y", $dtLoop));
      }
    } else {
      Error("CannotOpenLog");
    }
    return $aMonths;
  }

  function Sort1($a, $b) {
    if ($a[1] == $b[1]) { return 0; }
    return ($a[1] > $b[1]) ? -1 : 1;
  }

  function Sort2($a, $b) {
    if ($a[2] == $b[2]) { return 0; }
    return ($a[2] > $b[2]) ? -1 : 1;
  }

  function Sort3($a, $b) {
    if ($a[3] == $b[3]) { return 0; }
    return ($a[3] > $b[3]) ? -1 : 1;
  }

  function Sort4($a, $b) {
    if ($a[4] == $b[4]) { return 0; }
    return ($a[4] > $b[4]) ? -1 : 1;
  }

  function ValidateConfig() {
    // core values
    if (ValidateView($GLOBALS["sConfigDefaultView"]) != true) {
      Error("BadConfig", "sConfigDefaultView");
    }
    if (is_bool($GLOBALS["bConfigChangeSites"]) != true) {
      Error("BadConfig", "bConfigChangeSites");
    }
    if (is_bool($GLOBALS["bConfigUpdateSites"]) != true) {
      Error("BadConfig", "bConfigUpdateSites");
    }
  }

  function ValidateDate($iYear, $iMonth) {
    $iYear = intval($iYear);
    $iMonth = intval($iMonth);
    if (($iYear < 2000) || ($iYear > date("Y"))) {
      $iYear = intval(date("Y"));
    }
    if (($iMonth < 1) || ($iMonth > 12)) {
      $iMonth = intval(date("n"));
    }
    return mktime(0, 0, 0, $iMonth, 1, $iYear);
  }

  function ValidateView($sView) {
    $bValid = false;
    switch ($sView) {
      case "allmonths.all":
      case "browser.all":
      case "browser.family":
      case "country.Africa":
      case "country.all":
      case "country.Asia":
      case "country.continent":
      case "country.Europe":
      case "country.North America":
      case "country.Oceania":
      case "country.Other":
      case "country.South America":
      case "filetypes":
      case "keyphrases.all":
      case "keyphrases.point1":
      case "keyphrases.top10":
      case "keywords.all":
      case "keywords.point1":
      case "keywords.top10":
      case "os.all":
      case "os.family":
      case "pagerefs.all":
      case "pagerefs.domains":
      case "pagerefs.se":
      case "pagerefs.top10":
      case "pagerefs.top50":
      case "pages.topPages":
      case "pages.topBW":
      case "pages.topEntry":
      case "pages.topExit":
      case "robots":
      case "session":
      case "status":
      case "status.404":
      case "thismonth.all";
      case "thismonth.bandwidth":
      case "thismonth.hits":
      case "thismonth.pages":
      case "thismonth.visits":
      case "time";
        $bValid = true;
        break;
    }
    return $bValid;
  }



?>