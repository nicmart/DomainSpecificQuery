<?php
/**
 * This file is part of DomainSpecificQuery
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Nicolò Martini <nicmartnic@gmail.com>
 */

namespace DSQ\Lucene;

class PhraseExpression extends AbstractLuceneExpression
{
    private $slope = 0;

    /**
     * @param string $value     The value of the expression
     * @param int $slope        The proximity factor
     * @param float $boost      The boost factor of the expression
     * @param string $type      The type of the expression
     */
    public function __construct($value, $slope = 0, $boost = 1.0, $type = 'phrase')
    {
        parent::__construct($value, $boost, $type);

        $this->slope = (int) $slope;
    }


    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return '"' . $this->escape_phrase($this->getValue()) . '"'
            . $this->slopeSuffix()
            . $this->boostSuffix()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPrecedence($expression)
    {
        return true;
    }

    /**
     * Returns the slope suffix if slope is != 0
     * @return string
     */
    protected function slopeSuffix()
    {
        return $this->slope != 0 ? '~' . $this->slope : '';
    }
} 