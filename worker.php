#!/usr/bin/php
<?php
  $loQueue = msg_get_queue('12345');
  $loMessage = NULL;
  $lsType = "";
  print("Waiting...\n");
  while (msg_receive($loQueue,1,$lsType,512,$loMessage)) {
    print("Received...\n");
    print("$loMessage->id : $loMessage->name : $loMessage->submitted\n");
    print("Processing...\n");
    $lsName = $loMessage->id;
    `perl /srv/www/htdocs/testDR.pl $lsName &> /srv/www/htdocs/output/$lsName.err.worker`;
    $lfJobs = fopen("/srv/www/htdocs/jobs.completed","a");
    $lsDate = date("Y/m/d H:i:s");
    fwrite($lfJobs,"$loMessage->id\t$lsDate\n");
    fclose($lfJobs);
    $loMessage = NULL;
    $lsType = "";
    print("Waiting...\n");
  }
?>
