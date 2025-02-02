<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Form\DataTransformer;

use Sonata\AdminBundle\Model\ModelManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @final since sonata-project/admin-bundle 3.x
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class ArrayToModelTransformer implements DataTransformerInterface
{
    /**
     * @var ModelManagerInterface
     */
    protected $modelManager;

    /**
     * @var string
     */
    protected $className;

    /**
     * @param string $className
     */
    public function __construct(ModelManagerInterface $modelManager, $className)
    {
        $this->modelManager = $modelManager;
        $this->className = $className;
    }

    public function reverseTransform($array)
    {
        // when the object is created the form return an array
        // one the object is persisted, the edit $array is the user instance
        if ($array instanceof $this->className) {
            return $array;
        }

        $instance = new $this->className();

        if (!\is_array($array)) {
            return $instance;
        }

        return $this->modelManager->modelReverseTransform($this->className, $array);
    }

    public function transform($value)
    {
        return $value;
    }
}
