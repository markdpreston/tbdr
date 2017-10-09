  <div class='text'>
   This tool is for <b>Research Use Only</b>.
   Data and information provided through use of this tool are not intended for medical purpose or objective and should not be used for clinical diagnosis, patient management, or human clinical trials.
  </div>
  <div class='text'>
   Back to <a href='?page=results'>results</a> page.
  </div>
  <div class='text'>
   Single nucleotide polymorphisms (SNPs) in coding regions are annotated using the reference amino acid, codon number and alternative amino acid (e.g. Ser315Thr in katG).
   SNPs in non-coding regions (i.e. RNA genes and intergenic regions) are annotated using the reference nucleotide, gene coordinate and alternative nucleotide (e.g. A1401G in rrs or C-37A in eis promoter).
   Indels are annotated using the reference VCF allele, gene coordinate and alternative VCF allele (e.g. T902TA insertion in katG).
  </div>
  <?
   $lsId = "";
   $lsName = "";
   if (isset($_GET["id"])) { $lsId = $_GET["id"]; }
   if (isset($_GET["name"])) { $lsName = $_GET["name"]; }
   if ($lsId != "") {
  ?>
  <div class='subtitle'>Name: <?= $lsName ?></div>
  <div class='subtitle'>Sample: <?= $lsId ?></div>
  <? if (file_exists("output/$lsId.html")) { ?>
  <div class='text'><? include "output/$lsId.html"; ?></div>
  <hr/><div class='subtitle'>Log</div><div class='text'><? include "output/$lsId.log"; ?></div>
  <?
    include "drugs.php";
    include "lineages.php";
    include "citations.php";
   } else {
  ?>
  <div class='text'>
   Output file not yet ready.
   If the job is marked as complete in the <a href='?page=results'>results</a> then please contact <a href='mailto:mark.preston@lshtm.ac.uk'>Mark Preston</a> to help locate your results.
  </div>
  <?
    }
   } else {
    //  Get job information.
    $lsSubmitted = file_get_contents("/srv/www/htdocs/jobs.submitted","r");
    $lsCompleted = file_get_contents("/srv/www/htdocs/jobs.completed","r");
    $laSubmitted = array_filter(explode("\n",$lsSubmitted));
    $laCompleted = array_filter(explode("\n",$lsCompleted));
    //  Sort by submitted date.
    sort($laSubmitted);
    $laSubmitted = array_reverse($laSubmitted);
    for ($i = 0; $i < count($laSubmitted); $i++) {
     $laEntry = explode("\t",$laSubmitted[$i]);
     $laMatches = preg_grep("/^" . $laEntry[1] . "/",$laCompleted);
     if (count($laMatches) > 0) {
      $laSubmitted[$i] .= "\t" . array_shift($laMatches);
     }
     unset($laMatches);
    }
  ?>
  <div class='text'>
   <table cellpadding=0 cellspacing=0 width=100%>
    <tr><td width="15%">Id</td><td width="40%">Name</td><td width="20%">Submitted</td><td width="20%">Processed</td></tr>
    <?
     for ($i = 0; $i < count($laSubmitted); $i++) {
      $laLine      = explode("\t",$laSubmitted[$i]);
      $lsId        = $laLine[1];
      $lsName      = $laLine[2];
      $lsSubmitted = $laLine[0];
      $lsCompleted = "";
      if (count($laLine) > 3) { $lsCompleted = $laLine[4]; }
    ?>
    <tr>
     <td><a href='?page=results&id=<?= $lsId ?>&name=<?= $lsName ?>'><?= $lsId ?></td>
     <td><?= $lsName ?></td>
     <td><?= $lsSubmitted ?></td>
     <td><?= $lsCompleted ?></td>
    </tr>
    <?
     }
    ?>
   </table>
   <?
    }
   ?>
  </div>
 </body>
</html>
