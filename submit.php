<?
  $liMaxQueueLength = 30;
  //  ----------------------------------
  //  Check the queue size.
  function checkQueueSize($poQueue) {
    $riStatus = 0;
    if (getQueueSize($poQueue) >= 30) {
      $riStatus = 2;
    }
    return $riStatus;
  }

  //  Clear the error status.
  $liStatus = 0;
  //  Get the queue.
  $loQueue = msg_get_queue('12345');
  //  Get random id
  $lsId = uniqid();
  //  Check the queue size.
  $liStatus = checkQueueSize($loQueue);
  if (! array_key_exists("name",$_POST) && ! array_key_exists("fastq",$_FILES)) {
    $liStatus = 3;
  }
  if (0 == $liStatus) {
    //  Get the name or use the id.
    $lsName = "";
    if ($lsName == "") { $lsName = $lsId; }
    //  Check and move *all* uploaded files to the input area.
    $i = 0;
    foreach ($_FILES["fastq"]["error"] as $lsKey => $liError) {
      //  Get the temporary uploaded file name.
      $lsTemp = $_FILES["fastq"]["tmp_name"][$lsKey];
      //  Check the upload, file size and that there are not more than 2 entries then move to the processing directory.
      if ($liError == UPLOAD_ERR_OK && $_FILES["fastq"]["size"][$lsKey] > 0 && $i <= 2) {
        $i++;
        move_uploaded_file($lsTemp,"/srv/www/htdocs/input/$lsId.$i.fastq.gz");
      //  Error!
      } else if ($liError == UPLOAD_ERR_NO_FILE) {
      } else {
        $liStatus = 3;
      }
    }
    //  Check number of uploads and delete any stale input files.
    if ($i == 0 || $i > 2) {
      $liStatus = 4;
    }
  }
  if (0 == $liStatus) {
    $liStatus = checkQueueSize($loQueue);
  }
  if (0 == $liStatus) {
    //  Submit to the queue.
    $loMessage = new stdclass;
    $loMessage->id = $lsId;
    $loMessage->submitted = date("Y/m/d H:i:s");
    $loMessage->name = $lsName;
    if (msg_send($loQueue,1,$loMessage)) {
      $lfJobs = fopen("/srv/www/htdocs/jobs.submitted","a");
      fwrite($lfJobs,"$loMessage->submitted\t$loMessage->id\t$loMessage->name\n");
      fclose($lfJobs);
      $liStatus = 0;
    } else {
      $liStatus = 1;
    }
  }

  switch ($liStatus) {
    case 0:
      $lsMessage  = "Your job has been submitted with job id: $lsId.<br/>";
      $lsMessage .= "The results will be available <a href='?page=results&name=$lsName&id=$lsId'>here</a>.";
      break;
    case 1:
      $lsMessage  = "Your job has not been submitted as there is a problem with the job queue.<br/>";
      $lsMessage .= "Please <a href='/'>try again</a> and if this error persists then contact <a href='mailto:mark.preston@lshtm.ac.uk'>Mark Preston</a> to help resolve it.";
      break;
    case 2:
      $lsMessage  = "The queue limit of 10 jobs has been reached in it.<br/>";
      $lsMessage .= "Please <a href='/'>try again</a> later.";
      break;
    case 3:
      $lsMessage  = "The file upload has gone wrong: is your file size over 1Gb?<br/>";
      $lsMessage .= "Please <a href='/'>try again</a> and if this error persists then contact <a href='mailto:mark.preston@lshtm.ac.uk'>Mark Preston</a> to help resolve it.";
      break;
    case 4:
      $lsMessage  = "The file upload has gone wrong: not enough/too few files.<br/>";
      $lsMessage .= "Please <a href='/'>try again</a> and if this error persists then contact <a href='mailto:mark.preston@lshtm.ac.uk'>Mark Preston</a> to help resolve it.";
      break;
    default:
      $lsMessage  = "Crapola.<br/>";
      $lsMessage .= "Please <a href='/'>try again</a> and if this error persists then contact <a href='mailto:mark.preston@lshtm.ac.uk'>Mark Preston</a> to help resolve it.";
      break;
  }
?>
  <div class='text'>
   <?= $lsMessage ?>
  </div>
<?
 $liQueueLength = getQueueSize($loQueue);
 $lsPlural = "s";
 if ($liQueueLength == 1) { $lsPlural = ""; }
?>
  <div class='text'>
   The processing queue has <?= $liQueueLength ?> job<?= $lsPlural ?> in it.
  </div>
