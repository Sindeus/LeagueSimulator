<?php

class Player {
    
    protected $id; // Player ID number.
    
    protected $cMMR; // Current MMR
    protected $tMMR; // 'True' MMR based on player's true skill
    protected $lMMR; // Learned MMR; increases based on experience.
    protected $division; // Current Bronze V - Challenger divison status, 1 being Bronze V and Challenger being 26. -1 === unranked
    
    protected $tox; // Toxicity/disposition; determines propensity to be toxic relative to total population.
    
    protected $lWins; // Quantity lane wins.
    protected $sumLaneMod; // Sum lane modifiers.
    protected $sumSoloMMR; // Sum of MMR gained or lost by solo performance.
    protected $sumTeamMMR; // Sum of MMR gained or lost by team win.
    
    protected $qWins; // Quantity total wins.
    protected $qMatches; // Quantity total matches.
    
    function __construct($_id = -1, $_tMMR = 1200, $_tox = 1, $startTrue = false) {
        $this->id = $_id;
        ($startTrue) ? $this->cMMR = $_tMMR : $this->cMMR = 1200;
        $this->tMMR = $_tMMR;
        $this->lMMR = 0;
        $this->division = -1;
        
        $this->tox = $_tox;
        
        $this->qMatches = 0;
        $this->qWins = 0;
    }
    
    function __toString() {
        return $this->cMMR . ' | ' . $this->tMMR;
    }
    function getID() {
        return $this->id;
    }
    function getcMMR() {
        return $this->cMMR;
    }
    function getSkill() {
        return $this->tMMR + $this->lMMR;
    }
    function getTox() {
        return $this->tox;
    }
    function getMatches() {
        return $this->qMatches;
    }
    function getWins() {
        return $this->qWins;
    }
    function getSumLaneMod() {
        return $this->sumLaneMod;
    }
    function getAvgLaneMod() {
        if($this->qMatches == 0) {
            return 'None';
        }
        return $this->sumLaneMod / $this->qMatches;
    }
    function getWinrate() {
        if($this->qMatches == 0) {
            return 'None';
        }
        return $this->qWins / $this->qMatches;
    }
    function getLaneWinrate() {
        if($this->qMatches == 0) {
            return 'None';
        }
        return $this->lWins / $this->qMatches;
    }
    function getSoloMMR() {
        return $this->sumSoloMMR;
    }
    function getTeamMMR() {
        return $this->sumTeamMMR;
    }
    function addcMMR($x, $solo = false) {
        $this->cMMR += $x;
        if($solo) {
            $this->sumSoloMMR += $x;
        }
        else {
            $this->sumTeamMMR += $x;
        }
        if($this->cMMR < 0) { // MMR has hard floor of zero.
          $this->cMMR = 0;  
        }
    }
    function learn($x) {
        $this->lMMR += $x;
    }
    function addGame($win = false) {
        $this->qMatches += 1;
        if($win) {
            $this->qWins += 1;
        }     
    }
    function addLane($mod = 0, $team = 'blue') {
        if($team == 'purple') { // Corrects for purple team positive mods being negative.
            $mod *= -1;
        }
        $this->sumLaneMod += $mod;
        if($mod > 0) {
            $this->lWins += 1;
        }     
    }
}

class Game {
    
    protected $bTeam; // Array of players on blue team.
    protected $pTeam; // Array of players on purple team.
    protected $bTeamRatings; // Array of ratings (MMR, adjusted by game performance) for  blue team, used in determining win.
    protected $pTeamRatings; // Array of ratings for purple team.
    protected $mod; // Array of rating modifiers for each lane, 0 = mid, 1 = top, 2 = bot, 3 = jungle.
    protected $results; // Results and related statistics, stored in a GameResults obj.
    
    public static $chanceNC = 1;
    public static $chanceLeaver = 0.1;
    public static $chanceFeeder = 0.01;
    
