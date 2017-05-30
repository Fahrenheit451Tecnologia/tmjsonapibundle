<?php declare(strict_types=1);

namespace TM\JsonApiBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TM\JsonApiBundle\DependencyInjection\Compiler\ExpressionPass;
use TM\JsonApiBundle\DependencyInjection\Compiler\RegisterHandlersPass;
use TM\JsonApiBundle\DependencyInjection\Compiler\SerializerPass;

class TMJsonApiBundle extends Bundle
{
    const CONTENT_TYPE              = 'application/vnd.api+json';

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $builder)
    {
        $builder->addCompilerPass(new ExpressionPass());
        $builder->addCompilerPass(new SerializerPass());
        $builder->addCompilerPass(new RegisterHandlersPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}
