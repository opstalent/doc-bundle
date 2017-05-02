<?php

namespace Opstalent\DocBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class OpstalentDocBundle
 * @package OpstalentDocBundle
 */
class OpstalentDocBundle extends Bundle
{
    /**
     * @return string
     */
    public function getParent()
    {
        return 'NelmioApiDocBundle';
    }
}