    public static $complexScoring = true;
    public static $useLearning = true;
    public static $biasBlueTeam = true;
    
    public static $soloWeight = 0;
    
    function __construct($aPlayers) {
        
        // Randomize higher MMR team since no blue/purp side bias in sim.
        if(mt_rand(1, 2) == 1) {
            $this->bTeam = array($aPlayers[0], $aPlayers[9], $aPlayers[3], $aPlayers[6]);
            $this->pTeam = array($aPlayers[1], $aPlayers[8], $aPlayers[2], $aPlayers[7]);
        }
        else {
            $this->pTeam = array($aPlayers[0], $aPlayers[9], $aPlayers[3], $aPlayers[6]);
            $this->bTeam = array($aPlayers[1], $aPlayers[8], $aPlayers[2], $aPlayers[7]);
        }
        if(self::$biasBlueTeam) {
                $this->bTeam[] =  $aPlayers[4];
                $this->pTeam[] =  $aPlayers[5];
        }
        else {
            if(mt_rand(1, 2) == 1) {
                $this->bTeam[] =  $aPlayers[4];
                $this->pTeam[] =  $aPlayers[5];
            }
            else {
                $this->pTeam[] =  $aPlayers[4];
                $this->bTeam[] =  $aPlayers[5]; 
            }
        }
        shuffle($this->bTeam);
        shuffle($this->pTeam);
        $this->bTeamRatings = array();
        $this->pTeamRatings = array();
        for($i = 0; $i < 5; $i++) {
            $this->bTeamRatings[$i] = $this->bTeam[$i]->getSkill();
            $this->pTeamRatings[$i] = $this->pTeam[$i]->getSkill();
        }
        $this->results = new GameResults();
    }
    
    function __toString() {
        $s = 'Match Details<br /><hr>Blue Team<br />';
        foreach($this->bTeam as $x) {
            $s .= $x . '<br />';
        }
        $s .= '<hr>Purple Team<br />';
        foreach($this->pTeam as $x) {
            $s .= $x . '<br />';
        }
        $s .= '<hr>Lane Matchup Modifiers<br />';
        foreach($this->mod as $x) {
            $s .= $x . '<br />';
        }
        return $s . '<hr>';
    }
    
    /* Adjusts the rating of players 0 and 1 on each team based on their lane matchup.
     * The formula used adjusts for higher skill players being less likely to dramatically lose a lane by dividing the modifer by the lower MMR of the lane.
     * PRE: Players 0 and 1 on bTeam and pTeam exist.
     */
    function adjustSoloLanes() {
        for($i = 0; $i < 2; $i++) {
            $this->mod[$i] = $this->bTeamRatings[$i] - $this->pTeamRatings[$i];
            $this->bTeamRatings[$i] += $this->mod[$i];
            $this->pTeamRatings [$i] -= $this->mod[$i];

            $outcome = $this->bTeamRatings[$i] - $this->pTeamRatings[$i];
            $this->bTeam[$i]->addLane($outcome, 'blue');
            $this->pTeam[$i]->addLane($outcome, 'purple');
            $this->results->bContributions[$i] = $outcome;
            $this->results->pContributions[$i] = -1 * $outcome;
        }
    }
    
    /* Adjusts the rating of players 2 and 3 on each team based on their lane matchup.
     * Assumes each player in bot lane contributes 50% towards a win or loss of the lane.
     * PRE: Players 2 and 3 on bTeam and pTeam exist.
     */
    function adjustBotLane() {
        $this->mod[2] = $this->bTeamRatings[2] + $this->bTeamRatings[3] - $this->pTeamRatings[2] - $this->pTeamRatings[3];
        for($i = 2; $i < 4; $i++) {
            $this->bTeamRatings[$i] += $this->mod[2];
            $this->pTeamRatings[$i] -= $this->mod[2];

            $outcome = $this->bTeamRatings[$i] - $this->pTeamRatings[$i];
            $this->bTeam[$i]->addLane($outcome, 'blue');
            $this->pTeam[$i]->addLane($outcome, 'purple');
            $this->results->bContributions[$i] = $outcome;
            $this->results->pContributions[$i] = -1 * $outcome;
        }
    }
    
