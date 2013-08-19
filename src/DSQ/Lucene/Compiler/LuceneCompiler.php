<?php
/**
 * This file is part of DomainSpecificQuery
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Nicolò Martini <nicmartnic@gmail.com>
 */

namespace DSQ\Lucene\Compiler;


use DSQ\Compiler\TypeBasedCompiler;
use DSQ\Compiler\UnregisteredTransformationException;
use DSQ\Expression\BasicExpression;
use DSQ\Expression\BinaryExpression;
use DSQ\Expression\Expression;
use DSQ\Expression\TreeExpression;
use DSQ\Lucene\PhraseExpression;
use DSQ\Lucene\SpanExpression;
use DSQ\Lucene\TemplateExpression;
use DSQ\Lucene\TermExpression;
use DSQ\Lucene\BooleanExpression;
use DSQ\Lucene\FieldExpression;
use DSQ\Lucene\RangeExpression;

class LuceneCompiler extends TypeBasedCompiler
{
    public function __construct()
    {
        $this
            ->map('*', array($this, 'basicExpression'))
            ->map('*:DSQ\Expression\BinaryExpression', array($this, 'fieldExpression'))
            ->map('*:DSQ\Expression\TreeExpression', array($this, 'treeExpression'))
            ->map(array('>', '>=', '<', '<='), array($this, 'comparisonExpression'))
        ;
    }

    public function field($fieldName, $phrase = false, $boost = 1.0)
    {
        return function(BinaryExpression $expr, self $compiler) use ($fieldName, $phrase, $boost)
        {
            $value = $compiler->phrasize($expr->getRight(), $phrase);

            return new FieldExpression($fieldName, $value, $boost);
        };
    }

    public function term($phrase = false, $boost = 1.0)
    {
        return function(BinaryExpression $expr, self $compiler) use ($phrase, $boost)
        {
            return new TermExpression($compiler->phrasizeOrTermize($expr->getRight(), $phrase), $boost);
        };
    }

    public function tree(array $fieldNames, $op = 'and', $phrase = false, $boost = 1.0)
    {
        return function(BinaryExpression $expr, self $compiler) use ($fieldNames, $op, $phrase, $boost)
        {
            $value = $compiler->phrasize($expr->getRight(), $phrase);

            $tree = new SpanExpression(strtoupper($op), array(), $boost);

            foreach ($fieldNames as $fieldName) {
                $tree->addExpression(new FieldExpression($fieldName, $value));
            }

            return $tree;
        };
    }

    public function range($fieldName, $boost = 1.0)
    {
        $fieldTransf = $this->field($fieldName, false, $boost);

        return function(BinaryExpression $expr, self $compiler) use ($fieldName, $boost, $fieldTransf)
        {
            $val = $expr->getRight()->getValue();

            if (!is_array($val))
                return $fieldTransf($expr, $compiler);

            return new RangeExpression($val['from'], $val['to']);
        };
    }

    public function template($template, $phrase = false, $escape = true, $boost = 1.0)
    {
        return function(BinaryExpression $expr, self $compiler) use ($template, $phrase, $escape, $boost)
        {
            return new TemplateExpression($template, $compiler->phrasizeOrTermize($expr->getRight(), $phrase, $escape), $boost);
        };
    }

    public function combine($op, $transf1/**, $transf2,... */)
    {
        $transformations = func_get_args();
        array_shift($transformations);

        return function(Expression $expr, $compiler) use ($op, $transformations)
        {
            $tree = new SpanExpression(strtoupper($op));

            foreach ($transformations as $transformation) {
                $tree->addExpression($transformation($expr, $compiler));
            }

            return $tree;
        };

        return $tree;
    }

    public function regexps(array $regexpsMap)
    {
        return function(BinaryExpression $expr, $compiler) use ($regexpsMap)
        {
            $value = $expr->getRight()->getValue();

            foreach ($regexpsMap as $regexp => $transformation) {
                if (preg_match($regexp, $value))
                    return $transformation($expr, $compiler);
            }

            throw new UnregisteredTransformationException("There is no transformation matching the value \"$value\"");
        };
    }

    public function basicExpression(BasicExpression $expr, self $compiler)
    {
        return new TermExpression($expr->getValue());
    }

    public function fieldExpression(BinaryExpression $expr, self $compiler)
    {
        $value = $compiler->transform($expr->getRight());

        return new FieldExpression((string) $expr->getLeft()->getValue(), $value);
    }

    public function rangeExpression(BinaryExpression $expr, self $compiler)
    {
        $from = $compiler->transform($expr->getLeft());
        $to = $compiler->transform($expr->getRight());

        return new RangeExpression($from, $to);
    }

    public function treeExpression(TreeExpression $expr, self $compiler)
    {
        switch (strtolower($expr->getValue())) {
            case 'and':
                $operator = SpanExpression::OP_AND;
                break;
            case 'not':
                $operator = SpanExpression::OP_NOT;
                break;
            default:
                $operator = SpanExpression::OP_OR;
        }

        $spanExpr = new SpanExpression($operator);

        foreach ($expr->getChildren() as $child)
        {
            $spanExpr->addExpression($compiler->compile($child));
        }

        return $spanExpr;
    }

    public function comparisonExpression(BinaryExpression $expr, self $compiler)
    {
        $fieldname = $expr->getLeft()->getValue();
        $val = $compiler->transform($expr->getRight()->getValue());

        $from = '*';
        $to = '*';
        $includeLeft = true;
        $includeRight = true;

        switch ($expr->getValue())
        {
            case '>':
                $from = $val;
                $includeLeft = false;
                break;
            case '>=':
                $from = $val;
                break;
            case '<':
                $to = $val;
                $includeRight = false;
                break;
            case '<=':
                $to = $val;
                break;
        }

        return new FieldExpression($fieldname, new RangeExpression($from, $to, 1.0, $includeLeft, $includeRight));
    }

    public function phrasize(Expression $expr, $phrase = true)
    {
        return $phrase
            ? new PhraseExpression($expr->getValue())
            : $expr->getValue();
    }

    public function phrasizeOrTermize(Expression $expr, $phrase = true, $escape = true)
    {
        return $phrase
            ? new PhraseExpression($expr->getValue())
            : ($escape ? new TermExpression($expr->getValue()) : $expr->getValue())
        ;
    }
} 