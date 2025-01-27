<?php

declare(strict_types=1);

namespace Jot\HfRepository;

use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Jot\HfRepository\Event\AfterHydration;
use Jot\HfValidator\Validator\CNPJ;
use Jot\HfValidator\Validator\CPF;
use Jot\HfValidator\Validator\Elastic;
use Jot\HfValidator\Validator\Phone;
use Psr\Container\ContainerInterface;
use function Hyperf\Support\make;

#[Listener]
class BootValidatorListener implements ListenerInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            AfterHydration::class,
        ];
    }

    public function process(object $event): void
    {
        $annotations = [
            CNPJ::class,
            CPF::class,
            Phone::class,
            Elastic::class,
        ];

        foreach ($annotations as $annotation) {
            $validators = AnnotationCollector::getPropertiesByAnnotation($annotation);
            foreach ($validators as $validator) {
                if ($event->entity instanceof $validator['class']) {
                    $event->entity->addValidator($validator['property'], $validator['annotation']);
                }
            }
        }
    }
}