    /* Adjusts the rating of player 4 on each team based on jungle performance.  The total impact of the jungler on each lane is calculated as modifying the jungler.
     * Junglers are impacted each lane seperately based on the Skill of their lane allies and opponents, and by the enemy jungler.
     * PRE: Player 4 on bTeam and pTeam exists.
     */
    function adjustJungle() {
        $this->mod[3] = 0.5 * ($this->bTeamRatings[4] - $this->pTeamRatings[4]) +
                (($this->bTeamRatings[4] + $this->bTeamRatings[0] - $this->pTeamRatings[4] - $this->pTeamRatings[0]) +
                ($this->bTeamRatings[4] + $this->bTeamRatings[1] - $this->pTeamRatings[4] - $this->pTeamRatings[1]) +
                ($this->bTeamRatings[2] + $this->bTeamRatings[3] + $this->bTeamRatings[4] - $this->pTeamRatings[2] - $this->pTeamRatings[3] - $this->pTeamRatings[4]))
                / 6;
        $this->bTeamRatings[4] += $this->mod[3];
        $this->pTeamRatings[4] -= $this->mod[3];
        
        $outcome = $this->bTeamRatings[4] - $this->pTeamRatings[4];
        $this->bTeam[4]->addLane($outcome, 'blue');
        $this->pTeam[4]->addLane($outcome, 'purple');
        $this->results->bContributions[4] = $outcome;
        $this->results->pContributions[4] = -1 * $outcome;
    }
    
    /* Applies a random chance for some players to intentionally feed, leave, or not communicate.  Leavers don't contribute to their team's total rating;
     *   feeders apply their rating negatively to their team;
     *   noncommunicative players apply a 50% higher negative modifier or a 50% lower positive modifer.
     * PRE: Players 1-4 on bTeam and pTeam exist.
     */
    function toxify() {
        for($i = 0; $i < 4; $i++) {
           $rng = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
           $ctox = $this->bTeam[$i]->getTox();
           if($rng < $ctox * self::$chanceFeeder / 100) {
               $this->bTeamRatings[$i] *= -1;
               $this->results->toxic = true;
               $this->results->qFeeders += 1;
           }
           else if($rng < $ctox * self::$chanceLeaver / 100) {
               $this->bTeamRatings[$i] = 0;
               $this->results->toxic = true;
               $this->results->qLeavers += 1;
           }
           else if($rng < $ctox * self::$chanceNC / 100) {
               ($this->bTeamRatings[$i] < 0) ? $this->bTeamRatings[$i] *= 2 : $this->bTeamRatings[$i] /= 2; 
               $this->results->toxic = true;
               $this->results->qNCs += 1;
           }
        }
        for($i = 0; $i < 4; $i++) {
           $rng = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
           $ctox = $this->pTeam[$i]->getTox();
           if($rng < $ctox * self::$chanceFeeder / 100) {
               $this->pTeamRatings[$i] *= -1;
               $this->results->toxic = true;
               $this->results->qFeeders += 1;
           }
           else if($rng < $ctox * self::$chanceLeaver / 100) {
               $this->pTeamRatings[$i] = 0;
               $this->results->toxic = true;
               $this->results->qLeavers += 1;
           }
           else if($rng < $ctox * self::$chanceNC / 100) {
               ($this->pTeamRatings[$i] < 0) ? $this->pTeamRatings[$i] *= 2 : $this->pTeamRatings[$i] /= 2; 
               $this->results->toxic = true;
               $this->results->qNCs += 1;
           }
        }
    }
    
