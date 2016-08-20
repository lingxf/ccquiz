<html>
<title>Do Quiz</title>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
<meta http-equiv="Content-Language" content="zh-CN" /> 
<link rel="stylesheet" type="text/css" href="report.css" media="screen12"/>
<!--
	A php that could do a quiz test based on a database
	by Ling Xiaofeng <lingxf@gmail.com>
-->
<style type="text/css">
@media screen {
.print_ignore {
    display: none;
}

body, table, th, td {
    font-size:         12pt;
}

table, th, td {
    border-width:      1px;
    border-color:      #0000f0;
    border-style:      solid;
}
th, td {
    padding:           0.2em;
}
}
</style>
<body onload="show_filter()">
<?php
/*
	weekly report and manual tracking system
	copyright Xiaofeng(Daniel) Ling<xling@qualcomm.com>, 2012, Aug.
*/


#$link=mysql_connect("10.233.140.115:3306","weekly","week2pass");
#$link=mysql_connect("localhost","exam","exam2pass");
#$db=mysql_select_db("exam",$link);
$link=mysql_connect("localhost","uu185143","exam2pass");
$db=mysql_select_db("uu185143",$link);

global $login_id;	
global $show_techarea_case;

session_name("quiz");
session_start();

$sid=session_id();
$role = 0;
$usertb = "usertb";

if(isset($_POST['login'])){
	if(isset($_POST['user'])){
	    $login_id=$_POST['user'];
	    if(isset($_POST['password'])) $password=$_POST['password'];
	    $ret = check_passwd($login_id, $password);
	    if($ret == 1){
	        print("No user $login_id exist");
	        unset($_SESSION['user']); 
	    }else if($ret == 2){
	        print("wrong password");
	        unset($_SESSION['user']);
	    }else{
			$_SESSION = array();
			session_destroy();
			session_name("quiz");
			session_start();
	        $_SESSION['user'] = $login_id;
			$role = 1;
		}
	}
}else if(isset($_POST['register'])){
    header("Location: user_register.php");
    exit;
}

if(isset($_SESSION['user'])){
	$login_id=$_SESSION['user'];
	$role = 1;
}else{
	$login_id = "NoLogin";
	$_SESSION['quiz_id'] = 1000;
}

$action="init";
if(isset($_GET['action']))$action=$_GET['action'];
if($action == "logout"){
	$_SESSION = array();
	session_destroy();
	print "You are logout now";
	sleep(5);
    header("Location: do_quiz.php");
}else if($action == 'login'){
    header("Location: user_login.php");
}

if($role == 1)
	print "<a href=\"do_quiz.php\">Home</a> &nbsp;&nbsp;Login:$login_id &nbsp;&nbsp;<a href=\"do_quiz.php?action=logout\">Logout</a>";
else
	print "<a href=\"do_quiz.php\">Home</a> &nbsp;&nbsp;<a href=\"do_quiz.php?action=login\">Login</a>";

if($login_id == 'xling'){
	print "&nbsp;&nbsp;<a href=\"do_quiz.php?action=list_all\">List</a>";
	print "&nbsp;&nbsp;<a href=\"do_quiz.php?action=show_answer\">Answer</a>";
	print "&nbsp;&nbsp;<a href=\"do_quiz.php?action=show_wrong_test\">Show Wrong</a>";
	print "&nbsp;&nbsp;<a href=\"do_quiz.php?action=update_wrong\">Update Wrong</a>";
}
print("<br>");

if(isset($_GET['quiz_id'])){ 
	$quiz_id=$_GET['quiz_id'];
	$_SESSION['quiz_id'] = $quiz_id;
}


if(!isset($_SESSION['item_perpage'])) $_SESSION['item_perpage'] = 10;
if(!isset($_SESSION['start'])) $_SESSION['start'] = 1;
if(!isset($_SESSION['end'])) $_SESSION['end'] = $_SESSION['start'] + $_SESSION['item_perpage'] - 1;
if(!isset($_SESSION['paper_id'])) $_SESSION['paper_id'] = 0;
if(!isset($_SESSION['quiz_id'])) $_SESSION['quiz_id'] = -1;

if(isset($_SESSION['quiz_id'])) $quiz_id=$_SESSION['quiz_id'];

