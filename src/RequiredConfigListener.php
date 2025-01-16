<?php

declare(strict_types=1);

namespace Jot\HfRepository;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Framework\Event\BootApplication;
use Psr\Container\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

#[Listener]
class RequiredConfigListener implements ListenerInterface
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

    public function process(object $event): void
    {
        $output = new ConsoleOutput();

        foreach (['swagger', 'redis', 'etcd'] as $package) {
            if (!$this->container->get(ConfigInterface::class)->get($package)) {
                $output->writeln('');
                $output->writeln(sprintf('<options=bold;fg=red>[ERROR]</> The required package <options=bold>%s</> is not configured. To proceed, please run the following command before starting the application:', ucfirst($package)));
                $output->writeln('');
                $output->writeln(sprintf('    <options=bold>php bin/hyperf.php vendor:publish hyperf/%s</>', $package));
                $output->writeln('');
                exit(1);

            }
        };
    }
}