     /* Determines the winner of a game based on adjusted player ratings, stores results, and applies changes to MMR and Skill.
     * PRE: Players 1-4 on each team exist, as do their respective ratings.
     */
    function playGame() {
       
        if(self::$complexScoring) {
            $this->adjustSoloLanes();
            $this->adjustBotLane();
            $this->adjustJungle();
        }
        else {
            for($i = 0; $i < 5; $i++) {
                $outcome = $this->bTeamRatings[$i] - $this->pTeamRatings[$i];
                $this->bTeam[$i]->addLane($outcome, 'blue');
                $this->pTeam[$i]->addLane($outcome, 'purple');
                $this->results->bContributions[$i] = $outcome;
                $this->results->pContributions[$i] = -1 * $outcome;
            }
        }
        
        $this->results->rmods = $this->mod;
        $this->results->bRating = array_sum($this->bTeamRatings);
        $this->results->pRating = array_sum($this->pTeamRatings);
        
        for($i = 0; $i < 4; $i++){
            $this->results->bSkill += $this->bTeam[$i]->getSkill();
            $this->results->pSkill += $this->pTeam[$i]->getSkill();
        }
        
        for($i = 0; $i < 4; $i++){
            $this->results->bMMR += $this->bTeam[$i]->getcMMR();
            $this->results->pMMR += $this->pTeam[$i]->getcMMR();
        }
        
        
        $this->toxify();
        // Records ratings modified as a result of toxitiy.
        $this->results->bRatingMod = array_sum($this->bTeamRatings);
        $this->results->pRatingMod = array_sum($this->pTeamRatings);
        
        $netRatingDiff = $this->results->bRating - $this->results->pRating;
        $netSkillDiff = $this->results->bSkill - $this->results->pSkill;
        $netMMRDiff = $this->results->bMMR -$this->results->pMMR; 
        $this->results->nRating = $netRatingDiff;
        $this->results->nSkill = $netSkillDiff;
        $this->results->nMMR = $netMMRDiff;
        $this->results->aMMR = ($this->results->bMMR + $this->results->pMMR) / 10;
        $this->results->aSkill = ($this->results->bSkill + $this->results->pSkill) / 10;
        
        if(self::$biasBlueTeam) {
            $outcomeRating = 55.7 * $this->results->bRating - 44.3 * $this->results->pRating;
        }
        else {
            $outcomeRating = $netRatingDiff;
        }
        
        // In complex scoring, a random integer is added to net rating to account for chance / misplay potential.  More chance at lower skill levels, up to a cap.
        if(self::$complexScoring) {
            $bSkillFloor = ($this->results->bSkill < 600) ? 600 : $this->results->pSkill;
            $pSkillFloor = ($this->results->pSkill < 600) ? 600 : $this->results->pSkill;
            $outcomeRating = mt_rand(-60000 / $bSkillFloor, 60000 / $pSkillFloor) + $netRatingDiff;
        }
        
        $chance = $this->results->bMMR / ($this->results->bMMR + $this->results->pMMR);
        
        $bAbsSumContribution = 0;
        $pAbsSumContribution = 0;
        for($i = 0; $i < 5; $i++) {
            $bAbsSumContribution += abs($this->results->bContributions[$i]);
            $pAbsSumContribution += abs($this->results->pContributions[$i]);
        }
        // Absolute sum of team contribution can be zero if every player leaves; setting to 1 corrects for that possibility.
        if($bAbsSumContribution < 1) {
           $bAbsSumContribution = 1; 
        }
        if($pAbsSumContribution < 1) {
           $pAbsSumContribution = 1; 
        }
        
        if($outcomeRating < 0) {
            $this->results->winner = 'purple';
            for($i = 0; $i < 5; $i++) {
                $this->pTeam[$i]->addcMMR(round((20 * (1 - self::$soloWeight)) * (1 - $chance)), false);
                $this->pTeam[$i]->addcMMR(round((20 * self::$soloWeight) * 5 * $this->results->pContributions[$i] / $pAbsSumContribution), true);
                $this->bTeam[$i]->addcMMR(round((-20 * (1 - self::$soloWeight)) * $chance), false);
                $this->bTeam[$i]->addcMMR(round((20 * self::$soloWeight) * 5 * $this->results->bContributions[$i] / $bAbsSumContribution), true);
                $this->pTeam[$i]->addGame(true);
                $this->bTeam[$i]->addGame(false);
            }
            if(self::$useLearning && $this->results->toxic == false && $netSkillDiff > 100) {
                foreach($this->pTeam as $x) {
                $x->learn(round($netSkillDiff / 100));
                } 
            }
        }
        else {
            $this->results->winner = 'blue';
            for($i = 0; $i < 5; $i++) {
                $this->pTeam[$i]->addcMMR(round((-20 * (1 - self::$soloWeight)) * (1 - $chance)), false);
                $this->pTeam[$i]->addcMMR(round((20 * self::$soloWeight) * $this->results->pContributions[$i] / $pAbsSumContribution), true);
                $this->bTeam[$i]->addcMMR(round((20 * (1 - self::$soloWeight)) * $chance), false);
                $this->bTeam[$i]->addcMMR(round((20 * self::$soloWeight) * $this->results->bContributions[$i] / $bAbsSumContribution), true);
                $this->pTeam[$i]->addGame(false);
                $this->bTeam[$i]->addGame(true);
            }
            if(self::$useLearning && $this->results->toxic == false && $netSkillDiff < -100) {
                foreach($this->bTeam as $x) {
                $x->learn(round(-1 * $netSkillDiff / 100));
                } 
            }
        }
        return $this->results;
    }
}