$item_perpage = $_SESSION['item_perpage'];
$start = $_SESSION['start'];
$end = $_SESSION['end'];
$set_standard_answer = false;

$sql = " select * from quiz where user = '$login_id' order by start desc";
$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
if($row=mysql_fetch_array($res)){
	$action=$action;
	if($quiz_id == -1){
		$quiz_id = $row['quiz_id'];
		$_SESSION['quiz_id'] = $quiz_id;
	}
}else{
	print "Start new test";
	$action="new";
}

$paper_id = $_SESSION['paper_id'];
if(isset($_POST['submit'])) $action="submit";
if(isset($_POST['prev'])) $action="prev";
if(isset($_POST['next']))$action="next";
if(isset($_POST['begin'])) $action="begin";
if(isset($_POST['end']))$action="end";
if(isset($_POST['show_answer']))$action="show_answer";
if(isset($_POST['list_all']))$action="list_all";

switch($action){
	case "copy_answer":
		copy_answer($paper_id);
		break;
	case "list_all":
		list_quiz('all');
		break;
	case "show_wrong_test":
		show_wrong_test(2);
		break;
	case "update_wrong":
		update_wrong();
		break;
	case "show_answer":
		show_answer($paper_id);
		break;
	case "show_wrong":
		show_wrong();
		break;
    case "init":
		if($role > 0)
			list_quiz($login_id);
        show_test($login_id, $start, $end);
        break;
    case "new":
		new_test($login_id);
		$start = 1;
		$end = $start + $item_perpage;
		$_SESSION['start'] = $start;
		$_SESSION['end'] = $end;
		list_quiz($login_id);
		show_test($login_id, $start, $end);
		return;
        break;
    case "clone":
		clone_test($login_id, $quiz_id);
		$start = 1;
		$end = $start + $item_perpage;
		$_SESSION['start'] = $start;
		$_SESSION['end'] = $end;
		list_quiz($login_id);
		show_test($login_id, $start, $end);
        break;
    case "redo":
		$start = 1;
		$end = $start + $item_perpage;
		$_SESSION['start'] = $start;
		$_SESSION['end'] = $end;
		list_quiz($login_id);
		set_quiz_attribute($quiz_id, 'status', 2);
		show_test($login_id, $start, $end);
        break;
    case "review":
		$start = 1;
		$end = $start + $item_perpage;
		$_SESSION['start'] = $start;
		$_SESSION['end'] = $end;
		list_quiz($login_id);
		set_quiz_attribute($quiz_id, 'status', 3);
		show_test($login_id, $start, $end);
        break;

    case "next":
        save_answer($start, $end);
        $start += $item_perpage;
        $_SESSION['start'] = $start;
        $end = $start + $item_perpage - 1;
        $_SESSION['end'] = $end;
		list_quiz($login_id);
        show_test($login_id, $start, $end);
        break;
    case "begin":
        save_answer($start, $end);
		list_quiz($login_id);
        $start = 1;
        $_SESSION['start'] = $start;
        $end = $start + $item_perpage - 1;
        $_SESSION['end'] = $end;
        show_test($login_id, $start, $end);
        break;
    case "end":
        save_answer($start, $end);
        $end = get_total_items($paper_id);
		$start = $end + 1 - $item_perpage;
        $_SESSION['start'] = $start;
        $_SESSION['end'] = $end;
		list_quiz($login_id);
        show_test($login_id, $start, $end);
        break;

    case "prev":
        save_answer($start, $end);
        $start -= $item_perpage;
        if($start < 1)
            $start = 1;
        $_SESSION['start'] = $start;
        $end = $start + $item_perpage - 1;
        $_SESSION['end'] = $end;
		list_quiz($login_id);
        show_test($login_id, $start, $end);
        break;
    case "submit":
        save_answer($start, $end);
        show_score($login_id);
		list_quiz($login_id);
}

function show_answer($paper_id)
{ 

	$sql = "select t1.item_order, t2.answer from test_paper t1, item_bank t2 where t1.item_id = t2.item_id and paper_id = $paper_id";
	$res1=mysql_query($sql) or die("Invalid query:".$sql.mysql_error());
	while($row1=mysql_fetch_array($res1)) {
        $item_order = $row1['item_order'];
		$answer = $row1['answer'];

        print("$item_order.&nbsp; ");
        for($i=1; $i<=4; $i++){
            $chid = chr(ord('A') + $i - 1);
            $on = $answer & 1 << ($i - 1);
            if($on)
                print "$chid";
        }
        print("<br>");
    }

}

