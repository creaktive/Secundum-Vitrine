<?php
/*
Plugin Name: Vitrine Secundum
Plugin URI: http://secundum.com.br/vitrine-secundum
Description: Adicione Vitrines Secundum personalizadas nos seus posts. Lembre de <a href="options-general.php?page=secundum_vitrine.php">configurar</a> o Identificador MercadoSócios.
Author: Stanislaw Pusep e Jobson Lemos
Version: 2.5a
License: GPL v3 - http://www.gnu.org/licenses/gpl-3.0.html

Requer WordPress 2.8.4 ou mais recente.
*/

define('SECVITR_VERS',	'2.5a');
define('SECVITR_HOST',	'sistema.secundum.com.br');
define('SECVITR_CACHE',	'secvitr_cache');
define('SECVITR_HINTS',	'secvitr_hints');

$SecVitr_IDML	= intval(get_option('SecVitr_IDML'));
$SecVitr_COLS	= intval(get_option('SecVitr_COLS'));
$SecVitr_AUTO	= intval(get_option('SecVitr_AUTO'));
$SecVitr_HOME	= intval(get_option('SecVitr_HOME'));
$SecVitr_DAYS	= intval(get_option('SecVitr_DAYS'));
$SecVitr_INST	= intval(get_option('SecVitr_INST'));
$WordsSec_NUM	= intval(get_option('WordsSec_NUM'));
$SecVitr_CSS	= get_option('SecVitr_CSS');

if (filemtime(__FILE__) > $SecVitr_INST)
	SecVitr_Activate();


function SecVitr_fetch($host, $file, $content = null, $port = 80, $timeout = 60) {
	$gzip		= function_exists('gzdecode');

	if (isset($content)) {
		$req	= "POST $file HTTP/1.0\r\n";
		$req	.= "Content-Length: " . strlen($content) . "\r\n";
		$req	.= "Content-Type: application/x-www-form-urlencoded\r\n";
	} else
		$req	= "GET $file HTTP/1.0\r\n";

	$req		.= "Host: $host\r\n";
	$req		.= "User-Agent: WP Vitrine Secundum " . SECVITR_VERS . "\r\n";
	$req		.= "Referer: " . get_permalink() . "\r\n";

	if ($gzip)
		$req	.= "Accept-Encoding: gzip\r\n";

	$req		.= "\r\n";

	if (isset($content))
		$req	.= $content;

	$res		= '';
	$hdr		= array();
	$buf		= '';

	if (false != ($fs = @fsockopen($host, $port, $errno, $errstr, $timeout))) {
		fwrite($fs, $req);
		while (!feof($fs) && (strlen($res) < 102400))	// 100 KB limit
			$res .= fgets($fs, 1160);					// One TCP-IP packet
		fclose($fs);

		list($tmp, $res) = preg_split('%\r?\n\r?\n%', $res, 2);

		$tmp = preg_split('%\r?\n%', $tmp, -1, PREG_SPLIT_NO_EMPTY);
		$cod = array_shift($tmp);
		if (preg_match('%^HTTP/(1\.[01])\s+([0-9]{3})\s+(.+)$%i', $cod, $match))
			if ($match[2] == 200) {
				foreach ($tmp as $line)
					if (preg_match('%^([A-Z-a-z\-]+):\s*(.+)$%', $line, $match))
						$hdr[strtolower($match[1])] = $match[2];

				$buf = ($gzip && ($hdr['content-encoding'] == 'gzip')) ? gzdecode($res) : $res;
			}
	}

	return $buf;
}


function SecVitr_AdmMenu() {
	if (function_exists('add_options_page'))
		add_options_page('Options', 'Vitrine Secundum', 5, basename(__FILE__), 'SecVitr_SubPanel');
}