// Container class for statistics concerning a Game object.
class GameResults {
    public $winner; // 'blue' or 'purple' depending on winning team
    public $rmods; // Array of lane rating modifiers from a game, before secondary changes.
    public $bContributions; // Array of individual contribution scores from blue team.
    public $pContributions; // Array of individual contribution scores from purple team.
    
    public $nRating; // Net rating difference b/t teams.
    public $bRating; // Net rating of purple team.
    public $pRating; // Net rating of purple team.
    
    public $bRatingMod; // Tox-modified rating of blue team.
    public $pRatingMod; // Tox-modified rating of purple team.
    
    public $bSkill; // Skill sum of blue team.
    public $pSkill; // Skill sum of purple team.
    public $nSkill; // Net skill difference b/t teams.
    public $aSkill; // Avg skill difference b/t teams.
    
    public $bMMR; // Pregame MMR sum of blue team.
    public $pMMR; // Pregame MMR sum of purple team.
    public $nMMR; // Net pregame MMR difference b/t teams.
    public $aMMR; // Avg pregame MMR difference b/t teams.
    
    public $toxic; // True if game contained a toxic player, else false.
    public $qFeeders;
    public $qLeavers;
    public $qNCs;
    
    function __construct() {
        $this->winner = 'inprogress';
        $this->toxic = false;
    }
}

class Population {
    
    protected $simRun; // Has sim been run on population?
    
    protected $qPlayers; // Quantity players
    protected $qMatches; // Quantity matches per player.
    
    protected $Players;
    protected $Game;
    protected $Results;
    
    protected $s_sumSkill; // Total skill of all players before simulation;
    
    protected $f_sumSkill; // Final sum skill after simulation.
    protected $f_aSkillChange; // Average change in skill (learning) per player.
    
    function __construct($_qPlayers, $_qMatches) {
        $this->simRun = false;
        $this->qPlayers = $_qPlayers;
        $this->qMatches = $_qMatches;
        $this->Players = array();
        $this->Results = array();
    }
    
    /*
     * Populates Players array with a user-defined Player and fills w/ random Player objs.
     */
    function populate($pSkill, $pTox, $startTrue) {
        $this->Players[0] = new Player(1, $pSkill, $pTox, $startTrue);
        for($i = 1; $i < $this->qPlayers; $i++) {
            $this->Players[] = new Player($i + 1, round(stats_rand_normal(1200, 400, 1, 2400)), stats_rand_normal(1, 0.2, 0, 2), $startTrue);
        }
        $this->s_sumSkill = $this->getSumSkill();
    }
    
