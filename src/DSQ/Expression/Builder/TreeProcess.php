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

use Building\BuildProcess;
use Building\Context;
use DSQ\Expression\TreeExpression;

class TreeProcess implements BuildProcess
{
    /**
     * {@inheritdoc}
     */
    public function build(Context $context, $value = '')
    {
        $children = func_get_args();
        array_shift($children);
        array_shift($children);

        $tree = new TreeExpression($value);
        $context->process->subvalueBuilded($context, $tree);

        if ($children) {
            $tree->setChildren($children);
            return null;
        }

        return new Context($tree, $this);
    }

    /**
     * {@inheritdoc}
     */
    public function subvalueBuilded(Context $context, $expression)
    {
        $context->object->addChild($expression);
    }
} 