function SecVitr_SubPanel() {
	global $wpdb, $SecVitr_IDML, $SecVitr_CSS, $SecVitr_COLS, $SecVitr_AUTO, $SecVitr_HOME, $SecVitr_DAYS, $WordsSec_NUM;

	if (isset($_POST['info_update'])) {
		update_option('SecVitr_IDML', intval($_POST['SecVitr_IDML']));
		$SecVitr_IDML = intval(get_option('SecVitr_IDML'));

		update_option('SecVitr_AUTO', intval($_POST['SecVitr_AUTO1']) + (intval($_POST['SecVitr_AUTO2']) << 2) + (intval($_POST['SecVitr_AUTO3']) << 4));
		$SecVitr_AUTO = intval(get_option('SecVitr_AUTO'));

		update_option('SecVitr_HOME', intval($_POST['SecVitr_HOME']));
		$SecVitr_HOME = intval(get_option('SecVitr_HOME'));

		update_option('SecVitr_DAYS', intval($_POST['SecVitr_DAYS']));
		$SecVitr_DAYS = intval(get_option('SecVitr_DAYS'));

		update_option('SecVitr_CSS', $_POST['SecVitr_CSS']);
		$SecVitr_CSS = get_option('SecVitr_CSS');

		update_option('WordsSec_NUM', $_POST['WordsSec_NUM']);
		$WordsSec_NUM = get_option('WordsSec_NUM');

		$newcols = intval($_POST['SecVitr_COLS']);
		if ($SecVitr_COLS != $newcols) {
			update_option('SecVitr_COLS', $newcols);
			$SecVitr_COLS = get_option('SecVitr_COLS');

			$wpdb->query("TRUNCATE TABLE `" . $wpdb->prefix . SECVITR_CACHE . "`;");
		}

		echo "
		<div class='updated'>
			<p>
				<strong>Opções Atualizadas!</strong>
			</p>
		</div>";
	} else if (isset($_POST['SecVitr_Reset'])) {
		delete_option('SecVitr_IDML');
		delete_option('SecVitr_COLS');
		delete_option('SecVitr_AUTO');
		delete_option('SecVitr_HOME');
		delete_option('SecVitr_DAYS');
		delete_option('SecVitr_CSS');
		delete_option('WordsSec_NUM');
		$wpdb->query("DROP TABLE `" . $wpdb->prefix . SECVITR_CACHE . "`;");
		$wpdb->query("DROP TABLE `" . $wpdb->prefix . SECVITR_HINTS . "`;");

		SecVitr_Activate();

		echo "
		<div class='updated'>
			<p>
				<strong>Opções Reiniciadas!</strong>
			</p>
		</div>";
	} else if (isset($_POST['SecVitr_Cleanup'])) {
		$wpdb->query("TRUNCATE TABLE `" . $wpdb->prefix . SECVITR_CACHE . "`;");
		$wpdb->query("TRUNCATE TABLE `" . $wpdb->prefix . SECVITR_HINTS . "`;");

		echo "
		<div class='updated'>
			<p>
				<strong>Caches esvaziados!</strong>
			</p>
		</div>";
	}

	echo "
	<div class='wrap'>
		<h2>Opções da Vitrine Secundum</h2>

		<iframe src='http://" . SECVITR_HOST . "/vitrine-banner.php?v=" . urlencode(SECVITR_VERS) . '&id=' . $SecVitr_IDML . "' style='border: 0; width: 100%; height: 300px;' marginwidth='0' marginheight='0' frameborder='0' scrolling='auto'></iframe>

		<form name='SecVitr' method='post' action=''>
			<table class='form-table'>
				<tr valign='top'>
					<th scope='row'>
						<label for='SecVitr_IDML'>Identificador MercadoSócios</label>
					</th>
					<td>
						<input type='text' name='SecVitr_IDML' id='SecVitr_IDML' value='$SecVitr_IDML' class='regular-text code' />
						<span class='description'>o <a href='http://pmsapp.mercadolivre.com.br/jm/ml.pms.servlets.ShowCampaignServlet' target='_blank'>código de traqueamento</a> (numérico, usualmente de 7 dígitos) da sua campanha pode ser visto clicando no botão mais à direita abaixo de <b>Ações</b></span>
					</td>
				</tr>
				<tr valign='top'>
					<th scope='row'>
						<label for='WordsSec_NUM'>Links no texto do post</label>
					</th>
					<td>
						<select name='WordsSec_NUM' id='WordsSec_NUM'>";

	for ($i = 0; $i <= 10; $i++)
		printf("\n						<option value='%d' %s>%s&nbsp;</option>", $i, ($i == $WordsSec_NUM) ? 'selected="selected"' : '', $i ? $i : 'desabilitar');

	echo "
						</select>
						<span class='description'>quantidade de links a serem inseridos automaticamente, por post</span>
					</td>
				</tr>
				<tr valign='top'>
					<th scope='row'>
						<label for='SecVitr_HOME'>Vitrines na página inicial</label>
					</th>
					<td>
						<input name='SecVitr_HOME' id='SecVitr_HOME' value='1' " . ($SecVitr_HOME ? "checked='checked' " : '') . "type='checkbox' />
					</td>
				</tr>
				<tr valign='top'>
					<th scope='row'>
						<label for='SecVitr_COLS'>Produtos por anúncio</label>
					</th>
					<td>
						<select name='SecVitr_COLS' id='SecVitr_COLS'>";

	for ($i = 2; $i <= 5; $i++)
		printf("\n						<option value='%d' %s>%d&nbsp;</option>", $i, ($i == $SecVitr_COLS) ? 'selected="selected"' : '', $i);

	echo "
						</select>
						<span class='description'>selecione de acordo com a largura da coluna principal</span>
					</td>
				</tr>
				<tr valign='top'>
					<th scope='row'>
						<label for='SecVitr_AUTO1'>Vitrines automáticas no topo</label>
					</th>
					<td>
						<select name='SecVitr_AUTO1' id='SecVitr_AUTO1'>";

	for ($i = 0; $i <= 3; $i++)
		printf("\n						<option value='%d' %s>%s&nbsp;</option>", $i, ($i == ($SecVitr_AUTO & 0x3)) ? 'selected="selected"' : '', $i ? $i : 'desabilitado');

	echo "
						</select>
						<span class='description'></span>
					</td>
				</tr>
				<tr valign='top'>
					<th scope='row'>
						<label for='SecVitr_AUTO2'>Vitrines automáticas no rodapé</label>
					</th>
					<td>
						<select name='SecVitr_AUTO2' id='SecVitr_AUTO2'>";

	for ($i = 0; $i <= 3; $i++)
		printf("\n						<option value='%d' %s>%s&nbsp;</option>", $i, ($i == (($SecVitr_AUTO >> 2) & 0x3)) ? 'selected="selected"' : '', $i ? $i : 'desabilitado');

	echo "
						</select>
						<span class='description'></span>
					</td>
				</tr>
				<tr valign='top'>
					<th scope='row'>
						<label for='SecVitr_AUTO3'>Colocar vitrines automaticas mesmo nos posts que eu colocar manualmente</label>
					</th>
					<td>
						<input name='SecVitr_AUTO3' id='SecVitr_AUTO3' value='1' " . (($SecVitr_AUTO & 0x10) ? "checked='checked' " : '') . "type='checkbox' />
					</td>
				</tr>
				<tr valign='top'>
					<th scope='row'>
						<label for='SecVitr_DAYS'>Aplicar em posts dos últimos</label>
					</th>
					<td>
						<select name='SecVitr_DAYS' id='SecVitr_DAYS'>";

	for ($i = -1; $i <= 30; $i++)
		printf("\n						<option value='%d' %s>%s&nbsp;</option>", $i, ($i == $SecVitr_DAYS) ? 'selected="selected"' : '', ($i > 0) ? (($i > 1) ? "$i dias" : '1 dia') : (($i == -1) ? 'aplicar em todos os posts, inclusive os novos' : 'aplicar somente em posts antigos'));

	echo "
						</select>
						<span class='description'>limite para inserção de vitrines automáticas</span>
					</td>
				</tr>			</table>

			<h3>Código CSS da vitrine</h3>
			<p><label for='SecVitr_CSS'></label></p>
			<textarea name='SecVitr_CSS' id='SecVitr_CSS' class='large-text code' rows='10' cols='60'>${SecVitr_CSS}</textarea>

			<p class='submit'>
				<input type='submit' name='info_update' value='Salvar alterações' class='button-primary' />
				<input type='submit' onclick='return confirm(\"Você realmente gostaria de restaurar as configurações originais e esvaziar o cache?\");' name='SecVitr_Reset' value='Configurações originais' />
				<input type='submit' onclick='return confirm(\"Você realmente gostaria de limpar o cache?\");' name='SecVitr_Cleanup' value='Limpar cache' />
			</p>
		</form>
	</div>";
}

