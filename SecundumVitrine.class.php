<?php

/*

Classe para implementação da Vitrine Secundum em qualquer sistema em PHP.

Página oficial:	http://secundum.com.br/vitrine-secundum
Autores:	Stanislaw Pusep e Jobson Lemos
Versão:		2.0
Licença:	GPL v3 - http://www.gnu.org/licenses/gpl-3.0.html

*/

class SecundumVitrine {
	const SECVITR_HOST	= 'sistema.secundum.com.br';
	const SECVITR_VERS	= '2.0';

	function __construct($idml, $cols = 4) {
		$this->IDML		= $idml;
		$this->cols		= $cols;
		$this->expire	= 1.0;
		$this->tag		= 'secvitrine';

		$this->cache	= dirname(__FILE__) . DIRECTORY_SEPARATOR . '.SecundumVitrine';
		if (!is_writable($this->cache))
			die("ERRO: O diretório '{$this->cache}' não existe ou não tem permissão para a gravação!\n");

		$this->gzip		= function_exists('gzdecode');
		$this->URL		= 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	}

	function cache() {
		if (func_num_args())
			$this->cache = func_get_arg(0);
		return $this->cache;
	}

	function cols() {
		if (func_num_args())
			$this->cols = intval(func_get_arg(0));
		return $this->cols;
	}

	function expire() {
		if (func_num_args())
			$this->expire = floatval(func_get_arg(0));
		return $this->expire;
	}

	function tag() {
		if (func_num_args())
			$this->tag = preg_replace('%[^a-z0-9]%', '', func_get_arg(0));
		return $this->tag;
	}

	function css() {
		return "<style type='text/css'>
	.sec_vitrine {
		/* fonte Trebuchet MS; corpo 10 pixels */
		font-family: Trebuchet MS;
		font-size: 10px;
		line-height: 14px;
		/* texto centralizado na horizontal */
		text-align: center;
		vertical-align: top;
		/* bloco de anúncio destacado e centralizado */
		display: table;
		margin: 0 auto;
	}
	.sec_item_img { width: 90px; height: 90px; border: 0; }
	.sec_item_cell { width: 110px; max-height: 240px; float: left; padding: 5px; }
	.sec_link { font-weight: bold; float: right; }
</style>
";
	}

	function fetch($host, $file, $content = null, $port = 80, $timeout = 60) {
		if (isset($content)) {
			$req		= "POST $file HTTP/1.0\r\n";
			$req		.= "Content-Length: " . strlen($content) . "\r\n";
			$req		.= "Content-Type: application/x-www-form-urlencoded\r\n";
		} else
			$req		= "GET $file HTTP/1.0\r\n";

		$req			.= "Host: $host\r\n";
		$req			.= "User-Agent: Vitrine Secundum " . self::SECVITR_VERS . "\r\n";
		$req			.= "Referer: " . $this->URL . "\r\n";

		if ($this->gzip)
			$req		.= "Accept-Encoding: gzip\r\n";

		$req			.= "\r\n";

		if (isset($content))
			$req		.= $content;

		$res			= '';
		$hdr			= array();
		$buf			= '';

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

					$buf = ($this->gzip && ($hdr['content-encoding'] == 'gzip')) ? gzdecode($res) : $res;
				}
		}

		return $buf;
	}

	function ob_filter($content) {
		$content = preg_replace('%\[' . $this->tag . '/([a-z0-9\-]+)/([0-9]{4,6})\]%ei', '$this::vitrine("$1", $2)', $content);
		$content = preg_replace('%\[' . $this->tag . '/([a-z0-9\-]+)\]%ei', '$this::vitrine("$1")', $content);

		return $content;
	}

	function processar() {
		ob_start(array(&$this, 'ob_filter'));
	}

	function vitrine($busca, $categ = 0) {
		$busca	= strtolower($busca);
		$categ	= intval($categ);
		$uri	= $busca . ($categ ? '/' . $categ : '') . '?' . $this->cols;
		$id		= md5($uri);

		if (($html = $this::vitrine_cache($id)) === false) {
			$html = $this::fetch(self::SECVITR_HOST, '/vitrine.php/' . $uri);
			$this::vitrine_cache($id, $html);
		}

		return str_replace('%IDML%', $this->IDML, $html);
	}

	function vitrine_cache($id, $html = null) {
		$path = $this->cache . DIRECTORY_SEPARATOR . $id;

		if (empty($html)) {
			if (is_readable($path) && (filemtime($path) > (time() - ($this->expire*24*3600))))
				return file_get_contents($path);
			else
				return false;
		} else
			if (file_put_contents($path, $html) === false)
				die("ERRO: Não foi possível gravar '$path'\n");
	}
}

?>