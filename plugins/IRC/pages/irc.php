<?php
//WorldWeb XD: IRC Page
//Access: Logged-in users
//Todo: Make template

if(!$loguserid)
	Kill(__("You need to be logged in in order to access this page"));

$title = __("IRC Page");
makeCrumbs([actionlink('irc') => __("IRC Page")]);

// The network hostname of your IRC channel
$net = Settings::pluginget('ircserver');
// The actual name of the channel without the beginning #
$chan = Settings::pluginget('ircchannel');
// The name prefix for anyone joining
$bad = ["~", "&", "@", "?", "!", ".", ",", "=", "+", "%", "*"];
$handle = str_replace(" ", "", $loguser['name']);
$handle = str_replace($badchars, "_", $handle);
// KiwiIRC Client
$kiwilink = "https://kiwiirc.com/client/".$net."/?nick=".$handle."?#".$chan;
$kiwiclient = "<iframe src=\"".$kiwilink."\" style=\"border:0; width:100%; height:525px;\"><\iframe>";
?>
<table class="outline">
<tr class="header0"><th>
    Information
</th></tr>
<tr class="cell0 center"><td>
This is the IRC page, for chatting with other users on a channel. If you have your own client, go ahead and connect with this information:<br>
Server: <?php echo $net; ?><br>
Channel: #<?php echo $chan; ?><br>
Port: 6667<br><p>If your IRC client allows irc:// links, click <a href="irc://<?php echo $net; ?>/#<?php echo $chan; ?>">here</a> to join our channel!</p>
</td></tr>
</table><br><br>
<table class="outline"><tr class="header0"><th>IRC Client</th></tr>
<tr class="cell0 center"><td><?php echo $kiwiclient; ?></td></tr></table>
<br><br><table class="outline"><tr class="header0"><th>What Is IRC?</th></tr>
<tr class="cell0 center"><td>
	IRC - Internet Relay Chat is a method to broadcast and receive live, synchronous, messages. 
	There are hundreds of IRC channels (discussion areas) around the world, hosted on servers, on which people type their messages to others on the same channel interested in the same subject. 
	There are client IRC programs which provide graphical interfaces which make it easier for people log on and access active channels and send and receive the messages.<br><br>
	
click <?php actionlink('ircclients') => __("here") ?> for a list of clients for most platforms</td></tr></table>