function SecVitr_Header() {
	global $SecVitr_CSS;
	echo "
<link rel='stylesheet' type='text/css' href='http://sistema.secundum.com.br/vitrine-ad.css' />
<style type='text/css'>
${SecVitr_CSS}
</style>
";
}

function SecVitr_MetaBox() {
	if (function_exists('add_meta_box')) {
		add_meta_box('secvitr', 'Vitrine Secundum', 'SecVitr_Edit', 'page', 'normal', 'high');
		add_meta_box('secvitr', 'Vitrine Secundum', 'SecVitr_Edit', 'post', 'normal', 'high');
	}
}

function SecVitr_Edit() {
	global $SecVitr_IDML;
	echo "<iframe src='http://" . SECVITR_HOST . "/vitrine-custom.php?idml=${SecVitr_IDML}' style='border: 0; width: 100%; height: 500px;' marginwidth='0' marginheight='0' frameborder='0' scrolling='auto'></iframe>\n";
}

function SecVitr_Insert($content) {
	global $SecVitr_IDML, $SecVitr_AUTO, $SecVitr_HOME, $post, $WordsSec_JS_LOADED, $WordsSec_NUM;

	if (!empty($WordsSec_NUM)) {
		$pre	= '<script type="text/javascript"><!--';
		$pos	= '</div>';
		$id		= 'WordsSec' . md5($content);

		if (!isset($WordsSec_JS_LOADED)) {
			$WordsSec_JS_LOADED = true;
			$pre .= "
secundum_words_idml = $SecVitr_IDML;
secundum_words_maxrep = $WordsSec_NUM;
secundum_words_ids = new Object();
secundum_words_ids['$id'] = 1;
//--></script>
<script type=\"text/javascript\" src=\"http://widget.secundum.com.br/wordssec.js\"></script>
";
		} else
			$pre .= "
secundum_words_ids['$id'] = 1;
//--></script>
";

		$pre .= "<div id=\"$id\">";
		$content = $pre . $content . $pos;
	}


	if (($SecVitr_HOME == 0) && is_home()) {
		$content = preg_replace('%(?:<!--\s*)?\[secvitrine/([a-z0-9\-]+)/([0-9]{4,6})\](?:\s*-->)?%i', '', $content);
		$content = preg_replace('%(?:<!--\s*)?\[secvitrine/([a-z0-9\-]+)\](?:\s*-->)?%i', '', $content);
		return $content;
	}

	$rpc			= array();
	$rpc['post']	= (array) $post;
	$rpc['categs']	= wp_list_categories('depth=-1&echo=0&hierarchical=0&style=none');
	$rpc['tags']	= wp_tag_cloud('echo=0&format=array');
	$rpc			= base64_encode(gzcompress(serialize($rpc), 9));
	$rpc			= rtrim($rpc, '=');
	$rpc			= strtr($rpc, '+/', '-_');

	if (($SecVitr_AUTO & 0xf) && (($SecVitr_AUTO & 0x10) || !preg_match('%(<!--\s*)?\[secvitrine/[a-z0-9\-]+(/[0-9]{4,6})?\](\s*-->)?%i', $content))) {
		$hint = hint_fetch($rpc);

		$pre = '';
		$n = min(count($hint), ($SecVitr_AUTO) & 0x3);
		for ($i = 0; $i < $n; $i++)
			$pre .= sprintf("[secvitrine/%s]\n", array_shift($hint));

		$pos = '';
		$n = min(count($hint), ($SecVitr_AUTO >> 2) & 0x3);
		for ($i = 0; $i < $n; $i++)
			$pos .= sprintf("\n[secvitrine/%s]", array_shift($hint));

		$content = $pre . $content . $pos;
	}

	$content = preg_replace('%(?:<!--\s*)?\[secvitrine/([a-z0-9\-]+)/([0-9]{4,6})\](?:\s*-->)?%ei', 'ad_fetch(strtolower("$1"), $2)', $content);
	$content = preg_replace('%(?:<!--\s*)?\[secvitrine/([a-z0-9\-]+)\](?:\s*-->)?%ei', 'ad_fetch(strtolower("$1"))', $content);

	return $content;
}

