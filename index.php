<?
 $lsPage = "home";
 if (isset($_GET["page"])) { $lsPage = $_GET["page"]; }
 $loQueue = msg_get_queue('12345');
 $liQueueLength = getQueueSize($loQueue);
 $lsPlural = "s";
 if ($liQueueLength == 1) { $lsPlural = ""; }
 function getQueueSize ($poQueue) {
  $laStatus = msg_stat_queue($poQueue);
  return $laStatus["msg_qnum"];
 }
 $liMaxQueueLength = 30;
?>
<html>
 <head>
  <title>PathogenSeq: TBDR</title>
  <link type='image/png' rel='icon' href='/favicon.png'/>
  <link rel="stylesheet" href="tbdr.css" type="text/css" />
 </head>
 <body>
  <img src='https://intra.lshtm.ac.uk/assets/display_images/logo.png' style='float: right; padding: 5px'/>
  <div class='title'><a href='/'>TB Profiler</a></div>
  <div class='text'>
   This tool processes raw sequence data to infer strain type and identify known drug resistance markers.
  </div>
<? include "$lsPage.php"; ?>
  <hr/>
  <div class='subtitle'>Disclaimer</div>
  <div class='text'>
   This tool is for <b>Research Use Only</b> and is offered free for use.
   The London School of Hygiene and Tropical Medicine shall have no liability for any loss or damages of any kind, however sustained relating to the use of this tool.
  </div>
  <div class='text'>
    &copy; <?= date("Y"); ?> London School of Hygiene and Tropical Medicine; <a href='mailto:mark.preston@lshtm.ac.uk'>webmaster</a>
  </div>
 </body>
</html>
