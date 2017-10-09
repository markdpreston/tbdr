  <div class='text'>
   This tool is for <b>Research Use Only</b>.
   It has not been approved, cleared, or licensed by any regulatory authority.
   By submitting sequence data the user acknowledges no intended medical purpose or objective such as clinical diagnosis, patient management, or human clinical trials.
  </div>
  <div class='subtitle'>Results</div>
  <div class='text'>
   The results for all jobs are available <a href='?page=results'>here</a>.
  </div>
  <div class='subtitle'>Submit</div>
  <div class='text'>
   <?  if ($liQueueLength < $liMaxQueueLength) { ?>
   <form method="post" action="?page=submit" enctype="multipart/form-data">
    <!-- 1Gb per file maximum -->
    <input type="hidden" name="MAX_FILE_SIZE" value="1000000000"/>
    Please select one (single end) or two (paired end) gzipped FASTQ files to upload and process, each file must be under 1GB in size.
    If you choose to add a name for this analysis then do it carefully as it will be made public.
    <table cellpadding=0 cellspacing=0 width=100% style='margin-top: 1em'>
     <tr>
      <td width="25%">Public Name (optional):</td>
      <td><input type="text" name="name" size="40"/></td>
     </tr>
     <tr>
      <td>Gzipped FASTQ file:</td>
      <td><input type="file" name="fastq[]" size=200/></td>
     </tr>
     <tr>
      <td>Second FASTQ (optional):</td>
      <td><input type="file" name="fastq[]" size="100"/></td>
     </tr>
     <tr>
      <td/>
      <td><button type="submit" name="submit">Submit</button></td>
     </tr>
    </table>
    <br/>
    The processing queue has <?= $liQueueLength ?> job<?= $lsPlural ?> in it.
   </form>
   <?  } else {  ?>
   The processing queue is full, please try again later.
   <?  }  ?>
  </div>
  <div class='subtitle'>Example data</div>
  <div class='text'>
   <table cellpadding=0 cellspacing=0>
    <tr><td>Sample</td><td>FASTQ Data from the EBI</td><td>Profile</td></tr>
    <tr><td>Malawi/Mixed/MDR</td><td><a href='http://www.ebi.ac.uk/ena/data/view/ERR176616'>ERR176616</a></td><td><a href='?page=results&id=ERR176616&name=Malawi/Mixed/MDR'>Profile</a></td></tr>
    <tr><td>Malawi/Lineage 1</td><td><a href='http://www.ebi.ac.uk/ena/data/view/ERR190365'>ERR190365</a></td><td><a href='?page=results&id=ERR190365&name=Malawi/Lineage%201'>Profile</a></td></tr>
    <tr><td>Malawi/Lineage 4-Stype/Pan-Susceptible</td><td><a href='http://www.ebi.ac.uk/ena/data/view/ERR212132'>ERR212132</a></td><td><a href='?page=results&id=ERR212132&name=Malawi/Lineage%204-Stype/Pan-Susceptible'>Profile</a></td></tr>
    <tr><td>China/Lineage 2-Beijing/XDR</td><td><a href='http://www.ebi.ac.uk/ena/data/view/SRR671726'>SRR671726</a></td><td><a href='?page=results&id=SRR671726&name=China/Lineage%202-Beijing/XDR'>Profile</a></td></tr>
    <tr><td>Tibet/Lineage 4-T/XDR</td><td><a href='http://www.ebi.ac.uk/ena/data/view/SRR671740'>SRR671740</a></td><td><a href='?page=results&id=SRR671740&name=Tibet/Lineage%204-T/XDR'>Profile</a></td></tr>
   </table>
  </div>
  <div class='subtitle'>Further Information</div>
  <div class='text'>
   This tool, with application to a large, published dataset, is described in detail in the journal article:<br/>
   <div class='cite'>
    Rapid determination of anti-tuberculosis drug resistance from whole genome sequences<br/>
    F. Coll, R. McNerney, M.D. Preston, T.G. Clark, <i>et al.</i><br/>
    &lt;Submitted&gt;
   </div>
   Please cite us if you use our tool.<br/>
   Processing time is under 10 minutes per sample plus queuing time; for example &tilde;2:30 minutes for a 500Mb file.
  </div>
  <div class='text'>
    Website and tool created by Mark D Preston.
  </div>