function copy_answer($paper_id)
{ 
	$sheet_id = 0;
	$sql = "select t1.item_order, t2.answer from test_paper t1, item_bank t2 where t1.item_id = t2.item_id and paper_id = $paper_id";
	$res=mysql_query($sql) or die("Invalid query:".$sql.mysql_error());
	while($row1=mysql_fetch_array($res)) {
        $item_order = $row1['item_order'];
		$answer = $row1['answer'];
		$sql1 = "update answer_sheet set answer=$answer where sheet_id=$sheet_id and item_order = $item_order";
		$res1=mysql_query($sql1) or die("Invalid query:".$sql1.mysql_error());
    }

}

function check_passwd($login_id, $login_passwd)
{
	global $usertb;
	$sql1="SELECT * FROM $usertb WHERE user_id= '$login_id';";
	$res1=mysql_query($sql1) or die("Query Error:" . mysql_error());
	$row1=mysql_fetch_array($res1);
	if(!$row1)
		return 1;
	if($row1['password'] == "")
		return 0;
    if($row1['password'] == $login_passwd)
        return 0;
	$sql1="SELECT * FROM $usertb WHERE user_id = '$login_id' and password=ENCRYPT('$login_passwd', 'ab');";
	$res1=mysql_query($sql1) or die("Query Error:" . mysql_error());
	$row1=mysql_fetch_array($res1);
	if(!$row1)
		return 2;
//	$passwd = crypt($login_passwd);
	return 0;
}

function print_tdlist($tdlist)
{
	foreach($tdlist as $tdc)
	{
		print("<td>$tdc</td>"); 
	}
}

function update_wrong()
{
	for($i = 1; $i <= 55; $i++){
    	$sql = " select count(*) from answer_sheet t1, item_bank t2 where t1.answer !=0 and (t1.answer != t2.answer and t1.item_order = $i and t1.item_order = t2.item_id )";
		$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
		while($row=mysql_fetch_array($res)){
			$count = $row[0];
			break;
		}
		$sql = " update test_paper set `wrong`='$count' where `paper_id` = 0 and `item_order` = $i";
		$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	}
	print "Update done!";
}

function show_wrong()
{
	$paper = 0;
	$sql = " select item_order, wrong from test_paper where paper_id = $paper and wrong > 0 order by `wrong` desc";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	while($row=mysql_fetch_array($res)){
		print "${row[0]}: ${row[1]} <br>";
	}
}

function show_wrong_test($max_wrong)
{
    print('<form enctype="multipart/form-data" action="do_quiz.php" method="POST">');
	$paper_id = $_SESSION['paper_id'];
    $quiz_id = $_SESSION['quiz_id'];
;

	$sql = "select t2.item_id, item_order, subject, t2.answer, wrong, choice1, choice2, choice3, choice4 from test_paper t1, item_bank t2 where t1.item_id = t2.item_id and t1.paper_id = $paper_id and t1.wrong > $max_wrong order by t1.wrong desc";
	$res1=mysql_query($sql) or die("Invalid query:".$sql.mysql_error());
	while($row1=mysql_fetch_array($res1)) {
        $item_id = $row1['item_id'];
        $item_order = $row1['item_order'];
        $wrong = $row1['wrong'];
        $title = $row1['subject'];
		$title = str_replace("\n", '<br>', $title);
		$c_answer = $row1['answer'];
        print("$item_order.$title     ($wrong)<br>");
        for($i=1; $i<=4; $i++){
            $chid = chr(ord('A') + $i - 1);
            $chname = "answer_" .$item_order.'_'.$i;
            $c_on = $c_answer & 1 << ($i - 1);
            if($c_on)
                $checked = "checked";
            else
                $checked = "";
			if($c_on){
				print("<span style='font-family:Symbol;background:green;color:yellow'>");
            	print("$chid) ${row1['choice'.$i]} <input type=\"checkbox\" $disabled name=\"$chname\" $checked /><br>");
				print("</span>");
			}else
            	print("$chid) ${row1['choice'.$i]} <input type=\"checkbox\" $disabled name=\"$chname\" $checked /><br>");
        }
        print("<br>");
    }

    print("<input type=\"submit\" $disabled name=\"submit\" value=\"Submit\" />"); 
	if($login_id == 'xling'){
   		print("<input type=\"submit\" name=\"show_answer\" value=\"Answer\" />"); 
   		print("<input type=\"submit\" name=\"list_all\" value=\"List\" />"); 
	}
    print('</form>');
}

