<?php

declare(strict_types=1);

namespace Jot\HfRepository;

use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\Annotation\MultipleAnnotation;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Jot\HfValidator\Validator;
use Psr\Container\ContainerInterface;

#[Listener]
class BootValidatorListener implements ListenerInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    /**
     * Processes a given event by iterating through all registered validators and applying them.
     *
     * @param object $event The event object to be processed by the validators.
     * @return void
     */
    public function process(object $event): void
    {
        foreach ($this->getValidators() as $validator) {
            $this->processValidator($validator, $event);
        }
    }

    /**
     * Retrieves an array of validators available for use.
     *
     * @return array An array of validator class names, each representing a specific type of validation logic.
     */
    private function getValidators(): array
    {
        return [
            Validator\CNPJ::class,
            Validator\CPF::class,
            Validator\Exists::class,
            Validator\Email::class,
            Validator\Enum::class,
            Validator\Gt::class,
            Validator\Gte::class,
            Validator\Ip::class,
            Validator\Length::class,
            Validator\Lt::class,
            Validator\Lte::class,
            Validator\Password::class,
            Validator\Phone::class,
            Validator\Range::class,
            Validator\Regex::class,
            Validator\Required::class,
            Validator\Unique::class,
            Validator\Url::class,
        ];
    }

    /**
     * Processes a validator by collecting properties annotated with the given validator class and applying them to the specified event.
     *
     * @param string $validatorClass The fully qualified class name of the validator to be processed.
     * @param object $event The event object to which the collected validators will be applied.
     * @return void
     */
    private function processValidator(string $validatorClass, object $event): void
    {
        $collectedAnnotations = AnnotationCollector::getPropertiesByAnnotation($validatorClass);
        foreach ($collectedAnnotations as $annotationData) {
            if (method_exists($annotationData['annotation'], 'setContainer')) {
                $annotationData['annotation']->setContainer($this->container);
            }
            EntityValidator::addValidator($annotationData['class'], $annotationData['property'], $annotationData['annotation']);
        }
    }

}