    /*
     * Simulates games until the average player has played qMatches.
     * Extreme high and low skill players may have fewer matches since simulate will match extreme players
     * against players outside the analyzed population to prevent a top or bottom player from skewing results.
     */
    function simulate() {
        for($i = 0; $i < $this->qMatches / $this->qPlayers / 10; $i++) {
            for($j = -9; $j < $this->qPlayers; $j++) { // Starts at -9 to ensure every main player is hit.  Simulated players used outside array.
                usort($this->Players, array($this, 'comparePlayerMMRs'));
                $gPlayers;
                if($j < 0) {
                    $gPlayers = array_slice($this->Players, 0, 10 + $j);
                    for($k = 0; $k < -1 * $j; $k++) {
                        $gPlayers[] = new Player(-1, $this->Players[0]->getcMMR() - mt_rand(0, -100 * $j), 2 * mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax(), true);
                    }
                }
                else if($j > $this->qPlayers - 10) {
                    $rm = $this->qPlayers - $j; // Remaining real players.
                    $gPlayers = array_slice($this->Players, $j, $rm);
                    for($k = 0; $k < 10 - $rm; $k++) {
                        $gPlayers[] = new Player(-1, $this->Players[$this->qPlayers - 1]->getcMMR() + mt_rand(0, 100 * (10 - $rm)), 2 * mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax(), true);
                    }
                }
                else {
                    $gPlayers = array_slice($this->Players, $j, 10);
                }
                $this->Game = new Game($gPlayers);
                $this->Results[] = $this->Game->playGame();
            }
        }
        usort($this->Players, array($this, 'comparePlayerMMRs'));
        $this->f_sumSkill = $this->getSumSkill();
        $this->f_aSkillChange = ($this->f_sumSkill - $this->s_sumSkill) / $this->qPlayers;
        $this->simRun = true;
    }
    
    function comparePlayerMMRs($a, $b) {
        if($a->getcMMR() == $b->getcMMR()) {
            return 0;
        }
        return ($a->getcMMR() > $b->getcMMR()) ? 1 : -1;
    }
    
    /*
     * Returns the sum of player skill values.
     */
    function getSumSkill() {
        $s = 0;
        foreach($this->Players as $x) {
            $s += $x->getSkill();
        }
        return $s;
    }
    
    /*
     * Returns an array containing total blue team wins and blue team wins as a percentage of total games.
     */
    function getWins() {
        $bTeamWins = 0;
        foreach($this->Results as $x){
            if($x->winner == 'blue') {
                $bTeamWins++;
            }
        }
        return array($bTeamWins, round(100 * $bTeamWins / count($this->Results), 2));
    }
    
    /*
     * Returns an array containing total toxic games, toxic games as a percent of total games,
     * games w/ changed outcome due to toxicity, outcome changes as percent of toxic games,
     * and outcome changes as percent of total games.
     */
    function getToxicity() {
        $toxicGames = 0;
        $toxicWins = 0;
        foreach($this->Results as $x){
            if($x->toxic == true) {
                $toxicGames++;
                if(($x->bRating > $x->pRating && $x->bRatingMod < $x->pRatingMod) || ($x->bRating < $x->pRating && $x->bRatingMod > $x->pRatingMod)) {
                    $toxicWins++;
                }
            }
        }
        $perToxicWins = ($toxicGames == 0) ? 'Null' : round(100 * $toxicWins / $toxicGames, 2);
        
        return array($toxicGames, round(100 * $toxicGames / count($this->Results), 2), $toxicWins, $perToxicWins, round(100 * $toxicWins / count($this->Results), 2));
    }
    