function list_quiz($login_id)
{
	global $role;
	if($role == 0)
		return;
	print('<table border=1 bordercolor="#0000f0", cellspacing="0" cellpadding="0" style="padding:0.2em;border-color:#0000f0;border-style:solid; width: 800px;background: none repeat scroll 0% 0% #e0e0f5;font-size:12pt;border-collapse:collapse;border-spacing:1;table-layout:auto">');
	$new = "<a href=\"do_quiz.php?user=$login_id&action=new\">new</a>";
	print_tdlist(array('user','quiz id', 'start','end','duration','score', 'status', $new));
	if($login_id == 'all')
		$sql = " select * from quiz";
	else
		$sql = " select * from quiz where user = '$login_id'";

	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	while($row=mysql_fetch_array($res)){
		$quiz_id = $row['quiz_id']; 
		$user = $row['user'];
		$status = $row['status'];
		$done = $row['done'];
		$status_text = $status & 1 == 1 ? "finish" : $done;
		$quiz = "<a href=\"do_quiz.php?user=$user&quiz_id=$quiz_id\">$quiz_id</a>";
		$clone = "<a href=\"do_quiz.php?user=$user&quiz_id=$quiz_id&action=clone\">redo</a>";
		if($status & 1)
			$clone_text = $clone;
		else
			$clone_text = "";
		$score = "${row['score']}/$done";
		print("<tr>");
		print_tdlist(array($user, $quiz, $row['start'],$row['end'],$row['duration'], $score, $status_text, $clone_text));
		print("</tr>");
	}
	print("</table>");
}

function get_next_id($id_name)
{
	$sql = " select next_id from mg where id_name = '$id_name'";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	if($row=mysql_fetch_array($res)){
		$id = $row[0];
		$id++;
		$sql = "update mg set next_id= $id where id_name = '$id_name' ";
		$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
		return $id;
	}
}

function clone_test($login_id, $quiz_id)
{
	$quiz_id_old = $quiz_id;
	$quiz_id = get_next_id('quiz_id');
	$time = time();
	$time_start = strftime("%Y-%m-%d %H:%M:%S", $time);
    $_SESSION['quiz_id'] = $quiz_id;

	$sql = " select * from quiz where `quiz_id` = $quiz_id_old";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	if($row=mysql_fetch_array($res)){
		$paper_id = $row['paper_id'];
		$user = $row['user'];
	}else{
		print "clone error, no $quiz_id exist";
		return;
	}

	$sql = " insert into quiz set `user`='$user', `quiz_id` = $quiz_id, `paper_id` = $paper_id, start = '$time_start', status=2";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());

	$sql = " select * from answer_sheet where `sheet_id` = $quiz_id_old";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
 	while($row=mysql_fetch_array($res)){
		$answer = $row['answer'];
		$item_order = $row['item_order'];
		$sql2 = "insert into answer_sheet set sheet_id= $quiz_id, item_order=$item_order, answer=$answer";
		$res2 = mysql_query($sql2) or die("Invalid query:" . $sql2 . mysql_error());
	}
    $_SESSION['quiz_id'] = $quiz_id;
    $_SESSION['paper_id'] = $paper_id;
}

function new_test($login_id)
{
	$quiz_id = get_next_id('quiz_id');
    $paper_id = $_SESSION['paper_id'];
	$time = time();
	$time_start = strftime("%Y-%m-%d %H:%M:%S", $time);
    $_SESSION['quiz_id'] = $quiz_id;
	$sql = " insert into quiz set `user`='$login_id', `quiz_id` = $quiz_id, `paper_id` = $paper_id, start = '$time_start'";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
}

