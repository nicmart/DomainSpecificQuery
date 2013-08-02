<?php
/**
 * This file is part of DomainSpecificQuery
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Nicolò Martini <nicmartnic@gmail.com>
 */

$start = microtime(true);
include '../vendor/autoload.php';

$compiler = new DSQ\Compiler\LuceneCompiler\LuceneCompiler();

$compiler->registerTransformation(function(\DSQ\Expression\Expression $expr, \DSQ\Compiler\LuceneCompiler\LuceneCompiler $compiler) {
    $field = new \DSQ\Lucene\FieldExpression('fldin_txt_title', $phrase = new \DSQ\Lucene\PhraseExpression($expr->getRight()->getValue(), 12, 23.5));
    return $field;
}, '*', 'title');

$builder = new \DSQ\Expression\Builder\Builder();


$expression = $builder
    ->tree('and')
        ->field('fieldname', 'ciao a a tutti: io sono Nic')
        ->value('mah')
        ->field('title', 'che bel titolo')
        ->tree('or')
            ->value('ciao')
            ->field('author', '"Alessando Manzoni"')
            ->binary('>=', 'date', 2012)
            ->binary('<', 'date', 2030)
        ->end()
    ->getExpression();

var_dump((string) $compiler->compile($expression));

var_dump(microtime(true) - $start);
