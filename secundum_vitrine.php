<?php
/*
Plugin Name: Vitrine Secundum
Plugin URI: http://secundum.com.br/
Description: Adicione Vitrines Secundum personalizadas nos seus posts. Lembre de configurar o Identificador MercadoSócios.
Author: Stanislaw Pusep e Jobson Lemos
Version: 1.0
License: GPL v3 - http://www.gnu.org/licenses/gpl-3.0.html

Requer WordPress 2.8.4 ou mais recente.
*/

$SecVitr_IDML	= intval(get_option('SecVitr_IDML'));
$SecVitr_CSS	= get_option('SecVitr_CSS');

if (!function_exists('file_put_contents')) {
	function file_put_contents($filename, $data) {
		$f = @fopen($filename, 'w');
		if (!$f) {
			return false;
		} else {
			$bytes = fwrite($f, $data);
			fclose($f);
			return $bytes;
		}
	}
}

if (!function_exists('gzdecode')) {
	function gzdecode($data) {
		$len = strlen($data);
		if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
			return null;	// Not GZIP format (See RFC 1952)
		}

		$g = tempnam(sys_get_temp_dir(), 'secundum_words');
		@file_put_contents($g, $data);
		ob_start();
		readgzfile($g);
		@unlink($g);
		$d = ob_get_clean();
		return $d;
	}
}

if (!function_exists('sys_get_temp_dir')) {
	function sys_get_temp_dir() {
		if (!empty($_ENV['TMP']))		{ return realpath($_ENV['TMP']); }
		if (!empty($_ENV['TMPDIR']))	{ return realpath($_ENV['TMPDIR']); }
		if (!empty($_ENV['TEMP']))		{ return realpath($_ENV['TEMP']); }
		$tempfile = tempnam(uniqid(rand(), TRUE), '');
		if (file_exists($tempfile)) {
			@unlink($tempfile);
			return realpath(dirname($tempfile));
		}
	}
}

function secundum_fetch($host, $file, $port = 80, $timeout = 10) {
	$gzip		= (function_exists('gzdecode') || function_exists('readgzfile')) ? true : false;

	$req		= "GET $file HTTP/1.0\r\n";
	$req		.= "Host: $host\r\n";
	$req		.= "User-Agent: WP Secundum Vitrine 1.0\r\n";
	if ($gzip)
		$req	.= "Accept-Encoding: gzip\r\n";
	$req		.= "\r\n";
	$res		= '';
	$hdr		= array();
	$buf		= '';

	if (false != ($fs = @fsockopen($host, $port, $errno, $errstr, $timeout))) {
		fwrite($fs, $req);
		while (!feof($fs) && (strlen($res) < 0x100000))	// 1 MB limit
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
	global $SecVitr_IDML, $SecVitr_CSS;

	if (isset($_POST['info_update'])) {
		update_option('SecVitr_IDML', intval($_POST['SecVitr_IDML']));
		$SecVitr_IDML = intval(get_option('SecVitr_IDML'));

		update_option('SecVitr_CSS', $_POST['SecVitr_CSS']);
		$SecVitr_CSS = get_option('SecVitr_CSS');

		echo "
		<div class='updated'>
			<p>
				<strong>Opções Atualizadas!</strong>
			</p>
		</div>";
	};

	echo "
	<div class='wrap'>
		<h2>Opções da Vitrine Secundum</h2>

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
			</table>

			<h3>Código CSS da vitrine</h3>
			<p><label for='SecVitr_CSS'></label></p>
			<textarea name='SecVitr_CSS' id='SecVitr_CSS' class='large-text code' rows='3'>${SecVitr_CSS}</textarea>

			<p class='submit'>
				<input type='submit' name='info_update' value='Salvar alterações' class='button-primary' />
			</p>
		</form>
	</div>";
}

function SecVitr_Header() {
	global $SecVitr_CSS;
	echo "<style type='text/css'>\n${SecVitr_CSS}</style>\n";
}

function SecVitr_MetaBox() {
	if (function_exists('add_meta_box')) {
		add_meta_box('secvitr', 'Vitrine Secundum', 'SecVitr_Edit', 'page', 'normal', 'high');
		add_meta_box('secvitr', 'Vitrine Secundum', 'SecVitr_Edit', 'post', 'normal', 'high');
	}
}

function SecVitr_Edit() {
	global $SecVitr_IDML;
	echo "<iframe src='http://sistema.secundum.com.br/vitrine-custom.php?idml=${SecVitr_IDML}' style='border: 0; width: 100%; height: 500px;' marginwidth='0' marginheight='0' frameborder='0' scrolling='auto'></iframe>\n";
}

function SecVitr_Insert($content) {
	return preg_replace('%\[secvitrine/([a-z0-9\-]+)/([0-9]{4,6})\]%ei', 'ad_fetch(strtolower("$1"), $2)', $content);
}

function ad_fetch($busca, $categ) {
	global $wpdb, $SecVitr_IDML;

	$html = $wpdb->get_var("SELECT ad FROM `{$wpdb->prefix}secvitr_cache` WHERE (UNIX_TIMESTAMP(last) > UNIX_TIMESTAMP() - 24*3600) AND (srch = '${busca}') AND (ctg = ${categ})");
	if (empty($html)) {
		$html = secundum_fetch('sistema.secundum.com.br', "/vitrine.php/$busca/$categ");
		$wpdb->query($wpdb->prepare("
			INSERT INTO `{$wpdb->prefix}secvitr_cache` (id, srch, ctg, ad)
			VALUES (CRC32(CONCAT(%s, %d)), %s, %d, %s)
			ON DUPLICATE KEY UPDATE ad = %s
		", $busca, $categ, $busca, $categ, $html, $html));
	}

	return str_replace('%IDML%', $SecVitr_IDML, $html);
}

function SecVitr_Activate() {
	global $wpdb, $SecVitr_IDML, $SecVitr_CSS;

	$SecVitr_IDML = 1234567;
	add_option('SecVitr_IDML', $SecVitr_IDML);

	$SecVitr_CSS = ".sec_vitrine { font-family: Trebuchet MS; font-size: 11px; width: 480px; }
.sec_item_img { width: 90px; height: 90px; border: 0; }
.sec_item_cell { width: 116px; max-height: 240px; text-align: center; vertical-align: top; float: left; padding: 2px; }
";
	add_option('SecVitr_CSS', $SecVitr_CSS);

	if (!$wpdb->query("
			CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}secvitr_cache` (
				`id` int(10) NOT NULL,
				`last` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				`srch` varchar(50) DEFAULT NULL,
				`ctg` mediumint(8) unsigned DEFAULT '0',
				`ad` text,
				PRIMARY KEY (`id`),
				KEY `idx` (`last`,`srch`,`ctg`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Cache da Vitrine Secundum';
		")) {

		echo '<!-- Erro ao criar a tabela secvitr_cache: ';
		$wpdb->print_error();
		echo '-->';
	}

	return 1;
}


add_action('admin_menu',	'SecVitr_AdmMenu');
add_action('wp_head',		'SecVitr_Header');
add_action('admin_menu',	'SecVitr_MetaBox');
add_filter('the_content',	'SecVitr_Insert');

register_activation_hook(__FILE__, 'SecVitr_Activate');

?>