function save_answer($start, $end)
{
	global $set_standard_answer, $login_id;
    $quiz_id = $_SESSION['quiz_id'];
	if(get_quiz_attribute($quiz_id, 'status') & 1 == 1)
		return;
    for($item_order = $start; $item_order <= $end; $item_order++){
        $answer = 0;
        for ($i = 1; $i < 8; $i++){
            if(isset($_POST['answer_'.$item_order.'_'.$i])){
                #print $chid = chr(ord('A') + $i - 1);
                $answer |= 1 << $i - 1;       
            }
        }
	    $sql1 = " update answer_sheet set `answer` = $answer where `sheet_id` = $quiz_id and `item_order` = $item_order";
	    $sql_a = " update item_bank set `answer` = $answer where `item_id` = $item_order";
	    $sql2 = " insert into answer_sheet set `sheet_id`=$quiz_id, `item_order` = $item_order, `answer` = $answer ";
		if($set_standard_answer)
	   		$res1 = mysql_query($sql_a) or die("Invalid query:" . $sql1 . mysql_error());

	    $res1 = mysql_query($sql1) or die("Invalid query:" . $sql1 . mysql_error());
	    $rs = mysql_info();
        $match = 0;
	    if(preg_match("/matched:\s*(\d+)/", $rs, $matches)){
		        $match = $matches[1];
	    }
	    if($match == 0){
	    	$res1 = mysql_query($sql2);
	    	if(!$res1){
	    		if(mysql_errno() != 1062)
	    		 	die("Invalid query:" . $sql2 . mysql_error());
	    		else{
	    			print("Duplicate item:" . $item_order . "<br/>");
				}
	    	}
        }
    }
	$sql = "select count(*) from answer_sheet where sheet_id=$quiz_id and answer!=0";
	$res = mysql_query($sql) or die("Invalid query:".$sql.mysql_error());
	if($row = mysql_fetch_array($res)){
		$ct = $row[0];
		set_quiz_attribute($quiz_id, 'done', $ct);
    }
	update_score($login_id);

}

function get_total_items($paper_id)
{
	$total = 0;
    $sql = " select count(*) from test_paper where `paper_id` = $paper_id";
	$res = mysql_query($sql) or die("Invalid query:" .$sql. mysql_error());
	if($row = mysql_fetch_array($res))
		$total = $row[0];
	else
		print "no paper $paper_id exist";
	return $total;
}

function show_score($login_id)
{
    $quiz_id = $_SESSION['quiz_id'];
    $paper_id = $_SESSION['paper_id'];
	$correct = 0;

	$time = time();
	$time_end = strftime("%Y-%m-%d %H:%M:%S", $time);
	$total = get_total_items($paper_id);
	update_score($login_id);
	$correct = get_quiz_attribute($quiz_id, 'score');
    print "$login_id's score:Total:$total Correct:$correct";
	$sql = "update quiz set end='$time_end', status=1 where quiz_id=$quiz_id";
	$res = mysql_query($sql) or die("Invalid query:" .$sql. mysql_error());
	$sql = "update quiz set duration=(end-start) where quiz_id=$quiz_id";
	$res = mysql_query($sql) or die("Invalid query:" .$sql. mysql_error());
}

function update_score($login_id)
{
    $quiz_id = $_SESSION['quiz_id'];
    $paper_id = $_SESSION['paper_id'];
    $sql = " select count(*) from answer_sheet t1, item_bank t2, test_paper t3 where t1.`sheet_id` = $quiz_id and t1.item_order = t3.item_order and t3.item_id = t2.item_id and t1.answer = t2.answer";
	$res = mysql_query($sql) or die("Invalid query:" .$sql. mysql_error());
	if($row = mysql_fetch_array($res))
		$correct = $row[0];

	$sql = "update answer_sheet set score= $correct where sheet_id=$quiz_id";
	$res = mysql_query($sql) or die("Invalid query:" .$sql. mysql_error());
	$sql = "update quiz set score= $correct where quiz_id=$quiz_id";
	$res = mysql_query($sql) or die("Invalid query:" .$sql. mysql_error());
}

function get_quiz_attribute($quiz_id, $attr)
{
	$sql = "select * from quiz where quiz_id=$quiz_id";
	$res = mysql_query($sql) or die("Invalid query:".$sql.mysql_error());
	if($row = mysql_fetch_array($res)){
		$status = $row[$attr];
		return $status;
    }
	return -1;
}

function set_quiz_attribute($quiz_id, $attr, $value)
{
	$sql = "update quiz set `$attr` = $value where quiz_id=$quiz_id";
	$res = mysql_query($sql) or die("Invalid query:".$sql.mysql_error());
	if(mysql_affected_rows() == 1){
		return true;
    }
	return false;
}

