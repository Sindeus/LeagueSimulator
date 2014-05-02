<!DOCTYPE html>

<html>
    <head>                                                                                                                                                          
        <title>League Matchmaking Sim - Results</title>
        <link rel="stylesheet" type="text/css" href="/global.css">
         <script>
          (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
          (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
          m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
          })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
          ga('create', 'UA-50390428-1', 'net76.net');
          ga('send', 'pageview');
        </script>
    </head>
    <body>
        <h1>League Matchmaking Sim</h1>

<?php

require 'simulate.php';
include_once 'stats.php';

// - - - BEGIN FORM HANDLER - - -

$players = round((int)($_GET['players']), -1);
($players < 10) ? $players = 10 : null;
$matches = 100 * (int)($_GET['matches']);
($matches < 1) ? $matches = 100 * $players : null;

$feeder;
if($_GET['feeder'] < 0) {
    $feeder = 0;
}
else if($_GET['feeder'] > 100) {
    $feeder = 100;
}
else {
   $feeder = (float)($_GET['feeder']); 
}

$leaver;
if($_GET['leaver'] < 0) {
    $leaver = 0;
}
else if($_GET['leaver'] > 100) {
    $leaver = 100;
}
else {
   $leaver = (float)($_GET['leaver']); 
}

$nc;
if($_GET['nc'] < 0) {
    $nc = 0;
}
else if($_GET['nc'] > 100) {
    $nc = 100;
}
else {
   $nc = (float)($_GET['nc']); 
}

$skill;
if($_GET['skill'] < 0) {
    $skill = 0;
}
else if($_GET['skill'] > 2400) {
    $skill = 2400;
}
else {
   $skill = (int)($_GET['skill']); 
}

$toxicity;
if($_GET['toxicity'] < 0) {
    $toxicity = 0;
}
else if($_GET['toxicity'] > 2) {
    $toxicity = 2;
}
else {
   $toxicity = (float)($_GET['toxicity']); 
}

$solo;
if($_GET['solo'] < 0) {
    $solo = 0;
}
else if($_GET['solo'] > 100) {
    $solo = 1;
}
else {
   $solo = ((float)($_GET['solo']))/100; 
}

$start = (isset($_GET['start'])) ? true : false;

$learning = (isset($_GET['learning'])) ? true : false;

$bias = (isset($_GET['bias'])) ? true : false;

$complex = ($_GET['scoring'] == 'complex') ? true : false;

$mm = ($_GET['matchmaking'] == 'variable') ? true : false;

$seeding = ($_GET['seeding'] == 'normal') ? true : false;


// - - - END FORM HANDLER - - -

$p = new Population($players, $matches);
($seeding) ? $p->populate($skill, $toxicity, $start) : $p->zzzOLD_populate($skill, $toxicity, $start);
Game::$chanceFeeder = $feeder;
Game::$chanceLeaver = $leaver;
Game::$chanceNC = $nc;
Game::$complexScoring = $complex;
Game::$useLearning = $learning;
Game::$biasBlueTeam = $bias;
Game::$soloWeight = $solo;
($mm) ? $p->simulate() : $p->zzzOLD_simulate();
echo '<div class="pdump"><span class="subhead">Results</span><br><hr>';
$rWins = $p->getWins();
echo 'Blue Team Wins: ' . (real)$rWins[0] . ', (' . (real)$rWins[1] . '%)<br /><hr>';
echo $p;
$rTox = $p->getToxicity();
echo 'Toxic Games: ' . (real)$rTox[0] . ', (' . (real)$rTox[1] . '%)<br />';
echo 'Toxicity Changes Outcome: ' . (real)$rTox[2] . ', (' . (real)$rTox[3] . '% Toxic Games), (' . (real)$rTox[4] . '% Total Games)<br />';
$rToxType = $p->getToxicTypes();
echo 'Incidents of Non-Communicating Players: ' . (real)$rToxType[0] . ' (' . (real)$rToxType[3] . ' per game)<br />';
echo 'Incidents of Leaving: ' . (real)$rToxType[1] . ' (' . (real)$rToxType[4] . ' per game)<br />';
echo 'Incidents of Feeding: ' . (real)$rToxType[2] . ' (' . (real)$rToxType[5] . ' per game)';
echo '</div><div class="divider"></div><div class="pdump"><span class="subhead">Population Breakdown</span><br><hr>';
echo $p->getPlayerInfo();
echo '</div>';

?>

    </body>
</html>
