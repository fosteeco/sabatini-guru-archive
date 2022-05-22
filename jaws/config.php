<?php

  // core config parameters
  $sConfigDefaultView    = "thismonth.all";
  $bConfigChangeSites    = true;
  $bConfigUpdateSites    = true;
  $sUpdateSiteFilename   = "xml_update.php";

  // individual site configuration
  $aConfig["sabatini.guru"] = array(
    "statspath"   => "/var/lib/awstats/",
    "updatepath"  => "/usr/lib/cgi-bin/",
    "siteurl"     => "https://sabatini.guru",
    "theme"       => "default",
    "fadespeed"   => 250,
    "password"    => "fart123",
    "includes"    => ""
  );
?>
