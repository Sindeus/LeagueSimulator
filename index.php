<!-- 
LoL Matchmaking Simulator
v 0.06
Developed by Sindeus @ NA.
-->

<!DOCTYPE html>

<html>
    <head>                                                                                                                                                          
        <title>League Matchmaking Sim</title>
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
        <p>
            Interested in checking out the code or contributing?  The git is available <a href="https://github.com/Sindeus/LeagueSimulator">here</a>.
        </p>
        <form name="form" action="results.php" method="GET" enctype="multipart/form-data">
            <div class="content">
                <span class="subhead">Global Properties</span>
                <br />
                <span>Set the properties of the simulation as a whole.</span>
                <br />
                <hr>
                <span class="mouseover" title="The total number of players in the population.  Rounded to nearest multiple of 10.">Players: </span>
                <input type="text" name="players" value="100">
                <br /><br />
                <span class="mouseover" title="The number of matches played by each player in this simulation.  In Variable mode, this figure is approximate.">Matches Per Player: </span>
                <input type="text" name="matches" value="500">
                <br /><br />
                <span class="mouseover" title="Weight given to solo performance in MMR.">Solo Performance Weight in MMR: </span>
                <input type="text" name="solo" value="0"> %
                <br /><br />
                <span class="mouseover" title="The chance a player will intentionally feed.  Feeders contribute to the enemy team's victory instead of their own.">Chance of Feeder: </span>
                <input type="text" name="feeder" value="0.1"> %
                <br /><br />
                <span class="mouseover" title="The chance a player will leave or afk.  Leavers contribute nothing to the game at all.">Chance of Leaver: </span>
                <input type="text" name="leaver" value="1"> %
                <br /><br />
                <span class="mouseover" title="The chance a player will not communicate or refuse to cooperate with his team; these players make less of a positive impact or more of a negative impact.">Chance of Non-Communicative Player: </span>
                <input type="text" name="nc" value="5"> %
                <br /><br />
                <span class="mouseover" title="Allow players to learn from competitive games?">Allow Learning: </span>
                <input type="checkbox" name="learning" checked>
                <br /><br />
                <span class="mouseover" title="Bias winning 55.7% towards blue team to match actual League stats?  If no, the higher MMR team will be randomized instead of always being purple.">Bias Towards Blue Team: </span>
                <input type="checkbox" name="bias" checked>
                <br /><br />
                <span class="mouseover" title="Start each player's MMR set at their true skill?">Start With Correct MMR: </span>
                <input type="checkbox" name="start">
            </div>
            <div class="divider"></div>
            <div class="content">
                <span class="subhead">Player Properties</span>
                <br />
                Set the properties of a target player to follow and analyze.
                <br />
                <hr>
                <span class="mouseover" title="The skill of your player, expressed in his 'true' MMR from 0-2400.">True MMR (Skill): </span>
                <input type="text" name="skill" value="1200">
                <br /><br />
                <span class="mouseover" title="The disposition of your player, measured from 0-2 where 1 is exactly average chance to be toxic, 0 is no chance, and 2 is extremely high.">Toxicity: </span>
                <input type="text" name="toxicity" value="1">
                <br /><br />
                <span class="subhead">Algorithms</span>
                <br />
                Choose which algorithms to use for simulating games.
                <br />
                <hr>
                <table>
                    <tr> 
                        <td>Player Seeding</td>
                        <td>
                            <input type="radio" name="seeding" value="normal" checked> <span class="mouseover" title="True MMR follows a normal distribution about 1200 w/ stdev of 400, with hard bounds of 0 and 2400.">Normal</span>
                            <br />
                            <input type="radio" name="seeding" value="uniform"> <span class="mouseover" title="True MMR distributed uniformly between 0 and 2400.">Uniform</span>
                        </td>
                    </tr>
                    <tr>
                        <td>Matchmaking</td>
                        <td>
                            <input type="radio" name="matchmaking" value="variable" checked> <span class="mouseover" title="System prefers the most even matchups and distributes them so each player plays against an approximately even number of higher and lower skill players. Players near the top and bottom end of the spectrum play fewer matches, average players play more.">Variable</span>
                            <br />
                            <input type="radio" name="matchmaking" value="block"> <span class="mouseover" title="Every player plays an even number of matches, arranged by selecting groups of ten players in blocks starting from the bottom up.">Fixed Block</span>
                        </td>
                    </tr>
                    <tr>
                        <td>Scoring</td>
                        <td>
                            <input type="radio" name="scoring" value="complex" checked> <span class="mouseover" title="Outcome of a match determined by a combination of base skill, lane matchups, and misplay potential.">Complex</span>
                            <br />
                            <input type="radio" name="scoring" value="simple"> <span class="mouseover" title="Outcome of a match based purely on team skill.">Simple</span>
                        </td>
                    </tr>
                </table>
            </div>
            <div>
                <p>
                    <input type="submit" value="Submit">
                </p>
            </div>
        </form>
    </body>
</html>
