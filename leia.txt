SOBRE:
======

Classe para implementação da Vitrine Secundum em qualquer sistema em PHP.

Página oficial:	http://secundum.com.br/vitrine-secundum
Autores:	Stanislaw Pusep e Jobson Lemos
Versão:		2.0
Licença:	GPL v3 - http://www.gnu.org/licenses/gpl-3.0.html


VITRINES:
=========

Tags das vitrines, no formato '[secvitrine/ipod-nano/46065]', podem ser obtidos no seguinte endereço:
http://sistema.secundum.com.br/vitrine-custom.php


EXEMPLO:
========

Insira o seguinte código PHP no <HEAD> da sua página:

	<?php
	// importar a classe da vitrine
	include 'SecundumVitrine.class.php';
	// inicializa a vitrine: o primeiro parâmetro é o identificador de MercadoSócios; o segundo (opcional) é o número de colunas da vitrine
	$v = new SecundumVitrine(1234567, 3);
	// gera o código CSS padrão da vitrine
	echo $v->css();
	// procede com a substituição de tags pelas vitrines
	$v->processar();
	?>

Veja também o arquivo 'exemplo.php'.


CONFIGURAÇÕES:
==============

Inicialmente, crie a instância da vitrine para cada identificador de MercadoSócios:

	$v = new SecundumVitrine(1234567);

Você pode criar quantas instâncias quiser por página. As instâncias podem ser configurados da seguinte maneira:

	// define o diretório do cache da vitrine. O padrão é '.SecundumVitrine' no mesmo diretório em que o arquivo 'SecundumVitrine.class.php' está
	$v->cache($dir);

	// define o número de colunas da vitrine
	$v->cols($num);

	// define o tempo máximo que cada vitrine permanece em cache, em dias
	$v->expire($dias);

	// define tag identificador de vitrines. O padrão é 'secvitrine'
	$v->tag($str);

Os mesmos métodos, sem parâmetros, retornam a sua configuração atual:

	// retorna o número de colunas da vitrine
	echo $v->cols;

Se preferir dispensar o uso de $v->processar(), as vitrines individuais podem ser inseridas manualmente:

	// retorna o código HTML da vitrine para dados produto/categoria
	echo $v->vitrine($produto, $categoria);
