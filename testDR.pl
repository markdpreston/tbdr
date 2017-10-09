#!/usr/bin/perl -w
#
#  Released under GPL with the follwing two statements attached to every version of this file and to be included on any website using these codes.
#
#  Created by Mark D. Preston, LSHTM 2014.
#
#  See www.markdpreston.com.
#
#use strict;

die("perl $0 id\n") if ($#ARGV != 0);
my $psId = $ARGV[0];

my $lsPath = '/srv/www/htdocs/';
#my $lsPath = '/home/mark/data/website/tbdr/';
#my $lsPath = '/home/mark/t/';

my $lsSnap = $lsPath.'bin/snap';
my $lsSamtools = $lsPath.'bin/samtools';
my $lsBcftools = $lsPath.'bin/bcftools';
my $lsBwa = $lsPath.'bin/bwa';
my $lsVcfutils = $lsPath.'bin/vcfutils.pl';
#my $lsSamtools = 'samtools';
#my $lsBcftools = 'bcftools';
#my $lsBwa = 'bwa';
#my $lsVcfutils = 'vcfutils.pl';

my $lsRef = $lsPath.'process/rapid.fa';
my $lsFai = $lsRef.'.fai';
my $lsReindex = $lsPath.'process/reindex.txt'; # Used to link original/new chromosome coordinates; 0,1 columns only
my $lsDrugMetaData = $lsPath.'process/druginfo.txt';
my $lsDRGenes = $lsPath.'process/drgenes.txt'; # Used to annotate mutations in these regions; 1,2,3,4 columns only
my $lsDRDB = $lsPath.'process/drdb.txt'; # Used to infer DR status
my $lsLineageMetaData = $lsPath.'process/lineages.txt';
my $lsLineageSnps = $lsPath.'process/strain.txt'; # Used to infer strain type
my $lsAnnotations = $lsPath.'process/annotation.txt';

my $lsInput = $lsPath.'input/';
my $lsSam = $lsInput.$psId.".sam";
my $lsBam = $lsInput.$psId.".bam";
my $lsBai = $lsInput.$psId.".bam.bai";
my $lsBaiSorted = $lsInput.$psId.".sorted.bam.bai";
my $lsBamSorted = $lsInput.$psId.".sorted";
my $lsStats = $lsInput.$psId.".stats"; 
my $lsBcf = $lsInput.$psId.".bcf";
my $lsVcf = $lsInput.$psId.".vcf"; 
my $liMinQual = 30; # Minimum VCF quality to accept variant

open H, ">$lsPath/output/snap.$psId.html";
open D, ">$lsPath/output/snap.$psId.debug";
open L, ">$lsPath/output/snap.$psId.log";
open E, ">$lsPath/output/snap.$psId.err";
my $lsError = "$lsPath/output/snap.$psId.pipeline.err";

sub outputHtml {
  my $lsHtml = shift;
  print H $lsHtml;
}

sub outputDebug {
  my $lsMessage = shift;
  print D localtime() . ": " . $lsMessage . "<br/>\n";
}

sub outputLog {
  my $lsMessage = shift;
  outputDebug($lsMessage);
  print L localtime() . ": " . $lsMessage . "<br/>\n";
}

sub error {
  my $lsMessage = shift;
  outputLog($lsMessage);
  print E $lsMessage . "<br/>\n";
  die($lsMessage);
}

outputLog("Starting to process");

my $lsFastq = `ls $lsInput$psId* | grep ".fastq"`;
chomp($lsFastq);
my @laFastq = split(" ",$lsFastq);
outputDebug("Fastq " . join(" ",@laFastq)."\n");

outputDebug("Merging");
#  Merge to single fastq file.
if ($#laFastq ==  0) {
  outputDebug("One Fastq");
} elsif ($#laFastq ==  1) {
  outputDebug("Two fastq");
  `cat $laFastq[0] $laFastq[1] > $laFastq[0].temp`;
  `mv $laFastq[0].temp $laFastq[0]`;
  `rm $laFastq[1]`;
  outputDebug("Merged fastq");
} else {
  error("More than 2 fastq files found.");
}

outputLog("Mapping");
`$lsSnap single process $laFastq[0] -o $lsBam -so -sm 2 -t 2 -M 2> $lsError`;

outputLog("Indexing");
`$lsSamtools index $lsBam 2>> $lsError`;

outputDebug("Flagstat");
`$lsSamtools flagstat $lsBam > $lsStats 2>> $lsError`;

outputLog("Finding mutations");

outputDebug("Calling");
`$lsSamtools mpileup -B -Q 23 -d 2000 -C 50 -ugf $lsRef $lsBam | $lsBcftools view -bvcg - > $lsBcf`;
`$lsBcftools view $lsBcf | $lsVcfutils varFilter -d 10 -D 2000 | cut -f1-6 > $lsVcf`;

outputDebug("Removing temporary files ");
`rm -f $lsBamSorted* $lsSam $lsBam $lsBai $lsBcf $lsStats`;

outputLog("Finalising results");

#  Load original and reference positions.
outputDebug("Load reindexer");
my %lhSnpReindex = ();
my %lhSnpPresent = ();
open I, "<$lsReindex";
while (my $lsLine = <I>) {
  chomp($lsLine);
  my @a = split("\t",$lsLine);
  $lhSnpReindex{$a[0]} = $a[1];
  $lhSnpPresent{$a[1]} = 1;
}
close I;

#  Read sample VCF file.
outputDebug("Saving all SNPs/indels from VCF");
my %lhVcfRef = ();
my %lhVcfAlt = ();
open I, "<$lsVcf";
while (my $lsLine = <I>) {
  chomp($lsLine);
  unless($lsLine  =~ /^#/) {
    my @a = split("\t",$lsLine);
    my $liPosition = 0;
    if ($a[5] >=  $liMinQual) {
      if (defined $lhSnpReindex{$a[1]}) {
        $liPosition = $lhSnpReindex{$a[1]}; # original reference coordinate
        $lhVcfRef{$liPosition} = $a[3];
        $lhVcfAlt{$liPosition} = $a[4];
      } else {
        error("Check SNP coordinates file");
      }
    }
  }
}

#  Loading drug metadata
outputDebug("Loading drug metadata");
my @laDrugOrder = ();
open I, "<$lsDrugMetaData";
while (my $lsLine = <I>) {
  chomp($lsLine);
  my @a = split "\t", $lsLine;
  push(@laDrugOrder,$a[0]);
}
close I;

outputDebug("Predicting DR status");
my %lhDr = ();
open I, "<$lsDRDB";
while (my $lsLine = <I>) {
  chomp($lsLine);
  my @a = split("\t",$lsLine);
  my $liFound = 0;
  my @laPositions = split("/",$a[1]);
  for (my $i = 0; $i <= $#laPositions; $i++) {
    my $liPosition = $laPositions[$i];
    if (defined $lhVcfRef{$liPosition}) {
      if (length($lhVcfRef{$liPosition}) == 1 && length($lhVcfAlt{$liPosition}) == 1) {
        my $lcRef = (split("",$a[2]))[$i];
        my $lcAlt = (split("",$a[3]))[$i];
        if ($lhVcfRef{$liPosition} eq $lcRef && $lhVcfAlt{$liPosition} eq $lcAlt) {
          $liFound++;
        }
      }
    }
  }
  if ($liFound == $#laPositions + 1) {
    if ($a[4] =~ /promoter/) {
      $a[4] =~ s/_promoter//;
      $a[5] = "promoter";
    }
    if (exists($lhDr{$a[0]})) {
      $lhDr{$a[0]} .= ", $a[4] ($a[5])";
    } else {
      $lhDr{$a[0]} = "$a[4] ($a[5])";
    }
    outputDebug("DR\t$lsLine");
  }
}
close I;
$lhDr{"Multi drug resistance"} = "&nbsp;" if (exists($lhDr{"ISONIAZID"}) && exists($lhDr{"RIFAMPICIN"}));
$lhDr{"Extremely drug resistance"} = "&nbsp;" if (exists($lhDr{"ISONIAZID"}) && exists($lhDr{"RIFAMPICIN"}) && exists($lhDr{"FLUOROQUINOLONES"})
                            && (exists($lhDr{"AMIKACIN"}) || exists($lhDr{"CAPREOMYCIN"}) || exists($lhDr{"KANAMYCIN"})));

#  Loading lineage metadata
outputDebug("Loading lineage metadata");
my %lhLineageMetaData = ();
open I, "<$lsLineageMetaData";
while (my $lsLine = <I>) {
  chomp($lsLine);
  my @a = split "\t", $lsLine;
  $lhLineageMetaData{$a[0]} = $lsLine;
}
close I;

#  Loading lineage snp data
outputDebug("Loading lineage SNPs");
my %lhLineageSnps = ();
my %lhSnpLineages = ();
open I, "<$lsLineageSnps";
<I>;
while (my $lsLine = <I>) {
  chomp($lsLine);
  my @a = split("\t",$lsLine);
  $lhLineageSnps{$a[0]}{$a[1]} = $a[5];
  $lhSnpLineages{$a[1]} = $a[0];
}
close I;

#  Predict lineage
outputDebug("Predicting lineage");
my %lhLineage = ();
foreach my $lsLineage (sort keys %lhLineageSnps) {
  foreach my $liPosition (keys %{$lhLineageSnps{$lsLineage}}) {
    # If lineage-specific SNP coordinate is present in the new reference genome
    if (defined $lhSnpPresent{$liPosition}) {
      # in these two cases we do not expect SNP
      if ($lsLineage eq "lineage4" || $lsLineage eq "lineage4.9") {
        unless(defined $lhVcfAlt{$liPosition}) {
          outputDebug("\t$lsLineage\t$liPosition");
          $lhLineage{$lsLineage} = 1;
        }
      } else {
        if (defined $lhVcfAlt{$liPosition}) {
          if (length($lhVcfAlt{$liPosition}) == 1 && length($lhVcfRef{$liPosition}) == 1) {
            if ($lhVcfAlt{$liPosition} eq $lhLineageSnps{$lsLineage}{$liPosition}) {
              outputDebug("\t$lsLineage\t$liPosition");
              $lhLineage{$lsLineage} = 1;
            }
          }
        }
      }
    }
  }
}
outputDebug("Predicted strain type: " . join", ",sort(keys(%lhLineage)));

#  Loading snp annotation data
outputDebug("Loading SNP annotation");
my %lhSnpGenes = ();
my %lhSnpCodons = ();
my %lhSnpMutations = ();
open I, "<$lsAnnotations";
while (my $lsLine = <I>) {
  chomp($lsLine);
  my @a = split("\t",$lsLine);
  $lhSnpGenes{$a[0]} = $a[1];
  $lhSnpCodons{$a[0]} = $a[2];
  $lhSnpMutations{$a[0]}{$a[3]} = $a[6];
  $lhSnpMutations{$a[0]}{$a[4]} = $a[7];
  $lhSnpMutations{$a[0]}{$a[5]} = $a[8];
}
close I;


#  Output the HTML results.
outputHtml("<table cellpadding=0 cellspacing=0 width=100%>\n");
outputHtml("<tr><td>Drug<sup>1</sup></td><td>Resistance</td><td>Supporting Mutations</td></tr>\n");
foreach my $lsDrug (@laDrugOrder) {
  if (exists($lhDr{$lsDrug})) {
    outputHtml("<tr><td>" . ucfirst(lc($lsDrug)) . "</td><td>R</td><td>$lhDr{$lsDrug}</td></tr>\n");
  } else {
    outputHtml("<tr><td>" . ucfirst(lc($lsDrug)) . "</td><td>&nbsp;</td><td>&nbsp;</td></tr>\n");
  }
}
outputHtml("</table>\n");

#  Output lineage information.
outputHtml("<p/>\n");
if (length(keys(%lhLineage)) > 0) {
  outputHtml("<table cellpadding=0 cellspacing=0 width=100%>\n");
  outputHtml("<tr><td>Lineage<sup>2</sup></td><td>Name</td><td>Main Spoligotype</td><td>RDS</td></tr>\n");
  foreach my $lsLineage (sort(keys(%lhLineage))) {
    my @a = split "\t", $lhLineageMetaData{$lsLineage};
    outputHtml("<tr><td>$lsLineage</td><td>$a[1]</td><td>$a[2]</td><td>$a[3]</td></tr>\n");
  }
  outputHtml("</table>\n");
} else {
  outputHtml("No lineages identified.");
}

#  Identify multiple infections.
my @laLineages = sort(keys(%lhLineage));
foreach my $l (@laLineages) {
  delete $lhLineage{$l} if grep {/$l/ && $l ne $_} @laLineages;
}
outputDebug("Predicted strain type: " . join", ",sort(keys(%lhLineage)));
if (length(keys(%lhLineage)) > 1) {
  outputHtml("<br/>Potential mixed infection.");
}

#  Output other SNPs.
outputHtml("<p/>\n");
if (length(keys(%lhVcfRef)) > 0) {
  outputHtml("Mutations in candidate genes:<br/><br/>\n");
  outputHtml("<table cellpadding=0 cellspacing=0 width=100%>\n");
  outputHtml("<tr><td>Gene</td><td>Chromosome Position</td><td>Mutation</td><td>Lineage</td></tr>\n");
  foreach my $liPosition (sort { $a <=> $b} keys(%lhVcfRef)) {
    if (exists($lhSnpGenes{$liPosition})) {
      my $lsGene = $lhSnpGenes{$liPosition};
      my $lsMutation = "&nbsp;";
      my $lsLineage = "&nbsp;";
      $lsMutation = $lhSnpCodons{$liPosition} if (exists($lhSnpCodons{$liPosition}));
      $lsMutation .= $lhSnpMutations{$liPosition}{$lhVcfAlt{$liPosition}} if (exists($lhSnpMutations{$liPosition}{$lhVcfAlt{$liPosition}}));
      $lsMutation = "Indel: $lhVcfRef{$liPosition}/$lhVcfAlt{$liPosition}" if (length($lhVcfRef{$liPosition}) > 1 || length($lhVcfAlt{$liPosition}) > 1);
      $lsLineage = $lhSnpLineages{$liPosition} if (exists($lhSnpLineages{$liPosition}));    
      outputHtml("<tr><td>$lsGene</td><td>$liPosition</td><td>$lsMutation</td><td>$lsLineage</td></tr>\n");
    }
  }
  outputHtml("</table>\n");
} else {
  outputHtml("No SNPs identified.");
}

outputLog("End");

close H;
close L;
close E;
