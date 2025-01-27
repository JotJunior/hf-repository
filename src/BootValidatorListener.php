<?php

declare(strict_types=1);

namespace Jot\HfRepository;

use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Jot\HfRepository\Event\AfterHydration;
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
            AfterHydration::class,
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
            Validator\Elastic::class,
            Validator\Enum::class,
            Validator\Gt::class,
            Validator\Gte::class,
            Validator\Lt::class,
            Validator\Lte::class,
            Validator\Phone::class,
            Validator\Range::class,
            Validator\Regex::class,
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
            $this->applyValidatorToEntity($annotationData, $event);
        }
    }

    /**
     * Applies a validator to an entity based on the provided annotation data and event.
     *
     * @param array $annotationData An associative array containing annotation-specific data, including 'class', 'property', and 'annotation'.
     * @param object $event The event object, which is checked for type and entity compatibility before applying the validator.
     * @return void
     */
    private function applyValidatorToEntity(array $annotationData, object $event): void
    {
        if ($event instanceof AfterHydration && $event->entity instanceof $annotationData['class']) {
            $annotationData['annotation']->setContainer($this->container);
            $event->entity->addValidator($annotationData['property'], $annotationData['annotation']);
        }
    }
}
