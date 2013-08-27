<?php
/**
 * This file is part of DomainSpecificQuery
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Nicolò Martini <nicmartnic@gmail.com>
 */

namespace DSQ\Expression\Builder;


use Building\Builder;
use Building\Context;
use DSQ\Expression\TreeExpression;

class ExpressionBuilder extends Builder
{
    /**
     * @param string $op
     */
    public function __construct($op = 'and')
    {
        $this
            ->registerProcess('tree', $treeProcess = new TreeProcess)
            ->registerProcess('binary', new BinaryProcess)
            ->registerProcess('field', new FieldProcess)
            ->registerProcess('value', new ValueProcess)
        ;

        parent::__construct(new Context(new TreeExpression($op), $treeProcess));
    }

} 