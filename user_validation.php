<?php
################################################################################
# @Name : ./user_validation.php 
# @Description : display user validation form
# @Call : auto mail
# @Parameters : token
# @Author : Flox
# @Version : 3.2.5
# @Create : 16/10/2020
# @Update : 16/10/2020
################################################################################
//require functions 
require('core/init_get.php');
require('core/functions.php');

//init var
if(!isset($_POST['validation'])) $_POST['validation'] = '';
if(!isset($_POST['user_validation'])) $_POST['user_validation'] = '';
if(!isset($_POST['reason'])) $_POST['reason'] = '';

//db connection
require "connect.php";
$db->exec('SET sql_mode = ""');

//secure token value
$db_token=strip_tags($db->quote($_GET['token']));

//load parameters table
$qry=$db->prepare("SELECT * FROM `tparameters`");
$qry->execute();
$rparameters=$qry->fetch();
$qry->closeCursor();

//display error parameter
if ($rparameters['debug']) {
	ini_set('display_errors', 'On');
	ini_set('display_startup_errors', 'On');
	ini_set('html_errors', 'On');
	error_reporting(E_ALL);
} else {
	ini_set('display_errors', 'Off');
	ini_set('display_startup_errors', 'Off');
	ini_set('html_errors', 'Off');
	error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

if($_GET['token'])
{
	//check if token exist
	$qry=$db->prepare("SELECT `ticket_id` FROM `ttoken` WHERE token=:token");
	$qry->execute(array('token' => $_GET['token']));
	$row=$qry->fetch();
	$qry->closeCursor();

	if(!empty($row))
	{
		$token=true;
		$ticket_id=$row['ticket_id'];
	} else {
		$token=false;
		$ticket_id='';
	}
} else {
	$ticket_id='';
	$token=false;
}

//define PHP time zone
if($rparameters['server_timezone']) {date_default_timezone_set($rparameters['server_timezone']);}

//load locales
$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
if($lang=='fr') {$_GET['lang'] = 'fr_FR';}
else {$_GET['lang'] = 'en_US';}
define('PROJECT_DIR', realpath('./'));
define('LOCALE_DIR', PROJECT_DIR .'/locale');
define('DEFAULT_LOCALE', '($_GET[lang]');
require_once('./components/php-gettext/gettext.inc');
$encoding = 'UTF-8';
$locale = (isset($_GET['lang']))? $_GET['lang'] : DEFAULT_LOCALE;
T_setlocale(LC_MESSAGES, $locale);
T_bindtextdomain($_GET['lang'], LOCALE_DIR);
T_bind_textdomain_codeset($_GET['lang'], $encoding);
T_textdomain($_GET['lang']);

//get ticket data
$qry=$db->prepare("SELECT `title`,`description`,`user` FROM `tincidents` WHERE id=:id");
$qry->execute(array('id' => $ticket_id));
$ticket=$qry->fetch();
$qry->closeCursor();

if($_POST['validation'] && $ticket_id)
{
	//delete token
	$qry=$db->prepare("DELETE FROM `ttoken` WHERE token=:token");
    $qry->execute(array('token' => $_GET['token']));

    if(!$_POST['user_validation'])
    {
        //update ticket fields
        $qry=$db->prepare("UPDATE `tincidents` SET `state`='2', date_res='', techread='0' WHERE `id`=:id");
        $qry->execute(array('id' => $ticket_id));

        //insert switch state thread on ticket
        $qry=$db->prepare("INSERT INTO `tthreads` (`ticket`,`date`,`type`,`author`,`state`) VALUES (:ticket,:date,'5',:author,'2')");
        $qry->execute(array('ticket' => $ticket_id,'date' => date('Y-m-d H:i:s'), 'author' => $ticket['user']));

        if($_POST['reason'])
        {
            //secure string
            $_POST['reason']=nl2br(htmlspecialchars($_POST['reason'], ENT_QUOTES, 'UTF-8'));

            //insert reason on ticket resolution part
            $qry=$db->prepare("INSERT INTO `tthreads` (`ticket`,`date`,`type`,`author`,`text`) VALUES (:ticket,:date,'0',:author,:text)");
            $qry->execute(array('ticket' => $ticket_id,'date' => date('Y-m-d H:i:s'), 'author' => $ticket['user'], 'text' => $_POST['reason']));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8" />
		<title>GestSup | <?php echo T_('Validation demandeur'); ?></title>
		<link rel="shortcut icon" type="image/png" href="./images/favicon_survey.png" />
		<meta name="description" content="gestsup" />
		<meta name="robots" content="noindex, nofollow">
		<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
		<!-- bootstrap styles -->
		<link rel="stylesheet" href="./components/bootstrap/dist/css/bootstrap.min.css" />
		<!-- fontawesome styles -->
		<link rel="stylesheet" type="text/css" href="./components/fontawesome/css/fontawesome.min.css">
		<link rel="stylesheet" type="text/css" href="./components/fontawesome/css/solid.min.css">
		<!-- smartwizard styles -->
		<link rel="stylesheet" type="text/css" href="./components/smartwizard/dist/css/smart_wizard.min.css">
		<link rel="stylesheet" type="text/css" href="./components/smartwizard/dist/css/smart_wizard_theme_circles.min.css">
		
		<!-- ace styles -->
		<link rel="stylesheet" type="text/css" href="./template/ace/dist/css/ace-font.min.css">
		<link rel="stylesheet" type="text/css" href="./template/ace/dist/css/ace.min.css">
		<link rel="stylesheet" type="text/css" href="./template/ace/dist/css/ace-themes.min.css">
	</head>
	<body class="bgc-secondary-l4">
		<div style="body-container" >
			<nav class="navbar navbar-expand-lg navbar-fixed navbar-skyblue">
				<div class="navbar-inner">
					<div class="navbar-content">
                        <a class="navbar-brand text-white" href="#">
                            <?php 
                            //re-size logo if height superior 40px
                            if($rparameters['logo']) 
                            {
                                $height = getimagesize("./upload/logo/$rparameters[logo]");
                                $height=$height[1];
                                if($height>40) {$logo_size='height="40"';} else {$logo_size='';}
                            } else {$logo_size='';}
                            echo '&nbsp;<img style="border-style: none" '.$logo_size.' alt="logo" src="./upload/logo/'; if($rparameters['logo']=='') echo 'logo.png'; else echo $rparameters['logo'];  echo '" />';
                            echo '&nbsp;'.$rparameters['company']; 
                            ?>
                            <i class="fa fa-check text-80 ml-4" ></i>
                            <span><?php echo T_('Validation demandeur'); ?></span>
                        </a><!-- /.navbar-brand -->
					</div><!-- /.navbar-header -->
				</div><!--/.navbar-inner-->
			</nav>
			<div class="main-container p-4" id="main-container">
				<div role="main" class="main-content">
					<div class="card bcard shadow" id="card-1">
						<div class="card-header">
							<h5 class="card-title">
								<i class="fa fa-ticket-alt text-primary-m2"></i> <?php if($ticket_id) {echo T_('Ticket').' n°'.$ticket_id.' : '.$ticket['title'].'';} ?>
							</h5>
						</div><!-- /.card-header -->
						<div class="card-body p-0">
							<!-- to have smooth .card toggling, it should have zero padding -->
							<div class="p-3">
								<?php 
									if($rparameters['user_validation'])
									{
										if($token==true)
										{
											if($_POST['validation'])
											{
												echo DisplayMessage('success',T_('Votre réponse à bien été enregistrée, vous pouvez fermer cette page.'));
											} 
											else
											{
												echo '
												<div class="">
													<div class="">
														<form method="post" id="form" action="" class="form-horizontal" id="sample-form" >
															<div class="step-content row-fluid position-relative" id="step-container">
																<div class="col-xs-6 col-sm-2"></div>
                                                                <div class="col-xs-6 col-sm-10" id="content">
                                                                   <h4 class="lighter text-success pb-3 pt-3">'.T_('Votre ticket est-il bien résolu ?').'</h4>
                                                                    <div class="pl-4">
                                                                        <div class="radio">
                                                                            <label>
                                                                                <input name="user_validation" value="1" type="radio" checked class="ace">
                                                                                <span class="lbl"> '.T_('Oui').'</span>
                                                                            </label>
                                                                        </div>
                                                                        <div class="radio">
                                                                            <label>
                                                                                <input name="user_validation" OnChange="DisplayReason();" value="0" type="radio" class="ace">
                                                                                <span class="lbl"> '.T_('Non').' ('.T_('réouverture du ticket').')</span>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                    <div id="reason" class="pt-4 d-none" >
                                                                        <h6 class="lighter text-info">'.T_('Motif de réouverture').' :</h6>
                                                                        <textarea class="form-control" id="reason" name="reason" style="overflow: hidden; overflow-wrap: break-word; resize: horizontal; height: 86px;"></textarea>
                                                                    </div>
                                                                    <div class="pt-4">
                                                                        <h6 class="lighter text-info">'.T_('Description du ticket').' :</h6>
                                                                        <span class="text-grey text-90">
                                                                        '.$ticket['description'].'
                                                                        <span>
                                                                    </div>
																</div>
																<hr>
																<div class="row-fluid wizard-actions">
                                                                    <center>
                                                                        <a target="_blank" title="'.T_("Ouvre un nouvel onglet pour visualiser le ticket dans l'application").'"  href="index.php?page=ticket&id='.$ticket_id.'"
                                                                            <button type="submit" id="open_ticket" name="open_ticket" value="open_ticket" class="btn btn-info mr-2">
                                                                                <i class="fa fa-eye mr-1"></i>
                                                                                '.T_('Voir le ticket').'
                                                                            </button>
                                                                        </a>
                                                                        <button type="submit" id="validation" name="validation" value="validation" class="btn btn-success">
                                                                            <i class="fa fa-check mr-1"></i>    
                                                                            '.T_('Valider').'
                                                                        </button>
																	</center>
																</div>
															</div>
														</form>
													</div><!-- /widget-main -->
                                                </div><!-- /widget-body -->
                                               
												';
											}
										} else {
											echo DisplayMessage('error',T_('Jeton invalide, contacter votre administrateur'));
										}
									} else {
										echo DisplayMessage('error',T_("Le paramètre de validation demandeur n'est pas activé, contacter votre administrateur"));
									} 
									?>
							</div>
						</div><!-- /.card-body -->
					</div>	<!-- /.card -->		
                </div>
			</div>
        </div>
		<span style="position: absolute; bottom: 0; right: 0; font-size:10px; "><a target="_blank" title="<?php T_('Ouvre un nouvel onglet sur le site du logiciel GestSup')?>" href="https://gestsup.fr">GestSup.fr</a></span>
	</body>

      

	<!-- include  scripts -->
	<script type="text/javascript" src="./components/jquery/jquery.min.js"></script>
	<script type="text/javascript" src="./components/popper-js/dist/umd/popper.min.js"></script>
	<script type="text/javascript" src="./components/bootstrap/dist/js/bootstrap.min.js"></script>

	<!-- include ace scripts -->
    <script type="text/javascript" src="./template/ace/dist/js/ace.min.js"></script>
    
    <!-- display reason field if ratio change -->
    <script>                              
        function DisplayReason() {$("#reason").removeClass("d-none");}
    </script>
</html>