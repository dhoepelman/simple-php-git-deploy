<?php
// WARNING: this is WIP and currently hardcoded to my specific sutation. DO NOT USE THIS IN PRODUCTION
require_once str_replace("remove","deploy", basename(__FILE__, '.php').'-config.php');
if (!isset($_GET['sat']) || $_GET['sat'] !== SECRET_ACCESS_TOKEN || SECRET_ACCESS_TOKEN === 'BetterChangeMeNowOrSufferTheConsequences') {
	header('HTTP/1.0 403 Forbidden');
}

/**
* Execute a command and print the output
* @param string $command the command to be executed
* @param string $error_recovery_commands Command to be executed on error
* @param boolean $return_output whether to return output as a string
* @return (boolean|string[]) true or output as string array depending on $return_output when command was succesfully executed, false on error
*/
function execute_command($command, $error_recovery_command = "", $return_output = false) {
	set_time_limit(TIME_LIMIT); // Reset the time limit for each command
	if (file_exists(TMP_DIR) && is_dir(TMP_DIR)) {
		chdir(TMP_DIR); // Ensure that we're in the right directory
	}
	$output = array();
	exec($command.' 2>&1', $output, $return_code); // Execute the command
	// Output the result
	printf(
        "    <span class=\"prompt\">$</span> <span class=\"command\">%s</span>\n\n"
        , htmlentities(trim($command))
    );
    
    if(!empty($output)) {
        printf("<div class=\"output\">%s\n</div>\n"
        , htmlentities(implode("\n",array_map("trim",$output)))
        );
    }
	flush(); // Try to output everything as it happens

	// Error handling and cleanup
	if ($return_code !== 0) {
		printf('
	<div class="error">
	Error encountered!
	Stopping the script to prevent possible data loss.
	CHECK THE DATA IN YOUR TARGET DIR!
	</div>
	'
		);
		if ($error_recovery_command != "") {
			$tmp = shell_exec($error_recovery_command);
			printf('


	Cleaning up temporary files ...

	<span class="prompt">$</span> <span class="command">%s</span>
	<div class="output">%s</div>
	'
			, htmlentities(trim($error_recovery_command))
			, htmlentities(trim($tmp))
			);
		}
		error_log(sprintf(
		'Deployment error! %s'
		, __FILE__
		));
		return false;
	}else {
        if($return_output) {
            return $output;
        }else {
            return true;
        }
	}
}
?>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="robots" content="noindex">
	<title>Simple PHP Git deploy script</title>
	<style>
body { padding: 0 1em; background: #222; color: #fff; }
h2, .error { color: #c33; }
.prompt { color: #6be234; }
.command { color: #729fcf; }
.output { color: #999; }
.output_variable { color: #ffcc11;}
	</style>
</head>
<body>
<?php
if (!isset($_GET['sat']) || $_GET['sat'] !== SECRET_ACCESS_TOKEN) {
	die('<h2>ACCESS DENIED!</h2>');
}
?>
<pre>
<?php
// Make sure the local repository is available and up-to-date
if (!is_dir(TMP_DIR)) {
	// Clone the repository into the TMP_DIR
	if(execute_command(sprintf(
		'git clone --depth=1 %s %s'
		, REMOTE_REPOSITORY
		, TMP_DIR
	)) === false) {
        die(); // Error will be displayed in execute_command
    }
} else {
	// TMP_DIR exists and hopefully already contains the correct remote origin
	// so we'll fetch the changes and reset so checkouts don't give errors
	if(execute_command(sprintf(
		'git --git-dir="%s.git" --work-tree="%s" fetch -p origin'
		, TMP_DIR
		, TMP_DIR
	)) === false) {
        die(); // Error will be displayed in execute_command
    }
	if(!execute_command(sprintf(
		'git --git-dir="%s.git" --work-tree="%s" reset --hard FETCH_HEAD'
		, TMP_DIR
		, TMP_DIR
	))) {
        die(); // Error will be displayed in execute_command
    }
}

// Get all the remote branches from git
$remote_branches = execute_command(sprintf(
    'git --git-dir="%s.git" --work-tree="%s" branch -r'
	, TMP_DIR
	, TMP_DIR
	),"",true);
if($remote_branches === false) {
    die(); // Error will have been displayed in execute_command
}

// Strip "origin/" and remove pointers (e.g. "origin/HEAD -> origin/master" to just "HEAD")
foreach($remote_branches as &$rembranch) {
       $rembranch = preg_replace('#^\S+?/(\S+)( -> .*)?$#', '$1', trim($rembranch));
}

$feature_branch_regex = "feature/([a-zA-Z_\-]+)";

$targetdir = str_replace('$1', "", $DEPLOYMENTS[$feature_branch_regex]['TARGET_DIR']);
$dir = dir($targetdir);

while(($entry=$dir->read()) !== false) {
    if($entry === "." || $entry === "..") {
        continue;
    } 
    $path = $targetdir . $entry;
    $branch = "feature/".$entry;
    if(is_dir($path) && preg_match("~".$feature_branch_regex."~", $branch) && !in_array($branch, $remote_branches)) {
        execute_command(sprintf(
          'rm -r "%s"'
        , $path
        ),"",true);
	echo "\n";
    }
}
?>
</pre>
</body>
</html>