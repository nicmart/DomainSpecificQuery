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

use DSQ\Expression\Expression;

interface LuceneExpression extends Expression
{
    /**
     * @return LuceneQuery
     */
    public function getQuery();
} 