function hint_fetch($rpc) {
	global $wpdb, $post, $SecVitr_DAYS, $SecVitr_INST;

	$last = strtotime($post->post_modified);

	if (($SecVitr_DAYS != -1) && (($last > $SecVitr_INST) || ($SecVitr_DAYS && ($last < ($SecVitr_INST - $SecVitr_DAYS*24*3600)))))
		return array();

	$auto = $wpdb->get_var("SELECT hint FROM `" . $wpdb->prefix . SECVITR_HINTS . "` WHERE (postID = {$post->ID}) AND (UNIX_TIMESTAMP(last) > ${last})");
	if (empty($auto)) {
		$auto = SecVitr_fetch(SECVITR_HOST, '/vitrine-auto.php', 'rpc=' . $rpc);
		$wpdb->query($wpdb->prepare("
			INSERT INTO `" . $wpdb->prefix . SECVITR_HINTS . "` (postID, hint)
			VALUES (%d, %s)
			ON DUPLICATE KEY UPDATE hint = %s
		", $post->ID, $auto, $auto));
	}

	return explode(',', $auto);
}

function ad_fetch($busca, $categ = 0) {
	global $wpdb, $SecVitr_IDML, $SecVitr_COLS;

	$html = $wpdb->get_var("SELECT ad FROM `" . $wpdb->prefix . SECVITR_CACHE . "` WHERE (UNIX_TIMESTAMP(last) > UNIX_TIMESTAMP() - 24*3600) AND (srch = '${busca}') AND (ctg = ${categ})");
	if (empty($html)) {
		$html = SecVitr_fetch(SECVITR_HOST, '/vitrine.php/' . $busca . ($categ ? '/' . $categ : '') . '?' . $SecVitr_COLS);
		$wpdb->query($wpdb->prepare("
			INSERT INTO `" . $wpdb->prefix . SECVITR_CACHE . "` (id, srch, ctg, ad)
			VALUES (CRC32(CONCAT(%s, %d)), %s, %d, %s)
			ON DUPLICATE KEY UPDATE ad = %s
		", $busca, $categ, $busca, $categ, $html, $html));
	}

	return str_replace('%IDML%', $SecVitr_IDML, $html);
}

function SecVitr_Activate() {
	global $wpdb, $SecVitr_IDML, $SecVitr_CSS, $SecVitr_COLS, $SecVitr_AUTO, $SecVitr_HOME, $SecVitr_DAYS, $SecVitr_INST, $WordsSec_NUM;

	$SecVitr_IDML = 1234567;
	add_option('SecVitr_IDML', $SecVitr_IDML);

	$SecVitr_COLS = 4;
	add_option('SecVitr_COLS', $SecVitr_COLS);

	$SecVitr_AUTO = 0x5;
	add_option('SecVitr_AUTO', $SecVitr_AUTO);

	$SecVitr_HOME = 0;
	add_option('SecVitr_HOME', $SecVitr_HOME);

	$SecVitr_DAYS = -1;
	add_option('SecVitr_DAYS', $SecVitr_DAYS);

	$SecVitr_INST = time();
	update_option('SecVitr_INST', $SecVitr_INST);

	$WordsSec_NUM = 0;
	update_option('WordsSec_NUM', $WordsSec_NUM);

	$SecVitr_CSS = ".sec_vitrine {
	/* fonte Trebuchet MS; corpo 10 pixels */
	font-family: Trebuchet MS;
	font-size: 10px;
	line-height: 14px;
	/* texto centralizado na horizontal */
	text-align: center;
	vertical-align: top;
	/* bloco de anuncio destacado e centralizado */
	display: table;
	margin: 0 auto;
}
.sec_item_img { width: 90px; height: 90px; border: 0; }
.sec_item_cell { width: 100px; max-height: 240px; float: left; padding: 5px; }
.sec_link { font-weight: bold; float: right; }

/* o link WordsSec */
.secundum_words_link { color: #008000 !important; }
";
	add_option('SecVitr_CSS', $SecVitr_CSS);

	$wpdb->query("
		CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . SECVITR_CACHE . "` (
			`id` int(10) NOT NULL,
			`last` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`srch` varchar(50) DEFAULT NULL,
			`ctg` mediumint(8) unsigned DEFAULT '0',
			`ad` text,
			PRIMARY KEY (`id`),
			KEY `idx` (`last`,`srch`,`ctg`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Cache da Vitrine Secundum';
	");

	$wpdb->query("
		CREATE TABLE  IF NOT EXISTS `" . $wpdb->prefix . SECVITR_HINTS . "` (
			`postID` bigint(10) unsigned NOT NULL DEFAULT '0',
			`last` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`hint` text,
			PRIMARY KEY (`postID`),
			KEY `idx` (`last`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Sugestões de anúncios da Vitrine Secundum'
	");

	return 1;
}


add_action('admin_menu',	'SecVitr_AdmMenu');
add_action('wp_head',		'SecVitr_Header');
add_action('admin_menu',	'SecVitr_MetaBox');
add_filter('the_content',	'SecVitr_Insert', 1);

register_activation_hook(__FILE__, 'SecVitr_Activate');

?>