<?php

$title = __("Delete the user");

makeCrumbs([actionlink('deleteuser') => __("Delete User")]);

/*	Make 16 checks:
	1) If the user can edit his own profile: you don't want to be a hypocrite. This one is checked twice, with one of them being a message, while the other is displaying the page.
	2) If the user can edit other profiles. This is also checked twice, with one of them displaying the page and the other is a message.
	3) If the user has the delete permission set to on. 
	4) If the user has the ban permission set to on: You need to ban the user in order to delete, so it logical that you need that permission on. This is checked twice, one for the message and one to display the delete page. 
	5) If the user trying to delete another user is even logged in.
	6) If the user being deleted even exists.
	7) If the user trying to delete is banned and all the nessesary permissions are deactivated (it should normally be deactivated, but who knows) :P: Just because you're banned doesn't mean you have to go nuts and delete users. It'll be a strongly worded message to put some sence into them. Not like the end user will get some sence due to that user getting banned.
	8) If the user trying to delete has a lower rank than the one being deleted. This is checked 4 times, twice being the message, while the other two is displaying the nuke page.

	Yes, I know, its a lot of checks, but you have to be secure. Otherwise, you'll have a destroyed board: The delete feature is very powerfull. If ever someone got a hold of the nuke plugin pre-security updates with the nuke permission, your board can be done for, especially considering that recalc is only allowed to be ran by owner. (And no, I'm not going to make it being ran by permissions, at least, not right now.) I'm not sure how MyBB does it (and I don't want to know; it doesn't interest me.).
	*/

$uid = (int)$http->get("id");

$user = fetch(query("select * from {users} where id={0}", $uid));

$userdeleteperms = HasPermission('admin.editusers') && HasPermission('admin.userdelete') && HasPermission('user.editprofile') && HasPermission('admin.banusers');

if(!$loguserid)
	Kill(__("You must be logged in to delete a profile."));

if (!$userdeleteperms && $loguser['banned'])
	Kill(__("You may not use the user nuke due to you being banned. Look, just because your banned doesn't mean you have to ruin it for everyone else who's in your banned user club. Try improving, instead of trying to get revenge on the staff like an immature freak."));

if (!HasPermission('user.editprofile'))
	Kill(__("Don't be such a hypocrite. Before you decide to delete other users, how about check yourself?"));

if (!HasPermission('admin.editusers'))
	Kill(__("The deleting function is part of the editing other users function."));

if (!HasPermission('admin.banusers'))
	Kill(__("How do you expect to delete someone while you can't even ban them?"));

if(!$user)
	Kill(__("You cannot delete a user that doesn't exist."));

if ($targetrank >= $myrank || $myrank =< $targetrank)
	Kill(__("You may not delete a user whose level is equal to or above yours."));

if($user['tempbantime'])
	Kill(__('You can\'t delete a temporarely banned user.'))

if ($userdeleteperms && $myrank >= $targetrank && $targetrank =< $myrank) {
	$passwordFailed = false;

	if(isset($http->post("currpassword"))) {
		$sha = doHash($http->post('currpassword').SALT.$loguser['pss']);
		if(isValidPassword($http->post("currpassword"), $loguser['password'], $loguserid) || $loguser['password'] == $sha) {

			//Converts Deleter's password to the new hashing method if its old (Unrelated to the nuke, but it'll be better to do this...)
			if ($loguser['password'] == $sha) {
				$password = password_hash($http->post("currpassword"), PASSWORD_DEFAULT);

				Query("UPDATE {users} SET password = {0} WHERE id={1}", $loguser['password'], $loguserid);
			}

			//Delete posts from threads by user
			query("delete pt from {posts_text} pt
					left join {posts} p on pt.pid = p.id
					left join {threads} t on p.thread = t.id
					where t.user={0}", $uid);
			query("delete p from {posts} p
					left join {threads} t on p.thread = t.id
					where t.user={0}", $uid);

			//Delete posts by user			
			query("delete pt from {posts_text} pt
					left join {posts} p on pt.pid = p.id
					where p.user={0}", $uid);
			query("delete p from {posts} p
					where p.user={0}", $uid);

			//Delete threads by user
			query("delete t from {threads} t
					where t.user={0}", $uid);

			//Delete usercomments by user or to user
			query("delete from {usercomments}
					where uid={0} or cid={0}", $uid);

			//Delete the PM's sent to the user or sent by the user
			query("delete pt from {pmsgs_text} pt
				left join {pmsgs} p on pt.pid = p.id
				where p.userfrom={0} or p.userto={0}", $uid);
			query("delete p from {pmsgs} p
				where p.userfrom={0} or p.userto={0}", $uid);

			//Delete all the badges of the user.
			Query("delete from {badges} where owner = {0}", $uid);

			//Delete THE USER ITSELF
			query("delete from {users}
					where id={0}", $uid);

			//and then IP BAN HIM
			query("insert into {ipbans} (ip, reason, date) 
					values ({0}, {1}, 0)
					on duplicate key update ip=ip", $user["lastip"], "Deleting ".$user["name"]);

			//Log that the user is deleted: Just a safety check if an admin wants to know what happend to that user, and not make the user dissapear without a trace. It also now displays his ID (In case the delete function didn't delete something and an account has some problems, you know if its linked or not) and who nuked him.
			Report("[b]".$loguser['name']."[/] successfully deleted ".$user["name"]." (#".$uid.").");

			echo "User deleted!<br/>";
			echo "You will need to ", actionLinkTag("Recalculate statistics now", "recalc");

			throw new KillException();
		} else
			$passwordFailed = true;
	}

	if($passwordFailed) {
		Report("[b]".$loguser['name']."[/] tried to delete ".$user["name"]." (#".$uid.").");
		Alert("Invalid password. Please try again.");
	}

	echo "
	<form name=\"confirmform\" action=\"".actionLink("deleteuser", $uid)."\" method=\"post\" onsubmit=\"actionlogin.disabled = true; return true;\">
		<table class=\"outline margin width50\">
			<tr class=\"header0\">
				<th colspan=\"2\">
					".__("Delete the user!!")."
				</th>
			</tr>
			<tr>
				<td class=\"cell2\">
				</td>
				<td class=\"cell0\">
					".__("WARNING: This will IP-ban the user, and permanently and irreversibly delete the user itself and all his posts, threads, private messages, and profile comments. This user will be gone forever, as if he never existed.")."
					<br/><br/>
					".__("Please enter your password to confirm.")."
				</td>
			</tr>
			<tr>
				<td class=\"cell2\">
					<label for=\"currpassword\">".__("Password")."</label>
				</td>
				<td class=\"cell1\">
					<input type=\"password\" id=\"currpassword\" name=\"currpassword\" size=\"13\" maxlength=\"32\" />
				</td>
			</tr>
			<tr class=\"cell2\">
				<td></td>
				<td>
					<input type=\"submit\" name=\"actionlogin\" value=\"".__("Delete the user!!")."\" />
				</td>
			</tr>
		</table>
	</form>";
} else 
	Kill(__("You may not use the user delete function."));

function isValidPassword($password, $hash, $uid) {
	if (!password_verify($password, $hash))
		return false;

	if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
		$hash = password_hash($password, PASSWORD_DEFAULT);

		Query('UPDATE {users} SET password = {0} WHERE id = {1}', $hash, $uid);
	}

	return true;
}