    /*
     * Returns an array containing quantity of total noncommunicative players, leavers, and feeders,
     * followed by the average of each per game.
     */
    function getToxicTypes() {
        $qFeeders = 0;
        $qLeavers = 0;
        $qNCs = 0;
        foreach($this->Results as $x){
            $qFeeders += $x->qFeeders;
            $qLeavers += $x->qLeavers;
            $qNCs += $x->qNCs;
        }
        return array($qNCs, $qLeavers, $qFeeders, round($qNCs / count($this->Results), 2), round($qLeavers / count($this->Results), 2), round($qFeeders / count($this->Results), 2));
    }
    
    /*
     * Returns a string containing a breakdown of the basic info of each player in a population object.
     */
    function getPlayerInfo() {
            $s;
            for($i = 0; $i < $this->qPlayers; $i++) {
                $s .= 'Player ' . $this->Players[$i]->getID() . '<br />MMR: ' . (real)$this->Players[$i]->getcMMR() . 
                    '<br />Skill:  ' . (real)$this->Players[$i]->getSkill() .  
                    '<br />Skill (Adjusted):  ' . (real)($this->Players[$i]->getSkill() - $this->f_aSkillChange) . 
                    '<br />Toxicity:  ' . (real)$this->Players[$i]->getTox() .
                    '<br />Matches:  ' . (real)$this->Players[$i]->getMatches() .
                    '<br />Winrate:  ' . (real)$this->Players[$i]->getWinrate() .
                    '<br />Lane Winrate:  ' . (real)$this->Players[$i]->getLaneWinrate() .
                    '<br />Total Contribution:  ' . (real)$this->Players[$i]->getSumLaneMod() .
                    '<br />Avg Contribution Per Game:  ' . (real)$this->Players[$i]->getAvgLaneMod() .
                    '<br />MMR Gained/Lost From Team:  ' . (real)$this->Players[$i]->getTeamMMR() .
                    '<br />MMR Gained/Lost Solo:  ' . (real)$this->Players[$i]->getSoloMMR();
            if($i !== $this->qPlayers - 1) {
                $s .= '<br /><hr>';
            }
        }
        return $s;
    }
    