function show_test($login_id, $start, $end)
{
	global $role;
    print('<form enctype="multipart/form-data" action="do_quiz.php" method="POST">');
	$paper_id = $_SESSION['paper_id'];
    $quiz_id = $_SESSION['quiz_id'];
	print("Test Begin ===== $login_id quiz:$quiz_id <br>");
	$sql = "select * from test_paper where `item_order` > $end and `paper_id` = $paper_id";
    $hasmore = false;
    $hasprev = false;
	$res1 = mysql_query($sql) or die("Invalid query:" .$sql. mysql_error());
	if($row1 = mysql_fetch_array($res1)){
        $hasmore = true;
    }

	$sql = "select * from test_paper where `item_order`<$start  and paper_id=$paper_id";
	$res1 = mysql_query($sql) or die("Invalid query:".$sql.mysql_error());
	if($row1 = mysql_fetch_array($res1)){
        $hasprev = true;
    }

	$disabled = "";
	$status = get_quiz_attribute($quiz_id, 'status');
	if($status & 1 == 1)
		$disabled = "disabled";

    $ans_array = array();
	$sql = "select * from answer_sheet where sheet_id = $quiz_id and `item_order` >= $start and `item_order` <= $end";
	$res1=mysql_query($sql) or die("Invalid query:" .$sql. mysql_error());
	while($row1=mysql_fetch_array($res1)) {
        $order= $row1['item_order'];
        $answer = $row1['answer'];
        $ans_array["$order"] = $answer;
    }

	$sql = "select t2.item_id, item_order, subject, t2.answer, choice1, choice2, choice3, choice4 from test_paper t1, item_bank t2 where t1.item_id = t2.item_id and item_order >= $start and item_order <= $end";
	$res1=mysql_query($sql) or die("Invalid query:".$sql.mysql_error());
	while($row1=mysql_fetch_array($res1)) {
        $item_id = $row1['item_id'];
        $item_order = $row1['item_order'];
        $title = $row1['subject'];
		$title = str_replace("\n", '<br>', $title);
		$c_answer = $row1['answer'];
        //print("<input type=\"hidden\" name=\"id_start\" value=\"$item_id\" />");
        //print("<input type=\"hidden\" name=\"id_end\" value=\"$item_id\" />");
        $answer = 0;
        if(isset($ans_array["$item_order"]))
            $answer = $ans_array["$item_order"];
		if($c_answer != $answer && ($status != 0))
			print("<span style='font-family:Symbol;color:red'>$item_order.$title</span><br>");
		else
        	print("$item_order.$title<br>");
        #print("$item_id:$answer");
        for($i=1; $i<=4; $i++){
            $chid = chr(ord('A') + $i - 1);
            $chname = "answer_" .$item_order.'_'.$i;
            $on = $answer & 1 << ($i - 1);
            $c_on = $c_answer & 1 << ($i - 1);
            if($on)
                $checked = "checked";
            else
                $checked = "";
			if($c_on && $status == 3){
				print("<span style='font-family:Symbol;background:green;color:yellow'>");
            	print("$chid) ${row1['choice'.$i]} <input type=\"checkbox\" $disabled name=\"$chname\" $checked /><br>");
				print("</span>");
			}else
            	print("$chid) ${row1['choice'.$i]} <input type=\"checkbox\" $disabled name=\"$chname\" $checked /><br>");
        }
        print("<br>");
    }
	if($role == 0)
		$disabled = 'disabled';
    print('<input type="submit"'); print(' name="begin" value="Begin" />   ');
    print('<input type="submit"'); if(!$hasprev) print(" disabled "); print(' name="prev" value="Prev" />   ');
    print('<input type="submit"'); if(!$hasmore) print(" disabled "); print(' name="next" value="Next" />   ');
    print('<input type="submit"');  print(' name="end" value="End" />   ');
    print("<input type=\"submit\" $disabled name=\"submit\" value=\"Submit\" />"); 
	if($login_id == 'xling'){
   		print("<input type=\"submit\" name=\"show_answer\" value=\"Answer\" />"); 
   		print("<input type=\"submit\" name=\"list_all\" value=\"List\" />"); 
	}
    print('</form>');
}

?>


</body>
</html>