    /*
     * A limited version of getPlayerInfo, used for debug.
     */
    function getRegressionPoints() {
        $s;
        for($i = 0; $i < $this->qPlayers; $i++) {
            $s .= '<br />' . (real)$this->Players[$i]->getSkill() . 
            ' ' . (real)$this->Players[$i]->getcMMR();
        }
        return $s;
    }
                
    
    /*
     * Returns statistical analysis of object as a string.
     * PRE: Simulation has been run.
     */
    function __toString() {
        if(!$this->simRun) {
            return 'Simulation not run.';
        }
        include_once 'stats.php';
        $aSkill = array();
        $aTox = array();
        $aWR = array();
        $aLWR = array();
        $aMMR = array();
        $aDiff = array();
        $sumMMR = 0;
        for($i = 0; $i < $this->qPlayers; $i++) {
            $aSkill[$i] = $this->Players[$i]->getSkill();
            $aTox[$i] = $this->Players[$i]->getTox();
            $aWR[$i] = $this->Players[$i]->getWinrate();
            $aLWR[$i] = $this->Players[$i]->getLaneWinrate();
            $aMMR[$i] = $this->Players[$i]->getcMMR();
            $aDiff[$i] = abs($aSkill[$i] - $aMMR[$i]);
            $sumMMR += $this->Players[$i]->getcMMR();
        }
        $reg = stats_reg_lin($aSkill, $aMMR);
        $r = stats_calc_r($aSkill, $aMMR);
        $toxreg = stats_reg_lin($aTox, $aMMR);
        $toxr = stats_calc_r($aTox, $aMMR);
        $wrreg = stats_reg_lin($aWR, $aMMR);
        $wrr = stats_calc_r($aWR, $aMMR);
        $lwrreg = stats_reg_lin($aLWR, $aMMR);
        $lwrr = stats_calc_r($aLWR, $aMMR);
        $s .= 'Linear Regression: (MMR) = ' . round($reg[0], 2) . '(Skill) + ' . round($reg[1], 2) .
                '<br />r = ' . round($r, 2) .
                '<br />r&sup2; = ' . round(pow($r, 2), 2) .
                '<br /><hr>';
        $s .= 'Linear Regression: (MMR) = ' . round($toxreg[0], 2) . '(Toxicity) + ' . round($toxreg[1], 2) .
                '<br />r = ' . round($toxr, 2) .
                '<br />r&sup2; = ' . round(pow($toxr, 2), 2) .
                '<br /><hr>'; 
        $s .= 'Linear Regression: (MMR) = ' . round($wrreg[0], 2) . '(Winrate) + ' . round($wrreg[1], 2) .
                '<br />r = ' . round($wrr, 2) .
                '<br />r&sup2; = ' . round(pow($wrr, 2), 2) .
                '<br /><hr>'; 
        $s .= 'Linear Regression: (MMR) = ' . round($lwrreg[0], 2) . '(Lane Winrate) + ' . round($lwrreg[1], 2) .
                '<br />r = ' . round($lwrr, 2) .
                '<br />r&sup2; = ' . round(pow($lwrr, 2), 2) .
                '<br /><hr>';
        $avgDiff = stats_calc_amean($aDiff);
        $qHell = 0;
        $qHeaven = 0;
        $threshold = stats_calc_stdev($aMMR);
        for($i = 0; $i < $this->qPlayers; $i++) {
            if($this->Players[$i]->getcMMR() < $this->Players[$i]->getSkill() - $threshold) {
                $qHell++;
            }
            if($this->Players[$i]->getcMMR() > $this->Players[$i]->getSkill() + $threshold) {
                $qHeaven++;
            }
        }
        $s .= 'Avg Difference between Skill and MMR: ' . round($avgDiff, 2) .
                '<br />Avg Learned: ' . round($this->f_aSkillChange, 2) . 
                '<br /><span class="mouseover" title="This threshold is &sigma; (std deviation) of MMR.">ELO Hell/Heaven Threshold:</span> ' . abs(round($threshold)) . ' MMR below/above Skill.' . 
                '<br />Players in ELO Hell: ' . $qHell . ', (' . round(100 * $qHell / $this->qPlayers, 2) . '%), (' . round(10 * $qHell / $this->qPlayers, 2) . ' per game)' .
                '<br />Players in ELO Heaven: ' . $qHeaven . ', (' . round(100 * $qHeaven / $this->qPlayers, 2) . '%), (' . round(10 * $qHeaven / $this->qPlayers, 2) . ' per game)' .
                '<br /><hr>'; 
        return $s;
    }
    
    // DEPRECIATED 
    
     /*
     * DEPRECIATED v0.03
     * Populates Players array with a user-defined Player and fills w/ random Player objs.
     */
    function zzzOLD_populate($pSkill, $pTox, $startTrue) {
        $this->Players[0] = new Player(1, $pSkill, $pTox, $startTrue);
        for($i = 1; $i < $this->qPlayers; $i++) {
            $this->Players[] = new Player($i + 1, mt_rand(0, 2400), 2 * mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax(), $startTrue);
        }
        $this->s_sumSkill = $this->getSumSkill();
    }
    
    /*
     * DEPRECIATED v0.03
     * Simulates games until each player has at least qMatches games played.
     */
    function zzzOLD_simulate() {
        for($i = 0; $i < $this->qMatches / $this->qPlayers; $i++) {
            usort($this->Players, array($this, 'comparePlayerMMRs'));
            for($j = 0; $j < $this->qPlayers / 10; $j++) {
                $this->Game = new Game(array_slice($this->Players, $j * 10, 10));
                $this->Results[] = $this->Game->playGame();
            }
        }
        usort($this->Players, array($this, 'comparePlayerMMRs'));
        $this->simRun = true;
    }
}

?>
